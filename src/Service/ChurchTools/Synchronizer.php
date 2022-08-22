<?php

namespace App\Service\ChurchTools;

use App\Entity\Person;
use App\Repository\PersonRepository;
use CTApi\CTConfig;
use CTApi\Models\Person as CtPerson;
use CTApi\Requests\PersonRequest;

/**
 * Synchronizer our local data with the ChurchTools data.
 *
 * @author naitsirch
 */
class Synchronizer
{
    const SYNC_NOTHING = 0;
    const SYNC_DOWNSTREAM = 1;
    const SYNC_UPSTREAM = 2;

    private string $apiBaseUrl;

    private string $apiToken;

    private PersonRepository $personRepo;

    public function __construct(string $apiBaseUrl, string $apiToken, PersonRepository $personRepo)
    {
        $this->apiBaseUrl = $apiBaseUrl;
        $this->apiToken = $apiToken;
        $this->personRepo = $personRepo;

        CTConfig::setApiUrl($apiBaseUrl);
        CTConfig::setApiKey($apiToken);
    }

    public function iterateOverChurchtoolPersons(): \Generator
    {
        $page = 0;
        $limit = 15;

        while (true) {
            $page++;

            /** @var CtPerson[] $ctPersons */
            $ctPersons = PersonRequest::where('page', $page)
                ->where('limit', $limit)
                ->get();

            foreach ($ctPersons as $ctPerson) {
                yield $ctPerson;
            }

            if (count($ctPersons) < $limit) {
                break;
            }
        }
    }

    public function overrideLocalPerson(?Person $person, ?CtPerson $ctPerson): void
    {
        if (!$person && !$ctPerson) {
            return;
        }

        if (!$ctPerson) {
            $this->personRepo->remove($person);
            return;
        }

        $person->setMobile($ctPerson->getMobile());
        $person->setEmail($ctPerson->getEmail());
        $person->getAddress()->setStreet($ctPerson->getStreet());
        $person->getAddress()->setZip($ctPerson->getZip());
        $person->getAddress()->setCity($ctPerson->getCity());
        $person->getAddress()->setPhone($ctPerson->getPhonePrivate());
    }

    public function overrideChurchToolsPerson(?CtPerson $ctPerson, ?Person $person): void
    {
        if (!$person && !$ctPerson) {
            return;
        }

        if (!$person) {
            PersonRequest::delete($ctPerson);
            return;
        }

        $ctPerson->setMobile($person->getMobile() ?: '');
        $ctPerson->setEmail($person->getEmail() ?: '');
        $ctPerson->setStreet($person->getAddress()->getStreet() ?: '');
        $ctPerson->setZip($person->getAddress()->getZip() ?: '');
        $ctPerson->setCity($person->getAddress()->getCity() ?: '');
        $ctPerson->setPhonePrivate($person->getAddress()->getPhone() ?: '');

        PersonRequest::update($ctPerson, [
            'mobile', 'email', 'street', 'zip', 'city', 'phonePrivate',
        ]);
    }

    /**
     * Compare a local person's data with a person from ChurchTools.
     *
     * @return array The returned array contains all attributes that have differences
     *               with the attribute as key and an indexed array, first with
     *               the value of the local person and the value of the ChurchTools
     *               record at position two.
     */
    public static function diff(?Person $person, ?CtPerson $ctPerson): array
    {
        if ($person === null && $ctPerson === null) {
            return [];
        }

        $a = $b = [];

        if ($person !== null) {
            $address = $person->getAddress();
            $lastname = $person->getLastname() ?: $address->getFamilyName();

            $a = [
                'lastname' => $lastname,
                'firstname' => $person->getFirstname(),
                'dob' => $person->getDob()->format('Y-m-d'),
                'mobile' => $person->getMobile(),
                'email' => $person->getEmail(),
                'street' => $address->getStreet(),
                'zip' => $address->getZip(),
                'city' => $address->getCity(),
                'phone' => $address->getPhone(),
            ];
        }

        if ($ctPerson !== null) {
            $b = [
                'lastname' => $ctPerson->getLastName(),
                'firstname' => $ctPerson->getFirstName(),
                'dob' => $ctPerson->getBirthday(),
                'mobile' => $ctPerson->getMobile(),
                'email' => $ctPerson->getEmail(),
                'street' => $ctPerson->getStreet(),
                'zip' => $ctPerson->getZip(),
                'city' => $ctPerson->getCity(),
                'phone' => $ctPerson->getPhonePrivate(),
            ];
        }

        $attributes = array_unique(array_merge(array_keys($a), array_keys($b)));
        $diffs = [];

        foreach ($attributes as $attr) {
            if (!isset($a[$attr])) {
                $a[$attr] = null;
            } elseif (!isset($b[$attr])) {
                $b[$attr] = null;
            }

            if (in_array($attr, ['phone', 'mobile'])) {
                // normalize phone numbers for comparison
                $a[$attr] = self::normalizePhoneNumber($a[$attr]);
                $b[$attr] = self::normalizePhoneNumber($b[$attr]);
            } elseif ($b[$attr] === '') {
                $b[$attr] = null;
            }

            if ($a[$attr] === $b[$attr]) {
                continue;
            }

            $diffs[$attr] = [
                $a[$attr],
                $b[$attr],
            ];
        }

        return $diffs;
    }

    public static function normalizePhoneNumber(?string $phoneNumber): string
    {
        if (null === $phoneNumber) {
            return '';
        }
        
        $phoneNumber = trim(str_replace(['-', ' '], '', $phoneNumber));

        if (substr($phoneNumber, 0, 3) === '+49') {
            $phoneNumber = '0' . substr($phoneNumber, 3);
        }

        return $phoneNumber;
    }
}
