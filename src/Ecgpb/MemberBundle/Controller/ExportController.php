<?php

namespace Ecgpb\MemberBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ecgpb\MemberBundle\Exception\WorkingGroupWithoutLeaderException;

/**
 * Ecgpb\MemberBundle\Controller\ExportController
 *
 * @author naitsirch
 *
 * @Security("is_granted('ROLE_ADMIN')")
 */
class ExportController extends Controller
{
    public function pdfConfigAction()
    {
        return $this->render('EcgpbMemberBundle:Export:pdf_config.html.twig');
    }

    public function pdfAction(Request $request)
    {
        $generator = $this->get('ecgpb.member.pdf_generator.member_list_generator');
        /* @var $generator \Ecgpb\MemberBundle\PdfGenerator\MemberListGenerator */
        
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

    public function birthdayExcelAction()
    {
        $repo = $this->getDoctrine()->getManager()->getRepository('EcgpbMemberBundle:Person');
        $persons = $repo->findAllForBirthdayList();

        $translator = $this->get('translator');
        $title = $translator->trans('Birthday List') . ' ' . date('Y');

        $spreadsheet = $this->get('phpexcel')->createPHPExcelObject(); /* @var $spreadsheet \PHPExcel */
        $worksheet = $spreadsheet->getActiveSheet();

        $worksheet->setCellValueByColumnAndRow(0, 1, $title);
        $worksheet->mergeCells('A1:C1');
        $worksheet->setCellValueByColumnAndRow(0, 3, $translator->trans('DOB'));
        $worksheet->setCellValueByColumnAndRow(1, 3, $translator->trans('Name'));
        $worksheet->setCellValueByColumnAndRow(2, 3, $translator->trans('Age'));

        foreach ($persons as $index => $person) {
            $row = $index + 4;
            $worksheet->setCellValueByColumnAndRow(0, $row, $person->getDob()->format('d.m.Y'));
            $worksheet->setCellValueByColumnAndRow(1, $row, $person->getFirstname().' '.($person->getLastname() ?: $person->getAddress()->getFamilyName()));
            $worksheet->setCellValueByColumnAndRow(2, $row, date('Y') - $person->getDob()->format('Y'));
        }

        $worksheet->getColumnDimension('A')->setAutoSize(true);
        $worksheet->getColumnDimension('B')->setAutoSize(true);
        $worksheet->getColumnDimension('C')->setAutoSize(true);

        $writer = $this->get('phpexcel')->createWriter($spreadsheet, 'Excel2007');

        return $this->get('phpexcel')->createStreamedResponse($writer, 200, array(
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => sprintf('attachment; filename="%s.xlsx"', $title),
        ));
    }

    public function seniorsExcelAction()
    {
        $repo = $this->getDoctrine()->getManager()->getRepository('EcgpbMemberBundle:Person');
        $persons = $repo->findSeniors();
        /* @var $persons Person[] Array of all persons who are (or will become) at least 65 years old (in this year). */

        $translator = $this->get('translator');
        $title = $translator->trans('Seniors List') . ' ' . date('Y');

        $spreadsheet = $this->get('phpexcel')->createPHPExcelObject(); /* @var $spreadsheet \PHPExcel */
        $worksheet = $spreadsheet->getActiveSheet();

        $worksheet->setCellValueByColumnAndRow(0, 1, $title);
        $worksheet->mergeCells('A1:C1');
        $worksheet->setCellValueByColumnAndRow(0, 3, $translator->trans('DOB'));
        $worksheet->setCellValueByColumnAndRow(1, 3, $translator->trans('Name'));
        $worksheet->setCellValueByColumnAndRow(2, 3, $translator->trans('Age'));

        foreach ($persons as $index => $person) {
            $row = $index + 4;
            $worksheet->setCellValueByColumnAndRow(0, $row, $person->getDob()->format('d.m.Y'));
            $worksheet->setCellValueByColumnAndRow(1, $row, $person->getFirstname().' '.($person->getLastname() ?: $person->getAddress()->getFamilyName()));
            $worksheet->setCellValueByColumnAndRow(2, $row, date('Y') - $person->getDob()->format('Y'));
        }

        $worksheet->getColumnDimension('A')->setAutoSize(true);
        $worksheet->getColumnDimension('B')->setAutoSize(true);
        $worksheet->getColumnDimension('C')->setAutoSize(true);

        $writer = $this->get('phpexcel')->createWriter($spreadsheet, 'Excel2007');

        return $this->get('phpexcel')->createStreamedResponse($writer, 200, array(
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => sprintf('attachment; filename="%s.xlsx"', $title),
        ));
    }
}
