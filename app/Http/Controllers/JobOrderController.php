<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\SizeChartMaster;
use App\Models\JobOrderMaster;
use App\Models\JobOrderSizeMaster;
use Yajra\DataTables\Facades\DataTables;

class JobOrderController extends BaseController
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
            'page_title' => "Add Job Order",
            'page_main_title' => "Job Order",
            'page_child_title' => "Add",
            'isSuperAdmin' => $this->isSuperAdmin,
            'vendors' => VendorMaster::where('status', 0)->get(),
        ];

        return view('job_order.add', $page_data);
    }

    public function edit($id)
    {
        $job_order = JobOrderMaster::with(['sizes.size'])->findOrFail($id);

        // Check if job order can be edited (status should be 0)
        if ($job_order->status != 0) {
            return redirect()->route('job_order_master')->with('error', 'Cannot edit assigned job order.');
        }

        // Get all available sizes for this vendor (including new ones)
        $query = SizeChartMaster::where('vendor_id', $job_order->vendor_id)
            ->whereIn('status', [0, 1]);

        if ($job_order->vendor_id == 1 && $job_order->type) {
            $query->where('type', $job_order->type);
        }

        $allAvailableSizes = $query->get();

        // Create a merged collection of sizes with quantities
        $mergedSizes = collect();

        foreach ($allAvailableSizes as $availableSize) {
            // Check if this size exists in current job order
            $existingJobSize = $job_order->sizes->where('size_id', $availableSize->id)->first();

            $mergedSizes->push((object)[
                'id' => $availableSize->id,
                'size' => $availableSize->size,
                'qty' => $existingJobSize ? $existingJobSize->qty : 0,
                'size_id' => $availableSize->id,
                'is_new' => !$existingJobSize // Flag to identify new sizes
            ]);
        }

        // Replace the original sizes collection with merged data
        $job_order->setRelation('sizes', $mergedSizes);

        $page_data = [
            'page_title' => "Edit Job Order",
            'page_main_title' => "Job Order",
            'page_child_title' => "Edit",
            'isSuperAdmin' => $this->isSuperAdmin,
            'vendors' => VendorMaster::where('status', 0)->get(),
            'job_order' => $job_order,
        ];

        return view('job_order.edit', $page_data);
    }


    public function get_sizes_by_vendor(Request $request)
    {
        try {
            $query = SizeChartMaster::where('vendor_id', $request->vendor_id)
                ->whereIn('status', [0, 1]);

            // If vendor_id is 1 and type is provided, filter by type
            if ($request->vendor_id == 1 && $request->has('type') && !empty($request->type)) {
                $query->where('type', $request->type);
            }

            $sizes = $query->orderBy('id', 'asc')->get();

            return response()->json([
                'success' => true,
                'sizes' => $sizes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $rules = [
                'vendor_id' => 'required|exists:vendor_master,id',
                'job_no' => 'required|string|max:255',
                'style' => 'required|string|max:255',
                'color' => 'required|string|max:255',
            ];

            // Add type validation only for vendor_id = 1
            if ($request->vendor_id == 1) {
                $rules['type'] = 'required|in:Junior,Men';
            }

            $request->validate($rules);

            // Check for duplicate job_no
            if (JobOrderMaster::where('job_no', $request->job_no)->whereIn('status', [0, 1])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job Order Number already exists!'
                ]);
            }

            // Create job order
            $jobOrder = JobOrderMaster::create([
                'vendor_id' => $request->vendor_id,
                'job_no' => $request->job_no,
                'style' => $request->style,
                'color' => $request->color,
                'type' => $request->vendor_id == 1 ? $request->type : null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'status' => 0
            ]);

            // Get all sizes for the vendor (and type if vendor_id = 1)
            $query = SizeChartMaster::where('vendor_id', $request->vendor_id)
                ->whereIn('status', [0, 1]);

            if ($request->vendor_id == 1 && $request->type) {
                $query->where('type', $request->type);
            }

            $sizes = $query->get();

            // Insert size quantities
            foreach ($sizes as $size) {
                $qty = $request->input('qty_' . $size->id, 0); // Default to 0 if not provided

                JobOrderSizeMaster::create([
                    'job_id' => $jobOrder->id,
                    'size_id' => $size->id,
                    'qty' => $qty,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'status' => 0
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Job Order added successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $job_order = JobOrderMaster::findOrFail($id);

            // Check if job order can be updated (status should be 0)
            if ($job_order->status != 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update assigned job order.'
                ]);
            }

            $rules = [
                'vendor_id' => 'required|exists:vendor_master,id',
                'job_no' => 'required|string|max:255',
                'style' => 'required|string|max:255',
                'color' => 'required|string|max:255',
            ];

            // Add type validation only for vendor_id = 1
            if ($request->vendor_id == 1) {
                $rules['type'] = 'required|in:Junior,Men';
            }

            $request->validate($rules);

            // Check for duplicate job_no (excluding current record)
            if (JobOrderMaster::where('job_no', $request->job_no)
                ->where('id', '!=', $id)
                ->whereIn('status', [0, 1])
                ->exists()
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Job Order Number already exists!'
                ]);
            }

            // Update job order
            $job_order->update([
                'vendor_id' => $request->vendor_id,
                'job_no' => $request->job_no,
                'style' => $request->style,
                'color' => $request->color,
                'type' => $request->vendor_id == 1 ? $request->type : null,
            ]);

            // Delete existing size records
            JobOrderSizeMaster::where('job_id', $id)->delete();

            // Get all sizes for the vendor (and type if vendor_id = 1)
            $query = SizeChartMaster::where('vendor_id', $request->vendor_id)
                ->whereIn('status', [0, 1]);

            if ($request->vendor_id == 1 && $request->type) {
                $query->where('type', $request->type);
            }

            $sizes = $query->get();

            // Insert updated size quantities
            foreach ($sizes as $size) {
                $qty = $request->input('qty_' . $size->id, 0);

                JobOrderSizeMaster::create([
                    'job_id' => $job_order->id,
                    'size_id' => $size->id,
                    'qty' => $qty,
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'status' => 0
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Job Order updated successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function master()
    {
        $page_data = [
            'page_title' => "Job Order Master",
            'page_main_title' => "Job Order",
            'page_child_title' => "Master",
            'isSuperAdmin' => $this->isSuperAdmin,
            'vendors' => VendorMaster::where('status', 0)->get(),
        ];
        return view('job_order.master', $page_data);
    }

    public function table(Request $request)
    {
        $query = JobOrderMaster::with(['vendor', 'sizes.size'])
            ->whereIn('status', [0, 1])
            ->orderBy('id', 'desc');

        // Apply filters only if they are provided
        if ($request->filled('vendor_id') && $request->vendor_id != '') {
            $query->where('vendor_id', $request->vendor_id);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('job_no_actions', function ($job) {
                $dropdown = '<div class="dropdown">
                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        ' . $job->job_no . '
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item view-job-order" data-id="' . $job->id . '" href="javascript:void(0);">View</a></li>';

                // Only show Edit and Delete for status 0 (Not Assigned)
                if ($job->status == 0) {
                    $dropdown .= '<li><a class="dropdown-item" href="' . route('job_order_edit', $job->id) . '">Edit</a></li>';
                    $dropdown .= '<li><a class="dropdown-item delete-job-order" data-id="' . $job->id . '" href="javascript:void(0);">Delete</a></li>';
                }

                $dropdown .= '</ul></div>';

                return $dropdown;
            })
            ->addColumn('vendor_name', function ($job) {
                return $job->vendor ? $job->vendor->name : '-';
            })
            ->addColumn('style', function ($job) {
                return $job->style ?? '-';
            })
            ->addColumn('color', function ($job) {
                return $job->color ?? '-';
            })
            ->addColumn('status_badge', function ($job) {
                if ($job->status == 0) {
                    return '<span class="badge bg-warning text-dark">Not Assigned</span>';
                } elseif ($job->status == 1) {
                    return '<span class="badge bg-success">Assigned</span>';
                }
                return '<span class="badge bg-secondary">Unknown</span>';
            })
            ->rawColumns(['job_no_actions', 'status_badge'])
            ->make(true);
    }

    public function get_job_order_details(Request $request)
    {
        $job_order_details = JobOrderMaster::with(['vendor', 'sizes.size'])->find($request->id);
        return view('job_order.details', compact('job_order_details'));
    }

    public function delete(Request $request)
    {
        try {
            $job_order = JobOrderMaster::find($request->id);
            if ($job_order) {
                if ($job_order->status != 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete assigned job order.'
                    ]);
                }

                JobOrderSizeMaster::where('job_id', $job_order->id)
                    ->update(['status' => 2]);

                $job_order->status = 2;
                $job_order->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Job Order deleted successfully!',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Job Order not found!'
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
