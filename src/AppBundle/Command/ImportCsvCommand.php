<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Address;
use AppBundle\Entity\Person;

/**
 * AppBundle\Command\ImportCsv
 *
 * @author naitsirch
 */
class ImportCsvCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('ecgpb:member:import-csv')
            ->setDescription('Import a CSV file with all members.')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'The absolute filename of the CSV file to import.')
            ->addOption('separator', null, InputOption::VALUE_OPTIONAL, 'The absolute filename of the CSV file to import.', ',')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getOption('file');
        $separator = $input->getOption('separator');
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException("The file '$filename' does not exist.");
        }

        $em = $this->getContainer()->get('doctrine')->getManager(); /* @var $em \Doctrine\Common\Persistence\ObjectManager */

        // load existing members
        $existingPersons = array();
        foreach ($em->getRepository('AppBundle:Person')->findAll() as $person) {
            $index = $person->getLastnameAndFirstname() . ', ' . $person->getDob()->format('d.m.Y');
            $existingPersons[$index] = $person;
        }


        $headerRow = null;
        $addresses = array();
        $fp = fopen($filename, 'r');
        while ($row = fgetcsv($fp, 0, $separator, '"')) {
            if (!$headerRow) {
                $headerRow = $row;
                if (!in_array('NAME', $headerRow)) {
                    throw new \InvalidArgumentException('The file syntax is not correct. Either '
                        . 'you have not given the correct header columns or you have not used "," '
                        . 'as column separator.'
                    );
                }
                continue;
            }

            $row = array_combine($headerRow, $row);

            // check if user exists already
            $index = trim($row['NAME']) . ', ' . trim($row['VORNAME']) . ', ' . trim($row['Geburtsdat.']);
            if (isset($existingPersons[$index])) {
                $person = $existingPersons[$index];
            } else {
                $person = new Person();
            }
            
            $person->setDob(new \DateTime(trim($row['Geburtsdat.'])));
            $person->setEmail(empty($row['EMAIL']) ? null : trim($row['EMAIL']));
            $person->setFirstname(trim($row['VORNAME']));
            $person->setGender(trim($row['Geschlecht']) == Person::GENDER_FEMALE ? Person::GENDER_FEMALE : Person::GENDER_MALE);
            $person->setMobile(empty($row['Handy']) ? null : trim($row['Handy']));
            $em->persist($person);

            $addressKey = implode('|', array(trim($row['NAME']), trim($row['STRASSE']), trim($row['PLZ']), trim($row['Nummer'])));

            if ($person->getId()) {
                $output->writeln('Updated ' . $index);
            } else {
                if (isset($addresses[$addressKey])) {
                    $address = $addresses[$addressKey];
                } else {
                    $address = new Address();
                    $address->setCity(trim($row['ORT']));
                    $address->setFamilyName(trim($row['NAME']));
                    $address->setPhone(empty($row['Nummer']) ? null : trim($row['Nummer']));
                    $address->setStreet(trim($row['STRASSE']));
                    $address->setZip(trim($row['PLZ']));
                    $addresses[$addressKey] = $address;
                }

                $address->addPerson($person);
                $em->persist($address);

                $output->writeln('Persisted ' . $index);
            }
        }

        $em->flush();

        $output->writeln('Import finished.');
    }
}
