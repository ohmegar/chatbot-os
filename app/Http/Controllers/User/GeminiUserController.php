<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ChatbotDocument;
use App\Models\ChatbotLog;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
            $apiKey = config('gemini.api_key') ?? config('services.gemini.key');

            // 🟢 1. แปลง "คำถามของผู้ใช้" เป็น Vector ด้วยรุ่น gemini-embedding-001
            $qResponse = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent?key={$apiKey}", [
                'model' => 'models/gemini-embedding-001',
                'content' => [
                    'parts' => [
                        ['text' => $question]
                    ]
                ]
            ]);

            if (!$qResponse->successful()) {
                throw new \Exception('Embedding API Error: ' . $qResponse->body());
            }

            $questionVector = $qResponse->json('embedding.values');
            if (empty($questionVector)) {
                throw new \Exception('โครงสร้างข้อมูล Embedding จากคำถามไม่ถูกต้อง');
            }

            // 2. ดึง Chunks ทั้งหมดจาก Oracle ที่ยังไม่ถูกลบ
            $primaryKeyName = (new ChatbotDocument())->getKeyName();
            $rawChunks = DB::table('SUPPORT_CHATBOT_DOCUMENT_CHUNKS as c')
                ->join('SUPPORT_CHATBOT_DOCUMENTS as d', 'c.DOCUMENT_ID', '=', 'd.' . $primaryKeyName)
                ->whereNull('d.deleted_at')
                ->select('c.CHUNK_TEXT as chunk_text', 'd.title as title', 'c.EMBEDDING_VECTOR as embedding')
                ->get();

            if ($rawChunks->isEmpty()) {
                return response()->json(['status' => 'success', 'answer' => 'ขออภัยค่ะ ยังไม่มีเอกสารในระบบ', 'source' => null]);
            }

            // 3. คำนวณความเหมือน (Cosine Similarity) ทีละ Chunk
            $scoredChunks = [];
            foreach ($rawChunks as $row) {
                if (empty($row->embedding)) continue;

                $chunkVector = json_decode($row->embedding, true);
                if (!is_array($chunkVector)) continue;

                $score = $this->cosineSimilarity($questionVector, $chunkVector);

                $scoredChunks[] = [
                    'score'      => $score,
                    'chunk_text' => $row->chunk_text,
                    'title'      => $row->title
                ];
            }

            // 4. เรียงลำดับจากคะแนนความเหมือนมากที่สุด ไปน้อยที่สุด และหยิบมา Top 5
            usort($scoredChunks, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            $topChunks = array_slice($scoredChunks, 0, 5);

            if (empty($topChunks)) {
                return response()->json(['status' => 'success', 'answer' => 'ขออภัยค่ะ ไม่พบข้อมูลที่เกี่ยวข้อง', 'source' => null]);
            }

            // 5. สกัดเนื้อหาและชื่อ Title ของไฟล์ที่ได้คะแนนสูงสุดมาทำเป็น Context และ Source
            $contextLines = [];
            $matchedTitles = [];
            foreach ($topChunks as $item) {
                $contextLines[] = "[แหล่งที่มาเอกสาร: {$item['title']}]\n{$item['chunk_text']}";
                $matchedTitles[] = $item['title'];
            }

            $combinedContext = implode("\n\n--------------------\n\n", $contextLines);
            $sourceInfo = collect($matchedTitles)->unique()->implode(', ');

            // 6. ส่ง Prompt ให้ Gemini 3.5 Flash ตอบคำถาม
            $prompt = "คุณคือผู้ช่วยอัจฉริยะของกรมวิทยาศาสตร์บริการ หน้าที่ของคุณคือตอบคำถามโดยอ้างอิงจาก 'เนื้อหาเอกสาร' ที่กำหนดให้ด้านล่างนี้เท่านั้น " .
                "ห้ามใช้ความรู้ภายนอก ห้ามแต่งเติมเองเด็ดขาด! " .
                "หากคำตอบไม่ได้อยู่ในเอกสารนี้ ให้ตอบว่า 'ขออภัยค่ะ ไม่พบข้อมูลดังกล่าวในระเบียบหรือเอกสารที่อัปโหลดไว้' " .
                "--- เนื้อหาเอกสารอ้างอิง ---\n" .
                $combinedContext . "\n" .
                "----------------------------\n\n" .
                "คำถามจากผู้ใช้งาน: " . $question;

            $response = Gemini::generativeModel(model: 'gemini-3.5-flash')->generateContent($prompt);
            $aiAnswer = $response->text();

            // 7. บันทึกประวัติการสนทนาพร้อม Source
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



    // ฟังก์ชันคำนวณ Cosine Similarity สำหรับ PHP
    private function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        $count = min(count($a), count($b));
        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $magnitudeA += $a[$i] * $a[$i];
            $magnitudeB += $b[$i] * $b[$i];
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }


    // public function askAi(Request $request)
    // {
    //     $request->validate(['question' => 'required|string|max:1000']);
    //     $question = $request->input('question');
    //     $empId = Session::get('employee_id') ?? 1;

    //     try {
    //         // 🟢 1. แปลง "คำถามของผู้ใช้" เป็น Vector ผ่าน HTTP Client พร้อมแนบ API Key ที่ถูกต้อง
    //         $apiKey = config('gemini.api_key') ?? config('services.gemini.key');

    //         $qResponse = \Illuminate\Support\Facades\Http::withHeaders([
    //             'Content-Type' => 'application/json',
    //         ])->post("https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key={$apiKey}", [
    //             'model' => 'models/text-embedding-004',
    //             'content' => [
    //                 'parts' => [
    //                     ['text' => $question]
    //                 ]
    //             ]
    //         ]);

    //         if (!$qResponse->successful()) {
    //             throw new \Exception('API Error: ' . $qResponse->body());
    //         }

    //         $questionVector = $qResponse->json('embedding.values');
    //         if (empty($questionVector)) {
    //             throw new \Exception('โครงสร้างข้อมูล Embedding จากคำถามไม่ถูกต้อง');
    //         }

    //         // 2. ดึง Chunks ทั้งหมดจาก Oracle ที่ยังไม่ถูกลบ
    //         $primaryKeyName = (new ChatbotDocument())->getKeyName();
    //         $rawChunks = DB::table('SUPPORT_CHATBOT_DOCUMENT_CHUNKS as c')
    //             ->join('SUPPORT_CHATBOT_DOCUMENTS as d', 'c.DOCUMENT_ID', '=', 'd.' . $primaryKeyName)
    //             ->whereNull('d.deleted_at')
    //             ->select('c.CHUNK_TEXT as chunk_text', 'd.title as title', 'c.EMBEDDING_VECTOR as embedding')
    //             ->get();

    //         if ($rawChunks->isEmpty()) {
    //             return response()->json(['status' => 'success', 'answer' => 'ขออภัยครับ ยังไม่มีเอกสารในระบบ', 'source' => null]);
    //         }

    //         // 3. คำนวณความเหมือน (Cosine Similarity) ทีละ Chunk
    //         $scoredChunks = [];
    //         foreach ($rawChunks as $row) {
    //             if (empty($row->embedding)) continue;

    //             $chunkVector = json_decode($row->embedding, true);
    //             if (!is_array($chunkVector)) continue;

    //             $score = $this->cosineSimilarity($questionVector, $chunkVector);

    //             $scoredChunks[] = [
    //                 'score'      => $score,
    //                 'chunk_text' => $row->chunk_text,
    //                 'title'      => $row->title
    //             ];
    //         }

    //         // 4. เรียงลำดับจากคะแนนความเหมือนมากที่สุด ไปน้อยที่สุด และหยิบมา Top 5
    //         usort($scoredChunks, function ($a, $b) {
    //             return $b['score'] <=> $a['score'];
    //         });

    //         $topChunks = array_slice($scoredChunks, 0, 5);

    //         if (empty($topChunks)) {
    //             return response()->json(['status' => 'success', 'answer' => 'ขออภัยครับ ไม่พบข้อมูลที่เกี่ยวข้อง', 'source' => null]);
    //         }

    //         // 5. สกัดเอาเฉพาะเนื้อหาและชื่อ Title ของไฟล์ที่ได้คะแนนสูงสุดมาทำเป็น Context และ Source
    //         $contextLines = [];
    //         $matchedTitles = [];
    //         foreach ($topChunks as $item) {
    //             $contextLines[] = "[แหล่งที่มาเอกสาร: {$item['title']}]\n{$item['chunk_text']}";
    //             $matchedTitles[] = $item['title'];
    //         }

    //         $combinedContext = implode("\n\n--------------------\n\n", $contextLines);
    //         $sourceInfo = collect($matchedTitles)->unique()->implode(', ');

    //         // 6. ส่ง Prompt ให้ Gemini 3.5 Flash ตอบคำถามตามโครงสร้างเดิมที่เราทำไว้
    //         $prompt = "คุณคือผู้ช่วยอัจฉริยะของกรมวิทยาศาสตร์บริการ หน้าที่ของคุณคือตอบคำถามโดยอ้างอิงจาก 'เนื้อหาเอกสาร' ที่กำหนดให้ด้านล่างนี้เท่านั้น " .
    //             "ห้ามใช้ความรู้ภายนอก ห้ามแต่งเติมเองเด็ดขาด! " .
    //             "หากคำตอบไม่ได้อยู่ในเอกสารนี้ ให้ตอบว่า 'ขออภัยค่ะ ไม่พบข้อมูลดังกล่าวในระเบียบหรือเอกสารที่อัปโหลดไว้' " .
    //             "และให้ระบุชื่อหัวข้อเอกสาร (Title) จากป้ายกำกับ [แหล่งที่มาเอกสาร: ...] ที่คุณนำข้อมูลมาใช้ตอบ\n\n" .
    //             "--- เนื้อหาเอกสารอ้างอิง ---\n" .
    //             $combinedContext . "\n" .
    //             "----------------------------\n\n" .
    //             "คำถามจากผู้ใช้งาน: " . $question;

    //         $response = Gemini::generativeModel(model: 'gemini-3.5-flash')->generateContent($prompt);
    //         $aiAnswer = $response->text();

    //         // 7. บันทึกประวัติการสนทนาพร้อม Source
    //         ChatbotLog::create([
    //             'emp_id'     => $empId,
    //             'question'   => $question,
    //             'answer'     => $aiAnswer,
    //             'source'     => $sourceInfo,
    //             'ip_address' => $request->ip(),
    //         ]);

    //         return response()->json([
    //             'status' => 'success',
    //             'answer' => $aiAnswer,
    //             'source' => $sourceInfo
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'AI Error: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }


    // public function askAi(Request $request)
    // {
    //     $request->validate(['question' => 'required|string|max:1000']);
    //     $question = $request->input('question');
    //     $empId = Session::get('employee_id') ?? 1;


    //     try {
    //         // 🟢 1. แปลง "คำถามของผู้ใช้" เป็น Vector ผ่าน Gemini SDK (ปลอดภัยและไม่ติด 403)
    //         $qEmbeddingResponse = Gemini::embedding()
    //             ->model('text-embedding-004')
    //             ->content($question);

    //         $questionVector = $qEmbeddingResponse->values;

    //         if (empty($questionVector)) {
    //             throw new \Exception('โครงสร้างข้อมูล Embedding จากคำถามไม่ถูกต้อง');
    //         }


    //         // 2. ดึง Chunks ทั้งหมดจาก Oracle ที่ยังไม่ถูกลบ
    //         $primaryKeyName = (new ChatbotDocument())->getKeyName();
    //         $rawChunks = DB::table('SUPPORT_CHATBOT_DOCUMENT_CHUNKS as c')
    //             ->join('SUPPORT_CHATBOT_DOCUMENTS as d', 'c.DOCUMENT_ID', '=', 'd.' . $primaryKeyName)
    //             ->whereNull('d.deleted_at')
    //             ->select('c.CHUNK_TEXT as chunk_text', 'd.title as title', 'c.EMBEDDING_VECTOR as embedding')
    //             ->get();

    //         if ($rawChunks->isEmpty()) {
    //             return response()->json(['status' => 'success', 'answer' => 'ขออภัยครับ ยังไม่มีเอกสารในระบบ', 'source' => null]);
    //         }

    //         // 3. คำนวณความเหมือน (Cosine Similarity) ทีละ Chunk
    //         $scoredChunks = [];
    //         foreach ($rawChunks as $row) {
    //             if (empty($row->embedding)) continue;

    //             $chunkVector = json_decode($row->embedding, true);
    //             if (!is_array($chunkVector)) continue;

    //             $score = $this->cosineSimilarity($questionVector, $chunkVector);

    //             $scoredChunks[] = [
    //                 'score'      => $score,
    //                 'chunk_text' => $row->chunk_text,
    //                 'title'      => $row->title
    //             ];
    //         }

    //         // 4. เรียงลำดับจากคะแนนความเหมือนมากที่สุด ไปน้อยที่สุด และหยิบมา Top 5
    //         usort($scoredChunks, function ($a, $b) {
    //             return $b['score'] <=> $a['score'];
    //         });

    //         $topChunks = array_slice($scoredChunks, 0, 5);

    //         if (empty($topChunks)) {
    //             return response()->json(['status' => 'success', 'answer' => 'ขออภัยครับ ไม่พบข้อมูลที่เกี่ยวข้อง', 'source' => null]);
    //         }

    //         // 5. สกัดเอาเฉพาะเนื้อหาและชื่อ Title ของไฟล์ที่ได้คะแนนสูงสุดมาทำเป็น Context และ Source
    //         $contextLines = [];
    //         $matchedTitles = [];
    //         foreach ($topChunks as $item) {
    //             $contextLines[] = "[แหล่งที่มาเอกสาร: {$item['title']}]\n{$item['chunk_text']}";
    //             $matchedTitles[] = $item['title'];
    //         }

    //         $combinedContext = implode("\n\n--------------------\n\n", $contextLines);
    //         $sourceInfo = collect($matchedTitles)->unique()->implode(', ');

    //         // 6. ส่ง Prompt ให้ Gemini 3.5 Flash ตอบคำถามตามโครงสร้างเดิมที่เราทำไว้
    //         $prompt = "คุณคือผู้ช่วยอัจฉริยะของกรมวิทยาศาสตร์บริการ หน้าที่ของคุณคือตอบคำถามโดยอ้างอิงจาก 'เนื้อหาเอกสาร' ที่กำหนดให้ด้านล่างนี้เท่านั้น " .
    //             "ห้ามใช้ความรู้ภายนอก ห้ามแต่งเติมเองเด็ดขาด! " .
    //             "หากคำตอบไม่ได้อยู่ในเอกสารนี้ ให้ตอบว่า 'ขออภัยค่ะ ไม่พบข้อมูลดังกล่าวในระเบียบหรือเอกสารที่อัปโหลดไว้' " .
    //             "และให้ระบุชื่อหัวข้อเอกสาร (Title) จากป้ายกำกับ [แหล่งที่มาเอกสาร: ...] ที่คุณนำข้อมูลมาใช้ตอบ\n\n" .
    //             "--- เนื้อหาเอกสารอ้างอิง ---\n" .
    //             $combinedContext . "\n" .
    //             "----------------------------\n\n" .
    //             "คำถามจากผู้ใช้งาน: " . $question;

    //         $response = Gemini::generativeModel(model: 'gemini-3.5-flash')->generateContent($prompt);
    //         $aiAnswer = $response->text();

    //         // 7. บันทึกประวัติการสนทนาพร้อม Source
    //         ChatbotLog::create([
    //             'emp_id'     => $empId,
    //             'question'   => $question,
    //             'answer'     => $aiAnswer,
    //             'source'     => $sourceInfo,
    //             'ip_address' => $request->ip(),
    //         ]);

    //         return response()->json([
    //             'status' => 'success',
    //             'answer' => $aiAnswer,
    //             'source' => $sourceInfo
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'AI Error: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }


    // private function cosineSimilarity(array $a, array $b): float
    // {
    //     $dotProduct = 0;
    //     $magnitudeA = 0;
    //     $magnitudeB = 0;

    //     for ($i = 0; $i < count($a); $i++) {
    //         $dotProduct += $a[$i] * $b[$i];
    //         $magnitudeA += $a[$i] * $a[$i];
    //         $magnitudeB += $b[$i] * $b[$i];
    //     }

    //     $magnitudeA = sqrt($magnitudeA);
    //     $magnitudeB = sqrt($magnitudeB);

    //     if ($magnitudeA == 0 || $magnitudeB == 0) {
    //         return 0.0;
    //     }

    //     return $dotProduct / ($magnitudeA * $magnitudeB);
    // }


    // public function askAi(Request $request)
    // {
    //     $request->validate(['question' => 'required|string|max:1000']);
    //     $question = $request->input('question');
    //     $empId = Session::get('employee_id') ?? 1;

    //     try {
    //         // 1. ดึงเอกสารทั้งหมดที่ยังไม่ถูกลบ
    //         $documents = ChatbotDocument::whereNull('deleted_at')->latest()->get();

    //         if ($documents->isEmpty()) {
    //             return response()->json([
    //                 'status' => 'success',
    //                 'answer' => 'ขออภัยค่ะ ยังไม่มีเอกสารในระบบ',
    //                 'source' => null
    //             ]);
    //         }

    //         $primaryKeyName = (new ChatbotDocument())->getKeyName();
    //         $documentIds = $documents->pluck($primaryKeyName);

    //         // 2. ดึง Chunks ทั้งหมดพร้อมระบุ ID และ Title ของเอกสารแต่ละชิ้น
    //         $rawChunks = DB::table('SUPPORT_CHATBOT_DOCUMENT_CHUNKS as c')
    //             ->join('SUPPORT_CHATBOT_DOCUMENTS as d', 'c.DOCUMENT_ID', '=', 'd.' . $primaryKeyName)
    //             ->whereIn('c.DOCUMENT_ID', $documentIds)
    //             ->whereNull('d.deleted_at')
    //             ->orderBy('c.DOCUMENT_ID', 'desc')
    //             ->orderBy('c.CHUNK_INDEX', 'asc')
    //             ->select('c.CHUNK_TEXT as chunk_text', 'd.' . $primaryKeyName . ' as doc_id', 'd.title as title')
    //             ->get();

    //         if ($rawChunks->isEmpty()) {
    //             return response()->json([
    //                 'status' => 'success',
    //                 'answer' => 'ขออภัยค่ะ ไม่พบเนื้อหาข้อความภายในเอกสาร',
    //                 'source' => null
    //             ]);
    //         }

    //         // 3. จัดรูปแบบเนื้อหาให้ AI มองเห็นรหัสเอกสาร (ID) และข้อความ
    //         $contextLines = [];
    //         foreach ($rawChunks as $row) {
    //             if (!empty($row->chunk_text)) {
    //                 $docId = $row->doc_id;
    //                 $docTitle = $row->title ?? 'เอกสารไม่ระบุชื่อ';
    //                 // ใส่รหัส ID กำกับไว้ เพื่อให้ AI อ้างอิงกลับมาเป็นตัวเลข ID ได้แม่นยำ
    //                 $contextLines[] = "[DOC_ID: {$docId} | ชื่อเอกสาร: {$docTitle}]\n{$row->chunk_text}";
    //             }
    //         }
    //         $combinedContext = implode("\n\n--------------------\n\n", $contextLines);

    //         // 4. บังคับให้ AI ตอบกลับมาเป็น JSON โดยเลือก "DOC_ID" ที่ใช้ตอบ
    //         $prompt = "คุณคือผู้ช่วยอัจฉริยะของกรมวิทยาศาสตร์บริการ หน้าที่ของคุณคือตอบคำถามโดยอ้างอิงจาก 'เนื้อหาเอกสาร' ที่กำหนดให้ด้านล่างนี้เท่านั้น " .
    //             "ห้ามใช้ความรู้ภายนอก ห้ามแต่งเติมเองเด็ดขาด! " .
    //             "หากคำตอบไม่ได้อยู่ในเอกสารนี้ ให้ตอบคำว่า 'ขออภัยค่ะ ไม่พบข้อมูลดังกล่าวในระเบียบหรือเอกสารที่อัปโหลดไว้' " .
    //             "และให้คุณตรวจสอบดูว่าข้อมูลที่คุณนำมาใช้ตอบนั้นมาจาก [DOC_ID: ...] ของเอกสารเล่มไหน ให้ระบุตัวเลข DOC_ID นั้นกลับมาด้วย\n\n" .
    //             "กรุณาตอบกลับมาในรูปแบบ JSON ที่มีโครงสร้างนี้เท่านั้น (ห้ามมีข้อความอื่นนอกเหนือจาก JSON):\n" .
    //             "{\n" .
    //             "  \"answer\": \"ข้อความคำตอบของคุณที่จัดรูปแบบอ่านง่าย\",\n" .
    //             "  \"doc_id\": รหัสตัวเลข DOC_ID ของเอกสารที่ใช้ตอบ (ถ้าไม่พบข้อมูลหรือตอบว่าไม่พบ ให้ใส่ค่าเป็น null)\n" .
    //             "}\n\n" .
    //             "--- เนื้อหาเอกสารอ้างอิง ---\n" .
    //             $combinedContext . "\n" .
    //             "----------------------------\n\n" .
    //             "คำถามจากผู้ใช้งาน: " . $question;

    //         // 5. ส่งให้ Gemini ประมวลผล
    //         $response = Gemini::generativeModel(model: 'gemini-3.5-flash')->generateContent($prompt);
    //         $aiRawResponse = trim($response->text());

    //         // ทำความสะอาด JSON
    //         $cleanedJson = preg_replace('/^```json\s*|\s*```$/i', '', $aiRawResponse);
    //         $dataResponse = json_decode($cleanedJson, true);

    //         $aiAnswer = '';
    //         $sourceInfo = null;

    //         if (json_last_error() === JSON_ERROR_NONE && isset($dataResponse['answer'])) {
    //             $aiAnswer = $dataResponse['answer'];
    //             $selectedDocId = $dataResponse['doc_id'] ?? null;

    //             // 6. นำ DOC_ID ที่ AI เลือก ไปดึงชื่อ "Title ตรงๆ" จากฐานข้อมูลมาใช้งานทันที!
    //             if (!empty($selectedDocId)) {
    //                 $matchedDoc = ChatbotDocument::where($primaryKeyName, $selectedDocId)->first();
    //                 if ($matchedDoc && !empty($matchedDoc->title)) {
    //                     $sourceInfo = $matchedDoc->title; //  ได้ชื่อ Title ตรงเป๊ะจากตารางเอกสาร
    //                 }
    //             }
    //         } else {
    //             // กรณีสำรอง ถ้า JSON พัง ให้ใช้ข้อความดิบ
    //             $aiAnswer = $aiRawResponse;
    //             $sourceInfo = $documents->first()?->title;
    //         }

    //         // 7. บันทึกประวัติการสนทนาลงฐานข้อมูล
    //         ChatbotLog::create([
    //             'emp_id'     => $empId,
    //             'question'   => $question,
    //             'answer'     => $aiAnswer,
    //             'source'     => $sourceInfo,
    //             'ip_address' => $request->ip(),
    //         ]);

    //         return response()->json([
    //             'status' => 'success',
    //             'answer' => $aiAnswer,
    //             'source' => $sourceInfo
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'AI Error: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }




    public function myHistory()
    {
        $empId = Session::get('employee_id');
        $logs = ChatbotLog::where('emp_id', $empId)->orderBy('created_at', 'desc')->paginate(15);
        return view('pages.chatbot.history', compact('logs'));
    }
}
