<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSettingMaster extends Model
{
    protected $table = 'business_setting_master';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'value',
        'created_by',
        'created_at',
        'status',
    ];
}
