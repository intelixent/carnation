<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SizeChartMaster extends Model
{
    use HasFactory;

    protected $table = 'size_chart_master';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'size',
        'type',
        'created_by',
        'created_at',
        'status'
    ];

    public function vendor()
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id', 'id');
    }
}
