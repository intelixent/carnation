<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingListItem extends Model
{
    use HasFactory;

    protected $table = 'packing_list_items';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'packing_list_id',
        'po_item_id',
        'carton_id',
        'carton_name',
        'article_number',
        'size',
        'quantity',
        'created_by',
        'status'
    ];

    public function carton()
    {
        return $this->belongsTo(CartonMaster::class, 'carton_id');
    }

    public function po_item()
    {
        return $this->belongsTo(PoItems::class, 'po_item_id');
    }

    public function packingList()
    {
        return $this->belongsTo(PackingListMaster::class, 'packing_list_id');
    }
}
