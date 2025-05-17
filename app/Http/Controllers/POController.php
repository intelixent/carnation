<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\PoMaster;
use App\Models\PoItems;
use App\Models\PrefixSetting;
use Illuminate\Support\Facades\Http;
use App\Utils\POutils;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class POController extends BaseController
{
    protected $isSuperAdmin;

    public function __construct()
    {
        parent::__construct();
        $this->isSuperAdmin = request()->attributes->get('isSuperAdmin', false);
        $this->middleware('auth');

        if (!$this->isSuperAdmin) {
        }
    }

    public function get_po_table(Request $request)
    {
        $query = Poutils::getPoQuery($request, $this->isSuperAdmin);
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('po_ref_num', function ($row) {
                return '<a href="javascript:void(0)" class="po-details-link" data-id="' . $row->id . '">' . $row->po_ref_num . '</a>';
            })
            ->rawColumns(['po_ref_num', 'po_num', 'goods_ready_date', 'po_date', 'po_qty'])
            ->make(true);
    }
}
