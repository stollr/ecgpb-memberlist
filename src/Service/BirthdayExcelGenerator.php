<?php

namespace App\Service;

use App\Entity\Person;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * App\Service\BirthdayExcelGenerator
 *
 * @author naitsirch
 */
class BirthdayExcelGenerator
{
    private $doctrine;

    private $translator;

    public function __construct(RegistryInterface $doctrine, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
    }

    /**
     * Generate a spreadsheet object containing the dates of birth of all persons.
     *
     * @return Spreadsheet
     */
    public function generate(): Spreadsheet
    {
        $repo = $this->doctrine->getRepository(Person::class);
        $persons = $repo->findAllForBirthdayList();

        $translator = $this->translator;
        $title = $translator->trans('Birthday List') . ' ' . date('Y');

        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $worksheet->setCellValueByColumnAndRow(1, 1, $title);
        $worksheet->mergeCells('A1:C1');
        $worksheet->setCellValueByColumnAndRow(1, 3, $translator->trans('DOB'));
        $worksheet->setCellValueByColumnAndRow(2, 3, $translator->trans('Name'));
        $worksheet->setCellValueByColumnAndRow(3, 3, $translator->trans('Age'));

        foreach ($persons as $index => $person) {
            $row = $index + 4;
            $worksheet->setCellValueByColumnAndRow(1, $row, $person->getDob()->format('d.m.Y'));
            $worksheet->setCellValueByColumnAndRow(2, $row, $person->getFirstname().' '.($person->getLastname() ?: $person->getAddress()->getFamilyName()));
            $worksheet->setCellValueByColumnAndRow(3, $row, date('Y') - $person->getDob()->format('Y'));
        }

        $worksheet->getColumnDimension('A')->setAutoSize(true);
        $worksheet->getColumnDimension('B')->setAutoSize(true);
        $worksheet->getColumnDimension('C')->setAutoSize(true);

        return $spreadsheet;
    }
}
