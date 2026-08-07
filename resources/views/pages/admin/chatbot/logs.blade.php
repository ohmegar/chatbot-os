@extends('layouts.app')

@section('title', 'Chatbot')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">ประวัติการทำรายการระบบ AI (Audit Logs)</h1>
            <p class="text-sm text-gray-500">บันทึกประวัติการอัปโหลด ลบ และกู้คืนเอกสารของผู้ดูแลระบบ</p>
        </div>
        <a href="{{ route('admin.chatbot.docs') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-200 transition">
            กลับหน้าจัดการเอกสาร
        </a>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 text-xs font-semibold text-gray-500 uppercase">
                    <th class="p-4">การกระทำ (Action)</th>
                    <th class="p-4">รายละเอียด</th>
                    <th class="p-4">รหัสพนักงาน (Admin)</th>
                    <th class="p-4">วันเวลา</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition">
                        <td class="p-4">
                            @if($log->action === 'UPLOAD')
                                <span class="px-2.5 py-1 bg-green-50 text-green-700 rounded-lg text-xs font-semibold">UPLOAD</span>
                            @elseif($log->action === 'DELETE')
                                <span class="px-2.5 py-1 bg-red-50 text-red-700 rounded-lg text-xs font-semibold">DELETE</span>
                            @elseif($log->action === 'RESTORE')
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg text-xs font-semibold">RESTORE</span>
                            @else
                                <span class="px-2.5 py-1 bg-gray-50 text-gray-700 rounded-lg text-xs font-semibold">{{ $log->action }}</span>
                            @endif
                        </td>
                        <td class="p-4 text-gray-800 dark:text-white font-medium">{{ $log->description }}</td>
                        <td class="p-4 text-gray-500">{{ $log->emp_id }}</td>
                        <td class="p-4 text-xs text-gray-400 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-gray-400">ยังไม่มีประวัติการทำรายการในระบบ</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100 dark:border-gray-800">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
