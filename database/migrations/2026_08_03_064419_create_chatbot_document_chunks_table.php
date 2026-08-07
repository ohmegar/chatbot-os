<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_chatbot_document_chunks', function (Blueprint $table) {
            $table->id('ch_cid');
            $table->unsignedBigInteger('document_id'); // อ้างอิงตาราง support_chatbot_documents
            $table->text('chunk_text');               // ข้อความย่อยที่ถูกตัด (Chunk)
            $table->integer('chunk_index');           // ลำดับที่ของ Chunk ในเอกสารนั้นๆ
            $table->timestamps();

            // กำหนด Foreign Key เชื่อมโยง (ถ้า Oracle รองรับ)
            $table->foreign('document_id')->references('ch_did')->on('support_chatbot_documents')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_chatbot_document_chunks');
    }
};
