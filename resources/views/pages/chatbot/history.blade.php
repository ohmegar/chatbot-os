@extends('layouts.app')

@section('title', 'ประวัติการสนทนากับ AI')

@section('content')
    <div class="container mx-auto px-4 py-6" x-data="{ openModal: false, selectedQuestion: '', selectedAnswer: '', selectedTime: '' }">

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">ประวัติการสนทนากับ AI ของฉัน</h1>
            <a href="{{ route('chatbot.index') }}"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-sm hover:bg-gray-300 transition">
                &larr; กลับไปหน้าแชท
            </a>
        </div>

        <!-- ตารางแสดงรายการประวัติ -->
        <div
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                            <th class="p-4">คำถาม</th>
                            <th class="p-4">คำตอบย่อ</th>
                            <th class="p-4">เวลา</th>
                            <th class="p-4 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                <td class="p-4 font-medium text-gray-800 dark:text-gray-200 max-w-xs truncate">
                                    {{ $log->question }}
                                </td>
                                <td class="p-4 text-gray-500 dark:text-gray-400 max-w-sm truncate">
                                    {{ $log->answer }}
                                </td>
                                <td class="p-4 text-xs text-gray-400 whitespace-nowrap">
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="p-4 text-center whitespace-nowrap">
                                    <!-- 🟢 ปุ่มคลิกเพื่อเปิด Modal โดยส่งข้อมูลคำถาม/คำตอบเข้าไป -->
                                    <button
                                        @click="
                                selectedQuestion = '{{ addslashes($log->question) }}';
                                selectedAnswer = `{{ addslashes($log->answer) }}`;
                                selectedTime = '{{ $log->created_at->format('d/m/Y H:i') }}';
                                openModal = true;
                            "
                                        class="px-3 py-1.5 bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400 rounded-lg text-xs font-medium hover:bg-brand-100 transition">
                                        ดูรายละเอียด
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-gray-400">ยังไม่มีประวัติการสนทนา</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if (method_exists($logs, 'links'))
                <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

        <!-- 🟢 Modal สำหรับแสดงข้อความแบบเต็ม -->
        <div x-show="openModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;">

            <div class="bg-white dark:bg-gray-900 w-full max-w-2xl rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 overflow-hidden flex flex-col max-h-[85vh]"
                @click.away="openModal = false">

                <!-- Modal Header -->
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                    <div>
                        <h3 class="font-bold text-gray-800 dark:text-white text-base">รายละเอียดการสนทนา</h3>
                        <p class="text-xs text-gray-400" x-text="selectedTime"></p>
                    </div>
                    <button @click="openModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body (เลื่อนดูเนื้อหาด้านในได้ถ้าข้อความยาว) -->
                <div class="p-6 overflow-y-auto space-y-6 flex-1 text-sm">
                    <!-- คำถามของผู้ใช้ -->
                    <div>
                        <span
                            class="text-xs font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider block mb-1">คำถามของคุณ</span>
                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-200 font-medium"
                            x-text="selectedQuestion"></div>
                    </div>

                    <!-- คำตอบของ AI -->
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-1">คำตอบจาก AI
                            Assistant</span>
                        <!-- ใช้ x-html เพื่อให้รองรับการขึ้นบรรทัดใหม่หรือแท็ก HTML ถ้ามี -->
                        <div class="p-4 rounded-xl bg-gray-100 dark:bg-gray-800/80 text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-line"
                            x-html="selectedAnswer"></div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="px-6 py-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex justify-end">
                    <button @click="openModal = false"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-medium hover:bg-gray-300 transition">
                        ปิดหน้าต่าง
                    </button>
                </div>

            </div>
        </div>

    </div>
@endsection
