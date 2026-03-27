<?php

namespace App\Http\Controllers;

use App\Services\CustomTCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PdfGeneratorController extends Controller
{
    /**
     * Générer un PDF à partir d'une requête SQL
     *
     * Usage:
     * GET /api/generate-pdf?sql=SELECT * FROM customers LIMIT 10
     * POST /api/generate-pdf avec body: { "sql": "SELECT...", "headerLeft": "...", ... }
     */
    public function generate(Request $request)
    {
        // Valider la requête
        $validated = $request->validate([
            'sql' => 'required|string',
            'headerLeft' => 'nullable|string|max:100',
            'headerCenter' => 'nullable|string|max:100',
            'headerRight' => 'nullable|string|max:100',
            'logoPath' => 'nullable|string',
            'orientation' => 'nullable|string|in:P,L', // Portrait ou Landscape
            'title' => 'nullable|string|max:200',
        ]);

        try {
            // Exécuter la requête SQL
            $sqlQuery = $validated['sql'];
            $results = DB::select($sqlQuery);

            if (empty($results)) {
                return response()->json(['error' => 'Aucune donnée retournée par la requête SQL'], 400);
            }

            // Convertir les résultats en tableau associatif
            $data = array_map(fn ($row) => (array) $row, $results);

            // Récupérer les colonnes (clés du premier enregistrement)
            $columns = array_keys($data[0]);

            // Créer une instance de PDF personnalisée
            $pdf = new CustomTCPDF(
                $validated['orientation'] ?? 'P', // Portrait par défaut
                'mm',
                'A4',
                true,
                'UTF-8',
                false
            );

            // Configurer le document
            $pdf->SetCreator('Laravel PDF Generator');
            $pdf->SetAuthor('Application');
            $pdf->SetTitle($validated['title'] ?? 'Rapport PDF');
            $pdf->SetSubject('Données exportées');

            // Configurer les marges
            $pdf->SetMargins(15, 25, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(10);
            $pdf->SetAutoPageBreak(true, 20);

            // Définir les éléments du header
            $pdf->headerLeft = $validated['headerLeft'] ?? 'Document';
            $pdf->headerCenter = $validated['headerCenter'] ?? date('d/m/Y');
            $pdf->headerRight = $validated['headerRight'] ?? 'Rapport';

            // Définir le logo du footer
            $logoPath = $validated['logoPath'] ?? public_path('logo.png');
            if (file_exists($logoPath)) {
                $pdf->footerLogo = $logoPath;
            }

            // Ajouter une page
            $pdf->AddPage();

            // Titre du document (optionnel)
            if (! empty($validated['title'])) {
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 8, $validated['title'], 0, 1, 'C');
                $pdf->Ln(3);
            }

            // Générer le tableau
            $this->generateTable($pdf, $columns, $data);

            // Générer le PDF
            $pdfOutput = $pdf->Output('rapport.pdf', 'S'); // S = retourner en tant que string

            // Retourner le PDF en tant que réponse
            return response($pdfOutput, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="rapport.pdf"');

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la génération du PDF',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Générer un tableau dans le PDF
     */
    protected function generateTable(CustomTCPDF $pdf, array $columns, array $data): void
    {
        // Calculer la largeur de chaque colonne
        $pageWidth = $pdf->getPageWidth() - 30; // Largeur de la page moins les marges
        $columnWidth = $pageWidth / count($columns);

        // Limiter la largeur à un maximum pour éviter des colonnes trop larges
        $maxColumnWidth = 50;
        if ($columnWidth > $maxColumnWidth) {
            $columnWidth = $maxColumnWidth;
        }

        // Header du tableau
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(66, 133, 244); // Bleu
        $pdf->SetTextColor(255, 255, 255); // Blanc
        $pdf->SetDrawColor(50, 50, 50);
        $pdf->SetLineWidth(0.3);

        // Afficher les en-têtes de colonnes
        foreach ($columns as $column) {
            // Formater le nom de la colonne
            $headerName = str_replace('_', ' ', $column);
            $headerName = ucwords($headerName);

            $pdf->Cell($columnWidth, 7, $headerName, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Corps du tableau
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(245, 245, 245);

        $fill = false;
        foreach ($data as $row) {
            // Vérifier si une nouvelle page est nécessaire
            if ($pdf->GetY() > 260) {
                $pdf->AddPage();

                // Réafficher les en-têtes de colonnes sur la nouvelle page
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetTextColor(255, 255, 255);
                foreach ($columns as $column) {
                    $headerName = str_replace('_', ' ', $column);
                    $headerName = ucwords($headerName);
                    $pdf->Cell($columnWidth, 7, $headerName, 1, 0, 'C', true);
                }
                $pdf->Ln();

                // Réinitialiser la police pour le corps
                $pdf->SetFont('helvetica', '', 8);
                $pdf->SetTextColor(0, 0, 0);
            }

            foreach ($columns as $column) {
                $value = $row[$column] ?? '';

                // Formater les valeurs
                $value = $this->formatCellValue($value);

                $pdf->Cell($columnWidth, 6, $value, 1, 0, 'L', $fill);
            }
            $pdf->Ln();

            $fill = ! $fill; // Alterner les couleurs de fond
        }
    }

    /**
     * Formater une valeur de cellule
     */
    protected function formatCellValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        // Si c'est un booléen
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        // Si c'est un nombre décimal, formater avec 2 décimales
        if (is_numeric($value) && str_contains((string) $value, '.')) {
            return number_format((float) $value, 2, ',', ' ');
        }

        // Limiter la longueur du texte pour éviter le débordement
        $stringValue = (string) $value;
        if (mb_strlen($stringValue) > 50) {
            return mb_substr($stringValue, 0, 47).'...';
        }

        return $stringValue;
    }

    /**
     * Exemple : Générer un PDF de démonstration
     */
    public function demo(Request $request)
    {
        // Requête SQL par défaut
        $sql = 'SELECT id, first_name, last_name, email, city, average_balance, has_life_insurance 
                FROM customers 
                LIMIT 50';

        // Créer une requête avec les paramètres par défaut
        $demoRequest = new Request([
            'sql' => $sql,
            'headerLeft' => 'Rapport Clients',
            'headerCenter' => 'Données du '.date('d/m/Y'),
            'headerRight' => 'Confidentiel',
            'title' => 'Liste des Clients - Export PDF',
            'orientation' => 'L', // Landscape pour plus de colonnes
        ]);

        return $this->generate($demoRequest);
    }
}
