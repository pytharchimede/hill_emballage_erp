<?php
require_once __DIR__ . '/config.php';
// Préférence TCPDF si disponible (via Composer ou copie locale)
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    @include_once __DIR__ . '/../../vendor/autoload.php';
}
@include_once __DIR__ . '/../lib/tcpdf/tcpdf.php';
if (!class_exists('TCPDF')) {
    // Fallback FPDF (essaie plusieurs emplacements connus). Recommandé: installer TCPDF pour un rendu fiable.
    $fpdfCandidates = [
        __DIR__ . '/../lib/fpdf/fpdf.php',
        __DIR__ . '/../../vendor/setasign/fpdf/fpdf.php',
        __DIR__ . '/../../vendor/setasign/fpdf/src/Fpdf/Fpdf.php',
    ];
    foreach ($fpdfCandidates as $path) {
        if (is_file($path)) {
            @include_once $path;
            break;
        }
    }
}

if (class_exists('TCPDF')) {
    $code = <<<'PHP'
class PdfDoc extends TCPDF
{
    public $titleText = APP_NAME;
    public $subTitle = '';
    public $company = APP_NAME;
    public $companyInfo = '';
    public $accent = [255, 215, 0];
    public $accent2 = [80, 80, 80];

    public function Header()
    {
        $this->SetY(6);
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(0, 8, $this->titleText, 0, 1, 'L');
        if ($this->subTitle) {
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(60, 60, 60);
            $this->Cell(0, 6, $this->subTitle, 0, 1, 'L');
        }
        $this->SetY(6);
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(90, 90, 90);
        $this->Cell(0, 6, $this->company, 0, 2, 'R');
        $this->SetFont('helvetica', '', 8);
        $this->MultiCell(0, 4, $this->companyInfo, 0, 'R', false, 1, '', 14);
        $this->Ln(2);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 10, 'Généré le ' . date('d/m/Y H:i') . ' — ' . APP_NAME . ' — Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }

    public function sectionTitle($text)
    {
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(60, 60, 60);
        $this->Cell(0, 8, $text, 0, 1, 'L');
    }

    public function kv($label, $value)
    {
        $this->SetFont('helvetica', 'B', 10);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(40, 6, $label . ' : ', 0, 0, 'L');
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(20, 20, 20);
        $this->Cell(0, 6, (string)$value, 0, 1, 'L');
    }

    public function tableHeader($headers)
    {
        $this->SetFillColor(245);
        $this->SetTextColor(30, 30, 30);
        $this->SetFont('helvetica', 'B', 10);
        foreach ($headers as $col) {
            list($w, $txt) = $col;
            $this->Cell($w, 8, (string)$txt, 0, 0, 'L', true);
        }
        $this->Ln();
    }

    public function tableRow($cells)
    {
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(50, 50, 50);
        foreach ($cells as $col) {
            list($w, $txt) = $col;
            $this->Cell($w, 6, (string)$txt, 0, 0, 'L');
        }
        $this->Ln();
    }
}
PHP;
    eval($code);
} else {
    // Si FPDF n'est pas disponible, on définit un shim minimal pour éviter les erreurs d'analyse/exec.
    if (!class_exists('FPDF')) {
        // Shim très basique: absorbe les appels de méthodes, expose la largeur de page $w.
        // À utiliser uniquement comme secours; installez TCPDF ou FPDF en production.
        class FPDF
        {
            /** largeur page A4 par défaut (mm) */
            public $w = 210;
            public function __call($name, $arguments)
            { /* no-op */
            }
        }
    }
    class PdfDoc extends FPDF
    {
        public $titleText = APP_NAME;
        public $subTitle = '';
        public $company = APP_NAME;
        public $companyInfo = '';
        public $accent = [255, 215, 0]; // jaune premium
        public $accent2 = [80, 80, 80]; // gris anthracite

        function Header()
        {
            // Titre sur fond simulé (pas de rectangle plein pour compat minimal)
            $this->SetFillColor($this->accent[0], $this->accent[1], $this->accent[2]);
            $this->SetTextColor(40, 40, 40);
            $this->SetFont('helvetica', 'B', 16);
            $this->SetXY(10, 6);
            $this->Cell(0, 8, $this->titleText, 0, 1, 'L');
            // Sous-titre
            if ($this->subTitle) {
                $this->SetFont('helvetica', '', 10);
                $this->SetXY(10, 14);
                $this->Cell(0, 6, $this->subTitle, 0, 0, 'L');
            }
            // Bloc entreprise (texte à droite)
            $this->SetTextColor(90, 90, 90);
            $this->SetFont('helvetica', 'B', 11);
            $this->SetXY($this->w - 80, 6);
            $this->Cell(76, 6, $this->company, 0, 2, 'R');
            $this->SetFont('helvetica', '', 8);
            $this->MultiCell(76, 4, $this->companyInfo, 0, 'R');
            $this->Ln(4);
            $this->SetY(28);
        }

        function Footer()
        {
            $this->SetY(-18);
            $this->SetDrawColor(220, 220, 220);
            $this->SetLineWidth(0.2);
            // Ligne séparatrice
            $this->_out("% footer line\n");
            $this->SetTextColor(120, 120, 120);
            $this->SetFont('helvetica', '', 8);
            $this->Cell(0, 5, 'Généré le ' . date('d/m/Y H:i') . ' — ' . APP_NAME, 0, 0, 'L');
        }

        function sectionTitle($text)
        {
            $this->SetTextColor(60, 60, 60);
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 8, $text, 0, 1, 'L');
        }

        function kv($label, $value)
        {
            $this->SetFont('helvetica', 'B', 10);
            $this->SetTextColor(80, 80, 80);
            $this->Cell(40, 6, $label . ' : ', 0, 0, 'L');
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(20, 20, 20);
            $this->Cell(0, 6, (string)$value, 0, 1, 'L');
        }

        function tableHeader($headers)
        {
            $this->SetFillColor(245, 245, 245);
            $this->SetTextColor(30, 30, 30);
            $this->SetFont('helvetica', 'B', 10);
            foreach ($headers as $col) {
                list($w, $txt) = $col;
                $this->Cell($w, 8, (string)$txt, 0, 0, 'L', true);
            }
            $this->Ln();
        }

        function tableRow($cells)
        {
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(50, 50, 50);
            foreach ($cells as $col) {
                list($w, $txt) = $col;
                $this->Cell($w, 6, (string)$txt, 0, 0, 'L');
            }
            $this->Ln();
        }
    }
}

function pdf_output_headers($filename)
{
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename=' . $filename);
    header('X-Content-Type-Options: nosniff');
    if (!class_exists('TCPDF')) {
        header('X-PDF-Engine: FPDF-fallback');
    } else {
        header('X-PDF-Engine: TCPDF');
    }
}
