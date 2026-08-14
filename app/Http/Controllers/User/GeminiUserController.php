<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ChatbotDocument;
use App\Models\ChatbotLog;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // 🟢 ตรวจสอบว่ามี Facade นี้ด้านบน Controller
use Illuminate\Support\Facades\Log;
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
            // 1. ดึงเอกสารล่าสุดในระบบ
            // โดยปกติแล้ว หากโมเดล ChatbotDocument ของคุณมีการใช้งานแทร็ก Soft Deletes
            // (use SoftDeletes;) คำสั่งค้นหาข้อมูลทั่วไป เช่น ChatbotDocument::latest()->first()
            // จะทำการ ข้ามเอกสารที่ถูกย้ายไปถังขยะให้อัตโนมัติ อยู่แล้ว
            $document = ChatbotDocument::latest()->first();

            if (!$document) {
                return response()->json([
                    'status' => 'success',
                    'answer' => 'ขออภัยครับ ยังไม่มีเอกสารระเบียบในระบบ กรุณาอัปโหลดเอกสารก่อนใช้งาน',
                    'source' => null
                ]);
            }

            $sourceInfo = $document->original_filename ?? $document->title;
            $documentId = $document->getKey(); // หรือ $document->id

            // 2. ดึงข้อมูล Chunk โดยรองรับทั้งพิมพ์เล็กและพิมพ์ใหญ่ของ Oracle Driver
            $chunks = DB::table('SUPPORT_CHATBOT_DOCUMENT_CHUNKS')
                ->where('DOCUMENT_ID', $documentId)
                ->orderBy('CHUNK_INDEX', 'asc')
                ->get()
                ->map(function ($item) {
                    // ดึงข้อความจากคอลัมน์ ไม่ว่าจะมาเป็นตัวพิมพ์เล็กหรือพิมพ์ใหญ่
                    return $item->chunk_text ?? $item->CHUNK_TEXT ?? '';
                })
                ->toArray();

            $combinedContext = implode("\n\n", $chunks);

            // ลองใส่บรรทัดนี้ไว้ก่อนส่งให้ Gemini ใน GeminiUserController.php
            // Log::info("AI Context Content: " . substr($combinedContext, 0, 300)); // ดูข้อความ 300 ตัวอักษรแรก

            if (empty($combinedContext)) {
                return response()->json([
                    'status' => 'success',
                    'answer' => 'ขออภัยครับ ไม่พบเนื้อหาข้อความภายในระบบนี้',
                    'source' => $sourceInfo
                ]);
            }

            // 3. เขียน Prompt ควบคุมให้ AI อ่านจากเนื้อหา Chunk ที่ดึงมา
            $prompt = "คุณคือผู้ช่วยอัจฉริยะของกรมวิทยาศาสตร์บริการ หน้าที่ของคุณคือตอบคำถามโดยอ้างอิงจาก 'เนื้อหาเอกสาร' ที่กำหนดให้ด้านล่างนี้เท่านั้น " .
                "ห้ามใช้ความรู้ภายนอก ห้ามแต่งเติมเองเด็ดขาด! " .
                "หากคำตอบไม่ได้อยู่ในเอกสารนี้ ให้ตอบตรงๆ ว่า 'ขออภัยครับ ไม่พบข้อมูลดังกล่าวในระเบียบหรือเอกสารที่อัปโหลดไว้' \n\n" .
                "--- เนื้อหาเอกสารอ้างอิง ---\n" .
                $combinedContext . "\n" .
                "----------------------------\n\n" .
                "คำถามจากผู้ใช้งาน: " . $question;

            // 4. ส่งข้อมูลให้ Gemini ประมวลผล
            $response = Gemini::generativeModel(model: 'gemini-3.5-flash')->generateContent($prompt);
            $aiAnswer = $response->text();

            // 5. บันทึกประวัติการสนทนา
            ChatbotLog::create([
                'emp_id'     => $empId,
                'question'   => $question,
                'answer'     => $aiAnswer,
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
