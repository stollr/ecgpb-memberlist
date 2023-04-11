<?php

namespace App\Command;

use App\Entity\Person;
use App\Repository\PersonRepository;
use App\Service\ChurchTools\Synchronizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Command to synchronize personal data upstream to churchtools.
 *
 * @author naitsirch <naitsirch@e.mail.de>
 */
class ChurchtoolsSyncCommand extends Command
{
    private EntityManagerInterface $entityManager;

    private Synchronizer $synchronizer;

    private PersonRepository $personRepo;

    public function __construct(
        EntityManagerInterface $entityManager,
        Synchronizer $synchronizer,
        PersonRepository $personRepo,
    ) {
        parent::__construct(null);

        $this->entityManager = $entityManager;
        $this->synchronizer = $synchronizer;
        $this->personRepo = $personRepo;
    }

    protected function configure()
    {
        $this
            ->setName('churchtools:sync')
            ->setDescription('Synchronize personal interactively from and to churchtools.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');

        $deletedChurchToolsPerson = false;
        $comparedPersons = $issues = $namesOfCtPersonsWithoutDob = [];

        foreach ($this->synchronizer->iterateOverChurchtoolPersons() as $ctPerson) {
            if (!$ctPerson->getBirthday()) {
                $name = $ctPerson->getFirstName() . ' ' . $ctPerson->getLastName();
                $namesOfCtPersonsWithoutDob[] = $name;
                $issues[] = $msg = "$name is available in ChurchTools, but has no date of birth. "
                    . "A comparison is inappropriate, because different persons may have the same name. "
                    . "Date of birth is needed for automatic comparison. Please check manually.\n";
                $output->writeln("$msg\n---------------------------\n");
                continue;
            }

            $person = $this->personRepo->findOneOrNullByLastnameFirstnameAndDob(
                $ctPerson->getLastName(),
                $ctPerson->getFirstName(),
                new \DateTimeImmutable($ctPerson->getBirthday())
            );

            if ($person) {
                $comparedPersons[] = $person;

                $this->synchronizer->uploadChurchToolsPersonImage($ctPerson, $person);
            }

            $diff = $this->synchronizer->diff($person, $ctPerson);

            if (empty($diff)) {
                $output->writeln("{$person->getDisplayNameDob()} is in sync.\n");
                $output->writeln("---------------------------\n");
                continue;
            }

            if ($person) {
                $output->writeln($person->getDisplayNameDob() . "\n");
            } else {
                $name = $ctPerson->getFirstName() . ' ' . $ctPerson->getLastName() . ' (' . $ctPerson->getBirthday() . ')';
                $output->writeln("$name is not available locally.\n");
            }

            $table = new Table($output);
            $table->setHeaders([
                'Attribute',
                'Local value',
                'ChurchTools value',
            ]);

            foreach ($diff as $attr => $values) {
                $table->addRow(array_merge([$attr], $values));
            }

            // Add last update timestamp
            $personLastUpdate = $ctPersonLastUpdate = '';

            if ($person?->getUpdatedAt() && $person->getUpdatedAt() > new \DateTimeImmutable('1970-01-01')) {
                $personLastUpdate = $person->getUpdatedAt()->format('Y-m-d H:i:s');
            }

            if ($person?->getAddress()->getUpdatedAt() && (!$personLastUpdate || $person?->getAddress()->getUpdatedAt() > $person->getUpdatedAt())) {
                $personLastUpdate = $person->getAddress()->getUpdatedAt()->format('Y-m-d H:i:s');
            }

            if ($ctPerson && $ctPerson->getMeta()->getModifiedDate()) {
                $ctPersonLastUpdate = (new \DateTimeImmutable($ctPerson->getMeta()->getModifiedDate()))->format('Y-m-d H:i:s');
            }

            $table->addRow(['Last update', $personLastUpdate, $ctPersonLastUpdate]);

            $table->render();

            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'How should the data be synchronized?',
                ['skip', 'update locally', 'update churchtools', 'terminate'],
                0
            );

            $mode = $helper->ask($input, $output, $question);

            if ($mode === 'skip') {
                $output->writeln('No synchronization done.');
            } elseif ($mode === 'update locally') {
                $tuple = $this->synchronizer->overrideLocalPerson($person, $ctPerson);
                $this->entityManager->flush();

                if (!$person) {
                    // Add the new person to the array, otherwise a copy will be
                    // created and added to ChurchTools
                    $comparedPersons[] = $tuple[0];
                }

                $output->writeln($ctPerson ? "Local person's data has been updated." : "Local person's data has been removed.");
            } elseif ($mode === 'update churchtools') {
                $this->synchronizer->overrideChurchToolsPerson($ctPerson, $person);

                if ($person) {
                    $output->writeln("Update of ChurchTool's person data completed.");
                } else {
                    $deletedChurchToolsPerson = true;
                    $output->writeln("Removal of ChurchTool's person data completed.");
                }
            } else {
                $output->writeln('Terminated interactive synchronization.');
                return 0;
            }
            
            $output->writeln("---------------------------\n");
        }

        // Sync all persons which are new in the local database to ChurchTools
        $persons = $this->personRepo->findAllNotIn($comparedPersons);

        if (count($persons) > 0 && $deletedChurchToolsPerson) {
            $issues[] = 'At least one person was removed from ChurchTools, which breaks the pagination. '
                . 'Please run this command again to insert new persons.';
        } else {
            $terminate = false;

            foreach ($persons as $person) {
                $name = $person->getFirstnameAndLastname();

                if (in_array($name, $namesOfCtPersonsWithoutDob, true)) {
                    // We cannot be sure that the person in ChurchTools without date of birth
                    // is not identical to $person, so we have to skip.
                    $issues[] = "{$person->getDisplayNameDob()} *not* added to ChurchTools, because another person "
                        . "with the same name, but without date of birth, already exists there. Please check manually.\n";
                    continue;
                }

                $this->askForSyncingMissingPersonToChurchtools($person, $input, $output, terminated: $terminate);

                $output->writeln("---------------------------\n");

                if ($terminate) {
                    break;
                }
            }
        }

        foreach ($issues as $issue) {
            $output->writeln(sprintf('<comment>- %s</comment>', $issue));
        }

        return 0;
    }

    private function askForSyncingMissingPersonToChurchtools(Person $person, InputInterface $input, OutputInterface $output, bool &$terminated = false): void
    {
        $output->writeln($person->getDisplayNameDob() . " is not available in Churchtools.\n");

        $table = new Table($output);
        $table->setHeaders([
            'Attribute',
            'Local value',
            'ChurchTools value',
        ]);
        
        foreach (Synchronizer::getFlatPersonDatas($person) as $attr => $value) {
            if (!empty($value)) {
                $table->addRow([$attr, $value, '']);
            }
        }

        $table->render();

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'How should the data be synchronized?',
            ['skip', 'delete locally', 'submit to churchtools', 'terminate'],
            0
        );

        $mode = $helper->ask($input, $output, $question);

        if ($mode === 'skip') {
            $output->writeln('No synchronization done.');
        } elseif ($mode === 'delete locally') {
            $this->synchronizer->overrideLocalPerson($person, null);
            $this->entityManager->flush();

            $output->writeln("Local person's data has been removed.");
        } elseif ($mode === 'submit to churchtools') {
            $this->synchronizer->overrideChurchToolsPerson(null, $person, force: true);

            $output->writeln("Person has been added to ChurchTool.");
        } else {
            $output->writeln('Terminated interactive synchronization.');

            $terminated = true;
        }
    }
}
