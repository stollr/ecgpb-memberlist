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
        $generator = $this->get('ecgpb.member.pdf_generator.member_list_generator');
        /* @var $generator \Ecgpb\MemberBundle\PdfGenerator\MemberListGenerator */
        
        return new Response($generator->generate(), 200, array(
            'Content-Type' => 'application/pdf',
            //'Content-Type' => 'application/octet-stream',
            //'Content-Disposition' => 'attachment; filename="ECGPB Member List.pdf"',
        ));
    }
}
