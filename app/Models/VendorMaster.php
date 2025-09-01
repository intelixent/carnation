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
        'notes',
        // Billing Address Fields
        'billing_legal_name',
        'billing_address_1',
        'billing_address_2',
        'billing_city_town_village',
        'billing_pincode',
        'billing_gst_no',
        'billing_pan_no',
        'billing_gst_type',
        'billing_place_supply',
        'billing_state_id',
        // Shipping Address Fields
        'shipping_legal_name',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_city_town_village',
        'shipping_pincode',
        'shipping_gst_no',
        'shipping_pan_no',
        'shipping_gst_type',
        'shipping_place_supply',
        'shipping_state_id',
        'shipping_distance',
        // Other Fields
        'excess',
        'shortage',
        'discount',
        'payment_terms',
        'extraction_no',
        'custom_field_no',
        'created_by',
        'created_at',
        'status',
    ];

    public function billingState()
    {
        return $this->belongsTo(StateMaster::class, 'billing_state_id');
    }

    public function shippingState()
    {
        return $this->belongsTo(StateMaster::class, 'shipping_state_id');
    }

    // Keep the old state relationship for backward compatibility
    public function state()
    {
        return $this->belongsTo(StateMaster::class, 'billing_state_id');
    }
}
