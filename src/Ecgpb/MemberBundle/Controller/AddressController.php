<?php

namespace Ecgpb\MemberBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Ecgpb\MemberBundle\Entity\Address;
use Ecgpb\MemberBundle\Form\AddressType;

/**
 * Address controller.
 *
 */
class AddressController extends Controller
{

    /**
     * Lists all Address entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository('EcgpbMemberBundle:Address'); /* @var $repo \Doctrine\Common\Persistence\ObjectRepository */
        
        $builder = $repo->createQueryBuilder('address')
            ->select('address', 'person')
            ->leftJoin('address.persons', 'person')
        ;
        $entities = $builder->getQuery()->getResult();

        return $this->render('EcgpbMemberBundle:Address:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Address entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Address();
        $form = $this->createAddressForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('ecgpb.member.address.edit', array('id' => $entity->getId())));
        }

        return $this->render('EcgpbMemberBundle:Address:form.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Address entity.
     *
     */
    public function newAction()
    {
        $entity = new Address();
        $form   = $this->createAddressForm($entity);

        return $this->render('EcgpbMemberBundle:Address:form.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Address entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('EcgpbMemberBundle:Address')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        $editForm = $this->createAddressForm($entity);

        return $this->render('EcgpbMemberBundle:Address:form.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Edits an existing Address entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('EcgpbMemberBundle:Address')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        $editForm = $this->createAddressForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('ecgpb.member.address.edit', array('id' => $id)));
        }

        return $this->render('EcgpbMemberBundle:Address:form.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
        ));
    }
    /**
     * Deletes a Address entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('EcgpbMemberBundle:Address')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Address entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('ecgpb.member.address.index'));
    }

    /**
    * Creates a form to create a Address entity.
    *
    * @param Address $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createAddressForm(Address $entity)
    {
        $url = $entity->getId() > 0
            ? $this->generateUrl('ecgpb.member.address.update', array('id' => $entity->getId()))
            : $this->generateUrl('ecgpb.member.address.create')
        ;
        $form = $this->createForm(new AddressType(), $entity, array(
            'action' => $url,
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Save'));

        return $form;
    }
}
