<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportMaster extends Model
{
    use HasFactory;

    protected $table = 'transport_master';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'created_by',
        'created_at',
        'status',
    ];
}
