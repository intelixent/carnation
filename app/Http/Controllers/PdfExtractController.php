<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\VendorMaster;
use App\Models\PoMaster;
use App\Models\PoItems;
use App\Models\PrefixSetting;
use Illuminate\Support\Facades\Http;


class PdfExtractController extends BaseController
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
        $page_data = [
            'page_title' => "PO Master",
            'page_main_title' => "PO Master",
             'page_child_title' => "Master",
            'isSuperAdmin' => $this->isSuperAdmin,
           
        ];
         $page_data['vendors'] = VendorMaster::whereIn('status', [0, 1])
            ->orderBy('id', 'asc')
            ->get();

        return view('pdf_extract.index', $page_data);
    }

    public function add()
    {
        $page_data = [
            'page_title' => "Add",
            'page_main_title' => "PDF Extract",
        ];

        $page_data['vendors'] = VendorMaster::whereIn('status', [0, 1])
            ->orderBy('id', 'asc')
            ->get();

        return view('pdf_extract.add', $page_data);
    }

    public function processpdf(Request $request)
    {
        $company = $request->input('company');
        $pdfBase64 = $request->input('pdf_base64');

        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                ])->post('http://localhost:8000/process', [
                    'company' => $company,
                    'pdf_base64' => $pdfBase64,
                ]);

        if ($response->successful()) {
            $res_data = $response->json(); // assuming the API returns JSON
           
            $data['po_details']= $res_data['data']['po_details'];
            $data['article_info']= $res_data['data']['article_info'];
            $data['po_items']= $res_data['data']['po_items'];
            //print_r($data);
            $html = view('pdf_extract.pdf_response_view', compact('data'))->render();
            return response()->json(['status'=>true,'html' => $html]);
        } else {
            return response()->json(['error' => "Error Pdf"], 500);
        }
    }

     public function store(Request $request)
    {
        try {
            $prefixSetting = PrefixSetting::where('id', 1)->first();

            if (!$prefixSetting) {
                throw new \Exception('Po prefix setting not found');
            }

            $currentNumber = $prefixSetting->number;
            $poNo = $prefixSetting->format . str_pad($currentNumber, 5, '0', STR_PAD_LEFT);

          $po_details = json_decode($request->input('po_details'), true);
          $article_details = $request->input('article_details');
          $po_items = json_decode($request->input('po_items'), true);

        //   print_r($po_items);

            $poData = [
                'vendor_id' => "Jack Jone",
                'po_ref_num' => $poNo,
                'po_num' => $po_details['PO Number'],
                'po_date' => $po_details['PO Date'],
                'goods_ready_date' => $po_details['Goods Ready Date'],
                'mrp' => $po_details['MRP'],
                'vcp' => $po_details['VCP'],
                'colors' => $po_details['Colors'],
                'vendor_del_adr' => $po_details['Delivery Address'],
                'vendor_com_adr' => $po_details['Communication Address'],
                'vendor_gst' => $po_details['GSTIN'],
                'vendor_cin' => $po_details['CIN'],
                'article_info' => $article_details,
                'po_unit_price' => $request->input('po_unit_price'),
                'po_qty' => $request->input('po_qty'),
                'created_by' => auth()->user()->id,
                'created_at' => now(),
            ];

            $pomaster = PoMaster::create($poData);

            $prefixSetting->number = $currentNumber + 1;
            $prefixSetting->save();

            foreach($po_items as $po_item):
                $poitemData = [
                    'po_id' => $pomaster->id,
                    'item_sno' => $po_item['item_sno'],
                    'item_article_number' => $po_item['article_number'],
                    'item_id_color' => $po_item['artcicle_id_color'],
                    'size_in_years' => $po_item['size_years'],
                    'qty' => $po_item['quatity_uom'],
                    'uom' => $po_item['quatity_uom'],
                    'igst_taxable_value' => $po_item['igst_taxable_value'],
                    'igst_per' => $po_item['igst_percentage'],
                    'mrp' => $po_item['mrp'],
                    'ean_code' => $po_item['ean_code'],
                    'hsn_code' => $po_item['hsn_code'],
                    'created_at' => now(),
                    'created_by' => auth()->user()->id,
                ];

                PoItems::create($poitemData);

            endforeach;
            

            return response()->json([
                'success' => true,
                'message' => 'PO Stored successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding the lead: ' . $e->getMessage()
            ]);
        }
    }
}
