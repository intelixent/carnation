<?php 
namespace App\Utils;

use App\Models\PoMaster;
use App\Models\PoItems;
use Illuminate\Http\Request;
class POutils
{
    
    public static function getPoQuery(Request $request , $isSuperAdmin)
    {
        $user = auth()->user();

        $query = PoMaster::whereIn('status', [0, 1])
        ->orderBy('id', 'desc');
       
        return $query;
    }
}