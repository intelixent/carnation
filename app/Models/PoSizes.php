<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoSizes extends Model
{
    use HasFactory;

    protected $table = 'po_sizes';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'po_id',
        'vendor_id',
        'color',
        'size',
        'qty',
        'created_by',
        'created_at',
        'status',
    ];

    public function vendor()
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }
}
