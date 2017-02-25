<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Person;
use AppBundle\Form\PersonType;
use AppBundle\PdfGenerator\MemberListGenerator;

/**
 * Person controller.
 * @/Security("is_granted('ROLE_ADMIN')")
 */
class PersonController extends Controller
{
    /**
     * Lists all Person entities.
     *
     */
    public function indexAction()
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
     */
    public function updateAction(Request $request, $id)
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
                $personHelper = $this->get('person_helper'); /* @var $personHelper \AppBundle\Helper\PersonHelper */
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

    public function optimizedMemberPictureAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $person = $em->getRepository('AppBundle:Person')->find($id);
        if (!$person) {
            throw $this->createNotFoundException('Unable to find Person entity.');
        }

        $memberListGenerator = $this->get('ecgpb.member.pdf_generator.member_list_generator');
        $options = new \Tcpdf\Extension\Attribute\BackgroundFormatterOptions(
            null,
            MemberListGenerator::GRID_PICTURE_CELL_WIDTH,
            MemberListGenerator::GRID_ROW_MIN_HEIGHT
        );
        $formatter = $memberListGenerator->getPersonPictureFormatter($person);
        $formatter($options);
        $filename = $options->getImage();

        return new BinaryFileResponse($filename, 200, array(
            'Content-Type' => 'image/jpeg',
            'Content-Length' => filesize($filename),
        ));
    }
}
