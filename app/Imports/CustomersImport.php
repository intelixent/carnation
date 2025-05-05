<?php

namespace App\Imports;

use App\Models\CustomerMaster;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CustomersImport implements ToModel, WithStartRow, WithValidation, SkipsOnFailure
{
    private $rowCount = 0;

    public function startRow(): int
    {
        return 6;
    }

    public function model(array $row)
    {
        $this->rowCount++;

        if (empty($row[3])) {
            return null;
        }

        $createdDate = null;
        if (!empty($row[1])) {
            try {
                $createdDate = Carbon::createFromFormat('d-M-y', $row[1])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $createdDate = now();
            }
        } else {
            $createdDate = now();
        }

        $customerName = trim($row[4]);

        $existingCustomer = CustomerMaster::where('customer_no', $row[3])->where('name', $customerName)
        ->where('father_husband_name', $row[5])->where('contact_address', $row[6])->where('mobile_number', $row[9])
        ->where('email', $row[10])->where('residency_status', $row[11])
        ->first();

        if ($existingCustomer) {
            return null;
        }

        return new CustomerMaster([
            'customer_no' => $row[3],
            'name' => $customerName,
            'father_husband_name' => $row[5],
            'contact_address' => $row[6],
            'mobile_number' => $row[9],
            'email' => $row[10],
            'residency_status' => $row[11],
            'created_by' => Auth::id(),
            'created_at' => $createdDate,
        ]);
    }

    public function rules(): array
    {
        return [
            '3' => 'required',
            '4' => 'required',
        ];
    }

    public function onFailure(Failure ...$failures) {}

    public function getRowCount()
    {
        return $this->rowCount;
    }
}
