<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoMaster extends Model
{
    use HasFactory;

    protected $table = 'po_masters';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'po_ref_num',
        'po_num',
        'po_date',
        'goods_ready_date',
        'mrp',
        'vcp',
        'colors',
        'vendor_del_adr',
        'vendor_com_adr',
        'vendor_gst',
        'vendor_cin',
        'article_info',
        'po_unit_price',
        'po_qty',
        'created_by',
'updated_at',
'deleted_at',
        'created_at',
        'status',
    ];

    
}