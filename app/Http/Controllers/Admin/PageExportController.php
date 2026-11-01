<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Export\PageCsvExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PageExportController extends Controller
{
    /**
     * Hands the screen's filters (or the bulk selection's ids) to the exporter
     * and streams the result. `template=1` returns only the header row for the
     * import modal's "Download template" link.
     */
    public function export(Request $request, PageCsvExporter $exporter): StreamedResponse
    {
        return $exporter->export(
            ids: array_values(array_map('intval', (array) $request->query('ids', []))),
            search: trim((string) $request->query('search', '')),
            status: (string) $request->query('status', ''),
            type: (string) $request->query('type', ''),
            template: $request->boolean('template'),
        );
    }
}
