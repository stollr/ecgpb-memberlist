<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Person controller.
 *
 * @Route("/index")
 */
class IndexController extends Controller
{
    /**
     * @Route(name="app.index.encode_password", path="/encode_password")
     */
    public function encodePassword(Request $request)
    {
        if ($request->get('password')) {
            $encoder = $this
                ->get('security.encoder_factory')
                ->getEncoder('Symfony\Component\Security\Core\User\User')
            ;
            /* @var $encoder \Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder */

            return new Response($encoder->encodePassword($request->get('password'), ''));
        }

        return $this->render('/index/encode_password.html.twig');
    }
}
