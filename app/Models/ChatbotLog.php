<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotLog extends Model
{
    use HasFactory;

    protected $table = 'support_chatbot_logs'; // ชื่อตารางใน Oracle DB ของคุณ
    protected $primaryKey = 'ch_lid';
    public $sequence = 'support_chatbot_logs_seq';
    public $incrementing = true;
    public $timestamps = true;


    protected $fillable = [
        'emp_id',     // รหัสพนักงานผู้ถาม
        'question',   // คำถาม
        'answer',     // คำตอบจาก AI (Gemini)
        'source',
        'ip_address', // ไอพีแอดเดรส
    ];
}
