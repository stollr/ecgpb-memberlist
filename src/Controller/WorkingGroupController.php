<?php

namespace App\Controller;

use App\Entity\Person;
use App\Entity\WorkingGroup;
use App\Form\WorkingGroupType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Routing\Annotation\Route;

/**
 * WorkingGroup controller.
 *
 * @Route("/workinggroup")
 * @/Security("has_role('ROLE_ADMIN')")
 */
class WorkingGroupController extends Controller
{

    /**
     * Lists all WorkingGroup workingGroups.
     *
     * @Route("/", name="ecgpb.member.workinggroup.index")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $workingGroups = $em->getRepository(WorkingGroup::class)->findAllForListing();

        return $this->render('/working_group/index.html.twig', array(
            'working_groups' => $workingGroups,
        ));
    }

    /**
     * Displays a form to create a new WorkingGroup entity.
     *
     * @Route("/new", name="ecgpb.member.workinggroup.new")
     */
    public function newAction()
    {
        $workingGroup = new WorkingGroup();
        $form   = $this->createCreateForm($workingGroup);

        return $this->render('/working_group/form.html.twig', array(
            'working_group' => $workingGroup,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a new WorkingGroup entity.
     *
     * @Route("/create", name="ecgpb.member.workinggroup.create", methods={"POST"})
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

        return $this->render('/working_group/form.html.twig', array(
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
        $form = $this->createForm(WorkingGroupType::class, $workingGroup, array(
            'action' => $this->generateUrl('ecgpb.member.workinggroup.create'),
            'method' => 'POST',
            'attr' => array(
                'class' => 'form-horizontal',
                'role' => 'form',
            ),
            'working_group' => $workingGroup,
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to edit an existing WorkingGroup entity.
     *
     * @Route("/{id}/edit", name="ecgpb.member.workinggroup.edit")
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $workingGroup = $em->getRepository(WorkingGroup::class)->find($id);

        if (!$workingGroup) {
            throw $this->createNotFoundException('Unable to find WorkingGroup entity.');
        }

        $editForm = $this->createEditForm($workingGroup);

        return $this->render('/working_group/form.html.twig', array(
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
        $form = $this->createForm(WorkingGroupType::class, $workingGroup, array(
            'action' => $this->generateUrl('ecgpb.member.workinggroup.update', array('id' => $workingGroup->getId())),
            'method' => 'PUT',
            'attr' => array(
                'class' => 'form-horizontal',
                'role' => 'form',
            ),
            'working_group' => $workingGroup,
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Save'));

        return $form;
    }

    /**
     * Edits an existing WorkingGroup entity.
     *
     * @Route("/{id}/update", name="ecgpb.member.workinggroup.update", methods={"POST", "PUT"})
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $workingGroup = $em->getRepository(WorkingGroup::class)->find($id);
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

            $this->addFlash('success', 'All changes have been saved.');

            return $this->redirect($this->generateUrl('ecgpb.member.workinggroup.edit', array('id' => $id)));
        }

        return $this->render('/working_group/form.html.twig', array(
            'working_group'      => $workingGroup,
            'form'   => $editForm->createView(),
        ));
    }

    /**
     * Deletes a WorkingGroup entity.
     *
     * @Route("/{id}/delete", name="ecgpb.member.workinggroup.delete")
     */
    public function deleteAction(Request $request, $id)
    {
        $form->handleRequest($request);

        $em = $this->getDoctrine()->getManager();
        $workingGroup = $em->getRepository(WorkingGroup::class)->find($id);

        if (!$workingGroup) {
            throw $this->createNotFoundException('Unable to find WorkingGroup entity.');
        }

        $em->remove($workingGroup);
        $em->flush();

        return $this->redirect($this->generateUrl('ecgpb.member.workinggroup.index'));
    }

    /**
     * @Route("/assignables", name="ecgpb.member.workinggroup.assignables"))
     */
    public function assignablesAction()
    {
        $em = $this->getDoctrine()->getManager();
        
        $personRepo = $em->getRepository(Person::class);
        $persons = $personRepo->findPersonsToBeAssignedToWorkingGroup();

        $workingGroups = $em->getRepository(WorkingGroup::class)->findAll();

        return $this->render('/working_group/assignables.html.twig', array(
            'persons' => $persons,
            'working_groups' => $workingGroups,
        ));
    }

    /**
     * @Route("/assign", name="ecgpb.member.workinggroup.assign", methods={"POST"})
     */
    public function assignAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $personRepo = $em->getRepository(Person::class);
        $groupRepo = $em->getRepository(WorkingGroup::class);

        foreach ($request->get('person-to-group', array()) as $personId => $groupId) {
            if (empty($personId) || empty($groupId)) {
                continue;
            }
            $person = $personRepo->find($personId); /* @var $person \App\Entity\Person */
            $group = $groupRepo->find($groupId); /* @var $group WorkingGroup */

            if (!$person || !$group) {
                $this->addFlash('error', sprintf('Person with ID %s or working group with ID %s does not exist.', $personId, $groupId));
            } else if ($person->getGender() != $group->getGender()) {
                $this->addFlash('error', sprintf('Gender of "%s" does not match with working group\'s gender.', $person->getLastnameAndFirstname()));
            } else {
                $person->setWorkingGroup($group);
            }
        }

        $em->flush();

        $this->addFlash('success', 'Saved assignments.');

        return $this->redirect($this->generateUrl('ecgpb.member.workinggroup.assignables'));
    }

    /**
     * @Route("/persons_unable_to_work", name="ecgpb.member.workinggroup.persons_unable_to_work")
     */
    public function personsUnableToWorkAction()
    {
        $em = $this->getDoctrine()->getManager();

        $personRepo = $em->getRepository(Person::class);
        $persons = $personRepo->findPersonsUnableToWork();

        return $this->render('/working_group/persons_unable_to_work.html.twig', array(
            'persons' => $persons,
            'allWorkerStatus' => Person::getAllWorkerStatus(),
        ));
    }

    /**
     * @Route("/update_worker_status", name="ecgpb.member.workinggroup.update_worker_status", methods={"POST"})
     */
    public function updateWorkerStatusAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $workerStatus = $request->get('worker_status', []);

        $personRepo = $em->getRepository(Person::class);
        $persons = $personRepo->findBy(['id' => array_keys($workerStatus)]);

        $changedStatus = 0;
        foreach ($persons as $person) {
            /* @var $person Person */
            $newStatus = $workerStatus[$person->getId()];
            $newStatus = '' === $newStatus ? null : (int) $newStatus;

            if ($person->getWorkerStatus() !== $newStatus) {
                $person->setWorkerStatus($newStatus);
                $changedStatus++;
            }
        }

        $em->flush();

        $msg = 'The worker status of %number% persons has been changed.';
        $this->addFlash('success', $this->get('translator')->trans($msg, ['%number%' => $changedStatus]));

        return $this->redirectToRoute('ecgpb.member.workinggroup.persons_unable_to_work');
    }
}
