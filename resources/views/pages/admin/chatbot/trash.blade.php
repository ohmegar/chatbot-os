@extends('layouts.app')

@section('title', 'Chatbot')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-gray-800 dark:text-white">ถังขยะเอกสาร</h1>
                <p class="text-sm text-gray-500">จัดการกู้คืนเอกสารที่ถูกลบไปแล้ว</p>
            </div>
            <a href="{{ route('admin.chatbot.docs') }}"
                class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-200 transition">
                กลับหน้าจัดการเอกสาร
            </a>
        </div>

        @if (session('success'))
            <div class="p-4 bg-green-50 text-green-600 rounded-xl text-sm border border-green-100">{{ session('success') }}
            </div>
        @endif

        <div
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 uppercase">
                        <th class="p-4">ชื่อหัวข้อ</th>
                        <th class="p-4">ชื่อไฟล์ต้นฉบับ</th>
                        <th class="p-4 text-center">ดูไฟล์</th>
                        <th class="p-4">วันที่ลบ</th>
                        <th class="p-4 text-center">กู้คืน</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($trashedDocs as $doc)
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

                            <td class="p-4 text-xs text-gray-400">{{ $doc->deleted_at->format('d/m/Y H:i') }}</td>
                            <td class="p-4 text-center">
                                <form action="{{ route('admin.chatbot.restore', $doc->ch_did) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="text-xs text-brand-600 hover:underline font-medium">กู้คืน</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-gray-400">ไม่มีเอกสารในถังขยะ</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                {{ $trashedDocs->links() }}
            </div>
        </div>
    </div>
@endsection
