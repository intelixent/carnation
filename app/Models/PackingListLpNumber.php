<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingListLpNumber extends Model
{
    use HasFactory;

    protected $table = 'packing_list_lp_numbers';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'packing_list_id',
        'po_id',
        'article_number',
        'color',
        'carton_range',
        'lp_no',
        'created_by',
        'created_at',
    ];

    public function packingList()
    {
        return $this->belongsTo(PackingListMaster::class, 'packing_list_id');
    }

    public function po()
    {
        return $this->belongsTo(PoMaster::class, 'po_id');
    }
}
