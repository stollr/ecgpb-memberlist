<?php

namespace App\Service\Export;

use App\Repository\PersonRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Generator for the seniors spreadsheet.
 */
class SeniorsSpreadsheetGenerator
{
    private $personRepo;

    private $translator;

    public function __construct(PersonRepository $personRepo, TranslatorInterface $translator)
    {
        $this->personRepo = $personRepo;
        $this->translator = $translator;
    }

    /**
     * Generate the spreadsheet.
     */
    public function generate(): Spreadsheet
    {
        /** @vars Person[] $person Array of all persons who are (or will become) at least 65 years old (in this year). */
        $persons = $this->personRepo->findSeniors();

        $translator = $this->translator;
        $title = $translator->trans('Seniors List') . ' ' . date('Y');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValueByColumnAndRow(1, 1, $title);
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValueByColumnAndRow(1, 3, $translator->trans('DOB'));
        $sheet->setCellValueByColumnAndRow(2, 3, $translator->trans('Name'));
        $sheet->setCellValueByColumnAndRow(3, 3, $translator->trans('Age'));

        foreach ($persons as $index => $person) {
            $row = $index + 4;
            $sheet->setCellValueByColumnAndRow(1, $row, $person->getDob()->format('d.m.Y'));
            $sheet->setCellValueByColumnAndRow(2, $row, $person->getFirstname().' '.($person->getLastname() ?: $person->getAddress()->getFamilyName()));
            $sheet->setCellValueByColumnAndRow(3, $row, date('Y') - $person->getDob()->format('Y'));
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);

        return $spreadsheet;
    }
}
