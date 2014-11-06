<?php

namespace Ecgpb\MemberBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ecgpb\MemberBundle\Entity\WorkingGroup;
use Ecgpb\MemberBundle\Form\WorkingGroupType;

/**
 * WorkingGroup controller.
 * @/Security("has_role('ROLE_ADMIN')")
 */
class WorkingGroupController extends Controller
{

    /**
     * Lists all WorkingGroup workingGroups.
     *
     * @Route("/", name="ecgpb.member.workinggroup.index", defaults={"_locale"="de"})
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $workingGroups = $em->getRepository('EcgpbMemberBundle:WorkingGroup')->findAllForListing();

        return $this->render('EcgpbMemberBundle:WorkingGroup:index.html.twig', array(
            'working_groups' => $workingGroups,
        ));
    }

    /**
     * Displays a form to create a new WorkingGroup entity.
     *
     * @Route("/new", name="ecgpb.member.workinggroup.new", defaults={"_locale"="de"})
     */
    public function newAction()
    {
        $workingGroup = new WorkingGroup();
        $form   = $this->createCreateForm($workingGroup);

        return $this->render('EcgpbMemberBundle:WorkingGroup:form.html.twig', array(
            'working_group' => $workingGroup,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a new WorkingGroup entity.
     *
     * @Route("/create", name="ecgpb.member.workinggroup.create", defaults={"_locale"="de"})
     * @Method({"POST"})
     */
    public function createAction(Request $request)
    {
        $workingGroup = new WorkingGroup();
        $form = $this->createCreateForm($workingGroup);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($workingGroup);
            $em->flush();

            return $this->redirect($this->generateUrl('ecgpb.member.workinggroup.edit', array('id' => $workingGroup->getId())));
        }

        return $this->render('EcgpbMemberBundle:WorkingGroup:form.html.twig', array(
            'working_group' => $workingGroup,
            'form'   => $form->createView(),
        ));
    }

    /**
    * Creates a form to create a WorkingGroup entity.
    *
    * @param WorkingGroup $workingGroup The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createCreateForm(WorkingGroup $workingGroup)
    {
        $form = $this->createForm(new WorkingGroupType($workingGroup), $workingGroup, array(
            'action' => $this->generateUrl('ecgpb.member.workinggroup.create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'role' => 'form',
            ),
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to edit an existing WorkingGroup entity.
     *
     * @Route("/{id}/edit", name="ecgpb.member.workinggroup.edit", defaults={"_locale"="de"})
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $workingGroup = $em->getRepository('EcgpbMemberBundle:WorkingGroup')->find($id);

        if (!$workingGroup) {
            throw $this->createNotFoundException('Unable to find WorkingGroup entity.');
        }

        $editForm = $this->createEditForm($workingGroup);

        return $this->render('EcgpbMemberBundle:WorkingGroup:form.html.twig', array(
            'working_group'      => $workingGroup,
            'form'   => $editForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a WorkingGroup entity.
    *
    * @param WorkingGroup $workingGroup The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(WorkingGroup $workingGroup)
    {
        $form = $this->createForm(new WorkingGroupType($workingGroup), $workingGroup, array(
            'action' => $this->generateUrl('ecgpb.member.workinggroup.update', array('id' => $workingGroup->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'role' => 'form',
            ),
        ));

        $form->add('submit', 'submit', array('label' => 'Save'));

        return $form;
    }

    /**
     * Edits an existing WorkingGroup entity.
     *
     * @Route("/{id}/update", name="ecgpb.member.workinggroup.update", defaults={"_locale"="de"})
     * @Method({"POST"})
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $workingGroup = $em->getRepository('EcgpbMemberBundle:WorkingGroup')->find($id);
        /* @var $workingGroup WorkingGroup */

        if (!$workingGroup) {
            throw $this->createNotFoundException('Unable to find WorkingGroup entity.');
        }

        $oldPersons = $workingGroup->getPersons()->toArray();

        $editForm = $this->createEditForm($workingGroup);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            foreach ($oldPersons as $oldPerson) {
                if (!$workingGroup->getPersons()->contains($oldPerson)) {
                    $workingGroup->removePerson($oldPerson);
                }
            }

            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'All changes have been saved.');

            return $this->redirect($this->generateUrl('ecgpb.member.workinggroup.edit', array('id' => $id)));
        }

        return $this->render('EcgpbMemberBundle:WorkingGroup:form.html.twig', array(
            'working_group'      => $workingGroup,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Deletes a WorkingGroup entity.
     *
     * @Route("/{id}/delete", name="ecgpb.member.workinggroup.delete", defaults={"_locale"="de"})
     */
    public function deleteAction(Request $request, $id)
    {
        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();
        $workingGroup = $em->getRepository('EcgpbMemberBundle:WorkingGroup')->find($id);

        if (!$workingGroup) {
            throw $this->createNotFoundException('Unable to find WorkingGroup entity.');
        }

        $em->remove($workingGroup);
        $em->flush();

        return $this->redirect($this->generateUrl('ecgpb.member.workinggroup.index'));
    }
}
