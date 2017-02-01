<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
        return $this->redirect($this->generateUrl('ecgpb.member.address.index'));
    }

    public function encodePasswordAction(Request $request)
    {
        if ($request->get('password')) {
            $encoder = $this
                ->get('security.encoder_factory')
                ->getEncoder('Symfony\Component\Security\Core\User\User')
            ;
            /* @var $encoder \Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder */

            return new Response($encoder->encodePassword($request->get('password'), ''));
        }

        return $this->render('EcgpbMemberBundle:Index:encode_password.html.twig');
    }
}
