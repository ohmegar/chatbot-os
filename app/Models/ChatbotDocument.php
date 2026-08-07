<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatbotDocument extends Model
{
    use SoftDeletes;

    protected $table = 'support_chatbot_documents';
    protected $primaryKey = 'ch_did';
    public $sequence = 'support_chatbot_documents_seq';
    public $incrementing = true;
    public $timestamps = true;


    protected $fillable = ['title', 'file_path', 'original_filename', 'emp_id'];
}
