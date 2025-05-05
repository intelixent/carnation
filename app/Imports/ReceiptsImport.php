<?php

namespace App\Imports;

use App\Models\ProjectMaster;
use App\Models\PlotMaster;
use App\Models\CustomerMaster;
use App\Models\ReceiptMaster;
use App\Models\PaymentFor;
use App\Models\PaymentMode;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;

class ReceiptsImport implements ToCollection, WithStartRow, SkipsOnFailure
{
    private $rowCount = 0;
    private $processedReceipts = [];
    private $paymentForMap = [
        'REGISTRATION' => 3,
        'BOOKING ADVANCE' => 1,
        'AGREEMENT' => 2,
        'CONSTRUCTION' => 4,
        'ACCOUNT SETTLED' => 5,
        'PAYMENT SHEDULE' => 6
    ];

    private $paymentModeMap = [
        'BY NET BANKING - NEFT' => 3,
        'BY UPI' => 5,
        'BY NET BANKING - RTGS' => 4,
        'BY NET BANKING - IMPS' => 3,
        'BY DD' => 2,
        'BY CHEQUE' => 1
    ];

    public function startRow(): int
    {
        return 13;
    }

    public function collection(Collection $rows)
    {
        $plot = null;
        $customer = null;
        $site = null;

        foreach ($rows as $index => $row) {
            if (empty($row[0]) && empty($row[1]) && empty($row[2])) {
                continue;
            }

            if ((isset($row[0]) && strpos($row[0], '----') !== false) ||
                (isset($row[1]) && strpos($row[1], '----') !== false)
            ) {
                $headerText = '';
                if (isset($row[0]) && strpos($row[0], '----') !== false) {
                    $headerText = $row[0];

                    if ($index > 0 && isset($rows[$index - 1][0]) && $rows[$index - 1][0] != trim('Customer WiseTotal :')) {
                        $site = $rows[$index - 1][0];
                    }
                } else if (isset($row[1]) && strpos($row[1], '----') !== false) {
                    $headerText = $row[1];

                    if ($index > 0 && isset($rows[$index - 1][1]) && $rows[$index - 1][1] != trim('Customer WiseTotal :')) {
                        $site = $rows[$index - 1][1];
                    }
                    elseif ($index > 0 && isset($rows[$index - 1][0]) && $rows[$index - 1][0] != trim('Customer WiseTotal :')) {
                        $site = $rows[$index - 1][0];
                    }
                }

                list($plot, $customer) = explode('----', $headerText);
                $plot = trim($plot);
                $customer = trim($customer);
                continue;
            }

            if (!empty($row[3]) && isset($row[10]) && is_numeric($row[10])) {
                $paymentForValue = isset($row[6]) ? strtoupper(trim($row[6])) : '';
                $paymentModeValue = isset($row[8]) ? strtoupper(trim($row[8])) : '';
                $paymentByValue = isset($row[7]) ? strtoupper(trim($row[7])) : '';

                $this->processReceipt([
                    'plot' => $plot,
                    'site' => $site,
                    'customer' => $customer,
                    'receipt_no' => $row[3],
                    'date' => $row[1],
                    'amount' => $row[10],
                    'payment_for' => $paymentForValue,
                    'payment_mode' => $paymentModeValue,
                    'payment_by' => $paymentByValue,
                    'remarks' => $row[14] ?? null,
                ]);

                $this->rowCount++;
            }
        }
    }

