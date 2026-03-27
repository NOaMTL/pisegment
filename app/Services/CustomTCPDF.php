<?php

namespace App\Services;

use TCPDF;

class CustomTCPDF extends TCPDF
{
    /**
     * Éléments du header personnalisables
     */
    public string $headerLeft = '';

    public string $headerCenter = '';

    public string $headerRight = '';

    /**
     * Chemin du logo pour le footer
     */
    public string $footerLogo = '';

    /**
     * Header personnalisé
     * Affiche 3 zones : gauche, centre, droite
     */
    public function Header(): void
    {
        // Définir la police pour le header
        $this->SetFont('helvetica', 'B', 10);

        // Ligne du haut avec les 3 éléments
        $headerY = 10;
        $this->SetY($headerY);

        // Header gauche
        $this->SetX(15);
        $this->Cell(60, 6, $this->headerLeft, 0, 0, 'L');

        // Header centre
        $this->SetX(75);
        $this->Cell(60, 6, $this->headerCenter, 0, 0, 'C');

        // Header droite
        $this->SetX(135);
        $this->Cell(60, 6, $this->headerRight, 0, 0, 'R');

        // Ligne de séparation
        $this->SetY(18);
        $this->SetLineWidth(0.5);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(15, 18, 195, 18);
    }

    /**
     * Footer personnalisé
     * Affiche le logo en bas à gauche et la pagination en bas à droite
     */
    public function Footer(): void
    {
        // Position à 15 mm du bas
        $this->SetY(-15);

        // Logo en bas à gauche (si défini)
        if ($this->footerLogo && file_exists($this->footerLogo)) {
            $this->Image($this->footerLogo, 15, $this->GetY(), 30, 0, '', '', '', false, 300, '', false, false, 0);
        }

        // Numéro de page en bas à droite
        $this->SetFont('helvetica', 'I', 8);
        $pageText = sprintf('Page %d / %d', $this->getAliasNumPage(), $this->getAliasNbPages());
        $this->Cell(0, 10, $pageText, 0, 0, 'R');
    }
}
