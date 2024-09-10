<?php

namespace App\Service;

use App\Entity\Person;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

    public function __construct(ManagerRegistry $doctrine, TranslatorInterface $translator)
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

        $worksheet->setCellValue([1, 1], $title);
        $worksheet->mergeCells('A1:C1');
        $worksheet->setCellValue([1, 3], $translator->trans('DOB'));
        $worksheet->setCellValue([2, 3], $translator->trans('Name'));
        $worksheet->setCellValue([3, 3], $translator->trans('Age'));

        foreach ($persons as $index => $person) {
            $row = $index + 4;
            $worksheet->setCellValue([1, $row], $person->getDob()->format('d.m.Y'));
            $worksheet->setCellValue([2, $row], $person->getFirstname().' '.($person->getLastname() ?: $person->getAddress()->getFamilyName()));
            $worksheet->setCellValue([3, $row], date('Y') - $person->getDob()->format('Y'));
        }

        $worksheet->getColumnDimension('A')->setAutoSize(true);
        $worksheet->getColumnDimension('B')->setAutoSize(true);
        $worksheet->getColumnDimension('C')->setAutoSize(true);

        return $spreadsheet;
    }
}
