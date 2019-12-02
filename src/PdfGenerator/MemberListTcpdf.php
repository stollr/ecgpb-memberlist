<?php

namespace App\PdfGenerator;

/**
 * Description of MemberListTcpdf
 *
 * @author naitsirch
 */
class MemberListTcpdf extends \TCPDF
{
    /**
     * Marks the last page. Set this to `true` before the last page is added.
     * If this is set to true no footer will be printed anymore.
     *
     * @var bool
     */
    public $lastPage = false;

    public function Footer()
    {
        $this->SetX($this->getMargins()['left']);

        if ($this->page > 1 && !$this->lastPage) {
            $this->Cell(0, 0, $this->page, 0, 0, 'C');
        }
	}
}
