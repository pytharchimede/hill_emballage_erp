<?php
require_once __DIR__ . '/config.php';

// Charger TCPDF via vendor autoload s'il existe
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    @include_once __DIR__ . '/../../vendor/autoload.php';
}
// Ou via copie locale dans app/lib/tcpdf
if (!class_exists('TCPDF') && file_exists(__DIR__ . '/../lib/tcpdf/tcpdf.php')) {
    require_once __DIR__ . '/../lib/tcpdf/tcpdf.php';
}

if (!class_exists('TCPDF')) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h3>TCPDF manquant</h3>';
    echo '<p>Installez TCPDF avec Composer:</p>';
    echo '<pre>composer require tecnickcom/tcpdf</pre>';
    echo '<p>Ou copiez la librairie dans <code>app/lib/tcpdf</code> (fichier tcpdf.php).</p>';
    exit;
}

class PdfDoc extends TCPDF
{
    public $titleText = APP_NAME;
    public $subTitle = '';
    public $company = APP_NAME;
    public $companyInfo = '';

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
        // Entreprise à droite
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
        $this->SetFillColor(245, 245, 245);
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
