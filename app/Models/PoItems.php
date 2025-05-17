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
        'item_sno',
        'item_article_number',
        'item_id_color',
        'size_in_years',
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
        'status'
    ];
}
