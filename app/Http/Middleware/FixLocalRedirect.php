<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FixLocalRedirect
{
    /**
     * ดักจับ Response ขาออกทุกตัว ถ้าพบการรีไดเรกต์ไป .local ให้สลับคำเป็นโดเมนภายนอกทันที
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // เช็กว่ามีการสั่งเปลี่ยนหน้า (Redirect) ไหม
        if (method_exists($response, 'isRedirect') && $response->isRedirect()) {
            $location = $response->headers->get('Location');

            // ตรวจพบว่าแอปหรือแพ็กเกจพ่นคำว่า .local ออกมา
            if ($location && str_contains($location, '360.dss.local')) {
                // เปลี่ยนข้อความให้วิ่งกลับไปทางโดเมนภายนอกของกรมฯ ทันที
                $newLocation = str_replace('http://360.dss.local', 'https://www.dss.go.th/360', $location);
                $newLocation = str_replace('https://360.dss.local', 'https://www.dss.go.th/360', $newLocation);

                $response->headers->set('Location', $newLocation);
            }
        }

        return $response;
    }
}
