<?php

namespace App\Models;

use App\Models\HrPrefix;
use Yajra\Oci8\Eloquent\OracleEloquent as Model;

class HrEmployee extends Model
{
    protected $table = 'hr_employee';
    protected $primaryKey = 'emp_id';
    protected $gruaded = [];
    public $timestamps = false;


    public function hrPrefix()
    {
        return $this->belongsTo(HrPrefix::class, 'prefix_pfix_id', 'pfix_id');
    }


    public function directorBy()
    {
        return $this->belongsTo(HrEmployee::class, 'emp_id')->withDefault([
            'emp_name' => null,
            // 'dss_phone' => '',
        ])
            ->select('emp_name', 'emp_id', 'prefix_pfix_id');
    }


    public function HrDirectorBy()
    {
        return $this->belongsTo(HrEmployee::class, 'emp_id')->withDefault([
            'emp_name' => null,
            // 'dss_phone' => '',
        ])
            ->select('emp_name', 'emp_id', 'prefix_pfix_id');
    }


    public function HrStaffBy()
    {
        return $this->belongsTo(HrEmployee::class, 'emp_id')->withDefault([
            'emp_name' => null,
            // 'dss_phone' => '',
        ])
            ->select('emp_name', 'emp_id', 'prefix_pfix_id');
    }


    /**
     * สร้าง Accessor ชื่อ full_name_with_prefix
     * เรียกใช้งานได้โดย $employee->full_name_with_prefix
     */
    public function getFullNameWithPrefixAttribute()
    {
        // เข้าไปเอา pfix_name จากตาราง HrPrefix ผ่านความสัมพันธ์ hrPrefix()
        // ใช้ optional() เพื่อกัน Error กรณีพนักงานคนนั้นไม่มีข้อมูลคำนำหน้า
        $prefix = $this->hrPrefix->pfix_name ?? '';

        return $prefix . $this->emp_name;
    }
}
