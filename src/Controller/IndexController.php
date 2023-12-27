<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

/**
 * Person controller.
 */
#[Route(path: '/index')]
class IndexController extends AbstractController
{
    #[Route(name: 'app.index.encode_password', path: '/encode_password')]
    public function encodePassword(Request $request, EncoderFactoryInterface $encoderFactory)
    {
        if ($request->get('password')) {
            /** @var \Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder $encoder */
            $encoder = $encoderFactory->getEncoder(User::class);

            return new Response($encoder->encodePassword($request->get('password'), ''));
        }

        return $this->render('index/encode_password.html.twig');
    }
}
