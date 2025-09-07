<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceMaster extends Model
{
    use HasFactory;

    protected $table = 'invoice_masters';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'ref_no',
        'inv_date',
        'gst',
        'bill_to_details',
        'ship_to_details',
        'po_id',
        'pack_ids',
        'irn_details',
        'transporter_details',
        'grn_details',
        'vendor_id',
        'created_at',
        'created_by',
        'status',
    ];

    public function vendor()
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function po()
    {
        return $this->belongsTo(PoMaster::class, 'po_id');
    }
}
