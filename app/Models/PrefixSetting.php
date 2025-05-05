<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrefixSetting extends Model
{
    use HasFactory;

    protected $table = 'prefix_setting';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'name', 'format', 'number', 'created_at', 'status',
    ];
}
