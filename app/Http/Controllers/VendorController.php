<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\StateMaster;

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
                'mobile' => $request->mobile,
                'email' => $request->email,
                'address' => $request->address,
                'gst_no' => $request->gst_no,
                'state_id' => $request->state_id,
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
                'name' => $request->name,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'address' => $request->address,
                'gst_no' => $request->gst_no,
                'state_id' => $request->state_id,
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
}
