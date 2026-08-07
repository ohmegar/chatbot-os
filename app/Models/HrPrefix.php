<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrPrefix extends Model
{
    use HasFactory;

    protected $table = 'hr_prefix';
    protected $primaryKey = 'pfix_id';
    protected $guarded = [];
    public $timestamps = false;
}
