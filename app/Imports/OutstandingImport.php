<?php

namespace App\Imports;

use App\Models\CustomerMaster;
use App\Models\AgreementMaster;
use App\Models\ProjectMaster;
use App\Models\PlotMaster;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Validators\Failure;

class OutstandingImport implements ToModel, WithStartRow, WithValidation, SkipsOnFailure, WithEvents
{
    protected $sheetName;
    protected $rows = [];

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $this->sheetName = $event->getSheet()->getTitle();
            },
        ];
    }

    public function startRow(): int
    {
        return 3;
    }

    public function model(array $row)
    {
        $customerName = trim($row[2]);
        $totalCost = trim($row[4]);

        if (empty($customerName) || empty($totalCost)) {
            return null;
        }

        $this->rows[] = $row;

        $project = ProjectMaster::where('name', $this->sheetName)->first();
        if (!$project) {
            return null;
        }
        $projectId = $project->id;

        $customer = CustomerMaster::where('name', $customerName)
            ->where('project_id', $projectId)
            ->first();

        if (!$customer) {
            return null;
        }

        $existingAgreement = AgreementMaster::where('customer_id', $customer->id)
            ->where('project_id', $projectId)
            ->where('plot_id', $customer->plot_id)
            ->where('company_id', $project->company_id)
            ->first();

        if ($existingAgreement) {
            $existingAgreement->total_cost = $totalCost;
            $existingAgreement->save();
        } else {
            $agreement = new AgreementMaster();
            $agreement->customer_id = $customer->id;
            $agreement->project_id = $projectId;
            $agreement->plot_id = $customer->plot_id;
            $agreement->company_id = $project->company_id;
            $agreement->total_cost = $totalCost;
            $agreement->created_by = Auth::id();
            $agreement->created_at = now();
            $agreement->save();
        }

        return null;
    }

    public function rules(): array
    {
        return [
            '1' => 'required',
            '2' => 'required',
            '4' => 'required|numeric'
        ];
    }

    public function onFailure(Failure ...$failures) {}

    public function getRowCount()
    {
        return count($this->rows);
    }
}
