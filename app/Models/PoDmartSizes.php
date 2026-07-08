<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoDmartSizes extends Model
{
    use HasFactory;

    protected $table = 'po_dmart_sizes';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'po_id',
        'article_description',
        'ean_code',
        'hsn_code',
        'color',
        'size',
        'carton_qty',
        'ratio',
        'total_cartons',
        'case_lot',
        'total_qty',
        'gst_percentage',
        'price',
        'mrp_price',
        'created_by',
        'created_at',
        'status',
    ];

    public function vendor()
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }
}
