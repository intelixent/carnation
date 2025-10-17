<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderSizeMaster extends Model
{
    use HasFactory;

    protected $table = 'job_order_size_master';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'job_id',
        'size_id',
        'qty',
        'created_by',
        'created_at',
        'status'
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrderMaster::class, 'job_id', 'id');
    }

    public function size()
    {
        return $this->belongsTo(SizeChartMaster::class, 'size_id', 'id');
    }
}
