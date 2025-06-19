<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Support\Facades\DB;
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
use App\Models\PackingListMaster;
use App\Models\PackingListItem;
use App\Models\InvoiceMaster;

class InvoiceController extends BaseController
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

    public function genrate()
    {
        $page_data = [
            'page_title' => "Genrate Invoice",
            'page_main_title' => "Genrate Invoice",
            'page_child_title' => "Genrate Invoice",
            'isSuperAdmin' => $this->isSuperAdmin,
            'vendors' => VendorMaster::where('status', 0)->get(),

        ];
       

        return view('invoice.genrate', $page_data);
    }

    public function get_packging_list(Request $request)
    {
        $poId = $request->id;
        $vendor_id = $request->vendor_id;
        $packed_lists = PackingListMaster::where(array('po_id'=>$poId,'pack_status'=>1))->withCount('items')->get();
        if (!$packed_lists) {
            return response()->json(['error' => 'Not found'], 404);
        }


        return view('invoice.packing_details', compact(
            'packed_lists','poId','vendor_id'
        ));
    }

    public function store_invoice(Request $request)
    {
            $selectedpackids = $request->selectedpackids;
            $selected_po = $request->selected_po;
            $selected_vendor_id = $request->selected_vendor_id;
            
             $prefixSetting = PrefixSetting::where('id', 2)->first();
             try {

            $currentNumber = $prefixSetting->number;
            $inv_no = $prefixSetting->format . str_pad($currentNumber, 6, '0', STR_PAD_LEFT) .'/'.$prefixSetting->suffix;
            $InvoiceMaster = InvoiceMaster::create([
                'ref_no' => $inv_no,
                'inv_date' => date('Y-m-d'),
                'bill_to_details' => "",
                'ship_to_details' => "",
                'po_id' => $selected_po,
                'pack_ids' => implode(',',$selectedpackids),
                'vendor_id' => $selected_vendor_id,
                'created_by' => auth()->user()->id,
                'created_at' => now(),
            ]);
            //$ids = explode(',', $selectedpackids);
            PackingListMaster::whereIn('id', $selectedpackids)->update(['pack_status' => 2]);


            return response()->json([
                'success' => true,
                'message' => 'Invoice Generate successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the vendor: ' . $e->getMessage()
            ]);
        }

    }

        public function generateInvoice(Request $request)
        {
            $inv_id =  $request->id;
           $invoices_list = InvoiceMaster::find($inv_id);

           $extract_pack_list = explode(',',$invoices_list->pack_ids);

           $packed_items = PackingListItem::whereIn('packing_list_id', $extract_pack_list)
                    ->select('po_item_id', 'size', DB::raw('SUM(quantity) as total_quantity'),DB::raw('COUNT(DISTINCT id) as carton_counts'))
                    ->groupBy('po_item_id', 'size')
                    ->get();

            $invoice_item_details =array();      
             $po_details = PoMaster::find($invoices_list->po_id);  
             $unit_price = $po_details->po_unit_price;

             $article_info = json_decode($po_details->article_info,true);
            foreach($packed_items as $packed_item)
            {

            $total_amount = $packed_item->total_quantity * $unit_price;
            $discount = 0 ;
           $items_details = PoItems::find($packed_item->po_item_id);

              
              $invoice_item_details[] = array(
                        "description"=>$article_info['Article description'],
                         "hsn_code"=>$items_details->hsn_code,
                         "style"=>$article_info['ARTICLE'],
                         "color"=>$items_details->color,
                         "total_cartons"=>$packed_item->carton_counts,
                        "unit"=>$items_details->uom,
                        "size"=>$packed_item->size,
                        "qty"=>$packed_item->total_quantity,
                        "rate"=>$unit_price,
                        "amount"=>$total_amount,
                        "discount"=>$discount,
                        "taxable_value"=>$total_amount,
                    );
            }

            //echo "<pre>". print_r($invoice_item_details,true)."</pre>";
          
            $data['invoice_item_details']=$invoice_item_details;

            $pdf = Pdf::loadView('invoice.pdftemplate', $data)
             ->set_option('isHtml5ParserEnabled', true)
            ->set_option('isRemoteEnabled', true)
            ->setPaper('a4', 'portrait');
            return $pdf->stream('invoice.pdf');
        }


        public function master()
    {
        $page_data = [
            'page_title' => "Invoice Master",
            'page_main_title' => "Invoice Master",
            'page_child_title' => "Invoice Master",
            'isSuperAdmin' => $this->isSuperAdmin,
            'InvoiceMaster' => InvoiceMaster::where('status', 0)->get(),

        ];
       

        return view('invoice.master', $page_data);
    }

}