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
        'legal_name',
        'mobile',
        'email',
        'address_1',
        'address_2',
        'city_town_village',
        'pincode',
        'gst_no',
        'pan_no',
        'gst_type',
        'place_supply',
        'state_id',
        'excess',
        'shortage',
        'discount',
        'payment_terms',
        'extraction_no',
        'custom_field_no',
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
