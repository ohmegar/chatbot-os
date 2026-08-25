@extends('layouts.app')

@section('title', 'Chatbot')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-gray-800 dark:text-white">Training AI - จัดการคู่มือและระเบียบ</h1>
                <p class="text-sm text-gray-500">อัปโหลดไฟล์ PDF เพื่อให้ AI ใช้เป็นฐานข้อมูลในการตอบคำถาม</p>
            </div>
            <a href="{{ route('admin.chatbot.trash') }}"
                class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-200 transition">
                ดูถังขยะเอกสาร
            </a>
        </div>

        @if (session('success'))
            <div class="p-4 bg-green-50 text-green-600 rounded-xl text-sm border border-green-100">{{ session('success') }}
            </div>
        @endif

        <!-- Form Upload -->
        <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800">
            <form action="{{ route('admin.chatbot.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label
                        class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-2">ชื่อหัวข้อเอกสาร
                        / ระเบียบ</label>
                    <input type="text" name="title"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:border-brand-500"
                        required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase mb-2">ไฟล์ PDF
                        (สูงสุด 20MB)</label>
                    <input type="file" name="pdf_file" accept=".pdf"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100"
                        required>
                </div>
                <button type="submit"
                    class="px-6 py-3 bg-brand-500 text-white rounded-xl text-sm font-medium hover:bg-brand-600 transition shadow-md">
                    อัปโหลดและฝึกสอน AI
                </button>
            </form>
        </div>

        <!-- Document List Table -->
        <div
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 uppercase">
                        <th class="p-4">ชื่อหัวข้อ</th>
                        <th class="p-4">ชื่อไฟล์ต้นฉบับ</th>
                        <th class="p-4 text-center">ดูไฟล์</th>
                        <th class="p-4">วันที่อัปโหลด</th>
                        <th class="p-4 text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($documents as $doc)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition">
                            <td class="p-4 font-medium text-gray-800 dark:text-white">{{ $doc->title }}</td>
                            <td class="p-4 text-gray-500">{{ $doc->original_filename }}</td>
                            <td class="p-4 text-center">
                                @if (!empty($doc->file_path))
                                    <!-- ปุ่มคลิกเปิดดู PDF ในแท็บใหม่ -->
                                    <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank"
                                        class="px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg text-xs font-medium hover:bg-blue-100 transition inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                            </path>
                                        </svg>
                                        ดู PDF
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400">ไม่มีไฟล์</span>
                                @endif
                            </td>
                            <td class="p-4 text-xs text-gray-400">{{ $doc->created_at->format('d/m/Y H:i') }}</td>
                            <td class="p-4 text-center">
                                <form action="{{ route('admin.chatbot.destroy', $doc->ch_did) }}" method="POST"
                                    onsubmit="return confirm('คุณต้องการลบเอกสารนี้ใช่หรือไม่?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="text-xs text-red-600 hover:underline font-medium">ลบ</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-gray-400">ยังไม่มีเอกสารในระบบ</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                {{ $documents->links() }}
            </div>
        </div>
    </div>
@endsection
