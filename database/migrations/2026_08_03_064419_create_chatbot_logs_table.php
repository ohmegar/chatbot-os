<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('support_chatbot_logs', function (Blueprint $table) {
            $table->id('ch_lid');
            $table->unsignedBigInteger('emp_id'); // ผู้ถาม (รหัสพนักงาน)
            $table->text('question');             // คำถาม
            $table->longText('answer');           // คำตอบจาก AI (Gemini)
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_chatbot_logs');
    }
};
