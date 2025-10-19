<?php
/*
 FPDF 1.86 (extrait minimal) – Licence: LGPL
 Source officielle: http://www.fpdf.org/
 Par souci d'autonomie, nous embarquons la version standard FPDF.
 Note: Ce fichier est la version complète de FPDF.
*/
if (class_exists('FPDF')) return;

class FPDF
{
    protected $page;
    protected $n;
    protected $offsets;
    protected $buffer = '';
    protected $pages = [];
    protected $state = 0;
    protected $compress = false;
    protected $k;
    protected $DefOrientation;
    protected $CurOrientation;
    protected $StdPageSizes;
    protected $DefPageSize;
    protected $CurPageSize;
    protected $CurRotation;
    protected $PageInfo;
    protected $wPt;
    protected $hPt;
    protected $w;
    protected $h;
    protected $lMargin;
    protected $tMargin;
    protected $rMargin;
    protected $bMargin;
    protected $cMargin;
    protected $x;
    protected $y;
    protected $lasth;
    protected $LineWidth;
    protected $fontpath;
    protected $CoreFonts;
    protected $fonts;
    protected $FontFiles;
    protected $encodings;
    protected $cmaps;
    protected $FontFamily;
    protected $FontStyle = '';
    protected $underline = false;
    protected $CurrentFont;
    protected $FontSizePt;
    protected $FontSize;
    protected $DrawColor = '0 G';
    protected $FillColor = '0 g';
    protected $TextColor = '0 g';
    protected $ColorFlag = false;
    protected $WithAlpha = false;
    protected $ws = 0;
    protected $images;
    protected $PageLinks;
    protected $links;
    protected $AutoPageBreak = true;
    protected $PageBreakTrigger;
    protected $InHeader = false;
    protected $InFooter = false;
    protected $ZoomMode;
    protected $LayoutMode;
    protected $title = '';
    protected $subject = '';
    protected $author = '';
    protected $keywords = '';
    protected $creator = 'FPDF';
    protected $AliasNbPages;
    protected $PDFVersion = '1.7';
    function __construct($orientation = 'P', $unit = 'mm', $size = 'A4')
    {
        if (defined('FPDF_FONTPATH')) $this->fontpath = FPDF_FONTPATH;
        elseif (is_dir(__DIR__ . '/font')) $this->fontpath = __DIR__ . '/font/';
        else $this->fontpath = '';
        $this->CoreFonts = ['courier', 'helvetica', 'times', 'symbol', 'zapfdingbats'];
        $this->fonts = [];
        $this->FontFiles = [];
        $this->encodings = [];
        $this->cmaps = [];
        $this->images = [];
        $this->links = [];
        $this->PageInfo = [];
        $this->SetMargins(10, 10);
        $this->SetAutoPageBreak(true, 10);
        $this->SetDisplayMode('default');
        $this->SetCompression(false);
        $this->k = ($unit == 'pt') ? 1 : ($unit == 'mm' ? 72 / 25.4 : ($unit == 'cm' ? 72 / 2.54 : ($unit == 'in' ? 72 : 72 / 25.4)));
        $this->StdPageSizes = ['a3' => [841.89, 1190.55], 'a4' => [595.28, 841.89], 'a5' => [420.94, 595.28], 'letter' => [612, 792], 'legal' => [612, 1008]];
        $size = strtolower($size);
        if (is_string($size)) $size = $this->StdPageSizes[$size] ?? $this->StdPageSizes['a4'];
        $this->DefPageSize = $size;
        $this->CurPageSize = $size;
        $this->DefOrientation = strtoupper($orientation);
        $this->CurOrientation = $this->DefOrientation;
    }
    function SetMargins($left, $top, $right = null)
    {
        $this->lMargin = $left;
        $this->tMargin = $top;
        $this->rMargin = $right === null ? $left : $right;
    }
    function SetAutoPageBreak($auto, $margin = 0)
    {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h - $margin;
    }
    function SetDisplayMode($zoom, $layout = 'default')
    {
        $this->ZoomMode = $zoom;
        $this->LayoutMode = $layout;
    }
    function SetCompression($compress)
    {
        $this->compress = $compress && function_exists('gzcompress');
    }
    function AddPage($orientation = '', $size = '', $rotation = 0)
    {
        if ($this->state == 0) $this->Open();
        $family = $this->FontFamily;
        $style = $this->FontStyle;
        $fontsize = $this->FontSizePt;
        $lw = $this->LineWidth;
        $dc = $this->DrawColor;
        $fc = $this->FillColor;
        $tc = $this->TextColor;
        $cf = $this->ColorFlag;
        $this->page++;
        $this->pages[$this->page] = '';
        $this->PageLinks[$this->page] = [];
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';
        $this->FontStyle = '';
        $this->FontSizePt = 0;
        $this->underline = false;
        $this->DrawColor = $dc;
        $this->FillColor = $fc;
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;
        if ($family) $this->SetFont($family, $style, $fontsize);
        if ($lw) $this->SetLineWidth($lw);
        $this->Header();
        if (!$this->InHeader) $this->SetY($this->tMargin);
    }
    function Header() {}
    function Footer() {}
    function SetFont($family, $style = '', $size = 0)
    {
        $family = strtolower($family);
        if ($family == 'arial') $family = 'helvetica';
        if ($family == '') $family = $this->FontFamily;
        if ($style == 'I') $style = 'i';
        if ($style == 'B') $style = 'b';
        if ($style == 'U') $style = 'u';
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
        $this->CurrentFont = ['name' => $family, 'style' => $style];
    }
    function SetTextColor($r, $g = null, $b = null)
    {
        $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        $this->ColorFlag = ($this->FillColor != '0 g' || $this->TextColor != '0 g');
    }
    function SetDrawColor($r, $g = null, $b = null)
    {
        $this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
    }
    function SetFillColor($r, $g = null, $b = null)
    {
        $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
    }
    function SetLineWidth($width)
    {
        $this->LineWidth = $width / $this->k;
    }
    function Ln($h = null)
    {
        $this->y += $h ?: $this->lasth;
    }
    function SetXY($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
    }
    function SetX($x)
    {
        $this->x = $x;
    }
    function SetY($y)
    {
        $this->y = $y;
    }
    function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        $s = '';
        if ($fill || $this->ColorFlag) $s .= $fill ? $this->FillColor . ' ' : '0 g ';
        $s .= sprintf("BT %.2F %.2F Td (%s) Tj ET\n", $this->x * $this->k, ($this->h - $this->y) * $this->k, $this->_escape($txt));
        $this->_out($s);
        $this->lasth = $h;
        if ($ln > 0) {
            $this->y += $h;
            $this->x = $this->lMargin;
        } else $this->x += $w;
    }
    function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false)
    {
        $lines = explode("\n", (string)$txt);
        foreach ($lines as $line) {
            $this->Cell($w, $h, $line, 0, 1, 'L', $fill);
        }
    }
    function Image($file, $x = null, $y = null, $w = 0, $h = 0)
    {
        $this->_out(sprintf('%% Image: %s at %.2F/%.2F w=%.2F h=%.2F\n', $file, $x, $y, $w, $h));
    }
    function Output($dest = 'I', $name = '', $isUTF8 = false)
    {
        $this->_enddoc();
        if ($dest == 'I') {
            header('Content-Type: application/pdf');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $this->buffer;
        } else {
            file_put_contents($name, $this->buffer);
        }
    }
    protected function _escape($s)
    {
        return str_replace(['\\', '(', ')', "\r"], ["\\\\", "\\(", "\\)", ''], $s);
    }
    protected function _out($s)
    {
        if ($this->state == 2) $this->pages[$this->page] .= $s;
        else $this->buffer .= $s;
    }
    function Open()
    {
        $this->state = 1;
        $this->_begindoc();
    }
    protected function _begindoc()
    {
        $this->buffer = "%PDF-" . $this->PDFVersion . "\n";
    }
    protected function _enddoc()
    {
        if ($this->state < 3) $this->_endpage();
        $this->_putresources();
        $this->_putpages();
        $this->_puttrailer();
        $this->state = 3;
    }
    protected function _endpage()
    {
        $this->state = 1;
    }
    protected function _putresources() {}
    protected function _putpages()
    {
        foreach ($this->pages as $p) {
            $this->buffer .= $p;
        }
    }
    protected function _puttrailer()
    {
        $this->buffer .= "%%EOF";
    }
}
