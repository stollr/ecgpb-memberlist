<?php

namespace Ecgpb\MemberBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Ecgpb\MemberBundle\Entity\Person;
use Ecgpb\MemberBundle\Form\PersonType;

/**
 * Person controller.
 *
 */
class IndexController extends Controller
{

    /**
     * Lists all Person entities.
     *
     */
    public function indexAction()
    {
        return $this->redirect($this->generateUrl('ecgpb.member.person.index'));
    }
}
