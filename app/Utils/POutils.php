<?php

namespace App\Utils;

use App\Models\PoMaster;
use App\Models\PoItems;
use Illuminate\Http\Request;

class POutils
{
    public static function getPoQuery(Request $request, $isSuperAdmin)
    {
        $user = auth()->user();
        $query = PoMaster::query();

        if ($request->has('type')) {
            if ($request->type === 'all') {
                $query->whereIn('status', [0, 1]);
            } elseif ($request->type === 'amended') {
                $query->where('status', 1);
            }
        } else {
            $query->whereIn('status', [0, 1]);
        }

        if ($request->has('vendor_id') && !empty($request->vendor_id)) {
            $query->where('vendor_id', $request->vendor_id);
        }

        $query->orderBy('id', 'desc');
        return $query;
    }
}