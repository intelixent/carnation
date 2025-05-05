<?php

namespace App\Imports;

use App\Models\LeadMaster;
use App\Models\CityMaster;
use App\Models\SourceMaster;
use App\Models\LeadLogs;
use App\Models\LeadStatusMaster;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;

class LeadsImport implements ToModel, WithStartRow, WithValidation, SkipsOnFailure
{
    private $rowCount = 0;

    public function startRow(): int
    {
        return 11;
    }

    public function model(array $row)
    {
        if (empty($row[2]) || empty($row[7])) {
            return null;
        }

        $existingLead = LeadMaster::where('lead_no', $row[2])
            ->where('customer_name', $row[7])
            ->where('mobile_number', $row[12] ?? '')
            ->where('email', $row[15] ?? '')
            ->first();

        if ($existingLead) {
            return null;
        }

        $createdDate = null;
        if (!empty($row[6])) {
            try {
                $createdDate = Carbon::createFromFormat('d-M-Y', $row[6])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $createdDate = now();
            }
        } else {
            $createdDate = now();
        }

        $cityId = null;
        if (!empty($row[11])) {
            $city = CityMaster::firstOrCreate(
                ['name' => $row[11]],
                ['created_by' => Auth::id(), 'created_at' => now()]
            );
            $cityId = $city->id;
        }

        $sourceId = null;
        if (!empty($row[18])) {
            $source = SourceMaster::firstOrCreate(
                ['name' => $row[18]],
                ['created_by' => Auth::id(), 'created_at' => now()]
            );
            $sourceId = $source->id;
        }

        $lead = LeadMaster::create([
            'lead_no' => $row[2],
            'source_id' => $sourceId,
            'city_id' => $cityId,
            'customer_name' => $row[7],
            'mobile_number' => $row[12],
            'email' => $row[15] ?? null,
            'created_by' => Auth::id(),
            'created_at' => $createdDate,
        ]);

        $leadStatusId = null;
        if (!empty($row[17])) {
            $leadStatus = LeadStatusMaster::firstOrCreate(
                ['name' => $row[17]],
                ['created_by' => Auth::id(), 'created_at' => now()]
            );
            $leadStatusId = $leadStatus->id;
        }

        LeadLogs::create([
            'lead_id' => $lead->id,
            'lead_status' => $leadStatusId,
            'remarks' => $row[14] ?? null,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        $this->rowCount++;
    }

    public function rules(): array
    {
        return [
            '2' => 'required',
            '7' => 'required',
        ];
    }

    public function onFailure(Failure ...$failures) {}

    public function getRowCount()
    {
        return $this->rowCount;
    }
}
