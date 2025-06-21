<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransportMaster;

class TransportController extends BaseController
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
        }
    }

    public function index()
    {
        $page_data = [
            'page_title' => "Transport",
            'page_main_title' => "Settings",
            'page_child_title' => "Master",
            'isSuperAdmin' => $this->isSuperAdmin,
            'transports' => TransportMaster::whereIn('status', [0, 1])
                ->orderBy('id', 'asc')
                ->get(),
        ];

        return view('settings.transport.master', $page_data);
    }

    public function add()
    {
        return view('settings.transport.add');
    }

    public function store(Request $request)
    {
        try {
            $transport = TransportMaster::create([
                'name' => $request->name,
                'description' => $request->description,
                'created_by' => auth()->user()->id,
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transport added successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the transport: ' . $e->getMessage()
            ]);
        }
    }

    public function get_transport_details(Request $request)
    {
        $transport_details = TransportMaster::findOrFail($request->input('id'));
        return view('settings.transport.details', compact('transport_details'));
    }

    public function edit(Request $request)
    {
        $transport_details = TransportMaster::findOrFail($request->input('id'));
        return view('settings.transport.edit', compact('transport_details'));
    }

    public function update(Request $request)
    {
        try {
            $transport = TransportMaster::findOrFail($request->transport_id);

            $transport->update([
                'mobile' => $request->mobile,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transport updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the transport: ' . $e->getMessage()
            ]);
        }
    }

    public function delete(Request $request)
    {
        $transport = TransportMaster::find($request->id);
        if ($transport) {
            $transport->status = 2;
            if ($transport->save()) {
                return response()->json(['success' => true]);
            }
        }
        return response()->json(['success' => false]);
    }
}
