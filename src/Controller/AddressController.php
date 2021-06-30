<?php

namespace App\Controller;

use App\Entity\Address;
use App\Form\AddressType;
use App\Helper\PersonHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Address controller.
 *
 * @Route("/address")
 * @/Security("has_role('ROLE_ADMIN')")
 */
class AddressController extends Controller
{

    /**
     * Lists all Address entities.
     *
     * @Route("/index", name="ecgpb.member.address.index", defaults={"_locale"="de"})
     */
    public function indexAction(Request $request, PersonHelper $personHelper)
    {
        $em = $this->getDoctrine()->getManager();

        $repo = $em->getRepository(Address::class); /* @var $repo \App\Repository\AddressRepository */

        $filter = $request->get('filter', array());
        if (!empty($filter['no-photo'])) {
            $filter['no-photo'] = $personHelper->getPersonIdsWithoutPhoto();
        }

        $pagination = $this->get('knp_paginator')->paginate(
            $repo->getListFilterQb($filter),
            $request->query->get('page', 1)/*page number*/,
            15, /*limit per page*/
            array(
                'wrap-queries' => true,
                'defaultSortFieldName' => array('address.familyName', 'person.dob'),
                'defaultSortDirection' => 'asc',
            )
        );

        return $this->render('/address/index.html.twig', array(
            'pagination' => $pagination,
            'person_ids_without_photo' => $personHelper->getPersonIdsWithoutPhoto(),
            //'persons_with_picture' => $personsWithPicture,
        ));
    }
    /**
     * Creates a new Address entity.
     *
     * @Route("/create", name="ecgpb.member.address.create", methods={"POST"})
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
            
            $this->addFlash('success', 'The entry has been created.');

            return $this->redirect($this->generateUrl('ecgpb.member.address.edit', array('id' => $entity->getId())));
        }

        return $this->render('/address/form.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to create a new Address entity.
     *
     * @Route("/new", name="ecgpb.member.address.new", defaults={"_locale"="de"})
     */
    public function newAction()
    {
        $entity = new Address();
        $form   = $this->createAddressForm($entity);

        return $this->render('/address/form.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Address entity.
     *
     * @Route("/{id}/edit", name="ecgpb.member.address.edit", methods={"GET", "PUT"})
     */
    public function editAction(Address $address, Request $request, PersonHelper $personHelper)
    {
        $editForm = $this->createAddressForm($address);

        $form = $this->createAddressForm($address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            // person picture file
            foreach ($request->files->get('person-picture-file', array()) as $index => $file) {
                /* @var $file UploadedFile */
                if ($file) {
                    $person = $address->getPersons()->get($index);
                    $file->move($personHelper->getPersonPhotoPath(), $personHelper->getPersonPhotoFilename($person));
                }
            }
            
            $this->addFlash('success', 'All changes have been saved.');

            return $this->redirectToRoute('ecgpb.member.address.edit', ['id' => $address->getId()]);
        }
        
        return $this->render('/address/form.html.twig', [
            'entity' => $address,
            'form' => $editForm->createView(),
        ]);
    }

    /**
     * Edits an existing Address entity.
     *
     * @Route("/{id}/update", name="ecgpb.member.address.update", methods={"POST", "PUT"})
     */
    public function updateAction(Request $request, $id, PersonHelper $personHelper)
    {
        $em = $this->getDoctrine()->getManager();

        $address = $em->getRepository(Address::class)->find($id);
        /* @var $address Address */

        if (!$address) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }


        return $this->render('/address/form.html.twig', array(
            'entity' => $address,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Deletes a Address entity.
     *
     * @Route("/{id}/delete", name="ecgpb.member.address.delete")
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $address = $em->getRepository(Address::class)->find($id);
        /* @var $address Address */

        if (!$address) {
            throw $this->createNotFoundException('Unable to find Address entity.');
        }

        foreach ($address->getPersons() as $person) {
            if ($person->getLeaderOf()) {
                $person->getLeaderOf()->setLeader(null);
            }
        }

        $em->remove($address);
        $em->flush();

        $this->addFlash('success', 'The entry has been deleted.');

        if ($referrer = $request->headers->get('referer')) {
            return $this->redirect($referrer);
        }

        return $this->redirect($this->generateUrl('ecgpb.member.address.index'));
    }

    /**
     * Creates a form to create a Address entity.
     */
    private function createAddressForm(Address $address): \Symfony\Component\Form\Form
    {
        $form = $this->createForm(AddressType::class, $address, [
            'method' => $address->getId() ? 'PUT' : 'POST',
        ]);

        return $form;
    }
}
