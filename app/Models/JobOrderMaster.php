<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderMaster extends Model
{
    use HasFactory;

    protected $table = 'job_order_master';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'job_no',
        'style',
        'color',
        'type',
        'created_by',
        'created_at',
        'status'
    ];

    public function vendor()
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id', 'id');
    }

    public function sizes()
    {
        return $this->hasMany(JobOrderSizeMaster::class, 'job_id', 'id');
    }
}
