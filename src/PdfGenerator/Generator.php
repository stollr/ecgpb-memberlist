<?php

namespace App\PdfGenerator;

use Tcpdf\Extension\Table\Table;

/**
 * App\PdfGenerator\Generator
 *
 * @author naitsirch
 */
abstract class Generator
{
    const FONT_SIZE_XL = 11.5;
    const FONT_SIZE_L = 10;
    const FONT_SIZE_M = 9.25;
    const FONT_SIZE_S = 8.5;
    const FONT_SIZE_XS = 7.5;
    const FONT_SIZE_XXS = 6.5;

    public function addHeadlineMargin(\TCPDF $pdf)
    {
        $pdf->SetY($pdf->GetY() + 1.5);
    }

    public function addParagraphMargin(\TCPDF $pdf)
    {
        if (bccomp($pdf->getFontSizePt(), self::FONT_SIZE_M, 2) === 0) {
            $pdf->SetY($pdf->GetY() + 6);
        } elseif (bccomp($pdf->getFontSizePt(), self::FONT_SIZE_S, 2) === 0) {
            $pdf->SetY($pdf->GetY() + 4);
        } else {
            $pdf->SetY($pdf->GetY() + 10);
        }
    }

    public function useFontSizeXL(\TCPDF $pdf)
    {
        $pdf->SetFontSize(self::FONT_SIZE_XL);
    }

    public function useFontSizeL(\TCPDF $pdf)
    {
        $pdf->SetFontSize(self::FONT_SIZE_L);
    }
    
    public function useFontSizeM(\TCPDF $pdf)
    {
        $pdf->SetFontSize(self::FONT_SIZE_M);
    }
    
    public function useFontSizeS(\TCPDF $pdf)
    {
        $pdf->SetFontSize(self::FONT_SIZE_S);
    }
    
    public function useFontStyleBold(\TCPDF $pdf)
    {
        $pdf->SetFont('', 'B');
    }
    
    public function useFontStyleNormal(\TCPDF $pdf)
    {
        $pdf->SetFont('', '');
    }

    public function useFontStyleUnderlined(\TCPDF $pdf)
    {
        $pdf->SetFont('', 'U');
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
            $glyph = mb_substr($text, $c, 1, 'UTF-8');
            $lineToLong = false;

            while ($lineWidth + $charWidths[$c] > $maxLineWidth || "\n" === $glyph) {
                if ($lineToLong) {
                    // Prevent endless loop: Makes sure that at leas one character is printed per line.
                    break;
                }

                $pdf->Text($x, $y, $lineText, false, false, true, 0, 1);
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $lineText = '';
                $lineWidth = 0;

                if ("\n" === $glyph) {
                    $c++;
                } else {
                    $lineToLong = true;
                }
                
                // remove white spaces at the beginning of the next line
                while (' ' === ($glyph = mb_substr($text, $c, 1, 'UTF-8'))) {
                    $c++;
                }
            }
            $lineText .= $glyph;
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

    protected function getWordWiths($text)
    {
        $words = preg_split('/\s|\-', $text, -1, PREG_SPLIT_NO_EMPTY);
        $charWidths = $pdf->GetStringWidth($text, '', $pdf->getFontStyle(), $pdf->getFontSize(), true);
    }
}
