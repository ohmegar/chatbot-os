<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotDocument;
use App\Models\ChatbotDocumentLog;
use App\Models\ChatbotDocumentChunks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Enums\MimeType;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\DB; // 🟢 ตรวจสอบว่ามี Facade นี้ด้านบน Controller


class GeminiAdminController extends Controller
{
    public function index()
    {
        $documents = ChatbotDocument::orderBy('created_at', 'desc')->paginate(10);
        return view('pages.admin.chatbot.index', compact('documents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'pdf_file' => 'required|mimes:pdf|max:20480',
        ]);

        $file = $request->file('pdf_file');
        $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $filePath = $file->storeAs('chatbot_docs', $filename, 'public');
        $empId = Session::get('employee_id');
        $fullPath = storage_path('app/public/' . $filePath);

        // 1. บันทึกข้อมูลหลักลงตารางเอกสารก่อน (เพื่อให้มั่นใจว่ามี Record หลักแน่นอน)
        $document = ChatbotDocument::create([
            'title' => $request->input('title'),
            'file_path' => $filePath,
            'original_filename' => $file->getClientOriginalName(),
            'emp_id' => $empId,
        ]);

        try {
            // 2. พยายามอัปโหลดไฟล์ไปที่ Gemini Files API (ถ้ามีปัญหาตรงนี้ จะได้ไม่บล็อกการตัด Chunk)
            try {
                Gemini::files()->upload(
                    filename: $fullPath,
                    mimeType: MimeType::APPLICATION_PDF,
                    displayName: $request->input('title')
                );
            } catch (\Exception $geminiEx) {
                // หาก Gemini อัปโหลดไม่ผ่าน ให้ข้ามไปก่อนแต่บันทึกข้อมูลในระบบต่อได้
            }

            // 3. อ่านข้อความจาก PDF ด้วย Smalot PDF Parser
            $parser = new Parser();
            $pdf = $parser->parseFile($fullPath);
            $fullText = $pdf->getText();
            $fullText = preg_replace('/\s+/', ' ', $fullText);


            // 4. ตัดแบ่งข้อความ (Chunking) ขนาด 1,000 ตัวอักษร
            $chunkSize = 1000;
            $length = mb_strlen($fullText);

            if ($length > 0) {
                // 🟢 หาค่า ID สูงสุดปัจจุบันของตาราง Chunks เผื่อไว้ใช้ป้องกันการชน
                $nextId = DB::table('SUPPORT_CHATBOT_DOCUMENT_CHUNKS')->max('CH_CID') + 1;

                for ($i = 0; $i < $length; $i += $chunkSize) {
                    $chunkText = trim(mb_substr($fullText, $i, $chunkSize));
                    if ($chunkText !== '') {

                        // 🟢 บันทึกโดยระบุค่า CH_CID ควบคุมเอง ป้องกันปัญหา Oracle Trigger/Sequence ตีกัน
                        DB::table('SUPPORT_CHATBOT_DOCUMENT_CHUNKS')->insert([
                            'CH_CID'      => $nextId++, // กำหนด Primary Key เองและบวกเพิ่มทีละ 1
                            'DOCUMENT_ID' => $document->getKey(),
                            'CHUNK_TEXT'  => $chunkText,
                            'CHUNK_INDEX' => floor($i / $chunkSize),
                            'CREATED_AT'  => now(),
                            'UPDATED_AT'  => now(),
                        ]);
                    }
                }
            }

            // 5. บันทึก Log การอัปโหลดสำเร็จ
            ChatbotDocumentLog::create([
                'document_id' => $document->getKey(),
                'emp_id' => $empId,
                'action' => 'UPLOAD',
                'description' => 'อัปโหลด ตัดแบ่ง Chunks และ Training AI เอกสาร: ' . $request->input('title'),
            ]);

            return redirect()->back()->with('success', 'อัปโหลดและประมวลผลข้อความสำหรับ AI เรียบร้อยแล้ว');
        } catch (\Exception $e) {
            // หากเกิดข้อผิดพลาดในการตัด Chunk หรืออ่าน PDF จะเก็บบันทึก Log ข้อผิดพลาดไว้ให้เห็น
            ChatbotDocumentLog::create([
                'document_id' => $document->getKey(),
                'emp_id' => $empId,
                'action' => 'UPLOAD_ERROR',
                'description' => 'Error ตอนประมวลผล PDF: ' . $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'อัปโหลดไฟล์สำเร็จ แต่เกิดข้อผิดพลาดในการแปลงข้อความ PDF: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $document = ChatbotDocument::findOrFail($id);
        $empId = Session::get('employee_id');

        $docTitle = $document->title;

        // ทำ Soft Delete
        $document->delete();

        ChatbotDocumentLog::create([
            'document_id' => $document->getKey(),
            'emp_id' => $empId,
            'action' => 'DELETE',
            'description' => 'ย้ายเอกสารไปถังขยะ: ' . $docTitle,
        ]);

        return redirect()->back()->with('success', 'ย้ายเอกสารไปถังขยะเรียบร้อยแล้ว');
    }

    public function trash()
    {
        $trashedDocs = ChatbotDocument::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(10);
        return view('pages.admin.chatbot.trash', compact('trashedDocs'));
    }


    public function restore($id)
    {
        // 1. ค้นหาเอกสารที่อยู่ในถังขยะ (Soft Deleted)
        $document = ChatbotDocument::onlyTrashed()->findOrFail($id);

        // 2. สั่งกู้คืนข้อมูล (deleted_at จะกลับมาเป็น NULL อัตโนมัติ)
        $document->restore();

        $empId = Session::get('employee_id');

        // บันทึก Log
        ChatbotDocumentLog::create([
            'document_id' => $document->getKey(),
            'emp_id'      => $empId,
            'action'      => 'RESTORE',
            'description' => 'กู้คืนเอกสารจากถังขยะ: ' . $document->title,
        ]);

        return redirect()->back()->with('success', 'กู้คืนเอกสารสำเร็จ');
    }


    public function logs()
    {
        $logs = ChatbotDocumentLog::with('document')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('pages.admin.chatbot.logs', compact('logs'));
    }
}
