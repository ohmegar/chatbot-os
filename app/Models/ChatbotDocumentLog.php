<?php

namespace App\Models;

use App\Models\ChatbotDocument;
use Illuminate\Database\Eloquent\Model;


class ChatbotDocumentLog extends Model
{

    protected $table = 'support_chatbot_document_logs'; // ชื่อตารางใน Oracle DB ของคุณ
    protected $primaryKey = 'ch_dlid';
    public $sequence = 'support_chatbot_document_logs_seq';
    public $incrementing = true;
    public $timestamps = true;


    protected $fillable = [
        'document_id', // อ้างอิง ID เอกสาร (ถ้ามี)
        'emp_id',      // รหัสพนักงานผู้ทำรายการ (Admin)
        'action',      // การกระทำ เช่น 'UPLOAD', 'DELETE', 'RESTORE'
        'description', // รายละเอียดเพิ่มเติม
    ];

    // ความสัมพันธ์: Log นี้เชื่อมโยงกับเอกสารอะไร
    public function document()
    {
        return $this->belongsTo(ChatbotDocument::class, 'document_id', 'ch_did');
    }
}
