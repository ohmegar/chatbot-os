<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_chatbot_documents', function (Blueprint $table) {
            $table->id('ch_did');
            $table->string('title');
            $table->string('file_path');
            $table->string('original_filename');
            $table->unsignedBigInteger('emp_id'); // รหัสพนักงานผู้ทำรายการ
            $table->timestamps();
            $table->softDeletes(); // รองรับ Soft Delete
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_chatbot_documents');
    }
};
