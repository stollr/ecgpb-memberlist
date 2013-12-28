<?php

namespace Ecgpb\MemberBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
}
