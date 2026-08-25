<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ChatbotDocument;
use App\Models\ChatbotLog;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // 🟢 ตรวจสอบว่ามี Facade นี้ด้านบน Controller
use Illuminate\Support\Facades\Session;

class GeminiUserController extends Controller
{
    public function index()
    {
        return view('pages.chatbot.index');
    }



    public function askAi(Request $request)
    {
        $request->validate(['question' => 'required|string|max:1000']);
        $question = $request->input('question');
        $empId = Session::get('employee_id') ?? 1;

        try {
            // 1. ดึงเอกสารทั้งหมดที่ยังไม่ถูกลบ
            $documents = ChatbotDocument::whereNull('deleted_at')->latest()->get();

            if ($documents->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'answer' => 'ขออภัยค่ะ ยังไม่มีเอกสารในระบบ',
                    'source' => null
                ]);
            }

            $primaryKeyName = (new ChatbotDocument())->getKeyName();
            $documentIds = $documents->pluck($primaryKeyName);

            // 2. ดึง Chunks ทั้งหมดพร้อมระบุ ID และ Title ของเอกสารแต่ละชิ้น
            $rawChunks = DB::table('SUPPORT_CHATBOT_DOCUMENT_CHUNKS as c')
                ->join('SUPPORT_CHATBOT_DOCUMENTS as d', 'c.DOCUMENT_ID', '=', 'd.' . $primaryKeyName)
                ->whereIn('c.DOCUMENT_ID', $documentIds)
                ->whereNull('d.deleted_at')
                ->orderBy('c.DOCUMENT_ID', 'desc')
                ->orderBy('c.CHUNK_INDEX', 'asc')
                ->select('c.CHUNK_TEXT as chunk_text', 'd.' . $primaryKeyName . ' as doc_id', 'd.title as title')
                ->get();

            if ($rawChunks->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'answer' => 'ขออภัยค่ะ ไม่พบเนื้อหาข้อความภายในเอกสาร',
                    'source' => null
                ]);
            }

            // 3. จัดรูปแบบเนื้อหาให้ AI มองเห็นรหัสเอกสาร (ID) และข้อความ
            $contextLines = [];
            foreach ($rawChunks as $row) {
                if (!empty($row->chunk_text)) {
                    $docId = $row->doc_id;
                    $docTitle = $row->title ?? 'เอกสารไม่ระบุชื่อ';
                    // ใส่รหัส ID กำกับไว้ เพื่อให้ AI อ้างอิงกลับมาเป็นตัวเลข ID ได้แม่นยำ
                    $contextLines[] = "[DOC_ID: {$docId} | ชื่อเอกสาร: {$docTitle}]\n{$row->chunk_text}";
                }
            }
            $combinedContext = implode("\n\n--------------------\n\n", $contextLines);

            // 4. บังคับให้ AI ตอบกลับมาเป็น JSON โดยเลือก "DOC_ID" ที่ใช้ตอบ
            $prompt = "คุณคือผู้ช่วยอัจฉริยะของกรมวิทยาศาสตร์บริการ หน้าที่ของคุณคือตอบคำถามโดยอ้างอิงจาก 'เนื้อหาเอกสาร' ที่กำหนดให้ด้านล่างนี้เท่านั้น " .
                "ห้ามใช้ความรู้ภายนอก ห้ามแต่งเติมเองเด็ดขาด! " .
                "หากคำตอบไม่ได้อยู่ในเอกสารนี้ ให้ตอบคำว่า 'ขออภัยค่ะ ไม่พบข้อมูลดังกล่าวในระเบียบหรือเอกสารที่อัปโหลดไว้' " .
                "และให้คุณตรวจสอบดูว่าข้อมูลที่คุณนำมาใช้ตอบนั้นมาจาก [DOC_ID: ...] ของเอกสารเล่มไหน ให้ระบุตัวเลข DOC_ID นั้นกลับมาด้วย\n\n" .
                "กรุณาตอบกลับมาในรูปแบบ JSON ที่มีโครงสร้างนี้เท่านั้น (ห้ามมีข้อความอื่นนอกเหนือจาก JSON):\n" .
                "{\n" .
                "  \"answer\": \"ข้อความคำตอบของคุณที่จัดรูปแบบอ่านง่าย\",\n" .
                "  \"doc_id\": รหัสตัวเลข DOC_ID ของเอกสารที่ใช้ตอบ (ถ้าไม่พบข้อมูลหรือตอบว่าไม่พบ ให้ใส่ค่าเป็น null)\n" .
                "}\n\n" .
                "--- เนื้อหาเอกสารอ้างอิง ---\n" .
                $combinedContext . "\n" .
                "----------------------------\n\n" .
                "คำถามจากผู้ใช้งาน: " . $question;

            // 5. ส่งให้ Gemini ประมวลผล
            $response = Gemini::generativeModel(model: 'gemini-3.5-flash')->generateContent($prompt);
            $aiRawResponse = trim($response->text());

            // ทำความสะอาด JSON
            $cleanedJson = preg_replace('/^```json\s*|\s*```$/i', '', $aiRawResponse);
            $dataResponse = json_decode($cleanedJson, true);

            $aiAnswer = '';
            $sourceInfo = null;

            if (json_last_error() === JSON_ERROR_NONE && isset($dataResponse['answer'])) {
                $aiAnswer = $dataResponse['answer'];
                $selectedDocId = $dataResponse['doc_id'] ?? null;

                // 6. นำ DOC_ID ที่ AI เลือก ไปดึงชื่อ "Title ตรงๆ" จากฐานข้อมูลมาใช้งานทันที!
                if (!empty($selectedDocId)) {
                    $matchedDoc = ChatbotDocument::where($primaryKeyName, $selectedDocId)->first();
                    if ($matchedDoc && !empty($matchedDoc->title)) {
                        $sourceInfo = $matchedDoc->title; //  ได้ชื่อ Title ตรงเป๊ะจากตารางเอกสาร
                    }
                }
            } else {
                // กรณีสำรอง ถ้า JSON พัง ให้ใช้ข้อความดิบ
                $aiAnswer = $aiRawResponse;
                $sourceInfo = $documents->first()?->title;
            }

            // 7. บันทึกประวัติการสนทนาลงฐานข้อมูล
            ChatbotLog::create([
                'emp_id'     => $empId,
                'question'   => $question,
                'answer'     => $aiAnswer,
                'source'     => $sourceInfo,
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'success',
                'answer' => $aiAnswer,
                'source' => $sourceInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'AI Error: ' . $e->getMessage()
            ], 500);
        }
    }




    public function myHistory()
    {
        $empId = Session::get('employee_id');
        $logs = ChatbotLog::where('emp_id', $empId)->orderBy('created_at', 'desc')->paginate(15);
        return view('pages.chatbot.history', compact('logs'));
    }
}
