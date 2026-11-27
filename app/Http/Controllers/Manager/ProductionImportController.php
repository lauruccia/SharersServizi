<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ProductionImport;
use App\Models\ProductionRow;
use App\Services\ProductionImporter;
use Illuminate\Http\Request;
use RuntimeException;

/** Import del ReportDealer e correzioni sulle singole righe (flag "forza conteggio"). */
class ProductionImportController extends Controller
{
    public function index()
    {
        return view('manager.production.import', [
            'imports' => ProductionImport::query()->with('user')->latest('id')->limit(30)->get(),
        ]);
    }

    public function store(Request $request, ProductionImporter $importer)
    {
        $request->validate(['file' => ['required', 'file', 'max:15360', 'extensions:xls,xlsx,csv']], [
            'file.required' => 'Scegli il file ReportDealer da caricare.',
            'file.extensions' => 'Il file deve essere .xls, .xlsx o .csv.',
            'file.max' => 'Il file supera i 15 MB.',
        ]);

        $file = $request->file('file');
        try {
            $import = $importer->import($file->getRealPath(), $file->getClientOriginalName(), $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        ActivityLog::record('production.imported', $import, ['file' => $import->filename, 'created' => $import->rows_created, 'updated' => $import->rows_updated]);

        return redirect()->route('manager.production.import')->with('status',
            "Import completato: {$import->rows_created} nuove righe, {$import->rows_updated} aggiornate, {$import->rows_skipped} scartate.");
    }

    /** Flag "forza calcolo" su una riga: conta sempre / non conta mai / automatico. */
    public function force(Request $request, ProductionRow $row)
    {
        $data = $request->validate(['mode' => ['required', 'in:count,exclude,auto']]);
        $row->update(['force_count' => match ($data['mode']) {
            'count' => true,
            'exclude' => false,
            default => null,
        }]);
        ActivityLog::record('production.forced', $row, ['mode' => $data['mode'], 'contract' => $row->contract_id]);

        return back()->with('status', 'Contratto '.$row->contract_id.': conteggio '.match ($data['mode']) {
            'count' => 'forzato (conta sempre)',
            'exclude' => 'escluso (non conta)',
            default => 'tornato automatico',
        }.'.');
    }
}
