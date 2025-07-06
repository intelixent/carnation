<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\CartonMaster;
use App\Models\PoMaster;
use App\Models\PoItems;
use App\Models\PoSizes;
use App\Models\PrefixSetting;
use App\Models\PackingListMaster;
use App\Models\PackingListItem;
use App\Models\PackingListConfigMaster;
use App\Models\PackingListConfigItem;
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

    public function config()
    {
        $page_data = [
            'page_title' => "Config",
            'page_main_title' => "Packing List",
            'vendors' => VendorMaster::where('status', 0)->get(),
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('packing_list.config', $page_data);
    }

    public function get_config_vendor_po(Request $request)
    {
        $pos = PoMaster::with('vendor')
            ->where('vendor_id', $request->vendor_id)
            ->where('status', 1)
            ->get(['id', 'po_num', 'po_ref_num', 'po_job_num', 'vendor_id']);

        if ($pos->isEmpty()) {
            return response()->json(['error' => 'No PO for this vendor'], 404);
        }

        $transformedPos = $pos->map(function ($po) {
            return [
                'id' => $po->id,
                'po_num' => $po->po_num,
                'po_ref_num' => $po->po_ref_num,
                'po_job_num' => $po->po_job_num,
                'vendor_name' => $po->vendor->name ?? 'N/A'
            ];
        });

        return response()->json($transformedPos);
    }

    public function get_config_po_details(Request $request)
    {
        $po = PoMaster::with('vendor')->find($request->input('id'));
        if (!$po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        // Check if packing list items exist for this PO
        $hasPackingListItems = PackingListItem::whereHas('packingList', function ($q) use ($po) {
            $q->where('po_id', $po->id);
        })->exists();

        $existingConfig = PackingListConfigMaster::where('po_id', $po->id)->first();
        $selectedCartonId = $existingConfig ? $existingConfig->carton_id : null;

        // Determine styleRef
        $articleInfo = json_decode($po->article_info, true) ?: [];
        switch ($po->vendor_id) {
            case 1:
                $styleRef = $articleInfo['Article description'] ?? '';
                break;
            case 3:
                $styleRef = $articleInfo['style_description'] ?? '';
                break;
            case 4:
                // For Benetton, styleRef not used or comes from another field
                $styleRef = '';
                break;
            default:
                $styleRef = '';
        }

        // Fetch PO items or config items for size matrix
        $colorSizeMatrix = [];
        $allSizes = [];

        if ($po->vendor_id == 4) {
            // Use for Benetton
            $configItems = PoSizes::where('po_id', $request->input('id'))
                ->where('vendor_id', 4)
                ->get(['color', 'size', 'qty']);

            foreach ($configItems as $item) {
                $color = $item->color;
                $size = $item->size;
                $qty = $item->qty;

                $allSizes[] = $size;
                $colorSizeMatrix[$color][$size] = ($colorSizeMatrix[$color][$size] ?? 0) + $qty;
            }
        } else {
            // Default: use PoItems table
            $poItems = PoItems::where('po_id', $po->id)->get();
            foreach ($poItems as $item) {
                $color = $item->color ?? $item->id_color;
                $size = $item->size;
                $qty = $item->qty;

                $allSizes[] = $size;
                $colorSizeMatrix[$color][$size] = ($colorSizeMatrix[$color][$size] ?? 0) + $qty;
            }
        }

        $allSizes = array_values(array_unique($allSizes));

        // Get excess percentage and calculate packQtyMatrix
        $excessPercentage = $po->vendor->excess ?? 0;
        $packQtyMatrix = [];
        $totalPackQty = 0;
        foreach ($colorSizeMatrix as $color => $sizes) {
            foreach ($sizes as $size => $qty) {
                $packQty = ceil($qty * (1 + $excessPercentage / 100));
                $packQtyMatrix[$color][$size] = $packQty;
                $totalPackQty += $packQty;
            }
        }

        // Totals by size
        $poQtyBySizeTotal = [];
        $packQtyBySizeTotal = [];
        foreach ($allSizes as $size) {
            $poQty = array_sum(array_column($colorSizeMatrix, $size));
            $packQty = array_sum(array_column($packQtyMatrix, $size));
            $poQtyBySizeTotal[$size] = $poQty;
            $packQtyBySizeTotal[$size] = $packQty;
        }

        $cartons = CartonMaster::where('vendor_id', $po->vendor_id)
            ->where('status', 0)
            ->get();

        return view('packing_list.config_details', compact(
            'po',
            'styleRef',
            'colorSizeMatrix',
            'packQtyMatrix',
            'allSizes',
            'totalPackQty',
            'cartons',
            'poQtyBySizeTotal',
            'packQtyBySizeTotal',
            'selectedCartonId',
            'hasPackingListItems'
        ));
    }

    public function save_config_po_details(Request $request)
    {
        $po_id     = $request->input('po_id');
        $carton_id = $request->input('carton_id');

        // Validate PO exists
        $po = PoMaster::with('vendor')->find($po_id);
        if (!$po) {
            return response()->json(['error' => 'PO not found'], 404);
        }
        $excess    = $po->vendor->excess ?? 0;
        $shortage  = $po->vendor->shortage ?? 0;
        $vendor_id = $po->vendor_id;

        try {
            // Find or create master record
            $configMaster = PackingListConfigMaster::firstOrNew([
                'po_id'     => $po_id,
                'vendor_id' => $vendor_id,
            ]);

            // Update master fields
            $configMaster->fill([
                'carton_id' => $carton_id,
                'excess'    => $excess,
                'shortage'  => $shortage,
                'status'    => $configMaster->exists ? $configMaster->status : 0,
            ]);
            if (!$configMaster->exists) {
                $configMaster->created_by = auth()->user()->id;
                $configMaster->created_at = now();
            }
            $configMaster->save();

            // Prepare list of identifiers to keep
            $keepIds = [];

            if ($vendor_id == 4) {
                // For Benetton: use PoSizes
                $items = PoSizes::where('po_id', $po_id)
                    ->where('vendor_id', 4)
                    ->get(['color', 'size', 'qty']);

                foreach ($items as $item) {
                    $poQty   = $item->qty;
                    $packQty = ceil($poQty * (1 + $excess / 100));

                    $configItem = PackingListConfigItem::updateOrCreate([
                        'config_id' => $configMaster->id,
                        'color'     => $item->color,
                        'size'      => $item->size,
                    ], [
                        'po_id'      => $po_id,
                        'vendor_id'  => $vendor_id,
                        'po_qty'     => $poQty,
                        'pack_qty'   => $packQty,
                        'status'     => 0,
                        'created_by' => $configMaster->created_by,
                        'created_at' => $configMaster->created_at,
                        'updated_by' => auth()->user()->id,
                        'updated_at' => now(),
                    ]);

                    $keepIds[] = $configItem->id;
                }
            } else {
                // Default: use PoItems
                $items = PoItems::where('po_id', $po_id)->get();
                foreach ($items as $item) {
                    $color   = $item->color ?? $item->id_color ?? 'N/A';
                    $poQty   = $item->qty ?? 0;
                    $packQty = ceil($poQty * (1 + $excess / 100));

                    $configItem = PackingListConfigItem::updateOrCreate([
                        'config_id'  => $configMaster->id,
                        'po_item_id' => $item->id,
                    ], [
                        'po_id'      => $po_id,
                        'vendor_id'  => $vendor_id,
                        'color'      => $color,
                        'size'       => $item->size ?? 'N/A',
                        'po_qty'     => $poQty,
                        'pack_qty'   => $packQty,
                        'status'     => 0,
                        'created_by' => $configMaster->created_by,
                        'created_at' => $configMaster->created_at,
                        'updated_by' => auth()->user()->id,
                        'updated_at' => now(),
                    ]);

                    $keepIds[] = $configItem->id;
                }
            }

            // Remove any items not in the current list
            PackingListConfigItem::where('config_id', $configMaster->id)
                ->whereNotIn('id', $keepIds)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Packing list configuration saved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to save configuration: ' . $e->getMessage()
            ], 500);
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
            ->whereHas('packingListConfigs')
            ->where(function ($query) use ($search) {
                $query->where('po_num', 'like', "%{$search}%")
                    ->orWhere('po_job_num', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->select('id', 'po_num', 'po_job_num', 'vendor_id', 'po_date')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'po_num' => $item->po_num,
                    'po_job_num' => $item->po_job_num,
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
            'po_job_num' => $po->po_job_num,
            'po_date_formatted' => $po->po_date,
            'vendor_name' => $po->vendor ? $po->vendor->name : 'N/A',
            'excess' => $po->vendor->excess,
            'vendor_id' => $po->vendor_id
        ]);
    }

    public function get_po_colors(Request $request)
    {
        $poId = $request->input('po_id');

        $colors = PackingListConfigItem::whereHas('config', function ($query) use ($poId) {
            $query->where('po_id', $poId);
        })
            ->distinct()
            ->pluck('color')
            ->filter()
            ->values();

        return response()->json($colors);
    }

    public function get_sizes_with_qty(Request $request)
    {
        $poId     = $request->input('po_id');
        $article  = $request->input('article_number');
        $color    = $request->input('color');

        // Fetch the PO (to get vendor_id)
        $po = PoMaster::with('vendor')->find($poId);
        if (! $po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        $vendorId = $po->vendor_id;

        $sizes = [];

        if ($vendorId == 4) {
            $configItems = PackingListConfigItem::where('po_id', $poId)
                ->where('vendor_id', 4)
                ->where('color', $color)
                ->get();

            foreach ($configItems as $item) {
                $size   = $item->size;
                $maxQty = $item->pack_qty;

                // sum up how much has already been packed for this PO/color/size
                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($poId) {
                    $q->where('po_id', $poId);
                })
                    ->where('color', $color)
                    ->where('size', $size)
                    ->sum('quantity');

                $remainingQty = $maxQty - $packedQty;
                if ($remainingQty <= 0) {
                    continue;
                }

                $sizes[] = [
                    'size'           => $size,
                    'max_qty'        => $maxQty,
                    'packed_qty'     => $packedQty,
                    'remaining_qty'  => $remainingQty,
                    'config_item_id' => $item->id,
                ];
            }
        } else {
            // Other vendors: existing config‐item logic
            $configItems = PackingListConfigItem::whereHas('config', function ($q) use ($poId) {
                $q->where('po_id', $poId);
            })
                ->whereHas('poItem', function ($q) use ($article) {
                    $q->where('article_number', $article);
                })
                ->with(['poItem', 'config'])
                ->get();

            foreach ($configItems as $item) {
                $maxQty = $item->pack_qty;

                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($poId) {
                    $q->where('po_id', $poId);
                })
                    ->where('article_number', $article)
                    ->where('size', $item->size)
                    ->sum('quantity');

                $remainingQty = $maxQty - $packedQty;
                if ($remainingQty <= 0) {
                    continue;
                }

                $sizes[] = [
                    'size'           => $item->size,
                    'max_qty'        => $maxQty,
                    'packed_qty'     => $packedQty,
                    'remaining_qty'  => $remainingQty,
                    'config_item_id' => $item->id,
                ];
            }
        }

        return response()->json($sizes);
    }

    public function get_packing_list_items(Request $request)
    {
        $poId = $request->input('po_id');
        $color = $request->input('color');

        // Get all packing lists for this PO and color
        $packingLists = PackingListMaster::where('po_id', $poId)
            ->where('color', $color)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($packingLists->isEmpty()) {
            return response()->json([
                'packing_lists' => [],
                'can_add_items' => true
            ]);
        }

        $allPackingListsData = [];
        foreach ($packingLists as $packingList) {
            $items = PackingListItem::where('packing_list_id', $packingList->id)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'carton_name' => $item->carton_name ?? 'N/A',
                        'article_number' => $item->article_number,
                        'color' => $item->color,
                        'size' => $item->size,
                        'quantity' => $item->quantity,
                        'carton_id' => $item->carton_id,
                    ];
                });

            $allPackingListsData[] = [
                'packing_list_id' => $packingList->id,
                'pack_ref_no' => $packingList->pack_ref_no,
                'pack_status' => $packingList->pack_status,
                'items' => $items
            ];
        }

        // Check if we can add more items for this specific color
        $canAddItems = $this->checkIfCanAddItems($poId, $color);

        return response()->json([
            'packing_lists' => $allPackingListsData,
            'can_add_items' => $canAddItems
        ]);
    }

    private function checkIfCanAddItems($poId, $color)
    {
        // Get the PO to check vendor
        $po = PoMaster::find($poId);
        if (!$po) {
            return false;
        }

        $vendorId = $po->vendor_id;

        if ($vendorId == 4) {
            // For vendor 4, check config items directly for the specific color
            $configItems = PackingListConfigItem::where('po_id', $poId)
                ->where('vendor_id', 4)
                ->where('color', $color)
                ->get();

            foreach ($configItems as $item) {
                $maxQty = $item->pack_qty;
                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($poId) {
                    $q->where('po_id', $poId);
                })
                    ->where('color', $color)
                    ->where('size', $item->size)
                    ->sum('quantity');

                if ($packedQty < $maxQty) {
                    return true; // Still have items to pack for this color
                }
            }
        } else {
            // For other vendors, check through config items for the specific color
            $configItems = PackingListConfigItem::whereHas('config', function ($q) use ($poId) {
                $q->where('po_id', $poId);
            })
                ->where('color', $color) // Filter by the specific color
                ->with(['poItem'])
                ->get();

            foreach ($configItems as $item) {
                $maxQty = $item->pack_qty;
                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($poId) {
                    $q->where('po_id', $poId);
                })
                    ->where('article_number', $item->poItem->article_number)
                    ->where('color', $color)
                    ->where('size', $item->size)
                    ->sum('quantity');

                if ($packedQty < $maxQty) {
                    return true; // Still have items to pack for this color
                }
            }
        }

        return false; // All items are fully packed for this color
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
            ->with('carton:id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'carton_name' => $item->carton_name ?? 'N/A',
                    'article_number' => $item->article_number,
                    'color' => $item->color,
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
        $poId     = $request->input('id');
        $vendorId = $request->input('vendor_id');
        $color     = $request->input('color');

        $po = PoMaster::find($poId);
        if (! $po) {
            return response()->json(['error' => 'PO not found'], 404);
        }

        $job_num = $po->po_job_num;

        $packingConfig = PackingListConfigMaster::where('po_id', $poId)->first();
        if (! $packingConfig) {
            return response()->json(['error' => 'Packing list configuration not found for this PO'], 404);
        }

        $carton = CartonMaster::where('id', $packingConfig->carton_id)
            ->where('vendor_id', $vendorId)
            ->where('status', 0)
            ->first();

        $carton_id = $carton->id;

        if ($vendorId == 4) {
            // Benetton: po_item_id wasn't stored, so just pull distinct article_numbers
            $articles = PoItems::where('po_id', $poId)
                ->where('color', $color)
                ->pluck('article_number')
                ->unique()
                ->values();
        } else {
            // All other vendors: use packed config items + grouping logic
            $configItems = PackingListConfigItem::whereHas('config', function ($q) use ($poId) {
                $q->where('po_id', $poId);
            })
                ->where('color', $color)
                ->with('poItem')
                ->get();

            $filteredArticles = collect();

            // Group by article_number, size, and color
            $grouped = $configItems->groupBy(function ($item) {
                return $item->poItem->article_number . '|' . $item->size . '|' . $item->color;
            });

            foreach ($grouped as $group) {
                $poItem = $group->first()->poItem;
                if (! $poItem) {
                    continue;
                }

                $articleNumber  = $poItem->article_number;
                $size           = $group->first()->size;
                $poItemIds      = $group->pluck('po_item_id');
                $totalPackQty   = $group->sum('pack_qty');

                $actualPackedQty = PackingListItem::whereIn('po_item_id', $poItemIds)
                    ->where('size', $size)
                    ->sum('quantity');

                // only include if still to be packed
                if ($actualPackedQty < $totalPackQty) {
                    $filteredArticles->push($articleNumber);
                }
            }

            $articles = $filteredArticles->unique()->values();
        }

        return view('packing_list.item_add', compact(
            'carton_id',
            'poId',
            'color',
            'articles',
            'job_num'
        ));
    }

    public function item_store(Request $request)
    {
        $carton_data = $request->input('cartondata');
        $po_details = $request->input('po_details');

        $po_id = $po_details['po_id'];
        $color = $po_details['color'];
        $net_weight = $po_details['net_weight'];
        $carton_id = $po_details['carton_id'];

        // Fetch PO early so we know vendor_id
        $po = PoMaster::with('vendor')->find($po_id);

        $selected_color = $color;
        if (! $po) {
            return response()->json(['error' => 'PO not found'], 404);
        }
        $vendorId = $po->vendor_id;

        // Determine batch vs single
        // $isBatch = $request->has('sizes') && is_array($request->input('sizes'))
        //     && $request->has('quantities') && is_array($request->input('quantities'))
        //     && count($request->input('sizes')) === count($request->input('quantities'));

        //     print_r($isBatch);

        // Common validation rules
        $rules = [
            'po_details.po_id'          => 'required|exists:po_masters,id',
            'po_details.carton_id'      => 'required|exists:carton_master,id',
            'cartondata.*.article_number' => 'required|string',
            'po_details.color'          => 'required|string',
        ];
        // if ($isBatch) {
        //     $rules['cartondata.*.sizes'] = 'required|array|min:1';
        //     $rules['cartondata.sizes.size,*'] = 'required|string';
        //     $rules['cartondata.sizes.quantity.*'] = 'required|integer|min:1';
        //     //$rules['quantity.*'] = 'required|integer|min:1';
        // } else {
        //     $rules['cartondata.*.sizes']     = 'required|array|min:1';
        //     $rules['cartondata.sizes.size,*'] = 'required|string';
        //     $rules['cartondata.sizes.quantity.*'] = 'required|integer|min:1';
        // }

        $rules['cartondata.*.sizes']     = 'required|array|min:1';
        $rules['cartondata.sizes.size,*'] = 'required|string';
        $rules['cartondata.sizes.quantity.*'] = 'required|integer|min:1';


        // if ($vendorId != 4) {
        //     if ($isBatch) {
        //         $rules['config_item_ids'] = 'required|array|size:' . count($request->input('sizes', []));
        //         $rules['config_item_ids.*'] = 'required|exists:packing_list_config_items,id';
        //     } else {
        //         $rules['config_item_id'] = 'required|exists:packing_list_config_items,id';
        //     }
        // }
        $validated = $request->validate($rules);

        try {
            // Prepare arrays
            // if ($isBatch) {
            //     $sizes = $request->input('sizes');
            //     $quantities = $request->input('quantities');
            //     if ($vendorId != 4) {
            //         $configItemIds = $request->input('config_item_ids');
            //     } else {
            //         $configItemIds = array_fill(0, count($sizes), null);
            //     }
            // } else {
            //     $sizes = [$request->input('size')];
            //     $quantities = [$request->input('quantity')];
            //     if ($vendorId != 4) {
            //         $configItemIds = [$request->input('config_item_id')];
            //     } else {
            //         $configItemIds = [null];
            //     }
            // }

            $existingCount = PackingListMaster::where(array('po_id' => $po_id))
                ->count();
            $suffix = $existingCount + 1;
            $generatedPackRefNo = "{$po->po_job_num}/{$suffix}";
            // Create or fetch PackingListMaster
            $packingList = PackingListMaster::firstOrCreate(
                ['po_id' => $po_id, 'pack_status' => 0],
                [
                    'pack_ref_no' => $generatedPackRefNo,
                    'vendor_id'  => $po->vendor_id,
                    'po_no'      => $po->po_num,
                    'po_date'    => $po->po_date,
                    'color' => $selected_color,
                    'created_by' => auth()->user()->id,
                    'created_at' => now(),
                ]
            );

            // For batch: compute cartonName and timestamp once
            // if ($isBatch) {
            //     $firstCartonNumber = $this->getNextCartonNumber($vendorId, $packingList->id);
            //     $sharedCartonName = $this->formatCartonName($vendorId, $firstCartonNumber);
            //     $sharedTimestamp = now();
            // }

            // For single: prepare next carton number
            // if (! $isBatch) {
            //     $currentCartonNumber = $this->getNextCartonNumber($vendorId, $packingList->id);
            // }

            // Determine cartonName and timestamp
            // if ($isBatch) {
            //     $cartonName = $sharedCartonName;
            //     $createdAt = $sharedTimestamp;
            // } else {
            //     $cartonName = $this->formatCartonName($vendorId, $currentCartonNumber);
            //     $createdAt = now();
            // }

            $currentCartonNumber = $this->getNextCartonNumber($vendorId, $packingList->id);
            $cartonName = $this->formatCartonName($vendorId, $currentCartonNumber);
            $createdAt = now();

            foreach ($carton_data as $carton): //Aricle Loop Starts

                $sizes = $carton['sizes'];

                // Loop each entry
                foreach ($sizes as $idx => $size) {
                    //$qty = $quantities[$idx];
                    $qty = $size['quantity'];
                    //$configItemId = $configItemIds[$idx];
                    $configItemId = $size['config_item_id'];

                    // Check remaining qty for this size/color
                    if ($vendorId == 4) {

                        $poSize = PackingListConfigItem::where('po_id', $po_id)
                            ->where('vendor_id', 4)
                            ->where('color', $color)
                            ->where('size', $size['size'])
                            ->first();
                        if (! $poSize) {
                            return response()->json(['error' => "Size {$size['size']} not found in PoSizes"], 400);
                        }
                        $maxQty = $poSize->pack_qty;

                        $alreadyPacked = PackingListItem::whereHas('packingList', function ($q) use ($request) {
                            $q->where('po_id', $request->po_id);
                        })
                            ->where('color', $color)
                            ->where('size', $size['size'])
                            ->sum('quantity');
                    } else {
                        $configItem = PackingListConfigItem::find($configItemId);
                        if (! $configItem) {
                            return response()->json(['error' => "Configuration item not found for size {$size['size']}"], 400);
                        }
                        $maxQty = $configItem->pack_qty;


                        $alreadyPacked = PackingListItem::whereHas('packingList', function ($q) use ($request) {
                            $q->where('po_id', $request->po_id);
                        })
                            ->where('article_number', $carton['article_number'])
                            ->where('size', $size['size'])
                            ->sum('quantity');
                    }
                    $remaining = $maxQty - $alreadyPacked;

                    if ($qty > $remaining) {
                        return response()->json([
                            'error' => "Quantity for size {$size['size']} exceeds available limit. Available: {$remaining}"
                        ], 400);
                    }


                    // Find PoItem if exists
                    $poItem = PoItems::where('po_id', $po_id)
                        ->where('article_number', $carton['article_number'])
                        ->where('color', $color)
                        ->where('size', $size['size'])
                        ->first();



                    // Create the PackingListItem
                    PackingListItem::create([
                        'packing_list_id' => $packingList->id,
                        'vendor_id'       => $po->vendor_id,
                        'po_item_id'      => $vendorId == 4 ? null : ($poItem->id ?? null),
                        'carton_id'       => $carton_id,
                        'carton_name'     => $cartonName,
                        'article_number'  => $carton['article_number'],
                        'color'           => $color,
                        'size'            => $size['size'],
                        'quantity'        => $qty,
                        'net_weight'        =>  $net_weight,
                        'created_by'      => auth()->user()->id,
                        'created_at'      => $createdAt,
                    ]);
                }

            endforeach; // Article Multiple

            return response()->json([
                'success' => true,
                'po_id'   => $po_id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Helper methods unchanged:
    private function getNextCartonNumber($vendorId, $packingListId)
    {
        if ($vendorId == 1) {
            $prefix = 'C';
            $lastCarton = PackingListItem::where('packing_list_id', $packingListId)
                ->where('carton_name', 'like', $prefix . '%')
                ->orderByRaw('CAST(SUBSTRING(carton_name, 2) AS UNSIGNED) DESC')
                ->first();

            return $lastCarton
                ? ((int) substr($lastCarton->carton_name, 1)) + 1
                : 1;
        } else {
            $lastCarton = PackingListItem::where('packing_list_id', $packingListId)
                ->whereRaw('carton_name REGEXP "^[0-9]+$"')
                ->orderByRaw('CAST(carton_name AS UNSIGNED) DESC')
                ->first();

            return $lastCarton
                ? ((int) $lastCarton->carton_name) + 1
                : 1;
        }
    }

    private function formatCartonName($vendorId, $number)
    {
        if ($vendorId == 1) {
            return 'C' . $number;
        } else {
            return (string) $number;
        }
    }

    public function item_edit(Request $request)
    {
        $itemId = $request->input('id');
        $poId   = $request->input('po_id');

        $po = PoMaster::find($poId);
        if (! $po) {
            return response()->json(['error' => 'PO not found'], 404);
        }
        $vendorId = $po->vendor_id;
        $job_num  = $po->po_job_num;

        $item = PackingListItem::find($itemId);
        if (! $item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        // Determine color
        if ($vendorId == 4) {
            $color = $item->color;
        } else {
            $configItem = PackingListConfigItem::find($item->config_item_id);
            $color      = $configItem->color ?? $item->color;
        }

        // Get cartons
        $configuredCartonIds = PackingListConfigMaster::where('po_id', $poId)
            ->pluck('carton_id')->unique();
        $carton = CartonMaster::whereIn('id', $configuredCartonIds)
            ->where('vendor_id', $vendorId)
            ->where('status', 0)
            ->first();
        $carton_id = $carton->id;

        // Build articles list
        if ($vendorId == 4) {
            // from PoItems
            $articles = PoItems::where('po_id', $poId)
                ->where('color', $color)
                ->pluck('article_number')
                ->unique()->values();
        } else {
            // existing config logic
            $configItems = PackingListConfigItem::whereHas('config', fn($q) => $q->where('po_id', $poId))
                ->where('color', $color)->with('poItem')->get();

            $filtered = $configItems
                ->groupBy(fn($i) => "{$i->poItem->article_number}|{$i->size}|{$i->color}")
                ->flatMap(function ($group) use ($item) {
                    $poItem     = $group->first()->poItem;
                    $ids        = $group->pluck('po_item_id');
                    $totalPack  = $group->sum('pack_qty');
                    $packedQty  = PackingListItem::whereIn('po_item_id', $ids)
                        ->where('size', $group->first()->size)
                        ->sum('quantity');
                    return $packedQty < $totalPack ? [$poItem->article_number] : [];
                });
            $articles = collect($filtered)->unique()->values();
        }

        // Calculate max available quantity for current item
        if ($vendorId == 4) {
            $poSize = PackingListConfigItem::where('po_id', $poId)
                ->where('vendor_id', 4)
                ->where('color', $color)
                ->where('size', $item->size)
                ->first();

            $maxQty = $poSize ? $poSize->pack_qty : 0;
            $packedQty = PackingListItem::whereHas('packingList', fn($q) => $q->where('po_id', $poId))
                ->where('color', $color)
                ->where('size', $item->size)
                ->sum('quantity');

            $maxAvailableQty = $maxQty - $packedQty;
            $currentSizeData = [
                'max_qty' => $maxQty,
                'packed_qty' => $packedQty,
                'remaining_qty' => $maxAvailableQty
            ];
        } else {
            $configItem = PackingListConfigItem::where('po_id', $poId)
                ->where('vendor_id', $item->vendor_id)
                ->where('color', $color)
                ->where('size', $item->size)
                ->first();
            $maxQty = $configItem ? $configItem->pack_qty : 0;
            $packedQty = PackingListItem::whereHas('packingList', fn($q) => $q->where('po_id', $poId))
                ->where('article_number', $item->article_number)
                ->where('size', $item->size)
                ->sum('quantity');

            $maxAvailableQty = $maxQty - $packedQty;
            $currentSizeData = [
                'max_qty' => $maxQty,
                'packed_qty' => $packedQty,
                'remaining_qty' => $maxAvailableQty
            ];
        }

        return view('packing_list.item_edit', compact(
            'carton_id',
            'articles',
            'poId',
            'item',
            'color',
            'job_num',
            'maxAvailableQty',
            'currentSizeData'
        ));
    }

    public function item_update(Request $request)
    {
        // Now only quantity is updatable.
        // Fetch PO to know vendor
        $po = PoMaster::find($request->input('po_id'));
        if (! $po) {
            return response()->json(['error' => 'PO not found'], 404);
        }
        $vendorId = $po->vendor_id;

        // Validation: only id, po_id, quantity
        $rules = [
            'id'       => 'required|exists:packing_list_items,id',
            'po_id'    => 'required|exists:po_masters,id',
            'quantity' => 'required|integer|min:1',
        ];
        $validated = $request->validate($rules);

        try {
            $item = PackingListItem::find($validated['id']);
            if (! $item) {
                return response()->json(['error' => 'Item not found'], 404);
            }
            // Determine the maximum allowed for this item:
            $size = $item->size;
            $color = $item->color;
            $articleNumber = $item->article_number;

            if ($vendorId == 4) {
                $ps = PackingListConfigItem::where('po_id', $request->po_id)
                    ->where('vendor_id', 4)
                    ->where('color', $color)
                    ->where('size', $size)
                    ->first();
                if (! $ps) {
                    return response()->json(['error' => 'Size not found in PoSizes'], 400);
                }
                $maxQty = $ps->pack_qty;
                // Sum of all other items of same size/color in this PO:
                $packedOthers = PackingListItem::whereHas('packingList', function ($q) use ($request) {
                    $q->where('po_id', $request->po_id);
                })
                    ->where('color', $color)
                    ->where('size', $size)
                    ->where('id', '!=', $item->id)
                    ->sum('quantity');
            } else {
                // We need the original config_item_id stored on this item.
                $configItemId = $item->config_item_id;
                $ci = PackingListConfigItem::find($configItemId);
                if (! $ci) {
                    return response()->json(['error' => 'Configuration item not found'], 400);
                }
                $maxQty = $ci->pack_qty;
                $packedOthers = PackingListItem::whereHas('packingList', function ($q) use ($request) {
                    $q->where('po_id', $request->po_id);
                })
                    ->where('article_number', $articleNumber)
                    ->where('size', $size)
                    ->where('id', '!=', $item->id)
                    ->sum('quantity');
            }
            $remaining = $maxQty - $packedOthers;
            if ($request->quantity > $remaining) {
                return response()->json([
                    'error' => "Quantity exceeds available limit. Available: {$remaining}"
                ], 400);
            }

            // Only update quantity; leave carton_name, carton_id, article_number, color, size unchanged.
            $item->update([
                'quantity'   => $request->quantity,
            ]);

            return response()->json([
                'success' => true,
                'po_id'   => $request->po_id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function generateCartonName($vendorId, $packingListId, $color, $size, $articleNumber)
    {
        if ($vendorId == 1) {
            // First, check if a carton already exists with the same size and article number
            $existingCarton = PackingListItem::where('packing_list_id', $packingListId)
                ->where('color', $color)
                ->where('size', $size)
                ->where('article_number', $articleNumber)
                ->first();

            if ($existingCarton) {
                // Reuse the existing carton name
                return $existingCarton->carton_name;
            }

            // If not found, generate a new carton name with prefix 'C'
            $prefix = 'C';

            $lastCarton = PackingListItem::where('packing_list_id', $packingListId)
                ->where('carton_name', 'like', 'C%')
                ->orderByRaw('CAST(SUBSTRING(carton_name, 2) AS UNSIGNED) DESC')
                ->first();

            $nextNumber = $lastCarton ? ((int) substr($lastCarton->carton_name, 1)) + 1 : 1;

            return $prefix . $nextNumber;
        } else {
            // Other vendors – generate purely numeric carton name
            $lastCarton = PackingListItem::where('packing_list_id', $packingListId)
                ->whereRaw('carton_name REGEXP "^[0-9]+$"')
                ->orderByRaw('CAST(carton_name AS UNSIGNED) DESC')
                ->first();

            $nextNumber = $lastCarton ? ((int) $lastCarton->carton_name) + 1 : 1;

            return (string) $nextNumber;
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
        // Load the packing list with related items, carton, po_item, vendor, po
        $packingList = PackingListMaster::with([
            'items.carton',
            'items.po_item',
            'vendor',
            'po'
        ])->find($id);

        if (!$packingList) {
            abort(404, 'Packing list not found');
        }

        //
        // 1. COMMON HEADER DATA
        //

        // PO Number
        $poNum = $packingList->po->po_num ?? '';

        $poDate = $packingList->po->po_date ?? '';

        $poJobNum = $packingList->po->po_job_num ?? '';

        // Unique PO item IDs in this packing list
        $uniquePoItemIds = $packingList->items->pluck('po_item_id')->unique()->values()->toArray();

        // Unique article numbers
        $uniqueArticleNumbers = $packingList->items->pluck('article_number')->unique()->values()->toArray();
        $articleNumbersDisplay = implode(', ', $uniqueArticleNumbers);

        // Unique colors
        $uniqueColor = $packingList->items->pluck('color')->unique()->values()->toArray();
        $uniqueColorDisplay = implode(', ', $uniqueColor);

        // FIRST Carton details (for header dims/weight display if needed)
        $uniqueCarton = $packingList->items->pluck('carton_id')->unique()->values()->toArray();
        $firstCartonId = $uniqueCarton[0] ?? null;
        $firstCarton = $firstCartonId ? CartonMaster::find($firstCartonId) : null;

        $ctnLength  = $firstCarton->length ?? '';
        $ctnBreadth = $firstCarton->breadth ?? '';
        $ctnHeight  = $firstCarton->height ?? '';

        $ctnDimDisplay = '';
        if ($ctnLength !== '' || $ctnBreadth !== '' || $ctnHeight !== '') {
            // Use "X" or "*" as desired
            $ctnDimDisplay = "{$ctnLength}X{$ctnBreadth}X{$ctnHeight}";
        }

        // Weight field from first carton
        $ctnWeight = $firstCarton->weight ?? '';

        // Gender display from PoItems
        $genderDisplay = '';
        if (!empty($uniquePoItemIds)) {
            $genderArr = PoItems::whereIn('id', $uniquePoItemIds)
                ->pluck('gender')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
            $genderDisplay = implode(', ', $genderArr);
        }

        // Style description (type) display from PoItems
        $styleDescriptionsDisplay = '';
        if (!empty($uniquePoItemIds)) {
            if ($packingList->vendor_id == 4) {
                // For vendor ID 4 (Benetton), use part_description
                $styleArr = PoItems::whereIn('color', $uniqueColor)
                    ->where('po_id', $packingList->po->id)
                    ->pluck('part_description')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            } else {
                // For other vendors, use type
                $styleArr = PoItems::whereIn('id', $uniquePoItemIds)
                    ->pluck('type')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }
            $styleDescriptionsDisplay = implode(', ', $styleArr);
        }

        // All sizes present in packing list (for both detail table and summary)
        $allSizes = $packingList->items->pluck('size')->unique()->sort()->values();

        // Packed quantities per size
        $packedQuantities = $packingList->items
            ->groupBy('size')
            ->map(fn($itemsForSize) => $itemsForSize->sum('quantity'));

        //
        // Initialize variables for summary (used mainly for vendor_id == 2)
        //
        $orderedQuantities = collect(); // filtered order qty only for items in list
        $balances = collect();
        $percentages = collect();
        // Note: In Blade, you compute orderTotal and packTotal as needed

        //
        // tableData for main detail table
        //
        $tableData = null;

        // Initialize vendor ID 4 specific totals
        $totalCtn = 0;
        $totalNetWeight = 0;
        $totalGrossWeight = 0;

        //
        // 2. VENDOR-SPECIFIC LOGIC
        //
        if ($packingList->vendor_id == 2) {
            //
            // VENDOR ID 2 (Skechers-specific) - Using PUMA logic
            //

            // 1. Compute ordered quantities for items in this packing list
            if (!empty($uniquePoItemIds)) {
                $poItemsFiltered = PoItems::whereIn('id', $uniquePoItemIds)->get();
                $orderedQuantities = $poItemsFiltered
                    ->groupBy('size')
                    ->map(fn($itemsForSize) => $itemsForSize->sum('qty'));
            } else {
                $orderedQuantities = collect();
            }

            // 2. Compute balances & percentages per size
            foreach ($allSizes as $size) {
                $ordered = $orderedQuantities->get($size, 0);
                $packed  = $packedQuantities->get($size, 0);
                $balance = $ordered - $packed;
                $percentage = $ordered > 0 ? ($packed / $ordered) * 100 : 0;

                $balances[$size]    = $balance;
                $percentages[$size] = $percentage;
            }

            // Get dynamic sizeOrder from PO items or fallback
            $sizeOrder = [];
            if ($packingList->po_id) {
                $sizeOrder = PoItems::where('po_id', $packingList->po_id)
                    ->pluck('size')
                    ->unique()
                    ->values()
                    ->toArray();
            }
            if (empty($sizeOrder)) {
                $sizeOrder = $allSizes->toArray();
            }

            // Step 1: Group by size and find continuous ranges within each size
            $sizeRanges = [];

            foreach ($sizeOrder as $size) {
                $sizeItems = $packingList->items->where('size', $size);

                if ($sizeItems->isEmpty()) {
                    continue;
                }

                // Sort items by carton_name
                $sortedItems = $sizeItems->sortBy(function ($item) {
                    return intval($item->carton_name);
                });

                // Group continuous carton names for this size
                $currentGroup = [];
                $lastCartonName = null;

                foreach ($sortedItems as $item) {
                    $currentCartonName = intval($item->carton_name);

                    if ($lastCartonName === null || $currentCartonName == $lastCartonName + 1) {
                        $currentGroup[] = $item;
                    } else {
                        // Gap detected, save current group and start new one
                        if (!empty($currentGroup)) {
                            $sizeRanges[] = [
                                'size' => $size,
                                'items' => collect($currentGroup)
                            ];
                        }
                        $currentGroup = [$item];
                    }

                    $lastCartonName = $currentCartonName;
                }

                // Don't forget the last group
                if (!empty($currentGroup)) {
                    $sizeRanges[] = [
                        'size' => $size,
                        'items' => collect($currentGroup)
                    ];
                }
            }

            // Step 2: Merge ranges that have overlapping carton names
            $mergedRanges = [];

            foreach ($sizeRanges as $range) {
                $cartonNames = $range['items']->pluck('carton_name')->unique()->sort()->values()->toArray();
                $rangeKey = implode('-', $cartonNames);

                if (!isset($mergedRanges[$rangeKey])) {
                    $mergedRanges[$rangeKey] = [
                        'carton_names' => $cartonNames,
                        'items' => collect(),
                        'size_quantities' => []
                    ];
                }

                $mergedRanges[$rangeKey]['items'] = $mergedRanges[$rangeKey]['items']->merge($range['items']);
                $mergedRanges[$rangeKey]['size_quantities'][$range['size']] = $range['items']->sum('quantity');
            }

            // Step 3: Create table rows
            $tableRows = [];
            $totals = [
                'carton_count' => 0,
                'per_size'     => array_fill_keys($sizeOrder, 0),
                'total_pieces' => 0
            ];

            foreach ($mergedRanges as $rangeKey => $rangeData) {
                $cartonNames = $rangeData['carton_names'];
                $cartonCount = count($cartonNames);
                $allItems = $rangeData['items'];

                $firstName = $cartonNames[0];
                $lastName = end($cartonNames);

                // Create carton range
                $ctnRange = $cartonCount > 1 ? $firstName . '-' . $lastName : $firstName;

                $totalQty = $allItems->sum('quantity');

                $firstItem = $allItems->first();
                $carton = $firstItem->carton;

                $netWeightPerCarton = $carton->net_weight ?? 0;
                $grossWeightPerCarton = $carton->gross_weight ?? 0;

                $dimension = '';
                if (
                    ($carton->length ?? 0) > 0
                    || ($carton->breadth ?? 0) > 0
                    || ($carton->height ?? 0) > 0
                ) {
                    $dimension = ($carton->length ?? 0)
                        . '*' . ($carton->breadth ?? 0)
                        . '*' . ($carton->height ?? 0)
                        . ' CMS';
                }

                $perCartonQty = $cartonCount > 0 ? round($totalQty / $cartonCount) : 0;
                $mrp = $firstItem->po_item->mrp ?? '';

                $row = [
                    'article_number'  => $firstItem->article_number,
                    'color'           => $firstItem->color,
                    'ctn_range'       => $ctnRange,
                    'ctn_first'       => $firstName,
                    'ctn_last'        => $lastName,
                    'first_carton_id' => $cartonNames[0],
                    'ttl_ctn'         => $cartonCount,
                    'per_size'        => [],
                    'per_ctn'         => $perCartonQty,
                    'total'           => $totalQty,
                    'net_wt_per'      => $netWeightPerCarton,
                    'grs_wt_per'      => $grossWeightPerCarton,
                    'ctn_dim'         => $dimension,
                    'mrp'             => $mrp,
                    'po_item_id'      => $firstItem->po_item_id,
                ];

                // Initialize all size columns to 0
                foreach ($sizeOrder as $sizeCol) {
                    $row['per_size'][$sizeCol] = 0;
                }

                // Set quantities for each size
                foreach ($rangeData['size_quantities'] as $size => $qty) {
                    if (in_array($size, $sizeOrder)) {
                        $row['per_size'][$size] = $qty;
                    }
                }

                // Update totals
                $totals['carton_count'] += $cartonCount;
                foreach ($rangeData['size_quantities'] as $size => $qty) {
                    if (isset($totals['per_size'][$size])) {
                        $totals['per_size'][$size] += $qty;
                    }
                }
                $totals['total_pieces'] += $totalQty;

                $tableRows[] = $row;
            }

            // Sort table rows by first carton number
            usort($tableRows, function ($a, $b) {
                $aFirst = intval(explode('-', $a['ctn_range'])[0]);
                $bFirst = intval(explode('-', $b['ctn_range'])[0]);
                return $aFirst - $bFirst;
            });

            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows'      => $tableRows,
                'totals'    => $totals
            ];
        } elseif ($packingList->vendor_id == 3) {
            //
            // VENDOR ID 3 (PUMA-specific)
            //

            // For vendor 3, orderedQuantities/summary may not apply; skip or set empty
            $orderedQuantities = collect();
            $balances = collect();
            $percentages = collect();

            // 1. Compute ordered quantities for items in this packing list
            if (!empty($uniquePoItemIds)) {
                $poItemsFiltered = PoItems::whereIn('id', $uniquePoItemIds)->get();
                $orderedQuantities = $poItemsFiltered
                    ->groupBy('size')
                    ->map(fn($itemsForSize) => $itemsForSize->sum('qty'));
            } else {
                $orderedQuantities = collect();
            }

            // 2. Compute balances & percentages per size
            foreach ($allSizes as $size) {
                $ordered = $orderedQuantities->get($size, 0);
                $packed  = $packedQuantities->get($size, 0);
                $balance = $ordered - $packed;
                $percentage = $ordered > 0 ? ($packed / $ordered) * 100 : 0;

                $balances[$size]    = $balance;
                $percentages[$size] = $percentage;
            }

            // Get dynamic sizeOrder from PO items or fallback
            $sizeOrder = [];
            if ($packingList->po_id) {
                $sizeOrder = PoItems::where('po_id', $packingList->po_id)
                    ->pluck('size')
                    ->unique()
                    ->values()
                    ->toArray();
            }
            if (empty($sizeOrder)) {
                $sizeOrder = $allSizes->toArray();
            }

            // NEW APPROACH: Group by carton ranges first, then handle size distribution

            // Step 1: Sort all items by carton_name
            $sortedItems = $packingList->items->sortBy(function ($item) {
                return intval($item->carton_name);
            });

            // Step 2: Group into continuous carton ranges
            $cartonRanges = [];
            $currentRange = [];
            $lastCartonName = null;

            foreach ($sortedItems as $item) {
                $currentCartonName = intval($item->carton_name);

                if ($lastCartonName === null || $currentCartonName == $lastCartonName + 1 || $currentCartonName == $lastCartonName) {
                    // Same carton or continuous carton
                    $currentRange[] = $item;
                } else {
                    // Gap detected, save current range and start new one
                    if (!empty($currentRange)) {
                        $cartonRanges[] = collect($currentRange);
                    }
                    $currentRange = [$item];
                }

                $lastCartonName = $currentCartonName;
            }

            // Don't forget the last range
            if (!empty($currentRange)) {
                $cartonRanges[] = collect($currentRange);
            }

            // Step 3: Process each carton range to determine if it should be split or kept together
            $finalRanges = [];

            foreach ($cartonRanges as $range) {
                $cartonNames = $range->pluck('carton_name')->unique()->sort()->values()->toArray();

                // Check if all cartons in this range have the same size distribution
                $cartonSizeDistribution = [];
                foreach ($cartonNames as $cartonName) {
                    $cartonItems = $range->where('carton_name', $cartonName);
                    $sizes = $cartonItems->pluck('size')->unique()->sort()->values()->toArray();
                    $cartonSizeDistribution[$cartonName] = $sizes;
                }

                // Group cartons with identical size distributions
                $sizeGroups = [];
                foreach ($cartonSizeDistribution as $cartonName => $sizes) {
                    $sizeKey = implode('|', $sizes);
                    if (!isset($sizeGroups[$sizeKey])) {
                        $sizeGroups[$sizeKey] = [];
                    }
                    $sizeGroups[$sizeKey][] = $cartonName;
                }

                // Create separate ranges for each size group
                foreach ($sizeGroups as $sizeKey => $cartonNamesInGroup) {
                    // Check for continuity within each size group
                    sort($cartonNamesInGroup);

                    $continuousRanges = [];
                    $currentContinuous = [$cartonNamesInGroup[0]];

                    for ($i = 1; $i < count($cartonNamesInGroup); $i++) {
                        if (intval($cartonNamesInGroup[$i]) == intval($cartonNamesInGroup[$i - 1]) + 1) {
                            $currentContinuous[] = $cartonNamesInGroup[$i];
                        } else {
                            $continuousRanges[] = $currentContinuous;
                            $currentContinuous = [$cartonNamesInGroup[$i]];
                        }
                    }
                    $continuousRanges[] = $currentContinuous;

                    // Create final ranges
                    foreach ($continuousRanges as $continuousRange) {
                        $rangeItems = $range->whereIn('carton_name', $continuousRange);
                        $finalRanges[] = $rangeItems;
                    }
                }
            }

            // Step 4: Create table rows from final ranges
            $tableRows = [];

            foreach ($finalRanges as $rangeItems) {
                $cartonNames = $rangeItems->pluck('carton_name')->unique()->sort()->values()->toArray();
                $cartonCount = count($cartonNames);

                $firstName = $cartonNames[0];
                $lastName = end($cartonNames);

                // Create carton range display
                $ctnRange = $cartonCount > 1 ? $firstName . '-' . $lastName : $firstName;

                $totalQty = $rangeItems->sum('quantity');

                $firstItem = $rangeItems->first();
                $carton = $firstItem->carton;

                //dd($firstItem->net_weight);

                // $netWeightPerCarton = $carton->net_weight ?? 0;
                // $grossWeightPerCarton = $carton->gross_weight ?? 0;
                $netWeightPerCarton = $firstItem->net_weight ?? 0;
                $grossWeightPerCarton = ($firstItem->net_weight + 1.45) ?? 0;

                $totalNetWeight = $netWeightPerCarton * $cartonCount;
                $totalGrossWeight = $grossWeightPerCarton * $cartonCount;

                $dimension = '';
                if (
                    ($carton->length ?? 0) > 0
                    || ($carton->breadth ?? 0) > 0
                    || ($carton->height ?? 0) > 0
                ) {
                    $dimension = 'L' . ($carton->length ?? 0)
                        . '*B' . ($carton->breadth ?? 0)
                        . '*H' . ($carton->height ?? 0)
                        . 'CMS';
                }

                $perCartonQty = $cartonCount > 0 ? round($totalQty / $cartonCount) : 0;

                // Calculate size quantities for this range
                $sizeQuantities = $rangeItems->groupBy('size')->map(function ($items) {
                    return $items->sum('quantity');
                });

                $row = [
                    'ctn_range'    => $ctnRange,
                    'ttl_ctn'      => $cartonCount,
                    'color'        => $firstItem->po_item->id_color ?? '',
                    'per_size'     => [],
                    'per_ctn'      => $perCartonQty,
                    'total'        => $totalQty,
                    'net_wt_per'   => $netWeightPerCarton,
                    'grs_wt_per'   => $grossWeightPerCarton,
                    'net_wt_total' => $totalNetWeight,
                    'grs_wt_total' => $totalGrossWeight,
                    'ctn_dim'      => $dimension,
                ];

                // Initialize all size columns to 0
                foreach ($sizeOrder as $sizeCol) {
                    $row['per_size'][$sizeCol] = 0;
                }

                // Set quantities for each size in this range
                foreach ($sizeQuantities as $size => $qty) {
                    if (in_array($size, $sizeOrder)) {
                        $row['per_size'][$size] = $qty;
                    }
                }

                $tableRows[] = $row;
            }

            // Sort table rows by first carton number
            usort($tableRows, function ($a, $b) {
                $aFirst = intval(explode('-', $a['ctn_range'])[0]);
                $bFirst = intval(explode('-', $b['ctn_range'])[0]);
                return $aFirst - $bFirst;
            });

            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows'      => $tableRows,
            ];
        } elseif ($packingList->vendor_id == 4) {
            //
            // VENDOR ID 4 (Benetton-specific) - Group by size with continuous cartons, then merge same carton names across sizes
            //
            $orderedQuantities = collect();
            $balances = collect();
            $percentages = collect();

            // Get dynamic sizeOrder from PoSizes or fallback
            $sizeOrder = [];
            if ($packingList->po_id) {
                $sizeOrder = PoSizes::where('po_id', $packingList->po_id)
                    ->pluck('size')
                    ->unique()
                    ->values()
                    ->toArray();
            }
            if (empty($sizeOrder)) {
                $sizeOrder = $allSizes->toArray();
            }

            $uniqueColors = $packingList->items->pluck('color')->unique()->values()->toArray();

            // Get ordered quantities from PoSize
            if ($packingList->po_id) {
                $poSizesFiltered = PoSizes::whereIn('color', $uniqueColors)->where('po_id', $packingList->po_id)->get();
                $orderedQuantities = $poSizesFiltered
                    ->groupBy('size')
                    ->map(fn($itemsForSize) => $itemsForSize->sum('qty'));
            }

            // Compute balances & percentages for summary
            foreach ($allSizes as $size) {
                $ordered = $orderedQuantities->get($size, 0);
                $packed  = $packedQuantities->get($size, 0);
                $balance = $ordered - $packed;
                $percentage = $ordered > 0 ? ($packed / $ordered) * 100 : 0;

                $balances[$size]    = $balance;
                $percentages[$size] = $percentage;
            }

            // Step 1: Group by size and find continuous ranges within each size
            $sizeRanges = [];

            foreach ($sizeOrder as $size) {
                $sizeItems = $packingList->items->where('size', $size);

                if ($sizeItems->isEmpty()) {
                    continue;
                }

                // Sort items by carton_name
                $sortedItems = $sizeItems->sortBy(function ($item) {
                    return intval($item->carton_name);
                });

                // Group continuous carton names for this size
                $currentGroup = [];
                $lastCartonName = null;

                foreach ($sortedItems as $item) {
                    $currentCartonName = intval($item->carton_name);

                    if ($lastCartonName === null || $currentCartonName == $lastCartonName + 1) {
                        $currentGroup[] = $item;
                    } else {
                        // Gap detected, save current group and start new one
                        if (!empty($currentGroup)) {
                            $sizeRanges[] = [
                                'size' => $size,
                                'items' => collect($currentGroup)
                            ];
                        }
                        $currentGroup = [$item];
                    }

                    $lastCartonName = $currentCartonName;
                }

                // Don't forget the last group
                if (!empty($currentGroup)) {
                    $sizeRanges[] = [
                        'size' => $size,
                        'items' => collect($currentGroup)
                    ];
                }
            }

            // Step 2: Group ranges by carton names (merge different sizes with same carton names)
            $cartonGroups = [];

            foreach ($sizeRanges as $range) {
                $cartonNames = $range['items']->pluck('carton_name')->unique()->sort()->values()->toArray();
                $cartonNamesKey = implode(',', $cartonNames); // Use comma to create unique key

                if (!isset($cartonGroups[$cartonNamesKey])) {
                    $cartonGroups[$cartonNamesKey] = [
                        'carton_names' => $cartonNames,
                        'items' => collect(),
                        'size_quantities' => [],
                        'sizes' => []
                    ];
                }

                $cartonGroups[$cartonNamesKey]['items'] = $cartonGroups[$cartonNamesKey]['items']->merge($range['items']);
                $cartonGroups[$cartonNamesKey]['size_quantities'][$range['size']] = $range['items']->sum('quantity');
                $cartonGroups[$cartonNamesKey]['sizes'][] = $range['size'];
            }

            // Step 3: Create table rows
            $tableRows = [];
            $totals = [
                'carton_count' => 0,
                'per_size'     => array_fill_keys($sizeOrder, 0),
                'total_pieces' => 0,
                'net_weight'   => 0,
                'empty_box_weight' => 0,
                'gross_weight' => 0
            ];

            foreach ($cartonGroups as $cartonGroup) {
                $cartonNames = $cartonGroup['carton_names'];
                $cartonCount = count($cartonNames);
                $allItems = $cartonGroup['items'];

                $firstName = $cartonNames[0];
                $lastName = end($cartonNames);

                // Create carton range
                $ctnRange = $cartonCount > 1 ? $firstName . '-' . $lastName : $firstName;

                $totalQty = $allItems->sum('quantity');

                $firstItem = $allItems->first();
                $carton = $firstItem->carton;

                $netWeightPerCarton = $carton->net_weight ?? 0;
                $emptyBoxWeightPerCarton = $carton->empty_weight ?? 1.5;
                $grossWeightPerCarton = $carton->gross_weight ?? ($netWeightPerCarton + $emptyBoxWeightPerCarton);

                $totalNetWeightForRange = $netWeightPerCarton * $cartonCount;
                $totalEmptyBoxWeight = $emptyBoxWeightPerCarton * $cartonCount;
                $totalGrossWeightForRange = $grossWeightPerCarton * $cartonCount;

                $perCartonQty = $cartonCount > 0 ? round($totalQty / $cartonCount) : 0;

                $row = [
                    'ctn_range'        => $ctnRange,
                    'ctn_first'        => $firstName,
                    'ctn_last'         => $lastName,
                    'ttl_ctn'          => $cartonCount,
                    'color_code'       => $firstItem->color,
                    'per_size'         => array_fill_keys($sizeOrder, 0), // Initialize all sizes to 0
                    'per_ctn'          => $perCartonQty,
                    'grand_total'      => $totalQty,
                    'net_weight'       => $totalNetWeightForRange,
                    'empty_box_weight' => $totalEmptyBoxWeight,
                    'gross_weight'     => $totalGrossWeightForRange,
                    'sizes_involved'   => array_unique($cartonGroup['sizes']) // Track which sizes are in this row
                ];

                // Set quantities for each size that exists in this carton group
                foreach ($cartonGroup['size_quantities'] as $size => $qty) {
                    if (in_array($size, $sizeOrder)) {
                        $row['per_size'][$size] = $qty;
                    }
                }

                // Update totals
                $totals['carton_count'] += $cartonCount;
                foreach ($cartonGroup['size_quantities'] as $size => $qty) {
                    if (isset($totals['per_size'][$size])) {
                        $totals['per_size'][$size] += $qty;
                    }
                }
                $totals['total_pieces'] += $totalQty;
                $totals['net_weight'] += $totalNetWeightForRange;
                $totals['empty_box_weight'] += $totalEmptyBoxWeight;
                $totals['gross_weight'] += $totalGrossWeightForRange;

                $tableRows[] = $row;
            }

            // Sort table rows by first carton number
            usort($tableRows, function ($a, $b) {
                $aFirst = intval(explode('-', $a['ctn_range'])[0]);
                $bFirst = intval(explode('-', $b['ctn_range'])[0]);
                return $aFirst - $bFirst;
            });

            // Set the vendor ID 4 specific totals
            $totalCtn = $totals['carton_count'];
            $totalNetWeight = $totals['net_weight'];
            $totalGrossWeight = $totals['gross_weight'];

            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows'      => $tableRows,
                'totals'    => $totals
            ];
        } else {
            //
            // OTHER VENDORS: generic summary similar to vendor_id 2
            //

            // 1. Compute ordered quantities for items in this packing list
            if (!empty($uniquePoItemIds)) {
                $poItemsFiltered = PoItems::whereIn('id', $uniquePoItemIds)->get();
                $orderedQuantities = $poItemsFiltered
                    ->groupBy('size')
                    ->map(fn($itemsForSize) => $itemsForSize->sum('qty'));
            } else {
                $orderedQuantities = collect();
            }

            // 2. Compute balances & percentages per size
            foreach ($allSizes as $size) {
                $ordered = $orderedQuantities->get($size, 0);
                $packed  = $packedQuantities->get($size, 0);
                $balance = $ordered - $packed;
                $percentage = $ordered > 0 ? ($packed / $ordered) * 100 : 0;

                $balances[$size]    = $balance;
                $percentages[$size] = $percentage;
            }

            // 3. Determine sizeOrder for any detail table or consistent ordering in summary
            $sizeOrder = [];
            if ($packingList->po_id) {
                $sizeOrder = PoItems::where('po_id', $packingList->po_id)
                    ->pluck('size')
                    ->unique()
                    ->values()
                    ->toArray();
            }
            if (empty($sizeOrder)) {
                $sizeOrder = $allSizes->toArray();
            }

            // 4. (Optional) Build a generic detail tableData if you want similar breakdown as Skechers:
            //    If you do not need a full detail table for other vendors, you can skip building tableData.
            $groupedItems = $packingList->items->groupBy(function ($item) {
                return $item->article_number . '|' . $item->color . '|' . $item->size;
            });

            $tableRows = [];
            $totals = [
                'carton_count' => 0,
                'per_size'     => array_fill_keys($sizeOrder, 0),
                'total_pieces' => 0,
            ];

            foreach ($groupedItems as $groupKey => $groupItems) {
                list($articleNumber, $color, $size) = explode('|', $groupKey);

                // Carton info
                $cartonNames = $groupItems->pluck('carton_name')->unique()->sort()->values();
                $cartonIds   = $groupItems->pluck('carton_id')->unique()->values();
                $cartonCount = $cartonNames->count();

                $firstName = $cartonCount > 0 ? $cartonNames->first() : '';
                $lastName  = $cartonCount > 0 ? $cartonNames->last()  : '';
                $firstCartonId = $cartonCount > 0 ? $cartonIds->first() : null;

                $totalQty = $groupItems->sum('quantity');

                $firstItem = $groupItems->first();
                $carton    = $firstItem->carton;
                // dd($firstItem);
                // Weight/dimension if needed
                $netWeightPerCarton = $firstItem->net_weight ?? 0;
                $grossWeightPerCarton = ($firstItem->net_weight + 1.45) ?? 0;
                // $netWeightPerCarton   = $carton->net_weight ?? 0;
                // $grossWeightPerCarton = $carton->gross_weight ?? 0;
                $dimension = '';
                if (
                    ($carton->length ?? 0) > 0
                    || ($carton->breadth ?? 0) > 0
                    || ($carton->height ?? 0) > 0
                ) {
                    $dimension = ($carton->length ?? 0)
                        . '*' . ($carton->breadth ?? 0)
                        . '*' . ($carton->height ?? 0)
                        . ' CMS';
                }

                $perCartonQty = $cartonCount > 0 ? round($totalQty / $cartonCount) : 0;
                $mrp = $firstItem->po_item->mrp ?? '';
                $poItemId = $firstItem->po_item_id;

                $row = [
                    'article_number'  => $articleNumber,
                    'color'           => $color,
                    'size'            => $size,
                    'ctn_first'       => $firstName,
                    'ctn_last'        => $lastName,
                    'first_carton_id' => $firstCartonId,
                    'ttl_ctn'         => $cartonCount,
                    'per_size'        => array_fill_keys($sizeOrder, 0),
                    'per_ctn'         => $perCartonQty,
                    'total'           => $totalQty,
                    'net_wt_per'      => $netWeightPerCarton,
                    'grs_wt_per'      => $grossWeightPerCarton,
                    'ctn_dim'         => $dimension,
                    'mrp'             => $mrp,
                    'po_item_id'      => $poItemId,
                ];
                $row['per_size'][$size] = $totalQty;

                // Update totals
                $totals['carton_count'] += $cartonCount;
                $totals['per_size'][$size] += $totalQty;
                $totals['total_pieces'] += $totalQty;

                $tableRows[] = $row;
            }

            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows'      => $tableRows,
                'totals'    => $totals,
            ];

            // Pass totals or leave variables for view consistency
            $totalCtn = $totals['carton_count'];
            // If you have weight totals, compute similarly; otherwise leave as 0
            $totalNetWeight = $totals['total_pieces'] * 0; // or sum from rows if meaningful
            $totalGrossWeight = $totals['total_pieces'] * 0;
        }

        //
        // 3. PASS ALL DATA TO VIEW
        //
        $viewData = [
            'packing_list'             => $packingList,
            'all_sizes'                => $allSizes,
            'packed_quantities'        => $packedQuantities,
            'ordered_quantities'       => $orderedQuantities,
            'balances'                 => $balances,
            'percentages'              => $percentages,
            'tableData'                => $tableData,
            'poNum'                    => $poNum,
            'poDate'                   => $poDate,
            'poJobNum'                 => $poJobNum,
            'genderDisplay'            => $genderDisplay,
            'styleDescriptionsDisplay' => $styleDescriptionsDisplay,
            'articleNumbersDisplay'    => $articleNumbersDisplay,
            'uniqueColorDisplay'       => $uniqueColorDisplay,
            'ctnDimDisplay'            => $ctnDimDisplay,
            'ctnWeight'                => $ctnWeight,
            'totalCtn'                 => $totalCtn,
            'totalNetWeight'           => $totalNetWeight,
            'totalGrossWeight'         => $totalGrossWeight,
        ];

        //echo "<pre>".print_r($viewData,true)."</pre>";
        // dd($viewData);

        // Choose the correct view template
        if ($packingList->vendor_id == 4) {
            $viewTemplate = 'packing_list.benetton_print';
        } elseif ($packingList->vendor_id == 3) {
            $viewTemplate = 'packing_list.puma_print';
        } elseif ($packingList->vendor_id == 2) {
            $viewTemplate = 'packing_list.skechers_print';
        } else {
            $viewTemplate = 'packing_list.jack_print';
        }

        // Generate PDF
        $pdf = PDF::loadView($viewTemplate, $viewData)
            ->set_option('isHtml5ParserEnabled', true)
            ->set_option('isRemoteEnabled', true)
            ->setPaper('a4', 'landscape');

        return $pdf->stream('Packing_list_print.pdf');
    }

    public function packing_list_complete(Request $request)
    {
        try {
            $packingList = PackingListMaster::find($request->id);

            if (! $packingList) {
                return response()->json([
                    'success' => false,
                    'message' => 'Packing List not found!',
                ], 404);
            }

            $packingList->pack_status = 1;
            $packingList->save();

            return response()->json([
                'success' => true,
                'message' => 'Packing List marked complete!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
