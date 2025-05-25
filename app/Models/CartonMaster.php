<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartonMaster extends Model
{
    use HasFactory;

    protected $table = 'carton_master';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'name',
        'length',
        'breadth',
        'height',
        'weight',
        'created_by',
        'created_at',
        'status'
    ];

    public function vendor()
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id', 'id');
    }
}
