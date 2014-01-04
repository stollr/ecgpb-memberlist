<?php

namespace Ecgpb\MemberBundle\PdfGenerator;

use Tcpdf\Extension\Table\Table;

/**
 * Ecgpb\MemberBundle\PdfGenerator\Generator
 *
 * @author naitsirch
 */
abstract class Generator
{
    public function useFontSizeL(\TCPDF $pdf)
    {
        $pdf->SetFontSize(15);
    }
    
    public function useFontSizeM(\TCPDF $pdf)
    {
        $pdf->SetFontSize(11);
    }
    
    public function useFontSizeS(\TCPDF $pdf)
    {
        $pdf->SetFontSize(8);
    }
    
    public function useFontWeightBold(\TCPDF $pdf)
    {
        $pdf->SetFont('', 'B');
    }
    
    public function useFontWeightNormal(\TCPDF $pdf)
    {
        $pdf->SetFont('', '');
    }

    public function writeText(\TCPDF $pdf, $text)
    {
        $text = str_replace("\r", '', $text);
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $margins = $pdf->getMargins();
        $maxLineWidth = $pdf->getPageWidth() - $margins['left'] - $margins['right'];
        
        $charWidths = $pdf->GetStringWidth($text, '', $pdf->getFontStyle(), $pdf->getFontSize(), true);
        $strlen = count($charWidths);
        
        $lineText = '';
        $lineWidth = $x - $margins['left'];
        
        for ($c = 0; $c < $strlen; $c++) {
            if ($lineWidth + $charWidths[$c] > $maxLineWidth || "\n" == $text[$c]) {
                $pdf->Text($x, $y, $lineText, false, false, true, 0, 1);
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $lineText = '';
                $lineWidth = 0;
                
                // remove white spaces at the beginning of the next line
                while (' ' == mb_substr($text, $c, 1, 'UTF-8')) {
                    $c++;
                }
            }
            $lineText .= mb_substr($text, $c, 1, 'UTF-8');
            $lineWidth += $charWidths[$c];
        }
        
        if (!empty($lineText)) {
            $pdf->Text($x, $y, $lineText, false, false, true, 0, 1);
        }
    }
    
    public function addTable(\TCPDF $pdf)
    {
        return new Table($pdf);
    }
}
