<?php

namespace Ecgpb\MemberBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Ecgpb\MemberBundle\Controller\MinistryCategoryController
 *
 * @Security("has_role('ROLE_ADMIN')")
 */
class MinistryCategoryController extends Controller
{
    /**
     * Lists all Address entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository('EcgpbMemberBundle:Ministry\Category'); /* @var $repo \Doctrine\Common\Persistence\ObjectRepository */

        $builder = $repo->createQueryBuilder('address')
            ->select('address', 'person')
            ->leftJoin('address.persons', 'person')
            ->orderBy('address.familyName', 'asc')
            ->addOrderBy('person.dob', 'asc')
        ;
        $addresses = $builder->getQuery()->getResult();

        // person pictures
        $personHelper = $this->get('ecgpb.member.helper.person_helper'); /* @var $personHelper \Ecgpb\MemberBundle\Helper\PersonHelper */

        $personsWithPicture = array();
        foreach ($addresses as $address) {
            foreach ($address->getPersons() as $person) {
                $filename = $personHelper->getPersonPhotoPath() . DIRECTORY_SEPARATOR
                    . $personHelper->getPersonPhotoFilename($person)
                ;
                $personsWithPicture[$person->getId()] = file_exists($filename);
            }
        }

        return $this->render('EcgpbMemberBundle:MinistryCategory:index.html.twig', array(
            'entities' => $addresses,
            'persons_with_picture' => $personsWithPicture,
        ));
    }
    /**
     * Creates a new Address entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Address();
        $form = $this->createMinistryCategoryForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'The entry has been created.');

            return $this->redirect($this->generateUrl('ecgpb.member.address.edit', array('id' => $entity->getId())));
        }

        return $this->render('EcgpbMemberBundle:MinistryCategory:form.html.twig', array(
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
        $form   = $this->createMinistryCategoryForm($entity);

        return $this->render('EcgpbMemberBundle:MinistryCategory:form.html.twig', array(
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

        $entity = $em->getRepository('EcgpbMemberBundle:Ministry\Category')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        $editForm = $this->createMinistryCategoryForm($entity);

        return $this->render('EcgpbMemberBundle:MinistryCategory:form.html.twig', array(
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

        $address = $em->getRepository('EcgpbMemberBundle:Ministry\Category')->find($id);
        /* @var $address Address */

        if (!$address) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        $form = $this->createMinistryCategoryForm($address);
        $form->handleRequest($request);

        if ($form->isValid()) {
            foreach ($address->getRemovedEntities() as $removedEntity) {
                $em->remove($removedEntity);
            }

            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'All changes have been saved.');

            return $this->redirect($this->generateUrl('ecgpb.member.address.edit', array('id' => $id)));
        }

        return $this->render('EcgpbMemberBundle:MinistryCategory:form.html.twig', array(
            'entity' => $address,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Deletes a Address entity.
     *
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $address = $em->getRepository('EcgpbMemberBundle:Ministry\Category')->find($id);

        if (!$address) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        $em->remove($address);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', 'The entry has been deleted.');

        return $this->redirect($this->generateUrl('ecgpb.member.address.index'));
    }

    /**
    * Creates a form to create a Address entity.
    *
    * @param Address $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createMinistryCategoryForm(Address $entity)
    {
        $url = $entity->getId() > 0
            ? $this->generateUrl('ecgpb.member.address.update', array('id' => $entity->getId()))
            : $this->generateUrl('ecgpb.member.address.create')
        ;
        $form = $this->createForm(new AddressType(), $entity, array(
            'action' => $url,
            'method' => 'POST',
            'attr' => array(
                'enctype' => 'multipart/form-data',
                'class' => 'form-horizontal',
                'role' => 'form',
            ),
        ));

        $form->add('submit', 'submit', array('label' => 'Save'));

        return $form;
    }
}
