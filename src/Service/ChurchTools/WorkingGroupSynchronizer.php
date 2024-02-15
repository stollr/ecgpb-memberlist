<?php

namespace App\Service\ChurchTools;

use App\Entity\Person;
use App\Entity\WorkingGroup;
use App\Repository\PersonRepository;
use App\Repository\WorkingGroupRepository;
use CTApi\CTConfig;
use CTApi\Models\Groups\Group\Group as CtGroup;
use CTApi\Models\Groups\Group\GroupRequest;
use CTApi\Models\Groups\GroupMember\GroupMemberRequest;
use CTApi\Models\Groups\GroupTypeRole\GroupTypeRole;
use CTApi\Models\Groups\GroupTypeRole\GroupTypeRoleRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Synchronizer our local data with the ChurchTools data.
 */
class WorkingGroupSynchronizer
{
    private array $cachedGroupTypeRoles;

    public function __construct(
        private readonly WorkingGroupRepository $workingGroupRepo,
        private readonly PersonRepository $personRepo,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly int $ageLimit,
        string $churchtoolsApiBaseUrl,
        #[\SensitiveParameter]
        string $churchtoolsApiToken,
    ) {
        CTConfig::setApiUrl($churchtoolsApiBaseUrl);
        CTConfig::setApiKey($churchtoolsApiToken);
    }

    /**
     * Synchronizes all working groups with the groups in ChurchTools.
     *
     * @return string[]
     * @throws UnexpectedValueException
     * @throws LogicException
     */
    public function synchronizeAll(int $groupTypeId): array
    {
        $ctGroups = GroupRequest::where('group_type_ids', [$groupTypeId])
            ->get();

        $groups = $this->workingGroupRepo->findAll();
        $personIdsToGroups = [];
        $issues = [];

        foreach ($ctGroups as $ctGroup) {
            if (!preg_match('/Arbeitsgruppe (F|M) - (\d+)/', $ctGroup->getName(), $matches)) {
                throw new UnexpectedValueException("Churchtools working group \"{$ctGroup->getName()}\" has invalid name.");
            }

            $ctGender = strtolower($matches[1]);
            $ctNumber = (int) $matches[2];

            $group = current(array_filter($groups, fn($g) => $g->getChurchToolsId() === (int) $ctGroup->getId()));

            if (!$group) {
                $group = current(array_filter($groups, fn($g) => $g->getGender() === $ctGender && $g->getNumber() === $ctNumber));
            }

            if ($group) {
                $index = array_search($group, $groups, true);
                array_splice($groups, $index, 1);
            } else {
                $group = new WorkingGroup();

                $this->entityManager->persist($group);
            }

            $group->setChurchToolsId($ctGroup->getId());
            $group->setGender($ctGender);
            $group->setNumber($ctNumber);

            $this->synchronizeMembersOfGroup($ctGroup, $group, $personIdsToGroups, $issues);
        }

        // Check if a user is in multiple working groups in ChurchTools
        foreach ($personIdsToGroups as $personId => $_ctGroups) {
            if (count($_ctGroups) > 1) {
                $person = $this->personRepo->find($personId);
                
                $issues[] = $this->translator->trans('%person% is assigned to multiple groups: %groupNames%', [
                    '%person%' => $person->getDisplayNameDob(),
                    '%groupNames%' => implode(', ', array_map(fn ($g) => $g->getName(), $_ctGroups)),
                ]);
            }
        }

        foreach ($groups as $group) {
            $this->entityManager->remove($group);
        }

        $this->entityManager->flush();

        return $issues;
    }

    public function synchronizeMembersOfGroup(
        CtGroup $ctGroup,
        WorkingGroup $group,
        array &$personIdsToGroups,
        array &$issues
    ): void {
        $ctMembers = GroupMemberRequest::get($ctGroup->getId())->get();
        $members = $group->getPersons()->toArray();
        $leaderPerson = null;

        foreach ($ctMembers as $ctMember) {
            $ctPerson = $ctMember->getPerson();
            $ctRole = $this->loadCtGroupTypeRole($ctMember->getGroupTypeRoleId());

            $isLeader = match ($ctRole->getName()) { // $ctRole->getIsLeader() is not administrated, correctly
                'Leiter' => true,
                'Teilnehmer' => false,
                // we want this to throw an exception, because we do not know the role name.
            };

            if ($isLeader && $leaderPerson) {
                $issues[] = sprintf('Group "%" has multiple leaders.', $ctGroup->getName());
            }

            foreach ($members as $index => $_member) {
                if (!$_member->getChurchToolsId()) {
                    throw new \LogicException("{$_member->getDisplayNameDob()} has no ChurchTools ID. Please make a sync first.");
                }

                if ($_member->getChurchToolsId() === (int) $ctMember->getPersonId()) {
                    // Person is already member of the group, continue with next member
                    array_splice($members, $index, 1);

                    if ($isLeader) {
                        $leaderPerson = $_member;
                    }

                    continue 2;
                }
            }

            $person = $this->personRepo->findOneBy(['churchToolsId' => $ctMember->getPersonId()]);

            if (!$person) {
                $person = $this->personRepo->findOneByLastnameFirstnameAndDob(
                    $ctPerson->getLastName(),
                    $ctPerson->getFirstName(),
                    new \DateTimeImmutable($ctPerson->getBirthday())
                );
                $person?->setChurchToolsId($ctMember->getPersonId());
            }

            if (!$person) {
                $issues[] = $this->translator->trans("The person %name% could not be found in the local database.", [
                    '%name%' => "{$ctPerson->getFirstName()} {$ctPerson->getLastName()} ({$ctPerson->getBirthday()})",
                ]);
                continue;
            }

            if ($person->getAge() >= $this->ageLimit || $person->getWorkerStatus() !== Person::WORKER_STATUS_UNTIL_AGE_LIMIT) {
                $issues[] = $this->translator->trans('%person% is member of working group "%group%" in ChurchTools although the person is unsuitable.', [
                    '%person%' => $person->getDisplayNameDob(),
                    '%group%' => $ctGroup->getName(),
                ]);
                continue;
            }

            $person->setWorkingGroup($group);
            $personIdsToGroups[$person->getId()][] = $ctGroup;

            if ($isLeader) {
                $leaderPerson = $person;
            }
        }

        $group->setLeader($leaderPerson);

        // Remaining members should be removed from local groups
        foreach ($members as $person) {
            $group->removePerson($person);
        }
    }

    private function loadCtGroupTypeRole(int $groupTypeRoleId): GroupTypeRole
    {
        if (!isset($this->cachedGroupTypeRoles)) {
            $this->cachedGroupTypeRoles = [];

            foreach (GroupTypeRoleRequest::all() as $role) {
                $this->cachedGroupTypeRoles[$role->getId()] = $role;
            }
        }

        return $this->cachedGroupTypeRoles[$groupTypeRoleId] ?? null;
    }
}