    private function processReceipt($receiptData)
    {
        try {
            if (
                empty($receiptData['plot']) || empty($receiptData['site']) ||
                empty($receiptData['customer']) || empty($receiptData['receipt_no'])
            ) {
                Log::warning('Missing required receipt data', ['data' => $receiptData]);
                return null;
            }

            Log::info('receipt date', ['date' => $receiptData['date']]);

            $receiptDate = null;
            if (!empty($receiptData['date'])) {
                try {
                    if (is_numeric($receiptData['date'])) {
                        $excelBaseDate = Carbon::createFromDate(1899, 12, 30);
                        $receiptDate = $excelBaseDate->copy()->addDays((int)$receiptData['date'])->format('Y-m-d');
                    } else {
                        $receiptDate = Carbon::parse($receiptData['date'])->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    $receiptDate = now()->format('Y-m-d');
                    Log::warning('Could not parse date: ' . $receiptData['date'], ['error' => $e->getMessage()]);
                }
            } else {
                $receiptDate = now()->format('Y-m-d');
            }

            $project = ProjectMaster::where('name', $receiptData['site'])->first();
            if (!$project) {
                $project = ProjectMaster::create([
                    'name' => $receiptData['site'],
                    'company_id' => Auth::user()->company_id,
                    'created_by' => Auth::id(),
                    'created_at' => now(),
                ]);
                Log::info('Created new project', ['name' => $receiptData['site'], 'id' => $project->id]);
            }

            $plot = PlotMaster::where('plot_no', $receiptData['plot'])
                ->where('project_id', $project->id)
                ->first();

            if (!$plot) {
                $plot = PlotMaster::create([
                    'company_id' => Auth::user()->company_id,
                    'project_id' => $project->id,
                    'plot_no' => $receiptData['plot'],
                    'created_by' => Auth::id(),
                    'created_at' => now()
                ]);
                Log::info('Created new plot', ['plot_no' => $receiptData['plot'], 'id' => $plot->id]);
            }

            $customer = CustomerMaster::whereRaw('TRIM(name) LIKE ?', [trim($receiptData['customer'])])
                ->first();

            if (!$customer) {
                $customer = CustomerMaster::create([
                    'name' => trim($receiptData['customer']),
                    'company_id' => Auth::user()->company_id,
                    'project_id' => $project->id,
                    'plot_id' => $plot->id,
                    'created_by' => Auth::id(),
                    'created_at' => now()
                ]);
                Log::info('Created new customer', ['name' => $receiptData['customer'], 'id' => $customer->id]);
            } else {
                $customer->update([
                    'project_id' => $project->id,
                    'plot_id' => $plot->id
                ]);
                Log::info('Updated existing customer', ['name' => $receiptData['customer'], 'id' => $customer->id]);
            }

            $paymentForValue = isset($receiptData['payment_for']) ? $receiptData['payment_for'] : '';
            $paymentForId = isset($this->paymentForMap[$paymentForValue]) ? $this->paymentForMap[$paymentForValue] : null;

            $paymentModeValue = isset($receiptData['payment_mode']) ? $receiptData['payment_mode'] : '';
            $paymentModeId = isset($this->paymentModeMap[$paymentModeValue]) ? $this->paymentModeMap[$paymentModeValue] : null;

            $receipt = ReceiptMaster::create([
                'company_id' => Auth::user()->company_id,
                'project_id' => $project->id,
                'plot_id' => $plot->id,
                'customer_id' => $customer->id,
                'receipt_type' => 'old',
                'receipt_date' => $receiptDate,
                'receipt_no' => $receiptData['receipt_no'],
                'payment_for' => $paymentForId,
                'payment_by' => $receiptData['payment_by'],
                'payment_mode' => $paymentModeId,
                'payment_amount' => $receiptData['amount'],
                'payment_details' => json_encode([
                    'ref_number' => '',
                    'bank_name' => '',
                ]),
                'notes' => $receiptData['remarks'],
                'created_by' => Auth::id(),
                'created_at' => now(),
                'created_month' => now()->format('m'),
                'created_year' => now()->format('Y'),
            ]);

            $this->processedReceipts[] = $receiptData['receipt_no'];
            Log::info('Created receipt', [
                'receipt_no' => $receiptData['receipt_no'],
                'id' => $receipt->id,
                'converted_date' => $receiptDate
            ]);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Receipt import error: ' . $e->getMessage(), [
                'receipt_data' => $receiptData,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function onFailure(Failure ...$failures) {}

    public function getRowCount()
    {
        return $this->rowCount;
    }

    public function getProcessedReceipts()
    {
        return $this->processedReceipts;
    }
}
