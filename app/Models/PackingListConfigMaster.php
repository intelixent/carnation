<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingListConfigMaster extends Model
{
    use HasFactory;

    protected $table = 'packing_list_config_masters';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'po_id',
        'vendor_id',
        'carton_id',
        'excess',
        'shortage',
        'created_by',
        'created_at',
        'status',
    ];

    public function po()
    {
        return $this->belongsTo(PoMaster::class, 'po_id');
    }

    public function vendor()
    {
        return $this->belongsTo(VendorMaster::class, 'vendor_id');
    }

    public function carton()
    {
        return $this->belongsTo(CartonMaster::class, 'carton_id');
    }

    public function configItems()
    {
        return $this->hasMany(PackingListConfigItem::class, 'config_id');
    }
}
