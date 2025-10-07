<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoItems extends Model
{
    use HasFactory;

    protected $table = 'po_items';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'po_id',
        'sno',
        'article_number',
        'id_color',
        'size',
        'qty',
        'uom',
        'igst_taxable_value',
        'igst_per',
        'mrp',
        'ean_code',
        'hsn_code',
        'created_at',
        'created_by',
        'updated_at',
        'deleted_at',
        'status',
        'gender',
        'type',
        'content',
        'color',
        'color_code',
        'size_grp',
        'fi_dates',
        'unit_price',
        'total_amount',
        'style_description',
        'product_character',
        'pack_factor',
        'sku_line_no',
        'incoterm',
        'named_place',
        'part_description',
        'material_value',
        'total_value',
        'due_date',
        'location',
        'country',
    ];
}
