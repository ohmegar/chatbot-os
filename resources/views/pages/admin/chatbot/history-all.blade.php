@extends('layouts.app')

@section('title', 'ประวัติการสนทนาทั้งหมดในระบบ')

@section('content')
    <!-- 🟢 เพิ่ม selectedSource ใน x-data สำหรับเก็บค่าแหล่งอ้างอิง -->
    <div class="container mx-auto px-4 py-6" x-data="{ openModal: false, selectedQuestion: '', selectedAnswer: '', selectedEmp: '', selectedTime: '', selectedSource: '' }">

        <!-- Header & Search Box -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-800 dark:text-white">ประวัติการสนทนากับ AI ทั้งหมด (System Audit)</h1>
                <p class="text-xs text-gray-500 mt-1">ตรวจสอบรายการคำถาม คำตอบ และแหล่งอ้างอิงของเจ้าหน้าที่ทุกคนภายในระบบ
                </p>
            </div>

            <!-- ฟอร์มค้นหา -->
            <form method="GET" action="{{ route('admin.chatbot.history.all') }}" class="flex gap-2 w-full md:w-auto">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาคำถาม, พนักงาน..."
                    class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-white focus:outline-none focus:border-brand-500 w-full md:w-64">
                <button type="submit"
                    class="px-4 py-2 bg-brand-500 text-white rounded-xl text-sm font-medium hover:bg-brand-600 transition">
                    ค้นหา
                </button>
                @if (request('search'))
                    <a href="{{ route('admin.chatbot.history.all') }}"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-sm font-medium hover:bg-gray-300 transition">
                        ล้าง
                    </a>
                @endif
            </form>
        </div>

        <!-- ตารางแสดงรายการประวัติ -->
        <div
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                            <th class="p-4">รหัสพนักงาน (Emp ID)</th>
                            <th class="p-4">คำถาม</th>
                            <th class="p-4">คำตอบย่อ</th>
                            <th class="p-4">แหล่งอ้างอิง</th> <!-- 🟢 เพิ่มหัวข้อคอลัมน์แหล่งอ้างอิง -->
                            <th class="p-4">เวลา</th>
                            <th class="p-4 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                                <td class="p-4 font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ $log->emp_id }}
                                </td>
                                <td class="p-4 font-medium text-gray-800 dark:text-gray-200 max-w-xs truncate">
                                    {{ $log->question }}
                                </td>
                                <td class="p-4 text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                    {{ $log->answer }}
                                </td>
                                <!-- 🟢 แสดงชื่อแหล่งอ้างอิงย่อในตาราง -->
                                <td class="p-4 text-xs text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                    @if (!empty($log->source))
                                        <span class="inline-flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                                </path>
                                            </svg>
                                            {{ $log->source }}
                                        </span>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">-</span>
                                    @endif
                                </td>
                                <td class="p-4 text-xs text-gray-400 whitespace-nowrap">
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="p-4 text-center whitespace-nowrap">
                                    <!-- 🟢 ส่งค่า selectedSource เข้าไปในปุ่มเปิด Modal ด้วย -->
                                    <button
                                        @click="
                                            selectedQuestion = '{{ addslashes($log->question) }}';
                                            selectedAnswer = `{{ addslashes($log->answer) }}`;
                                            selectedEmp = 'รหัสพนักงาน: #{{ $log->emp_id }}';
                                            selectedTime = '{{ $log->created_at->format('d/m/Y H:i') }}';
                                            selectedSource = '{{ addslashes($log->source ?? '') }}';
                                            openModal = true;
                                        "
                                        class="px-3 py-1.5 bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400 rounded-lg text-xs font-medium hover:bg-brand-100 transition">
                                        ดูรายละเอียด
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-gray-400">ไม่พบข้อมูลประวัติการสนทนาในระบบ
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($logs->hasPages())
                <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

        <!-- Modal สำหรับแสดงข้อความแบบเต็ม -->
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
                        <h3 class="font-bold text-gray-800 dark:text-white text-base">รายละเอียดการสนทนา (Audit)</h3>
                        <p class="text-xs text-gray-400"><span x-text="selectedEmp"></span> | <span
                                x-text="selectedTime"></span></p>
                    </div>
                    <button @click="openModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-6 flex-1 text-sm">
                    <div>
                        <span
                            class="text-xs font-semibold text-brand-600 dark:text-brand-400 uppercase tracking-wider block mb-1">คำถามจากผู้ใช้</span>
                        <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-200 font-medium"
                            x-text="selectedQuestion"></div>
                    </div>

                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-1">คำตอบจาก AI
                            Assistant</span>
                        <div class="p-4 rounded-xl bg-gray-100 dark:bg-gray-800/80 text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-line"
                            x-html="selectedAnswer"></div>
                    </div>

                    <!-- 🟢 เพิ่มส่วนแสดงแหล่งอ้างอิงใน Modal -->
                    <template x-if="selectedSource">
                        <div
                            class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <svg class="w-4 h-4 text-brand-500 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                </path>
                            </svg>
                            <span>แหล่งอ้างอิงจาก: <strong class="text-gray-700 dark:text-gray-200 font-medium"
                                    x-text="selectedSource"></strong></span>
                        </div>
                    </template>
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
