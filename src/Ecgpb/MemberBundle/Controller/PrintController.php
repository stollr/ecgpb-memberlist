<?php

namespace Ecgpb\MemberBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ecgpb\MemberBundle\Controller\PrintController
 *
 * @author naitsirch
 */
class PrintController extends Controller
{
    public function htmlAction()
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository('EcgpbMemberBundle:Address'); /* @var $repo \Doctrine\Common\Persistence\ObjectRepository */
        
        $builder = $repo->createQueryBuilder('address')
            ->select('address', 'person')
            ->leftJoin('address.persons', 'person')
            ->orderBy('address.familyName', 'asc')
            ->addOrderBy('person.dob', 'asc')
        ;
        $addresses = $builder->getQuery()->getResult();
        
        return $this->render('EcgpbMemberBundle:Print:html.html.twig', array(
            'date' => new \DateTime(),
            'addresses' => $addresses
        ));
    }
    
    public function pdfAction()
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository('EcgpbMemberBundle:Address'); /* @var $repo \Doctrine\Common\Persistence\ObjectRepository */
        
        $builder = $repo->createQueryBuilder('address')
            ->select('address', 'person')
            ->leftJoin('address.persons', 'person')
            ->orderBy('address.familyName', 'asc')
            ->addOrderBy('person.dob', 'asc')
        ;
        $addresses = $builder->getQuery()->getResult();
        
        $templating = $this->get('templating'); /* @var $templating TwigEngine */
        
        $html = $templating->render('EcgpbMemberBundle:Print:pdf.html.twig', array(
            'date' => new \DateTime(),
            'addresses' => $addresses,
        ));
        
        $pdf = new \TCPDF('P', 'mm', 'A5', true, 'UTF-8', false);
        $pdf->SetTitle('ECGPB Member List');
        $pdf->SetMargins(9, 9, 9);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetAutoPageBreak(true, 9);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->SetFont('dejavusans', '', 10);

        $pdf->AddPage();

        $pdf->writeHTML($html, true, false, true, false, '');

        $content = $pdf->Output('ECGPB Member List.pdf', 'S');
        
        return new Response($content, 200, array(
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="ECGPB Member List.pdf"',
        ));
    }
}
