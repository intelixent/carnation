<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorMaster extends Model
{
    use HasFactory;

    protected $table = 'vendor_master';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'mobile',
        'email',
        'address',
        'gst_no',
        'state_id',
        'notes',
        'created_by',
        'created_at',
        'status',
    ];

    public function state()
    {
        return $this->belongsTo(StateMaster::class, 'state_id');
    }
}