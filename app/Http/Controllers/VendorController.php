<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\StateMaster;
use App\Models\CartonMaster;

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
            $this->middleware('permissions:list-vendor-carton')->only(['carton_selected_master', 'carton_puma_master', 'carton_jack_master', 'carton_skecher_master', 'carton_benetton_master', 'carton_vero_master']);
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
            'vendors' => VendorMaster::whereIn('status', [0, 1])
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
                'legal_name' => $request->legal_name,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'address_1' => $request->address_1,
                'address_2' => $request->address_2,
                'city_town_village' => $request->city_town_village,
                'pincode' => $request->pincode,
                'gst_no' => $request->gst_no,
                'pan_no' => $request->pan_no,
                'gst_type' => $request->gst_type,
                'place_supply' => $request->place_supply,
                'state_id' => $request->state_id,
                'excess' => $request->excess,
                'shortage' => $request->shortage,
                'discount' => $request->discount,
                'payment_terms' => $request->payment_terms,
                'notes' => $request->notes,
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
        $vendor_details = VendorMaster::with('state')->findOrFail($request->input('id'));
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

            $vendor->update([
                'legal_name' => $request->legal_name,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'address_1' => $request->address_1,
                'address_2' => $request->address_2,
                'city_town_village' => $request->city_town_village,
                'pincode' => $request->pincode,
                'gst_no' => $request->gst_no,
                'pan_no' => $request->pan_no,
                'gst_type' => $request->gst_type,
                'place_supply' => $request->place_supply,
                'state_id' => $request->state_id,
                'excess' => $request->excess,
                'shortage' => $request->shortage,
                'discount' => $request->discount,
                'payment_terms' => $request->payment_terms,
                'notes' => $request->notes,
            ]);

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
}
