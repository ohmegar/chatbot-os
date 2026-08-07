<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_chatbot_document_logs', function (Blueprint $table) {
            $table->id('ch_dlid');
            $table->unsignedBigInteger('document_id')->nullable(); // เอกสารที่เกี่ยวข้อง
            $table->unsignedBigInteger('emp_id');                 // ผู้ทำรายการ (Admin)
            $table->string('action');                             // การกระทำ เช่น UPLOAD, DELETE, RESTORE
            $table->text('description')->nullable();              // รายละเอียดเพิ่มเติม
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_chatbot_document_logs');
    }
};
