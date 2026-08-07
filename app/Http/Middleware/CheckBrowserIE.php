<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent; // นำเข้า Class Agent

class CheckBrowserIE
{
    public function handle(Request $request, Closure $next)
    {
        $agent = new Agent();

        // เช็คว่าถ้าเป็น IE (หรือชื่อเฉพาะคือ 'IE') ให้หยุดการทำงาน
        if (
            $agent->browser() === 'IE' ||
            str_contains($request->header('User-Agent'), 'Trident') ||
            str_contains($request->header('User-Agent'), 'MSIE')
        ) {
            return redirect()->route('ie');
        }

        return $next($request);
    }
}
