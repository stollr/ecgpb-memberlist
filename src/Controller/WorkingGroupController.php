<?php

namespace App\Controller;

use App\Entity\Person;
use App\Entity\WorkingGroup;
use App\Form\WorkingGroupType;
use App\Repository\PersonRepository;
use App\Repository\WorkingGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * WorkingGroup controller.
 */
#[Route(path: '/working-group')]
class WorkingGroupController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Lists all WorkingGroup workingGroups.
     */
    #[Route(path: '/', name: 'app.workinggroup.index')]
    public function indexAction(WorkingGroupRepository $repo): Response
    {
        $workingGroups = $repo->findAllForListing();

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
                $this->entityManager->persist($workingGroup);
                $this->entityManager->flush();

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
        $form = $this->createForm(WorkingGroupType::class, $workingGroup);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->entityManager->flush();

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
        $this->entityManager->remove($workingGroup);
        $this->entityManager->flush();

        $this->addFlash('success', 'The entry has been deleted.');

        return $this->redirectToRoute('app.workinggroup.index');
    }

    #[Route(path: '/assignables', name: 'app.workinggroup.assignables')]
    public function assignablesAction(
        PersonRepository $personRepo,
        WorkingGroupRepository $workingGroupRepo,
    ): Response {
        $persons = $personRepo->findPersonsToBeAssignedToWorkingGroup();
        $workingGroups = $workingGroupRepo->findAll();

        return $this->render('/working_group/assignables.html.twig', array(
            'persons' => $persons,
            'working_groups' => $workingGroups,
        ));
    }

    #[Route(path: '/assign', name: 'app.workinggroup.assign', methods: ['POST'])]
    public function assignAction(
        Request $request,
        PersonRepository $personRepo,
        WorkingGroupRepository $groupRepo,
    ): Response {
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

        $this->entityManager->flush();

        $this->addFlash('success', 'Saved assignments.');

        return $this->redirectToRoute('app.workinggroup.assignables');
    }

    #[Route(path: '/persons_unable_to_work', name: 'app.workinggroup.persons_unable_to_work')]
    public function personsUnableToWorkAction(PersonRepository $personRepo): Response
    {
        $persons = $personRepo->findPersonsUnableToWork();

        return $this->render('working_group/persons_unable_to_work.html.twig', array(
            'persons' => $persons,
            'allWorkerStatus' => Person::getAllWorkerStatus(),
        ));
    }

    #[Route(path: '/update_worker_status', name: 'app.workinggroup.update_worker_status', methods: ['POST'])]
    public function updateWorkerStatusAction(
        Request $request,
        PersonRepository $personRepo,
        TranslatorInterface $translator
    ): Response {
        $workerStatus = $request->get('worker_status', []);

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

        $this->entityManager->flush();

        $msg = 'The worker status of %number% persons has been changed.';
        $this->addFlash('success', $translator->trans($msg, ['%number%' => $changedStatus]));

        return $this->redirectToRoute('app.workinggroup.persons_unable_to_work');
    }
}
