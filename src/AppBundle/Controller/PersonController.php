<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Person;
use AppBundle\Form\PersonType;
use AppBundle\Helper\PersonHelper;
use AppBundle\PdfGenerator\MemberListGenerator;

/**
 * Person controller.
 *
 * @/Security("is_granted('ROLE_ADMIN')")
 * @Route("/person")
 */
class PersonController extends Controller
{
    /**
     * Lists all Person entities.
     *
     * @Route(name="ecgpb.member.person.index", path="/", defaults={"_locale"="de"})
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:Person')->findAll();

        return $this->render('AppBundle:Person:index.html.twig', array(
            'entities' => $entities,
        ));
    }

    /**
     * Creates a new Person entity.
     *
     * @Route(name="ecgpb.member.person.create", path="/create", methods={"POST"}, defaults={"_locale"="de"})
     */
    public function createAction(Request $request)
    {
        $person = new Person();
        $form = $this->createPersonForm($person);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($person);
            $em->flush();

            return $this->redirect($this->generateUrl('ecgpb.member.person.edit', array('id' => $person->getId())));
        }

        return $this->render('AppBundle:Person:form.html.twig', array(
            'person' => $person,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Person entity.
     *
     * @Route(name="ecgpb.member.person.new", path="/new", defaults={"_locale"="de"})
     */
    public function newAction()
    {
        $person = new Person();
        $form   = $this->createPersonForm($person);

        return $this->render('AppBundle:Person:form.html.twig', array(
            'person' => $person,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Person entity.
     *
     * @Route(name="ecgpb.member.person.edit", path="/{id}/edit", defaults={"_locale"="de"})
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $person = $em->getRepository('AppBundle:Person')->find($id);

        if (!$person) {
            throw $this->createNotFoundException('Unable to find Person entity.');
        }

        $editForm = $this->createPersonForm($person);

        return $this->render('AppBundle:Person:form.html.twig', array(
            'person'      => $person,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a Person person.
     *
     * @param Person $person The person
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createPersonForm(Person $person)
    {
        $url = $person->getId()
            ? $this->generateUrl('ecgpb.member.person.update', array('id' => $person->getId()))
            : $this->generateUrl('ecgpb.member.person.create')
        ;
        
        $form = $this->createForm(PersonType::class, $person, array(
            'action' => $url,
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'role' => 'form',
                'enctype' => 'multipart/form-data',
            ),
            'add_address_field' => true,
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Save'));

        return $form;
    }

    /**
     * Edits an existing Person entity.
     *
     * @Route(name="ecgpb.member.person.update", path="/{id}/update", methods={"PUT", "POST"}, defaults={"_locale"="de"})
     */
    public function updateAction(Request $request, $id, PersonHelper $personHelper)
    {
        $em = $this->getDoctrine()->getManager();

        $person = $em->getRepository('AppBundle:Person')->find($id);

        if (!$person) {
            throw $this->createNotFoundException('Unable to find Person person.');
        }

        $form = $this->createPersonForm($person);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em->flush();

            // person photo file
            if ($file = $request->files->get('person-picture-file')) {
                /* @var $file UploadedFile */
                $filename = $personHelper->getPersonPhotoFilename($person);
                $file->move($personHelper->getPersonPhotoPath(), $filename);
            }
            
            $this->get('session')->getFlashBag()->add('success', 'All changes have been saved.');

            return $this->redirect($this->generateUrl('ecgpb.member.person.edit', array('id' => $id)));
        }

        return $this->render('AppBundle:Person:form.html.twig', array(
            'person' => $person,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Deletes a Person entity.
     *
     * @Route(name="ecgpb.member.person.delete", path="/{id}/delete", defaults={"_locale"="de"})
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $person = $em->getRepository('AppBundle:Person')->find($id);
        /* @var $person Person */

        if (!$person) {
            throw $this->createNotFoundException('Unable to find Person person.');
        }

        if ($person->getLeaderOf()) {
            $person->getLeaderOf()->setLeader(null);
        }

        $em->remove($person);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'The entry has been deleted.');

        if ($referrer = $request->headers->get('referer')) {
            return $this->redirect($referrer);
        }

        return $this->redirect($this->generateUrl('ecgpb.member.person.index'));
    }

    /**
     * Generate and return the optimized member picture.
     *
     * @Route(name="ecgpb.member.person.optimized_member_picture", path="/{id}/optimized_member_picture", defaults={"_locale"="de"})
     */
    public function optimizedMemberPictureAction(Person $person, MemberListGenerator $generator)
    {
        $options = new \Tcpdf\Extension\Attribute\BackgroundFormatterOptions(
            null,
            MemberListGenerator::GRID_PICTURE_CELL_WIDTH,
            MemberListGenerator::GRID_ROW_MIN_HEIGHT
        );
        $formatter = $generator->getPersonPictureFormatter($person);
        $formatter($options);
        $filename = $options->getImage();

        return new BinaryFileResponse($filename, 200, array(
            'Content-Type' => 'image/jpeg',
            'Content-Length' => filesize($filename),
        ));
    }
}
