<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\CartonMaster;
use App\Models\PoMaster;
use App\Models\PoItems;
use App\Models\PrefixSetting;
use App\Models\PackingListMaster;
use App\Models\PackingListItem;
use Illuminate\Support\Facades\Http;

class PackingListController extends BaseController
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

    public function add()
    {
        $page_data = [
            'page_title' => "Packing List Entry",
            'page_main_title' => "Packing List",
            'page_child_title' => "Entry",
            'isSuperAdmin' => $this->isSuperAdmin,

        ];

        return view('packing_list.add', $page_data);
    }

    public function search_po(Request $request)
    {
        $search = $request->input('q');

        $results = PoMaster::query()
            ->where(function ($query) use ($search) {
                $query->where('po_num', 'like', "%{$search}%")
                    ->orWhere('vendor_id', 'like', "%{$search}%");
            })
            ->select('id', 'po_num', 'vendor_id', 'po_date')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'po_num' => $item->po_num,
                    'vendor_name' => $item->vendor_id,
                    'po_date' => $item->po_date
                ];
            });

        return response()->json($results);
    }

    public function get_po_details(Request $request)
    {
        $po = PoMaster::find($request->input('id'));

        if (!$po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        return response()->json([
            'po_num' => $po->po_num,
            'po_date_formatted' => \Carbon\Carbon::parse($po->po_date)->format('d-m-Y'),
            'vendor_name' => $po->vendor_id ?? 'N/A'
        ]);
    }

    public function item_add(Request $request)
    {
        $poId = $request->input('id');
        $cartons = CartonMaster::where('status', 0)->get();
        $articles = PoItems::where('po_id', $poId)
            ->distinct()
            ->pluck('article_number');

        return view('packing_list.item_add', compact('cartons', 'articles', 'poId'));
    }

    public function get_sizes(Request $request)
    {
        $poId = $request->input('po_id');
        $article = $request->input('article_number');

        $sizes = PoItems::where('po_id', $poId)
            ->where('article_number', $article)
            ->distinct()
            ->pluck('size');

        $options = '<option value="">Select Size</option>';
        foreach ($sizes as $size) {
            $options .= "<option value='{$size}'>{$size}</option>";
        }

        return response()->json(['options' => $options]);
    }

    public function item_store(Request $request)
    {
        $validated = $request->validate([
            'po_id' => 'required|exists:po_masters,id',
            'carton_id' => 'required|exists:carton_master,id',
            'article_number' => 'required',
            'size' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $packingList = PackingListMaster::firstOrCreate(
                ['po_id' => $request->po_id],
                [
                    'vendor_id' => PoMaster::find($request->po_id)->vendor_id,
                    'po_no' => PoMaster::find($request->po_id)->po_num,
                    'po_date' => PoMaster::find($request->po_id)->po_date,
                    'created_by' => auth()->id()
                ]
            );

            PackingListItem::create([
                'packing_list_id' => $packingList->id,
                'carton_id' => $request->carton_id,
                'article_number' => $request->article_number,
                'size' => $request->size,
                'quantity' => $request->quantity,
                'created_by' => auth()->id()
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
