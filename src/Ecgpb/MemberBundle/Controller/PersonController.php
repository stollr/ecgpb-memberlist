<?php

namespace Ecgpb\MemberBundle\Controller;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Ecgpb\MemberBundle\Entity\Person;
use Ecgpb\MemberBundle\Form\PersonType;
use Ecgpb\MemberBundle\PdfGenerator\MemberListGenerator;

/**
 * Person controller.
 *
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

        $entities = $em->getRepository('EcgpbMemberBundle:Person')->findAll();

        return $this->render('EcgpbMemberBundle:Person:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Person entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Person();
        $form = $this->createPersonForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('ecgpb.member.person.edit', array('id' => $entity->getId())));
        }

        return $this->render('EcgpbMemberBundle:Person:form.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Person entity.
     *
     */
    public function newAction()
    {
        $entity = new Person();
        $form   = $this->createPersonForm($entity);

        return $this->render('EcgpbMemberBundle:Person:form.html.twig', array(
            'entity' => $entity,
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

        $entity = $em->getRepository('EcgpbMemberBundle:Person')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Person entity.');
        }

        $editForm = $this->createPersonForm($entity);

        return $this->render('EcgpbMemberBundle:Person:form.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Person entity.
    *
    * @param Person $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createPersonForm(Person $entity)
    {
        $url = $entity->getId()
            ? $this->generateUrl('ecgpb.member.person.update', array('id' => $entity->getId()))
            : $this->generateUrl('ecgpb.member.person.create')
        ;
        
        $form = $this->createForm(new PersonType(), $entity, array(
            'action' => $url,
            'method' => 'POST',
            'attr' => array('class' => 'form-horizontal', 'role' => 'form'),
            'add_address_field' => true,
        ));

        $form->add('submit', 'submit', array('label' => 'Save'));

        return $form;
    }

    /**
     * Edits an existing Person entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('EcgpbMemberBundle:Person')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Person entity.');
        }

        $form = $this->createPersonForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em->flush();
            
            $this->get('session')->getFlashBag()->add('success', 'All changes have been saved.');

            return $this->redirect($this->generateUrl('ecgpb.member.person.edit', array('id' => $id)));
        }

        return $this->render('EcgpbMemberBundle:Person:form.html.twig', array(
            'entity' => $entity,
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
        $entity = $em->getRepository('EcgpbMemberBundle:Person')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Person entity.');
        }

        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('ecgpb.member.person.index'));
    }

    public function optimizedMemberPictureAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $person = $em->getRepository('EcgpbMemberBundle:Person')->find($id);
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
