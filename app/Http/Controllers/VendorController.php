<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\StateMaster;
use App\Models\CartonMaster;
use App\Models\SizeChartMaster;

class VendorController extends BaseController
{
    protected $isSuperAdmin;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->isSuperAdmin = request()->attributes->get('isSuperAdmin', false);
        $this->middleware('auth');

        // Only apply permission middleware for non-superadmin users
        if (!$this->isSuperAdmin) {
            $this->middleware('permissions:create-vendor')->only(['add', 'store']);
            $this->middleware('permissions:list-vendor')->only(['list']);
            $this->middleware('permissions:view-vendor')->only(['get_vendor_details']);
            $this->middleware('permissions:edit-vendor')->only(['edit_vendor', 'update']);
            $this->middleware('permissions:delete-vendor')->only('delete');
            $this->middleware('permissions:status-vendor')->only('update_status');
            $this->middleware('permissions:create-vendor-carton')->only(['carton_add', 'carton_store']);
            $this->middleware('permissions:list-vendor-carton')->only(['carton_selected_master', 'carton_puma_master', 'carton_jack_master', 'carton_skecher_master', 'carton_benetton_master', 'carton_vero_master', 'carton_dmart_master']);
            $this->middleware('permissions:view-vendor-carton')->only(['get_carton_details']);
            $this->middleware('permissions:edit-vendor-carton')->only(['carton_edit', 'carton_update']);
            $this->middleware('permissions:delete-vendor-carton')->only('carton_delete');
        }
    }

    public function index()
    {
        $page_data = [
            'page_title' => "Vendor",
            'page_main_title' => "Settings",
            'page_child_title' => "Master",
            'isSuperAdmin' => $this->isSuperAdmin,
            'vendors' => VendorMaster::with(['billingState', 'shippingState'])->whereIn('status', [0, 1])
                ->orderBy('id', 'asc')
                ->get(),
        ];

        return view('settings.vendor.master', $page_data);
    }

    public function add()
    {
        $states = StateMaster::where('status', 0)->get();
        return view('settings.vendor.add', compact('states'));
    }

    public function store(Request $request)
    {
        try {
            $vendor = VendorMaster::create([
                'name' => $request->name,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'notes' => $request->notes,
                // Billing Address
                'billing_legal_name' => $request->billing_legal_name,
                'billing_address_1' => $request->billing_address_1,
                'billing_address_2' => $request->billing_address_2,
                'billing_city_town_village' => $request->billing_city_town_village,
                'billing_pincode' => $request->billing_pincode,
                'billing_gst_no' => $request->billing_gst_no,
                'billing_pan_no' => $request->billing_pan_no,
                'billing_gst_type' => $request->billing_gst_type,
                'billing_state_id' => $request->billing_state_id,
                // Shipping Address
                'shipping_legal_name' => $request->shipping_legal_name,
                'shipping_address_1' => $request->shipping_address_1,
                'shipping_address_2' => $request->shipping_address_2,
                'shipping_city_town_village' => $request->shipping_city_town_village,
                'shipping_pincode' => $request->shipping_pincode,
                'shipping_gst_no' => $request->shipping_gst_no,
                'shipping_pan_no' => $request->shipping_pan_no,
                'shipping_place_supply' => $request->shipping_place_supply,
                'shipping_state_id' => $request->shipping_state_id,
                // Other Fields
                'excess' => $request->excess,
                'shortage' => $request->shortage,
                'discount' => $request->discount,
                'payment_terms' => $request->payment_terms,
                'created_by' => auth()->user()->id,
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vendor added successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the vendor: ' . $e->getMessage()
            ]);
        }
    }

    public function get_vendor_details(Request $request)
    {
        $vendor_details = VendorMaster::with(['billingState', 'shippingState'])->findOrFail($request->input('id'));
        return view('settings.vendor.details', compact('vendor_details'));
    }

    public function edit(Request $request)
    {
        $vendor_details = VendorMaster::findOrFail($request->input('id'));
        $states = StateMaster::where('status', 0)->get();
        return view('settings.vendor.edit', compact('vendor_details', 'states'));
    }

    public function update(Request $request)
    {
        try {
            $vendor = VendorMaster::findOrFail($request->vendor_id);

            $updateData = [
                'mobile' => $request->mobile,
                'email' => $request->email,
                'notes' => $request->notes,
                // Billing Address
                'billing_legal_name' => $request->billing_legal_name,
                'billing_address_1' => $request->billing_address_1,
                'billing_address_2' => $request->billing_address_2,
                'billing_city_town_village' => $request->billing_city_town_village,
                'billing_pincode' => $request->billing_pincode,
                'billing_gst_no' => $request->billing_gst_no,
                'billing_pan_no' => $request->billing_pan_no,
                'billing_gst_type' => $request->billing_gst_type,
                'billing_state_id' => $request->billing_state_id,
                // Shipping Address
                'shipping_legal_name' => $request->shipping_legal_name,
                'shipping_address_1' => $request->shipping_address_1,
                'shipping_address_2' => $request->shipping_address_2,
                'shipping_city_town_village' => $request->shipping_city_town_village,
                'shipping_pincode' => $request->shipping_pincode,
                'shipping_gst_no' => $request->shipping_gst_no,
                'shipping_pan_no' => $request->shipping_pan_no,
                'shipping_place_supply' => $request->shipping_place_supply,
                'shipping_distance' => $request->shipping_distance,
                'shipping_state_id' => $request->shipping_state_id,
                // Other Fields
                'excess' => $request->excess,
                'shortage' => $request->shortage,
                'discount' => $request->discount,
                'payment_terms' => $request->payment_terms,
            ];

            // Add UAE fields only for vendor ID 2
            if ($request->vendor_id == 2) {
                $updateData['uae_shipping_legal_name'] = $request->uae_shipping_legal_name;
                $updateData['uae_shipping_address_1'] = $request->uae_shipping_address_1;
                $updateData['uae_shipping_address_2'] = $request->uae_shipping_address_2;
                $updateData['uae_shipping_city_town_village'] = $request->uae_shipping_city_town_village;
                $updateData['uae_shipping_place_supply'] = $request->uae_shipping_place_supply;
            }

            $vendor->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Vendor updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the vendor: ' . $e->getMessage()
            ]);
        }
    }

    public function delete(Request $request)
    {
        $vendor = VendorMaster::find($request->id);
        if ($vendor) {
            $vendor->status = 2;
            if ($vendor->save()) {
                return response()->json(['success' => true]);
            }
        }
        return response()->json(['success' => false]);
    }

    public function update_status(Request $request)
    {
        $vendor = VendorMaster::find($request->id);
        if ($vendor) {
            $vendor->status = $request->status;
            if ($vendor->save()) {
                return response()->json(['success' => true]);
            }
        }
        return response()->json(['success' => false]);
    }

    public function carton_jack_master()
    {
        $cartons = CartonMaster::where('vendor_id', 1)
            ->where('status', 0)
            ->get();

        $page_data = [
            'page_title' => "Master",
            'page_main_title' => "Settings",
            'page_child_title' => "Carton",
            'cartons' => $cartons,
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('settings.vendor.carton.jack_master', $page_data);
    }

    public function carton_skecher_master()
    {
        $cartons = CartonMaster::where('vendor_id', 2)
            ->where('status', 0)
            ->get();

        $page_data = [
            'page_title' => "Master",
            'page_main_title' => "Settings",
            'page_child_title' => "Carton",
            'cartons' => $cartons,
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('settings.vendor.carton.skecher_master', $page_data);
    }

    public function carton_puma_master()
    {
        $cartons = CartonMaster::where('vendor_id', 3)
            ->where('status', 0)
            ->get();

        $page_data = [
            'page_title' => "Master",
            'page_main_title' => "Settings",
            'page_child_title' => "Carton",
            'cartons' => $cartons,
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('settings.vendor.carton.puma_master', $page_data);
    }

    public function carton_benetton_master()
    {
        $cartons = CartonMaster::where('vendor_id', 4)
            ->where('status', 0)
            ->get();

        $page_data = [
            'page_title' => "Master",
            'page_main_title' => "Settings",
            'page_child_title' => "Carton",
            'cartons' => $cartons,
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('settings.vendor.carton.benetton_master', $page_data);
    }

    public function carton_selected_master()
    {
        $cartons = CartonMaster::where('vendor_id', 5)
            ->where('status', 0)
            ->get();

        $page_data = [
            'page_title' => "Master",
            'page_main_title' => "Settings",
            'page_child_title' => "Carton",
            'cartons' => $cartons,
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('settings.vendor.carton.selected_master', $page_data);
    }

    public function carton_vero_master()
    {
        $cartons = CartonMaster::where('vendor_id', 6)
            ->where('status', 0)
            ->get();

        $page_data = [
            'page_title' => "Master",
            'page_main_title' => "Settings",
            'page_child_title' => "Carton",
            'cartons' => $cartons,
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('settings.vendor.carton.vero_master', $page_data);
    }

    public function carton_dmart_master()
    {
        $cartons = CartonMaster::where('vendor_id', 8)
            ->where('status', 0)
            ->get();

        $page_data = [
            'page_title' => "Master",
            'page_main_title' => "Settings",
            'page_child_title' => "Carton",
            'cartons' => $cartons,
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('settings.vendor.carton.dmart_master', $page_data);
    }

    public function carton_rare_rabbit_master()
    {
        $cartons = CartonMaster::where('vendor_id', 9)
            ->where('status', 0)
            ->get();

        $page_data = [
            'page_title' => "Master",
            'page_main_title' => "Settings",
            'page_child_title' => "Carton",
            'cartons' => $cartons,
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        return view('settings.vendor.carton.rare_rabbit_master', $page_data);
    }

    public function carton_add()
    {
        $vendors = VendorMaster::where('status', 0)->get();
        return view('settings.vendor.carton.add', compact('vendors'));
    }

    public function carton_store(Request $request)
    {
        try {
            $request->validate([
                'vendor_id' => 'required|exists:vendor_master,id',
                'cartons' => 'required|array|min:1',
                'cartons.*.length' => 'required|numeric|min:0',
                'cartons.*.breadth' => 'required|numeric|min:0',
                'cartons.*.height' => 'required|numeric|min:0',
                'cartons.*.weight' => 'required|numeric|min:0',
            ]);

            $vendorId = $request->vendor_id;
            $cartons = $request->cartons;
            $createdBy = auth()->id();

            foreach ($cartons as $cartonData) {
                CartonMaster::create([
                    'vendor_id' => $vendorId,
                    'length' => $cartonData['length'],
                    'breadth' => $cartonData['breadth'],
                    'height' => $cartonData['height'],
                    'weight' => $cartonData['weight'],
                    'created_by' => $createdBy,
                    'created_at' => now(),
                    'status' => 0
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cartons added successfully!',
                'vendor_id' => $vendorId
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function carton_edit(Request $request)
    {
        $carton = CartonMaster::find($request->id);
        $vendors = VendorMaster::where('status', 0)->get();
        return view('settings.vendor.carton.edit', compact('carton', 'vendors'));
    }

    public function carton_update(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:carton_master,id',
                'length' => 'required|numeric|min:0',
                'breadth' => 'required|numeric|min:0',
                'height' => 'required|numeric|min:0',
                'weight' => 'required|numeric|min:0',
            ]);

            $carton = CartonMaster::find($request->id);
            $carton->update([
                'length' => $request->length,
                'breadth' => $request->breadth,
                'height' => $request->height,
                'weight' => $request->weight,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Carton updated successfully!',
                'vendor_id' => $request->vendor_id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function carton_delete(Request $request)
    {
        try {
            $carton = CartonMaster::find($request->id);
            if ($carton) {
                $vendorId = $carton->vendor_id;
                $carton->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Carton deleted successfully!',
                    'vendor_id' => $vendorId
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Carton not found!'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function size_chart_master()
    {
        $page_data = [
            'page_title' => "Size Chart",
            'page_main_title' => "Settings",
            'page_child_title' => "Master",
            'isSuperAdmin' => $this->isSuperAdmin,
        ];

        // Get all size charts with vendor relationship
        $sizeCharts = SizeChartMaster::with('vendor')
            ->whereIn('status', [0, 1])
            ->orderBy('vendor_id', 'asc')
            ->orderBy('type', 'asc')
            ->get();

        // Group by vendor_id and type
        $vendors = $sizeCharts->groupBy(function ($item) {
            return $item->vendor_id . '-' . ($item->type ?? 'default');
        });

        $page_data['vendors'] = $vendors;

        return view('settings.vendor.size_chart.master', $page_data);
    }

    public function size_chart_add()
    {
        $vendors = VendorMaster::where('status', 0)->get();

        // Get existing sizes grouped by vendor and type
        $existingSizes = SizeChartMaster::whereIn('status', [0, 1])
            ->orderBy('vendor_id')
            ->orderBy('type')
            ->orderBy('size')
            ->get()
            ->groupBy(function ($item) {
                return $item->vendor_id . '-' . ($item->type ?? 'default');
            });

        return view('settings.vendor.size_chart.add', compact('vendors', 'existingSizes'));
    }

    public function size_chart_store(Request $request)
    {
        try {
            $rules = [
                'vendor_id' => 'required|exists:vendor_master,id',
                'sizes' => 'required|array|min:1',
                'sizes.*' => 'required|string|max:255',
            ];

            if ($request->vendor_id == 1) {
                $rules['type'] = 'required|in:Junior,Men';
            }

            $request->validate($rules);

            $duplicates = [];
            $created = 0;

            foreach ($request->sizes as $size) {
                // Check for duplicate
                $duplicateQuery = SizeChartMaster::where('vendor_id', $request->vendor_id)
                    ->where('size', trim($size))
                    ->whereIn('status', [0, 1]);

                if ($request->vendor_id == 1) {
                    $duplicateQuery->where('type', $request->type);
                }

                if ($duplicateQuery->exists()) {
                    $duplicates[] = $size;
                    continue;
                }

                // Create the size chart entry
                SizeChartMaster::create([
                    'vendor_id' => $request->vendor_id,
                    'size' => trim($size),
                    'type' => $request->vendor_id == 1 ? $request->type : null,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'status' => 0
                ]);
                $created++;
            }

            $message = $created > 0 ? "$created size(s) added successfully!" : "No sizes were added.";
            if (!empty($duplicates)) {
                $message .= " Duplicate sizes skipped: " . implode(', ', $duplicates);
            }

            return response()->json([
                'success' => $created > 0,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function get_size_chart_details(Request $request)
    {
        try {
            $vendor_id = $request->vendor_id;
            $type = $request->type;

            $query = SizeChartMaster::with('vendor')
                ->where('vendor_id', $vendor_id)
                ->whereIn('status', [0, 1]);

            if ($vendor_id == 1 && $type && $type != 'null') {
                $query->where('type', $type);
            }

            $size_chart_details = $query->orderBy('size', 'asc')->get();
            $vendor = VendorMaster::find($vendor_id);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            return view('settings.vendor.size_chart.details', compact('size_chart_details', 'vendor', 'type'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function size_chart_edit(Request $request)
    {
        try {
            $vendor_id = $request->vendor_id;
            $type = $request->type;

            $query = SizeChartMaster::where('vendor_id', $vendor_id)
                ->whereIn('status', [0, 1]);

            if ($vendor_id == 1 && $type && $type != 'null') {
                $query->where('type', $type);
            }

            $existing_sizes = $query->orderBy('size', 'asc')->get();
            $vendor = VendorMaster::find($vendor_id);

            if (!$vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vendor not found'
                ], 404);
            }

            // Pass size_chart_details for backward compatibility if needed
            $size_chart_details = $existing_sizes->first();

            return view('settings.vendor.size_chart.edit', compact('existing_sizes', 'vendor', 'type', 'size_chart_details'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function size_chart_update(Request $request)
    {
        try {
            $rules = [
                'vendor_id' => 'required|exists:vendor_master,id',
                'new_sizes' => 'nullable|array',
                'new_sizes.*' => 'required|string|max:255',
                'updated_sizes' => 'nullable|array',
                'updated_sizes.*.id' => 'required|exists:size_chart_master,id',
                'updated_sizes.*.size' => 'required|string|max:255',
            ];

            if ($request->vendor_id == 1) {
                $rules['type'] = 'required|in:Junior,Men';
            }

            $request->validate($rules);

            $duplicates = [];
            $updated = 0;
            $created = 0;

            // Update existing sizes
            if ($request->updated_sizes) {
                foreach ($request->updated_sizes as $sizeData) {
                    $sizeChart = SizeChartMaster::find($sizeData['id']);

                    // Check for duplicate before updating
                    $duplicateQuery = SizeChartMaster::where('vendor_id', $request->vendor_id)
                        ->where('size', trim($sizeData['size']))
                        ->where('id', '!=', $sizeData['id'])
                        ->whereIn('status', [0, 1]);

                    if ($request->vendor_id == 1) {
                        $duplicateQuery->where('type', $request->type);
                    }

                    if ($duplicateQuery->exists()) {
                        $duplicates[] = $sizeData['size'];
                        continue;
                    }

                    $sizeChart->update([
                        'size' => trim($sizeData['size']),
                    ]);
                    $updated++;
                }
            }

            // Create new sizes
            if ($request->new_sizes) {
                foreach ($request->new_sizes as $size) {
                    // Check for duplicate
                    $duplicateQuery = SizeChartMaster::where('vendor_id', $request->vendor_id)
                        ->where('size', trim($size))
                        ->whereIn('status', [0, 1]);

                    if ($request->vendor_id == 1) {
                        $duplicateQuery->where('type', $request->type);
                    }

                    if ($duplicateQuery->exists()) {
                        $duplicates[] = $size;
                        continue;
                    }

                    SizeChartMaster::create([
                        'vendor_id' => $request->vendor_id,
                        'size' => trim($size),
                        'type' => $request->vendor_id == 1 ? $request->type : null,
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                        'status' => 0
                    ]);
                    $created++;
                }
            }

            $message = [];
            if ($updated > 0) $message[] = "$updated size(s) updated";
            if ($created > 0) $message[] = "$created new size(s) added";
            if (empty($message)) $message[] = "No changes made";

            $finalMessage = implode(', ', $message) . "!";

            if (!empty($duplicates)) {
                $finalMessage .= " Duplicate sizes skipped: " . implode(', ', $duplicates);
            }

            return response()->json([
                'success' => ($updated > 0 || $created > 0),
                'message' => $finalMessage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function size_chart_delete(Request $request)
    {
        try {
            $sizeChart = SizeChartMaster::find($request->id);

            if (!$sizeChart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Size chart not found'
                ]);
            }

            $sizeChart->update(['status' => 2]);

            return response()->json([
                'success' => true,
                'message' => 'Size deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
