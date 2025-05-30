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
use Barryvdh\DomPDF\Facade\Pdf;

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

    public function index()
    {
        $packingLists = PackingListMaster::where('status', 0)
            ->orderBy('id', 'desc')
            ->get();

        $page_data = [
            'page_title' => "Master",
            'page_main_title' => "Packing List",
            'packingLists' => $packingLists,
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('packing_list.master', $page_data);
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

        $results = PoMaster::with('vendor')
            ->where(function ($query) use ($search) {
                $query->where('po_num', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->select('id', 'po_num', 'vendor_id', 'po_date')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'po_num' => $item->po_num,
                    'vendor_name' => $item->vendor ? $item->vendor->name : 'N/A',
                    'po_date' => $item->po_date
                ];
            });

        return response()->json($results);
    }

    public function get_packing_po_details(Request $request)
    {
        $po = PoMaster::with('vendor')->find($request->input('id'));

        if (!$po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        return response()->json([
            'po_num' => $po->po_num,
            'po_date_formatted' => \Carbon\Carbon::parse($po->po_date)->format('d-m-Y'),
            'vendor_name' => $po->vendor ? $po->vendor->name : 'N/A',
            'vendor_id' => $po->vendor_id
        ]);
    }

    public function get_packing_list_items(Request $request)
    {
        $poId = $request->input('po_id');

        $packingList = PackingListMaster::where('po_id', $poId)->first();

        if (!$packingList) {
            return response()->json([]);
        }

        $items = PackingListItem::where('packing_list_id', $packingList->id)
            ->with('carton:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'carton_name' => $item->carton->name ?? 'N/A',
                    'article_number' => $item->article_number,
                    'size' => $item->size,
                    'quantity' => $item->quantity,
                    'carton_id' => $item->carton_id
                ];
            });

        return response()->json($items);
    }

    public function edit($id)
    {
        $packingList = PackingListMaster::with('items.carton')->find($id);

        if (!$packingList) {
            return redirect()->route('packing_list_list')->with('error', 'Packing list not found');
        }

        $page_data = [
            'page_title' => "Edit Packing List",
            'page_main_title' => "Packing List",
            'page_child_title' => "Edit",
            'isSuperAdmin' => $this->isSuperAdmin,
            'packingList' => $packingList
        ];

        return view('packing_list.edit', $page_data);
    }

    public function get_packing_list_items_by_id(Request $request)
    {
        $packingListId = $request->input('packing_list_id');

        $items = PackingListItem::where('packing_list_id', $packingListId)
            ->with('carton:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'carton_name' => $item->carton->name ?? 'N/A',
                    'article_number' => $item->article_number,
                    'size' => $item->size,
                    'quantity' => $item->quantity,
                    'carton_id' => $item->carton_id
                ];
            });

        return response()->json($items);
    }

    public function packing_list_details(Request $request)
    {
        $id = $request->input('id');

        $packingList = PackingListMaster::with(['items.carton'])
            ->find($id);

        if (!$packingList) {
            return response()->json(['error' => 'Packing list not found'], 404);
        }

        return view('packing_list.details', compact('packingList'));
    }

    public function item_add(Request $request)
    {
        $poId = $request->input('id');
        $vendorId = $request->vendor_id;

        $cartons = CartonMaster::where('vendor_id', $vendorId)->where('status', 0)->get();

        $articles = PoItems::where('po_id', $poId)
            ->distinct()
            ->pluck('article_number');

        return view('packing_list.item_add', compact('cartons', 'articles', 'poId'));
    }

    public function item_edit(Request $request)
    {
        $itemId = $request->input('id');
        $poId = $request->input('po_id');

        $item = PackingListItem::find($itemId);
        if (!$item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        $cartons = CartonMaster::where('status', 0)->get();
        $articles = PoItems::where('po_id', $poId)
            ->distinct()
            ->pluck('article_number');

        // Get sizes for the selected article
        $sizes = PoItems::where('po_id', $poId)
            ->where('article_number', $item->article_number)
            ->distinct()
            ->pluck('size');

        return view('packing_list.item_edit', compact('cartons', 'articles', 'poId', 'item', 'sizes'));
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
            $po = PoMaster::with('vendor')->find($request->po_id);

            $packingList = PackingListMaster::firstOrCreate(
                ['po_id' => $request->po_id],
                [
                    'vendor_id' => $po->vendor_id,
                    'po_no' => $po->po_num,
                    'po_date' => $po->po_date,
                    'created_by' => auth()->id()
                ]
            );

            $poItem = PoItems::where('po_id', $request->po_id)
                ->where('article_number', $request->article_number)
                ->where('size', $request->size)
                ->first();

            PackingListItem::create([
                'packing_list_id' => $packingList->id,
                'po_item_id' => $poItem->id,
                'carton_id' => $request->carton_id,
                'article_number' => $request->article_number,
                'size' => $request->size,
                'quantity' => $request->quantity,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'po_id' => $validated['po_id']
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function item_update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:packing_list_items,id',
            'po_id' => 'required|exists:po_masters,id',
            'carton_id' => 'required|exists:carton_master,id',
            'article_number' => 'required',
            'size' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $item = PackingListItem::find($validated['id']);

            $poItem = PoItems::where('po_id', $request->po_id)
                ->where('article_number', $request->article_number)
                ->where('size', $request->size)
                ->first();

            $item->update([
                'po_item_id' => $poItem->id,
                'carton_id' => $request->carton_id,
                'article_number' => $request->article_number,
                'size' => $request->size,
                'quantity' => $request->quantity,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'po_id' => $validated['po_id']
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function item_delete(Request $request)
    {
        try {
            $item = PackingListItem::find($request->id);

            if (!$item) {
                return response()->json(['error' => 'Item not found'], 404);
            }

            $packingList = PackingListMaster::find($item->packing_list_id);
            $itemCount = PackingListItem::where('packing_list_id', $packingList->id)->count();

            if ($itemCount <= 1) {
                return response()->json(['error' => 'Cannot delete the last item'], 400);
            }

            $item->delete();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function po_print($id)
    {
        $packingList = PackingListMaster::with([
            'items.carton',
            'items.po_item',
            'vendor',
            'po'
        ])->find($id);

        if (!$packingList) {
            abort(404, 'Packing list not found');
        }

        $allSizes = $packingList->items->pluck('size')->unique()->sort()->values();

        $packedQuantities = $packingList->items->groupBy('size')->map(function ($items) {
            return $items->sum('quantity');
        });

        $orderedQuantities = collect();
        if ($packingList->po_id) {
            $poItems = PoItems::where('po_id', $packingList->po_id)->get();
            $orderedQuantities = $poItems->groupBy('size')->map(function ($items) {
                return $items->sum('qty');
            });
        }

        $balances = collect();
        $percentages = collect();

        foreach ($allSizes as $size) {
            $ordered = $orderedQuantities->get($size, 0);
            $packed = $packedQuantities->get($size, 0);
            $balance = $ordered - $packed;
            $percentage = $ordered > 0 ? ($packed / $ordered) * 100 : 0;

            $balances[$size] = $balance;
            $percentages[$size] = $percentage;
        }

        // Initialize table data variable
        $tableData = null;

        // PUMA-specific table generation
        if ($packingList->vendor_id == 3) {
            // Get dynamic size order from PO items instead of static array
            $sizeOrder = [];
            if ($packingList->po_id) {
                $sizeOrder = PoItems::where('po_id', $packingList->po_id)
                    ->orderBy('sno') // Use serial number to maintain order
                    ->pluck('size')
                    ->unique()
                    ->values()
                    ->toArray();
            }

            // If no sizes found from PO, fall back to packing list sizes
            if (empty($sizeOrder)) {
                $sizeOrder = $allSizes->toArray();
            }

            // Group items by size (not by carton content pattern)
            $sizeGroups = $packingList->items->groupBy('size');

            // Build table rows - one row per size
            $tableRows = [];

            foreach ($sizeOrder as $size) {
                if (!$sizeGroups->has($size)) {
                    continue; // Skip if no items for this size
                }

                $sizeItems = $sizeGroups->get($size);

                // Get all carton IDs for this size
                $cartonIds = $sizeItems->pluck('carton_id')->unique()->sort()->values();
                $cartonCount = $cartonIds->count();

                // Handle carton name range
                $ctnRange = '';
                if ($cartonCount > 0) {
                    // Get first and last carton names
                    $firstCarton = CartonMaster::find($cartonIds->first());
                    $lastCarton = CartonMaster::find($cartonIds->last());
                    
                    $firstName = $firstCarton ? $firstCarton->name : '';
                    $lastName = $lastCarton ? $lastCarton->name : '';
                    
                    $ctnRange = ($cartonCount == 1) 
                        ? $firstName 
                        : $firstName . '-' . $lastName;
                }

                // Get total quantity for this size
                $totalQty = $sizeItems->sum('quantity');

                // Get carton details (use first carton's details for weight and dimensions)
                $firstItem = $sizeItems->first();
                $carton = $firstItem->carton;

                // Calculate totals
                $netWeightPerCarton = $carton->net_weight ?? 0;
                $grossWeightPerCarton = $carton->gross_weight ?? 0;
                $totalNetWeight = $netWeightPerCarton * $cartonCount;
                $totalGrossWeight = $grossWeightPerCarton * $cartonCount;

                // Format dimensions as L*B*H CMS
                $dimension = '';
                if (($carton->length ?? 0) > 0 || ($carton->breadth ?? 0) > 0 || ($carton->height ?? 0) > 0) {
                    $dimension = 'L' . ($carton->length ?? 0) . '*B' . ($carton->breadth ?? 0) . '*H' . ($carton->height ?? 0) . 'CMS';
                }

                // Calculate per carton quantity (total qty divided by number of cartons)
                $perCartonQty = $cartonCount > 0 ? round($totalQty / $cartonCount) : 0;

                $row = [
                    'ctn_range' => $ctnRange,
                    'ttl_ctn' => $cartonCount,
                    'color' => $firstItem->po_item->id_color ?? '',
                    'per_size' => [],
                    'per_ctn' => $perCartonQty,
                    'total' => $totalQty,
                    'net_wt_per' => $netWeightPerCarton,
                    'grs_wt_per' => $grossWeightPerCarton,
                    'net_wt_total' => $totalNetWeight,
                    'grs_wt_total' => $totalGrossWeight,
                    'ctn_dim' => $dimension
                ];

                // Fill size quantities - only current size will have quantity, others will be 0
                foreach ($sizeOrder as $sizeCol) {
                    $row['per_size'][$sizeCol] = ($sizeCol == $size) ? $totalQty : 0;
                }

                $tableRows[] = $row;
            }

            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows' => $tableRows
            ];
        }

        // Determine template based on vendor
        $viewTemplate = $packingList->vendor_id == 3
            ? 'packing_list.puma_print'
            : 'packing_list.jack_print';

        $pdf = PDF::loadView($viewTemplate, [
            'packing_list' => $packingList,
            'all_sizes' => $allSizes,
            'packed_quantities' => $packedQuantities,
            'ordered_quantities' => $orderedQuantities,
            'balances' => $balances,
            'percentages' => $percentages,
            'tableData' => $tableData  // Only used by PUMA template
        ])
            ->set_option('isHtml5ParserEnabled', true)
            ->set_option('isRemoteEnabled', true)
            ->setPaper('a4', 'landscape');

        return $pdf->stream('Packing_list_print.pdf');
    }
}
