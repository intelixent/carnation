<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingListConfigItem extends Model
{
    use HasFactory;

    protected $table = 'packing_list_config_items';

    protected $primaryKey = 'id';
    
    public $timestamps = false;

    protected $fillable = [
        'config_id',
        'po_item_id',
        'color',
        'size',
        'po_qty',
        'pack_qty',
        'created_by',
        'created_at',
        'status',
    ];

    public function config()
    {
        return $this->belongsTo(PackingListConfigMaster::class, 'config_id');
    }

    public function poItem()
    {
        return $this->belongsTo(PoItems::class, 'po_item_id');
    }
}
