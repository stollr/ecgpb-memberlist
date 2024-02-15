<?php

namespace App\Controller;

use App\Entity\Person;
use App\PdfGenerator\MemberListGenerator;
use App\Service\BirthdayExcelGenerator;
use App\Service\Export\SeniorsSpreadsheetGenerator;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * App\Controller\ExportController
 */
#[Route(path: '/export')]
class ExportController extends AbstractController
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[Route(name: 'app.export.pdf_config', path: '/pdf_config')]
    public function pdfConfigAction()
    {
        return $this->render('/export/pdf_config.html.twig');
    }

    #[Route(name: 'app.export.pdf', path: '/pdf')]
    public function pdfAction(Request $request, MemberListGenerator $generator)
    {
        $pdf = $generator->generate(array(
            'pages_with_member_placeholders' => $request->get('pages_with_member_placeholders', 1),
            'pages_for_notes' => $request->get('pages_for_notes', 3),
        ));

        return new Response($pdf, 200, array(
            'Content-Type' => 'application/pdf',
            //'Content-Type' => 'application/octet-stream',
            //'Content-Disposition' => 'attachment; filename="ECGPB Member List.pdf"',
        ));
    }

    #[Route(name: 'app.export.csv', path: '/csv')]
    public function csvAction(): Response
    {
        $ageLimit = $this->getParameter('ecgpb.working_groups.age_limit');
        $phoneUtil = PhoneNumberUtil::getInstance();

        $repo = $this->getDoctrine()->getRepository(Person::class);
        $builder = $repo->createQueryBuilder('person')
            ->select('person', 'address')
            ->join('person.address', 'address')
            ->orderBy('address.familyName', 'asc')
            ->addOrderBy('person.dob', 'asc')
        ;
        $persons = $builder->getQuery()->getResult();

        $csv = "Nachname;Vorname;Geburtsdatum;Geschlecht;E-Mail;Mobil;Telefon;Straße;PLZ;Ort;Arbeitsgruppe\r\n";

        foreach ($persons as $person) {
            /* @var $person \App\Entity\Person */
            $workingGroup = null;
            if ($person->getWorkerStatus() === Person::WORKER_STATUS_UNTIL_AGE_LIMIT
                && $person->getAge() < $ageLimit && $person->getWorkingGroup()
            ) {
                $workingGroup = $person->getWorkingGroup()->getDisplayName($this->translator);
            }

            $row = array(
                $person->getAddress()->getFamilyName(),
                $person->getFirstname(),
                $person->getDob()->format('d.m.Y'),
                $person->getGender(),
                $person->getEmail(),
                $person->getMobile() ? $phoneUtil->format($person->getMobile(), PhoneNumberFormat::E164) : '',
                $person->getAddress()->getPhone() ? $phoneUtil->format($person->getAddress()->getPhone(), PhoneNumberFormat::E164) : '',
                $person->getAddress()->getStreet(),
                $person->getAddress()->getZip(),
                $person->getAddress()->getCity(),
                $workingGroup,
            );

            $row = array_map(function ($value) {
                if (strpos($value, '"') !== false || strpos($value, ';') !== false) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }
                return $value;
            }, $row);

            $csv .= implode(';', $row) . "\r\n";
        }

        return new Response($csv, 200, array(
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="Mitglieder.csv"',
        ));
    }

    #[Route(name: 'app.export.birthday_excel', path: '/birthday_excel')]
    public function birthdayExcel(BirthdayExcelGenerator $generator)
    {
        $title = $this->translator->trans('Birthday List') . ' ' . date('Y');
        $spreadsheet = $generator->generate();

        $response = new StreamedResponse(null, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => sprintf('attachment; filename="%s.xlsx"', $title),
        ]);
        $response->setCallback(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        return $response;
    }

    #[Route(name: 'app.export.seniors_excel', path: '/seniors-excel')]
    public function seniorsExcel(SeniorsSpreadsheetGenerator $generator): Response
    {
        $title = $this->translator->trans('Seniors List') . ' ' . date('Y');
        $spreadsheet = $generator->generate();

        return new StreamedResponse(
            function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            },
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => sprintf('attachment; filename="%s.xlsx"', $title),
            ]
        );
    }

    #[Route(name: 'app.export.email_addresses', path: '/email_addresses')]
    public function emailAddressesAction()
    {
        $repo = $this->getDoctrine()->getManager()->getRepository(Person::class);
        $emails = $repo->getAllEmailAdresses();

        $content = "Comma separated:\r\n\r\n" .
            implode(',', $emails) . "\r\n\r\n" .
            "New lines:\r\n\r\n" .
            implode("\r\n", $emails)
        ;

        return new Response($content, 200, array(
            'Content-Type' => 'text/plain',
        ));
    }
}
