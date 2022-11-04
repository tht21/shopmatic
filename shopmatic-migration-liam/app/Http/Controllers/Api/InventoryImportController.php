<?php

namespace App\Http\Controllers\Api;

use App\Jobs\BulkUpdateInventoryJob;
use App\Models\ProductImportTask;
use App\Utilities\Excel\GenerateCsv;
use App\Utilities\Excel\VerifyCsv;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ExportExcelTask;
use App\Utilities\Excel\GenerateExcel;
use App\Jobs\InventoryDownloadCsvJob;
use App\Constants\ExcelType;




class InventoryImportController extends Controller
{
    /**
     * Upload Excel to server storage
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadCsv(Request $request)
    {
        /** @var Shop $shop */
        $isCreateInventory = $request->input('is_create_inventory');
        $shop = $request->session()->get('shop');

        if ($request->file('csv')->isValid()) {
            $excelFile = $request->file('csv');
            $fileName = 'inventories_bulk_edit_' . $shop->id . '_' . Carbon::now()->timestamp . '.csv';

            $task = ProductImportTask::whereJsonContains('settings', $excelFile->getClientOriginalName())->first();

            $verifyCsv = new VerifyCsv($excelFile);
            Excel::import($verifyCsv, $excelFile);

            if (is_null($task) && $verifyCsv->getType() === 'Csv\UpdateInventory') {

                Storage::disk('excel')->putFileAs('import', $excelFile, $fileName);

                $settings = [
                    'is_create_inventory' => $isCreateInventory,
                ];

                $task = ProductImportTask::create([
                    'shop_id' => $shop->id,
                    'user_id' => Auth::user()->id,
                    'source_type' => $verifyCsv->getType(),
                    'source' => 'import/' . $fileName,
                    'settings' => $settings
                ]);
                BulkUpdateInventoryJob::dispatch($task->fresh(), $request->getClientIp());
                return $this->respondWithMessage(null, 'CSV file will be processing shortly.');

            } elseif (!is_null($task)) {
                return $this->respondWithError('File uploaded already exist in our server.');
            }
        }

        return $this->respondWithError('File uploaded is not valid.');
    }


    /**
     * Download inventories to CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadCsv(Request $request)
    {
        /** @var Shop $shop */
        $shop = $request->session()->get('shop');
        if (empty($shop)) {
            return $this->respondBadRequestError('There is no shop selected');
        }

        // Insert into export excel task.
        $task = ExportExcelTask::create([
            'shop_id' => $shop->id,
            'user_id' => auth()->user()->id,
            'source_type' => ExcelType::DOWNLOAD_INVENTORY()->getValue(),
            'source' => $shop->id,
            'settings' => $request->except('now')
        ]);
        if ($request->input('now')) {
            $url = InventoryDownloadCsvJob::dispatchNow($task->fresh());

            return $this->respondWithMessage(['url' => $url], 'Csv file generated successfully.');
        }

        InventoryDownloadCsvJob::dispatch($task->fresh());

        return $this->respondWithMessage(null, 'Csv file will be generated shortly.');
    }
}
