@extends('layouts.app')

@section('title', 'Chatbot')

<style>
    @keyframes blink {
        0% {
            opacity: .2;
        }

        20% {
            opacity: 1;
        }

        100% {
            opacity: .2;
        }
    }

    .typing-dot span {
        animation: blink 1.4s infinite both;
        height: 8px;
        width: 8px;
        background-color: #9CA3AF;
        display: inline-block;
        border-radius: 50%;
        margin: 0 2px;
    }

    .typing-dot span:nth-child(2) {
        animation-delay: .2s;
    }

    .typing-dot span:nth-child(3) {
        animation-delay: .4s;
    }
</style>

@section('content')
    <div
        class="flex flex-col h-[calc(100vh-120px)] bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
        <!-- Header -->
        <div
            class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50/50 dark:bg-gray-800/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-500 flex items-center justify-center text-white shadow-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                        </path>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-gray-800 dark:text-white">DSS AI Assistant (ทดสอบขอบข่ายระเบียบสารบรรณ)</h2>
                    <p class="text-xs text-gray-500">ระบบปัญญาประดิษฐ์อัจฉริยะขับเคลื่อนด้วย Google Gemini API</p>
                </div>
            </div>
            <a href="{{ route('chatbot.history') }}"
                class="text-xs font-medium text-brand-600 hover:underline">ประวัติการสนทนาของฉัน</a>
        </div>

        <!-- Chat Messages Box -->
        <div id="chat-box" class="flex-1 overflow-y-auto p-6 space-y-4">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-brand-500 flex items-center justify-center text-white text-xs shrink-0">AI
                </div>
                <div class="p-4 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm max-w-xl">
                    สวัสดี! น้องมะลิ พร้อมช่วยตอบคำถามและค้นหาข้อมูลจากเอกสารระเบียบภายในแล้ว
                </div>
            </div>
        </div>

        <!-- Input Form -->
        <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
            <form id="chat-form" class="flex gap-3">
                @csrf
                <input type="text" id="question-input" placeholder="พิมพ์คำถามของท่านที่นี่..."
                    class="flex-1 px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:border-brand-500 text-sm"
                    required>
                <button type="submit"
                    class="px-6 py-3 rounded-xl bg-brand-500 text-white font-medium hover:bg-brand-600 transition shadow-md text-sm">
                    ส่งข้อความ
                </button>
            </form>
        </div>
    </div>

    <!-- Script สำหรับส่งแชทแบบ AJAX พร้อม Loading State -->
    <script>
        document.getElementById('chat-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const input = document.getElementById('question-input');
            const chatBox = document.getElementById('chat-box');
            const question = input.value.trim();
            if (!question) return;

            // 1. แสดงข้อความฝั่งผู้ใช้
            chatBox.innerHTML += `
                <div class="flex justify-end mb-4">
                    <div class="p-4 rounded-2xl bg-brand-500 text-white text-sm max-w-xl">${question}</div>
                </div>`;
            input.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;

            // 2. แสดง Loading State (กำลังประมวลผล)
            const loadingId = 'loading-' + Date.now();
            chatBox.innerHTML += `
                <div id="${loadingId}" class="flex items-start gap-3 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-brand-500 flex items-center justify-center text-white text-xs shrink-0">AI</div>
                    <div class="p-4 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-500 text-sm flex items-center gap-2">
                        <span>DSS AI กำลังประมวลผลคำตอบ</span>
                        <div class="typing-dot flex items-center">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>`;
            chatBox.scrollTop = chatBox.scrollHeight;

            try {
                const response = await fetch("{{ route('chatbot.ask') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        question: question
                    })
                });
                const data = await response.json();

                // 3. ลบกล่อง Loading ออกเมื่อได้คำตอบ
                document.getElementById(loadingId).remove();

                if (response.ok && data.status === 'success') {
                    const formattedAnswer = formatAiResponse(data.answer);

                    // 🟢 เพิ่มส่วนแสดงแหล่งอ้างอิง (Source) ไว้ด้านล่างคำตอบของ AI ตรงนี้
                    let sourceHtml = '';
                    if (data.source) {
                        sourceHtml = `
        <div class="text-xs text-gray-400 dark:text-gray-500 italic mt-2 pt-2 border-t border-gray-100 dark:border-gray-700 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 inline shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
            </svg>
            <span>แหล่งอ้างอิงจาก: <strong class="text-gray-600 dark:text-gray-300 font-medium">${data.source}</strong></span>
        </div>
    `;
                    }

                    // แสดงผลคำตอบ AI พร้อมแหล่งอ้างอิงที่ดึงมาจาก Title
                    chatBox.innerHTML += `
                        <div class="flex items-start gap-3 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-brand-500 flex items-center justify-center text-white text-xs shrink-0">AI</div>
                            <div class="p-4 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm max-w-xl leading-relaxed shadow-sm">
                                <div>${formattedAnswer}</div>
                                ${sourceHtml}
                            </div>
                        </div>`;
                } else {
                    chatBox.innerHTML +=
                        `<div class="text-red-500 text-xs p-2 mb-4">⚠️ Error: ${data.message || 'เกิดข้อผิดพลาดในการตอบกลับจาก AI'}</div>`;
                }
            } catch (err) {
                const loadingElement = document.getElementById(loadingId);
                if (loadingElement) loadingElement.remove();

                chatBox.innerHTML +=
                    `<div class="text-red-500 text-xs p-2 mb-4">⚠️ Network Error: ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้</div>`;
            }
            chatBox.scrollTop = chatBox.scrollHeight;
        });

        // ฟังก์ชันจัดรูปแบบข้อความขึ้นบรรทัดใหม่
        function formatAiResponse(text) {
            if (!text) return '';
            return text.replace(/\n/g, '<br>');
        }
    </script>
@endsection
