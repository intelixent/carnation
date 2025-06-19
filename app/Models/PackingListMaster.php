<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingListMaster extends Model
{
    use HasFactory;

    protected $table = 'packing_list_masters';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'po_id',
        'pack_ref_no',
        'vendor_id',
        'po_no',
        'po_date',
        'created_at',
        'created_by',
        'status',
        'pack_status',
        'color'
    ];

    public function vendor()
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function po()
    {
        return $this->belongsTo(PoMaster::class, 'po_id');
    }

    public function items()
    {
        return $this->hasMany(PackingListItem::class, 'packing_list_id');
    }
}
