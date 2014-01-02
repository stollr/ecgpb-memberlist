<?php

namespace Ecgpb\MemberBundle\PdfGenerator;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bridge\Twig\TwigEngine;

/**
 * Ecgpb\MemberBundle\PdfGenerator\MemberListGenerator
 *
 * @author naitsirch
 */
class MemberListGenerator extends Generator implements GeneratorInterface
{
    private $doctrine;
    private $templating;
    private $parameters;
    
    public function __construct(
        RegistryInterface $doctrine,
        TwigEngine $templating,
        array $parameters
    ) {
        $this->doctrine = $doctrine;
        $this->templating = $templating;
        $this->parameters = $parameters;
    }
    
    /**
     * @return string
     */
    public function generate()
    {
        $addresses = $this->getAddresses();
        
//        $templating = $this->get('templating'); /* @var $templating TwigEngine */
//        
//        $html = $templating->render('EcgpbMemberBundle:Print:pdf.html.twig', array(
//            'date' => new \DateTime(),
//            'addresses' => $addresses,
//        ));
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
        
        // initiate XY positions
        $margins = $pdf->getMargins();
        $pdf->SetX($margins['left']);
        $pdf->SetY($margins['top']);
        
        $this->addCover($pdf);

//        foreach (explode('<!--PAGE_BREAK-->', $html) as $htmlPage) {
//            $pdf->AddPage();
//            $pdf->writeHTML($html, true, false, true, false, '');
//        }

        return $pdf->Output(null, 'S');
    }
    
    private function addCover(\TCPDF $pdf)
    {   
        // TODO: add logo
        
        $pdf->SetLineWidth(1);
        $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->getPageWidth() - $pdf->GetX(), $pdf->GetY());
        
        $pdf->SetX($pdf->GetX() + 3);
        $pdf->Text($pdf->GetX(), $pdf->GetY(), $this->parameters['ecgpb.contact.name'] . "\n");
        
        $pdf->SetFontSize(20);
        $pdf->Text($pdf->GetX() + 5, $pdf->GetY(), 'Mitgliederliste ' . "\n" . date('Y'), false, false, true, 0, 'C');
    }
    
    /**
     * Returns all addresses with the corresponding persons.
     * @return \Ecgpb\MemberBundle\Entity\Address[]
     */
    private function getAddresses()
    {
        $em = $this->doctrine->getManager();

        $repo = $em->getRepository('EcgpbMemberBundle:Address');
        /* @var $repo \Doctrine\Common\Persistence\ObjectRepository */
        
        $builder = $repo->createQueryBuilder('address')
            ->select('address', 'person')
            ->leftJoin('address.persons', 'person')
            ->orderBy('address.familyName', 'asc')
            ->addOrderBy('person.dob', 'asc')
        ;
        
        return $builder->getQuery()->getResult();
    }
}
