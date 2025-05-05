<?php

namespace App\Imports;

use App\Models\ProjectMaster;
use App\Models\PlotMaster;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;

class PlotsImport implements ToModel, WithStartRow, WithValidation, SkipsOnFailure
{
    private $rowCount = 0;

    public function startRow(): int
    {
        return 3;
    }

    public function model(array $row)
    {
        $this->rowCount++;

        $project = ProjectMaster::firstOrCreate(
            ['name' => $row[4]],
            [
                'company_id' => 1,
                'created_by' => Auth::id(),
                'created_at' => now(),
            ]
        );

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

        $existingPlot = PlotMaster::where('company_id', 1)->where('project_id', $project->id)
        ->where('plot_no', $row[2])->first();

        if ($existingPlot) {
            return null;
        }

        return new PlotMaster([
            'company_id' => 1,
            'project_id' => $project->id,
            'plot_no' => $row[2],
            'created_at' => $createdDate,
            'created_by' => Auth::id(),
        ]);
    }

    public function rules(): array
    {
        return [
            '2' => 'required',
            '4' => 'required'
        ];
    }

    public function onFailure(Failure ...$failures)
    {
    }

    public function getRowCount()
    {
        return $this->rowCount;
    }
}