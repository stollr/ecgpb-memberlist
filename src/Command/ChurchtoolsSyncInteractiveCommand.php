<?php

namespace App\Command;

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
class ChurchtoolsSyncInteractiveCommand extends Command
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
            ->setName('churchtools:sync:interactive')
            ->setDescription('Synchronize personal interactively from and to churchtools.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');

        foreach ($this->synchronizer->iterateOverChurchtoolPersons() as $ctPerson) {
            $person = $this->personRepo->findOneOrNullByLastnameFirstnameAndDob(
                $ctPerson->getLastName(),
                $ctPerson->getFirstName(),
                new \DateTimeImmutable($ctPerson->getBirthday())
            );

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

            if ($person && $person->getUpdatedAt() && $person->getUpdatedAt() > new \DateTimeImmutable('1970-01-01')) {
                $personLastUpdate = $person->getUpdatedAt()->format('Y-m-d H:i:s');
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
                $this->synchronizer->overrideLocalPerson($person, $ctPerson);

                $output->writeln($ctPerson ? "Local person's data is going to be updated." : "Local person's data is going to be removed.");
            } elseif ($mode === 'update churchtools') {
                $this->synchronizer->overrideChurchToolsPerson($ctPerson, $person);

                $output->writeln($person ? "Update of ChurchTool's person data completed." : "Removal of ChurchTool's person data completed.");
            } else {
                $output->writeln('Terminated interactive synchronization.');
                break;
            }
            
            $output->writeln("---------------------------\n");
        }

        $this->entityManager->flush();

        $output->writeln("Saved local data.\n");

        return 0;
    }

}
