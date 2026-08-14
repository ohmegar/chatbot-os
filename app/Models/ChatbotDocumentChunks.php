<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotDocumentChunks extends Model
{

    protected $table = 'support_chatbot_document_chunks';
    protected $primaryKey = 'ch_cid';
    public $sequence = 'support_chatbot_document_chunks_seq';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'document_id',
        'chunk_text',
        'chunk_index',
    ];
}
