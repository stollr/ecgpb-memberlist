<?php

namespace App\Controller;

use App\Repository\WorkingGroupRepository;
use App\Service\ChurchTools\WorkingGroupSynchronizer;
use CTApi\CTConfig;
use CTApi\Models\Groups\Group\GroupRequest;
use CTApi\Models\Groups\Group\GroupTypeRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/churchtools-sync')]
class ChurchtoolsSyncController extends AbstractController
{
    public function __construct(string $churchtoolsApiBaseUrl, #[\SensitiveParameter] string $churchtoolsApiToken)
    {
        CTConfig::setApiUrl($churchtoolsApiBaseUrl);
        CTConfig::setApiKey($churchtoolsApiToken);
    }

    #[Route('/', name: 'app.churchtools_sync.index')]
    public function indexAction(WorkingGroupRepository $workingGroupRepo): Response
    {
        $groupTypes = GroupTypeRequest::all();

        return $this->render('churchtools_sync/index.html.twig', [
            'workingGroups' => $workingGroupRepo->findAll(),
            'groupTypes' => $groupTypes,
        ]);
    }

    #[Route('/sync-working-groups', name: 'app.churchtools_sync.sync_working_groups', methods: ['POST'])]
    public function syncWorkingGroupsAction(Request $request, WorkingGroupSynchronizer $synchronizer): Response
    {
        $groupTypeId = $request->request->getInt('group_type_id');

        try {
            $issues = $synchronizer->synchronizeAll($groupTypeId);

            foreach ($issues as $issue) {
                $this->addFlash('warning', $issue);
            }

            $this->addFlash('success', 'Synchronized all working groups.');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }



        return $this->redirectToRoute('app.churchtools_sync.index');
    }
}
