<?php

namespace App\Controller;

use App\Entity\Person;
use App\Entity\WorkingGroup;
use App\Form\WorkingGroupType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * WorkingGroup controller.
 */
#[Route(path: '/workinggroup')]
class WorkingGroupController extends AbstractController
{

    /**
     * Lists all WorkingGroup workingGroups.
     */
    #[Route(path: '/', name: 'app.workinggroup.index')]
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
     */
    #[Route(path: '/new', name: 'app.workinggroup.new')]
    public function new(Request $request)
    {
        $workingGroup = new WorkingGroup();
        $form = $this->createForm(WorkingGroupType::class, $workingGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($workingGroup);
                $em->flush();

                $this->addFlash('success', 'The entry has been created.');

                return $this->redirectToRoute('app.workinggroup.edit', ['id' => $workingGroup->getId()]);
            }

            $this->addFlash('error', 'The submitted data is invalid. Please check your inputs.');
        }

        return $this->render('working_group/form.html.twig', [
            'workingGroup' => $workingGroup,
            'form'   => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing WorkingGroup entity.
     */
    #[Route(path: '/{id}/edit', name: 'app.workinggroup.edit')]
    public function edit(WorkingGroup $workingGroup, Request $request)
    {
        $form = $this->createForm(WorkingGroupType::class, $workingGroup, [
            'method' => 'PUT',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->addFlash('success', 'All changes have been saved.');

                return $this->redirectToRoute('app.workinggroup.edit', ['id' => $workingGroup->getId()]);
            }

            $this->addFlash('error', 'The submitted data is invalid. Please check your inputs.');
        }

        return $this->render('working_group/form.html.twig', array(
            'workingGroup' => $workingGroup,
            'form' => $form->createView(),
        ));
    }

    /**
     * Deletes a WorkingGroup entity.
     */
    #[Route(path: '/{id}/delete', name: 'app.workinggroup.delete')]
    public function delete(WorkingGroup $workingGroup)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($workingGroup);
        $em->flush();

        $this->addFlash('success', 'The entry has been deleted.');

        return $this->redirectToRoute('app.workinggroup.index');
    }

    #[Route(path: '/assignables', name: 'app.workinggroup.assignables')]
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

    #[Route(path: '/assign', name: 'app.workinggroup.assign', methods: ['POST'])]
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

        return $this->redirect($this->generateUrl('app.workinggroup.assignables'));
    }

    #[Route(path: '/persons_unable_to_work', name: 'app.workinggroup.persons_unable_to_work')]
    public function personsUnableToWorkAction()
    {
        $em = $this->getDoctrine()->getManager();

        $personRepo = $em->getRepository(Person::class);
        $persons = $personRepo->findPersonsUnableToWork();

        return $this->render('working_group/persons_unable_to_work.html.twig', array(
            'persons' => $persons,
            'allWorkerStatus' => Person::getAllWorkerStatus(),
        ));
    }

    #[Route(path: '/update_worker_status', name: 'app.workinggroup.update_worker_status', methods: ['POST'])]
    public function updateWorkerStatusAction(Request $request, TranslatorInterface $translator)
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
        $this->addFlash('success', $translator->trans($msg, ['%number%' => $changedStatus]));

        return $this->redirectToRoute('app.workinggroup.persons_unable_to_work');
    }
}
