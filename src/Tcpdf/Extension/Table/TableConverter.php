<?php

namespace Tcpdf\Extension\Table;

/**
 * Tcpdf\Extension\Table\TableConverter
 *
 * @author naitsirch
 */
class TableConverter
{
    private $table;
    private $fontSettings;
    private $calculatedRowHeights;
    private $calculatedCellWidths;

    public function __construct(Table $table)
    {
        $this->table = $table;
        $this->convert();
    }

    private function _getCellWidths()
    {
        $cellWidths = array();
        foreach ($this->getTable()->getRows() as $row) {
            $c = 0;
            foreach ($row->getCells() as $cell) {
                if ($cell->getColspan() == 1) {
                    $width = $cell->getWidth() ?: $this->getPdf()->GetStringWidth($cell->getText());
                    if (empty($cellWidths[$c]) || $width > $cellWidths[$c]) {
                        $cellWidths[$c] = $width;
                    }
                }
                $c += $cell->getColspan();
            }
        }
        return $cellWidths;
    }
    
    /**
     * Calculates and returns an 2 dimensional array with the width for each
     * table cell of each row.
     * 
     * @return array
     */
    private function _getCalculatedCellWidths()
    {
        if (isset($this->calculatedCellWidths)) {
            return $this->calculatedCellWidths;
        }
        
        $cellWidths = $this->_getCellWidths();

        // check if the sum of cell widths is valid
        $cellWidthSum = array_sum($cellWidths);

        $margins = $this->getPdf()->getMargins();
        $maxWidth = $this->getPdf()->getPageWidth() - $margins['left'] - $margins['right'];

        $definedWidth = $this->getTable()->getWidth() ?: null;
        if ($cellWidthSum > $maxWidth || $definedWidth) {
//            $cellWordWidths = $this->_getCellWordWidths();
//            $cellWordWidthsSum = array_sum($cellWordWidths);
//            $restWidth = $maxWidth;
            foreach ($cellWidths as $index => $width) {
//                $wordWidth = current($cellWordWidths);
//                next($cellWordWidths);
                if ($definedWidth) {
                    $newWidth = ($width / $cellWidthSum) * $definedWidth;
                } else {
                    $newWidth = ($width / $cellWidthSum) * $maxWidth;
                }
//                if ($cellWordWidthsSum < $maxWidth) {
//                    if ($wordWidth > $newWidth) {
//                        $newWidth = $wordWidth;
//                        $restWidth -= $wordWidth;
//                    }
//                }
                $cellWidths[$index] = $newWidth;
            }
        }

        // set new calculated widths to the cells
        $r = 0;
        foreach ($this->getTable()->getRows() as $row) {
            $c = $cr = 0; // $cr = real cell index
            foreach ($row->getCells() as $cell) {
                $width = 0;
                for ($i = 0; $i < $cell->getColspan(); $i++) {
                    $width += $cellWidths[$c];
                    $c++;
                }
                $this->calculatedCellWidths[$r][$cr] = $width;
                $cr++;
            }
            $r++;
        }
        
        return $this->calculatedCellWidths;
    }

    private function _getCellWordWidths()
    {
        $longestWordWidths = array();
        foreach ($this->getTable()->getRows() as $row) {
            $c = 0;
            foreach ($row->getCells() as $cell) {
                if ($cell->getColspan() == 1) {
                    // width of the longest word
                    $maxWidth = 0;
                    foreach (explode(' ', $cell->getText()) as $word) {
                        $width = $this->getPdf()->GetStringWidth($word);
                        if ($width > $maxWidth) {
                            $maxWidth = $width;
                        }
                    }
                    if (empty($longestWordWidths[$c]) || $maxWidth > $longestWordWidths[$c]) {
                        $longestWordWidths[$c] = $maxWidth;
                    }
                }
                $c += $cell->getColspan();
            }
        }
        return $longestWordWidths;
    }

    private function _getRowHeights()
    {
        $rowHeights = array();
        $r = 0;
        foreach ($this->getTable()->getRows() as $row) {
            foreach ($row->getCells() as $cell) {
                $lines = $cell->getLineNumber();
                $height = $cell->getLineHeight();
                if (empty($rowHeights[$r]) || $height * $lines > $rowHeights[$r]) {
                    $rowHeights[$r] = $height * $lines;
                }
            }
            $r++;
        }
        return $rowHeights;
    }

    private function _saveFontSettings()
    {
        $this->fontSettings = array(
            'family'  => $this->getPdf()->getFontFamily(),
            'style'   => $this->getPdf()->getFontStyle(),
            'size'    => $this->getPdf()->getFontSize(),
            'size_pt' => $this->getPdf()->getFontSizePt(),
        );
        return $this;
    }

    private function _restoreFontSettings()
    {
        if (!$this->fontSettings) {
            throw new RuntimeException('No settings has been saved, yet.');
        }
        $this->getPdf()->SetFont(
            $this->fontSettings['family'],
            $this->fontSettings['style'],
            $this->fontSettings['size_pt']
        );

        return $this;
    }
    
    /**
     * @return Table
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return \TCPDF
     */
    public function getPdf()
    {
        return $this->getTable()->getPdf();
    }

    private function convert()
    {
        $cellWidths = $this->_getCalculatedCellWidths();
        $rowHeights = $this->_getRowHeights();

        // after all sizes are collected, we can start printing the cells
        $x = $this->getPdf()->GetX();
        $r = 0;
        foreach ($this->getTable()->getRows() as $row) {
            $c = 0;
            $y2 = $this->getPdf()->GetY();
            $x2 = $x;
            foreach ($row->getCells() as $cell) {
                // calculate the width (regard colspan)
                $width = $cellWidths[$r][$c];

                // calculate optimal number of lines and height of the cell
                $lines = (int) floor($rowHeights[$r] / $cell->getLineHeight());
                $height = $cell->getLineHeight() + (fmod($rowHeights[$r], $cell->getLineHeight()) / $lines);

                // now we have to append the difference of lines as line-breaks at the end of our text
                $text = $cell->getText() . str_repeat("\n", $lines - $cell->getLineNumber() + 1);

                // set correct X/Y position for this cell
                $this->getPdf()->SetXY($x2, $y2);
                $x2 = $x2 + $width;

                // save styles and set needed
                $this->_saveFontSettings();
                $this->getPdf()->SetFont(
                    $this->getPdf()->getFontFamily(),
                    $cell->getFontWeight() == Cell::FONT_WEIGHT_BOLD ? 'B' : '',
                    $cell->getFontSize()
                );

                // write cell to pdf
                $this->getPdf()->MultiCell($width, $height, $text, $cell->getBorder(), $cell->getAlign(), $cell->getFill());

                $this->_restoreFontSettings();

                $c++;
            }
            $this->getPdf()->SetX($x);
            $r++;
        }
    }
}
