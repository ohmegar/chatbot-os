<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Exception;
use Vizir\KeycloakWebGuard\Services\KeycloakService;
use Vizir\KeycloakWebGuard\Auth\KeycloakAccessToken;

class CheckKeycloakSession
{
    /**
     * ดึง KeycloakService เข้ามาใช้งานผ่าน Constructor Injection
     */
    public function __construct(protected KeycloakService $keycloakService) {}

    /**
     * ดักจับทุกๆ Request ที่วิ่งเข้ามาในกลุ่ม Route ที่ระบุไว้
     */
    public function handle(Request $request, Closure $next)
    {
        // ด่านที่ 1: ตรวจสอบ Session ฝั่ง Laravel ว่ายังคงล็อกอินอยู่ไหม
        if (!Auth::guard('web')->check()) {
            return $this->forceLogout($request);
        }

        // ด่านที่ 2: ดึงข้อมูล Token ที่เคยเก็บไว้ขึ้นมาตรวจสอบ
        $tokenData = $this->keycloakService->retrieveToken();

        if (empty($tokenData) || empty($tokenData['access_token'])) {
            return $this->forceLogout($request);
        }

        // นำข้อมูล Token มาแปลงเป็น Object เพื่อใช้งานฟังก์ชันภายในของแพ็กเกจ
        $token = new KeycloakAccessToken($tokenData);

        // ด่านที่ 3: ตรวจสอบว่า Access Token หมดอายุแล้วหรือยัง?
        if ($token->hasExpired()) {

            // ถ้าหมดอายุ ให้ลองเช็กว่ามี Refresh Token ติดมาด้วยไหม
            if (!empty($tokenData['refresh_token'])) {
                try {
                    // ยิงไปหา Keycloak Server เพื่อขอ Token ชุดใหม่มาแทนใบเดิม
                    $newTokenData = $this->keycloakService->refreshAccessToken($tokenData);

                    // บันทึก Token ชุดใหม่ลงใน Session ของระบบ
                    $this->keycloakService->saveToken($newTokenData);
                } catch (Exception $e) {
                    // หาก Refresh ล้มเหลว (เช่น เซิร์ฟเวอร์ปฏิเสธ หรือ Refresh Token หมดอายุไปด้วย)
                    return $this->forceLogout($request);
                }
            } else {
                // ไม่มี Refresh Token สำหรับใช้ต่ออายุ -> สั่งเตะออกทันที
                return $this->forceLogout($request);
            }
        }

        // หากผ่านทุกด่านอย่างถูกต้อง ให้ทำงานต่อไปยังหน้าเว็บที่ผู้ใช้เรียก
        return $next($request);
    }

    /**
     * ฟังก์ชันสำหรับทำลาย Session และขับผู้ใช้ออกจากระบบอย่างปลอดภัย
     */
    protected function forceLogout(Request $request)
    {
        // 1. ล็อกเอาต์ฝั่ง Laravel
        Auth::guard('web')->logout();

        // 2. ลบ Token ของ Keycloak ที่เคยบันทึกไว้ใน Session ออก
        $this->keycloakService->forgetToken();

        // 3. เคลียร์ข้อมูลใน Session ทั้งหมด และสร้าง CSRF Token ใบใหม่เพื่อความปลอดภัย
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 4. ตรวจสอบประเภทการเรียกใช้ หากส่งมาแบบ API (JSON) ให้ส่ง Http Status 401 กลับไป
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated or Session Expired.'], 401);
        }

        // 5. หากใช้งานผ่านหน้าเว็บปกติ ให้ Redirect เด้งกลับไปที่หน้าล็อกอินหลักของ Keycloak
        return redirect()->route('keycloak.login');
    }
}
