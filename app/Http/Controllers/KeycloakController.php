<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HrEmployee;
// use App\Models\Loglogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;

class KeycloakController extends Controller
{

    public function keycloakSession()
    {

        // ทำการล้างค่า session ก่อนดำเนินการต่อไป
        // Session::flush();

        // 🧪 ทดสอบใส่ dd() ดักดูว่าวิ่งมาถึงฟังก์ชันนี้จริงๆ หรือยัง
        // dd('วิ่งมาถึง KeycloakController แล้ว!', Auth::user());

        $response = Http::get('http://backoffice-api.dss.local/api/getEmployeeById/' . Auth::user()->id);
        $emp = json_decode($response, true);

        // 🧪 ดักดูค่า $emp ตรงนี้ว่าได้ข้อมูลจริงหรือไม่
        // dd('เช็คค่า API Emp:', $emp);

        // check array value status 400 หรือ null
        if (is_null($emp) or $emp['status'] == 400) {
            // แจ้งเตือนไม่พบข้อมูลในระบบบุคลากร
            session(['status' => 405, 'msg' => 'ท่านไม่สิทธิ์ใช้งาน หรือ Emp. ID ใน Back Office ไม่ตรงกับ AD']);

            return redirect()->route('permission');
        } else {


            // 2. จัดการรูปภาพ (Map ข้อมูลจาก API เส้นรวม)
            // $myEmpId = $emp['employee']['id'] ?? null;
            $employeeId = Auth::user()->id;
            $imageName = null;

            if ($employeeId) {
                try {
                    // ยิง API เส้นรวมเพื่อหาชื่อไฟล์ภาพที่คู่กับ emp_id
                    $responseAll = Http::timeout(5)->get('http://backoffice-api.dss.local/api/getEmployee');
                    if ($responseAll->successful()) {
                        $imageMap = collect($responseAll->json()['data'] ?? [])->pluck('image', 'emp_id')->toArray();
                        $imageName = $imageMap[$employeeId] ?? null;
                    }
                } catch (\Exception $e) {
                    Log::warning('Image Mapping Warning: ' . $e->getMessage());
                }
            }

            session([
                'employee_id' => $emp['employee']['id'],
                'employee_title_th' => $emp['employee']['title_th'],
                'employee_name_th' => $emp['employee']['name_th'],
                'employee_title_en' => $emp['employee']['title_en'],
                'employee_name_en' => $emp['employee']['name_en'],
                'employee_position' => $emp['employee']['position'],
                'employee_phone' => $emp['employee']['phone'],
                'employee_dss_mail' => $emp['employee']['dss_mail'],
                'employee_type' => $emp['employee']['type'],
                'employee_type_name' => $emp['employee']['type_name'],
                'org_div_division_id' => $emp['org']['div']['division_id'],
                'org_div_division_code' => $emp['org']['div']['division_code'],
                'org_div_division_name' => $emp['org']['div']['division_name'],
                'org_div_division_abbr' => $emp['org']['div']['division_abbr'],
                'org_sub_sub_division_id' => $emp['org']['sub']['sub_division_id'],
                'org_sub_sub_division_code' => $emp['org']['sub']['sub_division_code'],
                'org_sub_sub_division_name' => $emp['org']['sub']['sub_division_name'],
                'org_sub_sub_division_abbr' => $emp['org']['sub']['sub_division_abbr'],
                'org_child_child_division_id' => $emp['org']['child']['child_division_id'],
                'org_child_child_division_code' => $emp['org']['child']['child_division_code'],
                'org_child_child_division_name' => $emp['org']['child']['child_division_name'],
                'org_child_child_division_abbr' => $emp['org']['child']['child_division_abbr'],

                'employee_image_name'       => $imageName ?: 'none', // ถ้าไม่มีชื่อไฟล์ ให้เก็บคำว่า none ไว้แทนเพื่อไม่ให้ parameter ว่าง
            ]);


            if ($employeeId) {
                $dataPlus = HrEmployee::where('emp_id', $employeeId)->get();
                // dd($dataPlus);

                // **แก้ไข: ตรวจสอบว่ามีข้อมูลใน Collection หรือไม่ ก่อนเข้าถึงดัชนี [0]**
                if ($dataPlus->isNotEmpty() && isset($dataPlus[0]->birth_date)) {
                    Session::put('birth_date', $dataPlus[0]->birth_date);
                } else {
                    // กรณีไม่พบข้อมูล หรือข้อมูล BIRTH_DATE เป็น null
                    Session::put('birth_date', null);
                }
            } else {
                // กรณี employee_id ใน Session หายไป (ไม่น่าจะเกิดขึ้นถ้าโค้ด API ทำงานถูกต้อง)
                Session::put('birth_date', null);
            }




            // add success login
            // $loglogin = new Loglogin;
            // $loglogin->log_name = Session::get('employee_title_th') . Session::get('employee_name_th');

            // // ip Address
            // if (getenv('HTTP_X_FORWARDED_FOR'))
            //     $ip = getenv('HTTP_X_FORWARDED_FOR');
            // else
            //     $ip = getenv('REMOTE_ADDR');

            // $loglogin->ip = $ip;
            // $loglogin->save();


            // dd(session()->all());

            return redirect()->route('chatbot.index');
            // // แก้เป็นแบบนี้
            // return redirect()->route('dashboard');

            // หรือถ้าต้องการระบุเป็น Path ตรงๆ
            // return redirect('/home');

            //  เปลี่ยนทิศทางไปยังหน้า Dashboard หลัก
            // return redirect()->route('dashboard');
        }
    }


    /**
     * Proxy สำหรับดึงรูปภาพจาก Server ภายใน (ป้องกันปัญหา Cross-Origin และ HTTP)
     */
    public function getEmployeeImage($filename = null)
    {

        // 1. ถ้าไม่มี filename ส่งมา หรือชื่อไฟล์เป็นค่าว่าง
        if (empty($filename) || $filename === 'null') {
            return redirect(asset('assets/img/avatars/1.png'));
        }

        // 2. เพิ่มการตรวจสอบถ้า $filename มีเครื่องหมาย "-"
        // ให้ชี้ไปที่ public/images/logo.png
        if (str_contains($filename, '-')) {
            return redirect(asset('images/logo-color-png.png'));
        }

        $url = "http://hr-expertise.dss.local/storage/images/profile/" . $filename;

        try {
            $response = Http::timeout(5)->get($url);
            if ($response->successful()) {
                return Response::make($response->body(), 200, [
                    'Content-Type' => $response->header('Content-Type'),
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }

            // ถ้า Response ไม่ successful (เช่น 404) ให้ส่งรูป default
            return redirect(asset('assets/img/avatars/1.png'));
        } catch (\Exception $e) {
            Log::error("Proxy Image Error: " . $e->getMessage());
        }

        // ถ้าหาไม่เจอ ให้ส่งรูป Default (Avatar) กลับไป
        return redirect(asset('assets/img/avatars/1.png'));
    }
}
