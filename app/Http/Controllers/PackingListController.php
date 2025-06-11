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
            'selectedCartonId'
        ));
    }

    public function save_config_po_details(Request $request)
    {
        try {
            $po_id    = $request->input('po_id');
            $carton_id = $request->input('carton_id');

            // Get PO and vendor info
            $po = PoMaster::with('vendor')->find($po_id);
            if (!$po) {
                return response()->json(['error' => 'PO not found'], 404);
            }

            $excess      = $po->vendor->excess ?? 0;
            $shortage    = $po->vendor->shortage ?? 0;
            $vendor_id   = $po->vendor_id;

            // Create master record
            $configMaster = PackingListConfigMaster::create([
                'po_id'      => $po_id,
                'vendor_id'  => $vendor_id,
                'carton_id'  => $carton_id,
                'excess'     => $excess,
                'shortage'   => $shortage,
                'created_by' => auth()->user()->id,
                'created_at' => now(),
                'status'     => 0,
            ]);

            if ($vendor_id == 4) {
                // For Benetton: use PoSizes, no po_item_id
                $configItems = PoSizes::where('po_id', $po_id)
                    ->where('vendor_id', 4)
                    ->get(['color', 'size', 'qty']);

                foreach ($configItems as $item) {
                    $poQty    = $item->qty;
                    $packQty  = ceil($poQty * (1 + $excess / 100));

                    PackingListConfigItem::create([
                        'po_id' => $po_id,
                        'config_id' => $configMaster->id,
                        'vendor_id' => $vendor_id,
                        // 'po_item_id' omitted for vendor_id 4
                        'color'     => $item->color,
                        'size'      => $item->size,
                        'po_qty'    => $poQty,
                        'pack_qty'  => $packQty,
                        'created_by' => auth()->user()->id,
                        'created_at' => now(),
                        'status'    => 0,
                    ]);
                }
            } else {
                // Default: use PoItems
                $poItems = PoItems::where('po_id', $po_id)->get();

                foreach ($poItems as $poItem) {
                    $color   = $poItem->color ?? $poItem->id_color ?? 'N/A';
                    $poQty   = $poItem->qty ?? 0;
                    $packQty = ceil($poQty * (1 + $excess / 100));

                    PackingListConfigItem::create([
                        'po_id' => $po_id,
                        'config_id'  => $configMaster->id,
                        'po_item_id' => $poItem->id,
                        'vendor_id' => $vendor_id,
                        'color'      => $color,
                        'size'       => $poItem->size ?? 'N/A',
                        'po_qty'     => $poQty,
                        'pack_qty'   => $packQty,
                        'created_by' => auth()->user()->id,
                        'created_at' => now(),
                        'status'     => 0,
                    ]);
                }
            }

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
            $poSizes = PoSizes::where('po_id', $poId)
                ->where('vendor_id', 4)
                ->where('color', $color)
                ->get();

            foreach ($poSizes as $ps) {
                $maxQty = $ps->qty;

                // sum up how much has already been packed for this PO/color/size
                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($poId) {
                    $q->where('po_id', $poId);
                })
                    ->where('color', $color)
                    ->where('size', $ps->size)
                    ->sum('quantity');

                $remainingQty = $maxQty - $packedQty;
                if ($remainingQty <= 0) {
                    continue;
                }

                $sizes[] = [
                    'size'           => $ps->size,
                    'max_qty'        => $maxQty,
                    'packed_qty'     => $packedQty,
                    'remaining_qty'  => $remainingQty,
                    'config_item_id' => $ps->id,
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

        $packingList = PackingListMaster::where('po_id', $poId)->first();

        if (!$packingList) {
            return response()->json([]);
        }

        $items = PackingListItem::where('packing_list_id', $packingList->id)
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
        // Fetch PO early so we know vendor_id
        $po = PoMaster::with('vendor')->find($request->input('po_id'));
        if (! $po) {
            return response()->json(['error' => 'PO not found'], 404);
        }
        $vendorId = $po->vendor_id;

        // Validation rules adjust for vendor 4
        $rules = [
            'po_id'           => 'required|exists:po_masters,id',
            'carton_id'       => 'required|exists:carton_master,id',
            'article_number'  => 'required|string',
            'color'           => 'required|string',
            'size'            => 'required|string',
            'quantity'        => 'required|integer|min:1',
        ];
        if ($vendorId != 4) {
            // only require config_item_id for non-Benetton
            $rules['config_item_id'] = 'required|exists:packing_list_config_items,id';
        }

        $validated = $request->validate($rules);

        try {
            // Determine how much is allowed
            if ($vendorId == 4) {
                // Benetton: get the original qty from PoSizes
                $poSize = PoSizes::where('po_id', $request->po_id)
                    ->where('vendor_id', 4)
                    ->where('color', $request->color)
                    ->where('size', $request->size)
                    ->first();

                if (! $poSize) {
                    return response()->json(['error' => 'Size not found in PoSizes'], 400);
                }

                $maxQty = $poSize->qty;

                // already packed
                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($request) {
                    $q->where('po_id', $request->po_id);
                })
                    ->where('color', $request->color)
                    ->where('size', $request->size)
                    ->sum('quantity');
            } else {
                // other vendors: from config item
                $configItem = PackingListConfigItem::find($request->config_item_id);
                if (! $configItem) {
                    return response()->json(['error' => 'Configuration item not found'], 400);
                }

                $maxQty = $configItem->pack_qty;
                $packedQty = PackingListItem::whereHas('packingList', function ($q) use ($request) {
                    $q->where('po_id', $request->po_id);
                })
                    ->where('article_number', $request->article_number)
                    ->where('size', $request->size)
                    ->sum('quantity');
            }

            $remainingQty = $maxQty - $packedQty;
            if ($request->quantity > $remainingQty) {
                return response()->json([
                    'error' => "Quantity exceeds available limit. Available: {$remainingQty}"
                ], 400);
            }

            // Create or fetch the packing list master
            $packingList = PackingListMaster::firstOrCreate(
                ['po_id' => $request->po_id],
                [
                    'vendor_id' => $po->vendor_id,
                    'po_no'     => $po->po_num,
                    'po_date'   => $po->po_date,
                    'created_by' => auth()->user()->id,
                    'created_at' => now(),
                ]
            );

            // Figure out the PoItems entry (if any)
            $poItem = PoItems::where('po_id', $request->po_id)
                ->where('article_number', $request->article_number)
                ->where('color', $request->color)
                ->where('size', $request->size)
                ->first();

            // Generate carton name however you like
            $cartonName = $this->generateCartonName(
                $po->vendor_id,
                $packingList->id,
                $request->color,
                $request->size,
                $request->article_number
            );

            PackingListItem::create([
                'packing_list_id' => $packingList->id,
                'vendor_id'       => $po->vendor_id,
                'po_item_id'      => $vendorId == 4 ? null : ($poItem->id ?? null),
                'carton_id'       => $request->carton_id,
                'carton_name'     => $cartonName,
                'article_number'  => $request->article_number,
                'color'           => $request->color,
                'size'            => $request->size,
                'quantity'        => $request->quantity,
                'created_by' => auth()->user()->id,
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'po_id'   => $request->po_id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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
                        ->where('id', '!=', $item->id)
                        ->sum('quantity');
                    return $packedQty < $totalPack ? [$poItem->article_number] : [];
                });
            $articles = collect($filtered)->unique()->values();
        }

        // Build sizes+qty list
        $sizesWithQty = [];
        if ($vendorId == 4) {
            // from PoSizes
            $poSizes = PoSizes::where('po_id', $poId)
                ->where('vendor_id', 4)
                ->where('color', $color)
                ->get();

            foreach ($poSizes as $ps) {
                if ($ps->color !== $item->color) {
                    continue;
                }
                $maxQty = $ps->qty;
                $packed = PackingListItem::whereHas('packingList', fn($q) => $q->where('po_id', $poId))
                    ->where('color', $color)
                    ->where('size', $ps->size)
                    ->sum('quantity');
                $rem = $maxQty - $packed;
                // allow editing current size even if rem=0
                if ($rem <= 0 && $item->size !== $ps->size) {
                    continue;
                }
                $sizesWithQty[] = [
                    'size'          => $ps->size,
                    'max_qty'       => $maxQty,
                    'packed_qty'    => $packed,
                    'remaining_qty' => max(0, $rem),
                    'config_item_id' => null,
                ];
            }
        } else {
            // existing config logic
            $configItems = PackingListConfigItem::whereHas('config', fn($q) => $q->where('po_id', $poId))
                ->where('color', $color)
                ->whereHas('poItem', fn($q) => $q->where('article_number', $item->article_number))
                ->with(['poItem', 'config'])
                ->get();

            foreach ($configItems as $ci) {
                $maxQty = $ci->pack_qty;
                $packed = PackingListItem::whereHas('packingList', fn($q) => $q->where('po_id', $poId))
                    ->where('article_number', $item->article_number)
                    ->where('size', $ci->size)
                    ->sum('quantity');
                $rem = $maxQty - $packed;
                if ($rem <= 0 && $item->size !== $ci->size) {
                    continue;
                }
                $sizesWithQty[] = [
                    'size'           => $ci->size,
                    'max_qty'        => $maxQty,
                    'packed_qty'     => $packed,
                    'remaining_qty'  => max(0, $rem),
                    'config_item_id' => $ci->id,
                ];
            }
        }

        return view('packing_list.item_edit', compact(
            'carton_id',
            'articles',
            'poId',
            'item',
            'sizesWithQty',
            'color',
            'job_num'
        ));
    }

    public function item_update(Request $request)
    {
        $po = PoMaster::find($request->input('po_id'));
        if (! $po) {
            return response()->json(['error' => 'PO not found'], 404);
        }
        $vendorId = $po->vendor_id;

        // Validation
        $rules = [
            'id'             => 'required|exists:packing_list_items,id',
            'po_id'          => 'required|exists:po_masters,id',
            'carton_id'      => 'required|exists:carton_master,id',
            'article_number' => 'required|string',
            'color'          => 'required|string',
            'size'           => 'required|string',
            'quantity'       => 'required|integer|min:1',
        ];
        if ($vendorId != 4) {
            $rules['config_item_id'] = 'required|exists:packing_list_config_items,id';
        }
        $validated = $request->validate($rules);

        try {
            $item = PackingListItem::find($validated['id']);

            // Determine max and packed qty
            if ($vendorId == 4) {
                $ps = PoSizes::where('po_id', $request->po_id)
                    ->where('vendor_id', 4)
                    ->where('color', $request->color)
                    ->where('size', $request->size)
                    ->first();
                if (! $ps) {
                    return response()->json(['error' => 'Size not found in PoSizes'], 400);
                }
                $maxQty   = $ps->qty;
                $packed   = PackingListItem::whereHas('packingList', fn($q) => $q->where('po_id', $request->po_id))
                    ->where('color', $request->color)
                    ->where('size', $request->size)
                    ->where('id', '!=', $item->id)
                    ->sum('quantity');
            } else {
                $ci = PackingListConfigItem::find($request->config_item_id);
                if (! $ci) {
                    return response()->json(['error' => 'Configuration item not found'], 400);
                }
                $maxQty = $ci->pack_qty;
                $packed = PackingListItem::whereHas('packingList', fn($q) => $q->where('po_id', $request->po_id))
                    ->where('article_number', $request->article_number)
                    ->where('size', $request->size)
                    ->where('id', '!=', $item->id)
                    ->sum('quantity');
            }

            $remaining = $maxQty - $packed;
            if ($request->quantity > $remaining) {
                return response()->json([
                    'error' => "Quantity exceeds available limit. Available: {$remaining}"
                ], 400);
            }

            // Re-generate carton name if changed
            $cartonName = $item->carton_name;
            if ($item->carton_id != $request->carton_id) {
                $cartonName = $this->generateCartonName(
                    $vendorId,
                    $item->packing_list_id,
                    $request->color,
                    $request->size,
                    $request->article_number
                );
            }

            // Find corresponding PoItem (if any)
            $poItem = PoItems::where('po_id', $request->po_id)
                ->where('article_number', $request->article_number)
                ->where('color', $request->color)
                ->where('size', $request->size)
                ->first();

            $item->update([
                'po_item_id'     => $vendorId == 4 ? null : ($poItem->id ?? null),
                'carton_id'      => $request->carton_id,
                'carton_name'    => $cartonName,
                'article_number' => $request->article_number,
                'size'           => $request->size,
                'quantity'       => $request->quantity,
                'updated_by'     => auth()->id(),
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
            // VENDOR ID 2 (Skechers-specific)
            //

            // 2.1 Filtered ordered quantities: only PO items in this packing list
            if (!empty($uniquePoItemIds)) {
                $poItemsFiltered = PoItems::whereIn('id', $uniquePoItemIds)->get();
                $orderedQuantities = $poItemsFiltered
                    ->groupBy('size')
                    ->map(fn($itemsForSize) => $itemsForSize->sum('qty'));
            }

            // 2.2 Compute balances & percentages for summary
            foreach ($allSizes as $size) {
                $ordered = $orderedQuantities->get($size, 0);
                $packed  = $packedQuantities->get($size, 0);
                $balance = $ordered - $packed;
                $percentage = $ordered > 0 ? ($packed / $ordered) * 100 : 0;

                $balances[$size]    = $balance;
                $percentages[$size] = $percentage;
            }

            // 2.3 Build detail tableData (same as before, with ctn_first/last, etc.)
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

            $groupedItems = $packingList->items->groupBy(function ($item) {
                return $item->article_number . '|' . $item->color . '|' . $item->size;
            });

            $tableRows = [];
            $totals = [
                'carton_count' => 0,
                'per_size'     => array_fill_keys($sizeOrder, 0),
                'total_pieces' => 0
            ];

            foreach ($groupedItems as $groupKey => $groupItems) {
                list($articleNumber, $color, $size) = explode('|', $groupKey);

                // Carton names & IDs in this group
                $cartonNames = $groupItems->pluck('carton_name')->unique()->sort()->values();
                $cartonIds   = $groupItems->pluck('carton_id')->unique()->values();
                $cartonCount = $cartonNames->count();

                $firstName = $cartonCount > 0 ? $cartonNames->first() : '';
                $lastName  = $cartonCount > 0 ? $cartonNames->last()  : '';
                $firstCartonId = $cartonCount > 0 ? $cartonIds->first() : '';

                $totalQty = $groupItems->sum('quantity');

                $firstItem = $groupItems->first();
                $carton    = $firstItem->carton;

                $netWeightPerCarton   = $carton->net_weight ?? 0;
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
                // Set only this size
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

            // Group items by size
            $sizeGroups = $packingList->items->groupBy('size');
            $tableRows = [];

            foreach ($sizeOrder as $size) {
                if (!$sizeGroups->has($size)) {
                    continue;
                }
                $sizeItems = $sizeGroups->get($size);

                $cartonNames = $sizeItems->pluck('carton_name')->unique()->values();
                $cartonCount = $cartonNames->count();

                $firstName = $cartonCount > 0 ? $cartonNames->first() : '';
                $lastName  = $cartonCount > 0 ? $cartonNames->last()  : '';
                $ctnRange = $cartonCount > 0
                    ? ($cartonCount == 1 ? $firstName : $firstName . '-' . $lastName)
                    : '';

                $totalQty = $sizeItems->sum('quantity');

                $firstItem = $sizeItems->first();
                $carton = $firstItem->carton;

                $netWeightPerCarton = $carton->net_weight ?? 0;
                $grossWeightPerCarton = $carton->gross_weight ?? 0;
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

                foreach ($sizeOrder as $sizeCol) {
                    $row['per_size'][$sizeCol] = ($sizeCol == $size) ? $totalQty : 0;
                }

                $tableRows[] = $row;
            }

            $tableData = [
                'sizeOrder' => $sizeOrder,
                'rows'      => $tableRows,
            ];
        } elseif ($packingList->vendor_id == 4) {
            //
            // VENDOR ID 4 (Benetton-specific) - Group by SIZE
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

            // 2.2 Compute balances & percentages for summary (similar to vendor ID 2)
            foreach ($allSizes as $size) {
                $ordered = $orderedQuantities->get($size, 0);
                $packed  = $packedQuantities->get($size, 0);
                $balance = $ordered - $packed;
                $percentage = $ordered > 0 ? ($packed / $ordered) * 100 : 0;

                $balances[$size]    = $balance;
                $percentages[$size] = $percentage;
            }

            // Group items by SIZE for Benetton format
            $sizeGroups = $packingList->items->groupBy('size');
            $tableRows = [];
            $totals = [
                'carton_count' => 0,
                'per_size'     => array_fill_keys($sizeOrder, 0),
                'total_pieces' => 0,
                'net_weight'   => 0,
                'empty_box_weight' => 0,
                'gross_weight' => 0
            ];

            foreach ($sizeGroups as $size => $sizeItems) {
                // Get carton names for this size group
                $cartonNames = $sizeItems->pluck('carton_name')->unique()->sort()->values();
                $cartonIds   = $sizeItems->pluck('carton_id')->unique()->values();
                $cartonCount = $cartonNames->count();

                $firstName = $cartonCount > 0 ? $cartonNames->first() : '';
                $lastName  = $cartonCount > 0 ? $cartonNames->last()  : '';
                $firstCartonId = $cartonCount > 0 ? $cartonIds->first() : '';

                // Get the color for this size group (assuming items in same size group have same color)
                $color = $sizeItems->first()->color ?? '';

                $totalQty = $sizeItems->sum('quantity');
                $perCartonQty = $cartonCount > 0 ? round($totalQty / $cartonCount) : 0;

                // Get carton details for weight calculations
                $firstItem = $sizeItems->first();
                $carton = $firstItem->carton;

                $netWeightPerCarton = $carton->net_weight ?? 0;
                $emptyBoxWeightPerCarton = $carton->empty_weight ?? 1.5; // Default as shown in HTML
                $grossWeightPerCarton = $carton->gross_weight ?? ($netWeightPerCarton + $emptyBoxWeightPerCarton);

                $totalNetWeightForSize = $netWeightPerCarton * $cartonCount;
                $totalEmptyBoxWeight = $emptyBoxWeightPerCarton * $cartonCount;
                $totalGrossWeightForSize = $grossWeightPerCarton * $cartonCount;

                $row = [
                    'ctn_first'        => $firstName,
                    'ctn_last'         => $lastName,
                    'ttl_ctn'          => $cartonCount,
                    'color_code'       => $color,
                    'size'             => $size,
                    'per_size'         => array_fill_keys($sizeOrder, 0),
                    'per_ctn'          => $perCartonQty,
                    'grand_total'      => $totalQty,
                    'net_weight'       => $totalNetWeightForSize,
                    'empty_box_weight' => $totalEmptyBoxWeight,
                    'gross_weight'     => $totalGrossWeightForSize,
                ];

                // Fill only the current size with quantity
                $row['per_size'][$size] = $totalQty;

                // Update totals
                $totals['carton_count'] += $cartonCount;
                $totals['per_size'][$size] += $totalQty;
                $totals['total_pieces'] += $totalQty;
                $totals['net_weight'] += $totalNetWeightForSize;
                $totals['empty_box_weight'] += $totalEmptyBoxWeight;
                $totals['gross_weight'] += $totalGrossWeightForSize;

                $tableRows[] = $row;
            }

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

                // Weight/dimension if needed
                $netWeightPerCarton   = $carton->net_weight ?? 0;
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
}
