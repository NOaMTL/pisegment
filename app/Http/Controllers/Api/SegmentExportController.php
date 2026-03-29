<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SegmentBuilder\SegmentQueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SegmentExportController extends Controller
{
    /**
     * Export segment results to CSV or Excel
     */
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'condition_groups' => 'required|array',
            'condition_groups.*.logical_operator' => 'required|string|in:AND,OR',
            'condition_groups.*.next_operator' => 'nullable|string|in:AND,OR',
            'condition_groups.*.conditions' => 'required|array',
            'condition_groups.*.conditions.*.field' => 'required|string',
            'condition_groups.*.conditions.*.operator' => 'required|string',
            'condition_groups.*.conditions.*.value' => 'nullable',
            'format' => 'required|string|in:csv,excel',
        ]);

        $builder = SegmentQueryBuilder::fromArray($validated);
        $query = $builder->build();

        // Limit to prevent memory issues
        $customers = $query->limit(10000)->get();

        if ($validated['format'] === 'csv') {
            return $this->exportCsv($customers);
        }

        // For Excel, we'll use CSV for now as well
        // TODO: Implement proper Excel export with PhpSpreadsheet
        return $this->exportCsv($customers);
    }

    private function exportCsv($customers)
    {
        $filename = 'segment-export-'.date('Y-m-d-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($customers) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'ID',
                'Nom complet',
                'Âge',
                'Ville',
                'Solde moyen',
                'Assurance-vie',
                'Crédit immo',
                'Crédit auto',
            ], ';');

            // Rows
            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->id,
                    $customer->full_name,
                    $customer->age,
                    $customer->city,
                    number_format($customer->average_balance, 2, ',', ' '),
                    $customer->has_life_insurance ? 'Oui' : 'Non',
                    $customer->has_home_loan ? 'Oui' : 'Non',
                    $customer->has_car_loan ? 'Oui' : 'Non',
                ], ';');
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}
