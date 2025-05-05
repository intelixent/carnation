<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\VendorMaster;

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
}
