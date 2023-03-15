<?php

namespace App\Service\ChurchTools;

use App\Entity\Address;
use App\Entity\Person;
use App\Helper\PersonHelper;
use App\Repository\AddressRepository;
use App\Repository\PersonRepository;
use CTApi\CTConfig;
use CTApi\Models\Person as CtPerson;
use CTApi\Requests\FileRequest;
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

    private AddressRepository $addressRepo;

    private PersonHelper $personHelper;

    public function __construct(
        string $apiBaseUrl,
        string $apiToken,
        PersonRepository $personRepo,
        AddressRepository $addressRepo,
        PersonHelper $personHelper,
    ) {
        $this->apiBaseUrl = $apiBaseUrl;
        $this->apiToken = $apiToken;
        $this->personRepo = $personRepo;
        $this->addressRepo = $addressRepo;
        $this->personHelper = $personHelper;

        CTConfig::setApiUrl($apiBaseUrl);
        CTConfig::setApiKey($apiToken);
    }

    /**
     * @return CtPerson[]
     */
    public function iterateOverChurchtoolPersons(): \Generator
    {
        $page = 0;
        $limit = 15;

        while (true) {
            $page++;

            /** @var CtPerson[] $ctPersons */
            $ctPersons = PersonRequest::where('status_ids', [3]) // id 3 = member // we only compare members
                ->where('page', $page)
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

    /**
     * Overrides the local person from the data of Churchtools. If the local person
     * is null (doesn't exists) it will be created.
     *
     * @return array{0: ?Person, 1: ?CtPerson}
     */
    public function overrideLocalPerson(?Person $person, ?CtPerson $ctPerson): array
    {
        if (!$person && !$ctPerson) {
            return [$person, $ctPerson];
        }

        if (!$ctPerson) {
            if ($person->getAddress()->getPersons()->count() === 1) {
                $this->addressRepo->remove($person->getAddress());
            }

            $this->personRepo->remove($person);
            return [$person, $ctPerson];
        }

        if (!$person) {
            $person = new Person();
            $person->setAddress(new Address());

            $person->setFirstname(trim($ctPerson->getFirstName()));
            $person->getAddress()->setFamilyName(trim($ctPerson->getLastName()));

            if ($ctPerson->getSexId()) {
                $person->setGender($ctPerson->getSexId() === '2' ? Person::GENDER_FEMALE : Person::GENDER_MALE);
            }

            if ($ctPerson->getBirthday()) {
                $person->setDob(new \DateTime($ctPerson->getBirthday()));
            }

            $this->personRepo->add($person);
            $this->addressRepo->add($person->getAddress());
        }

        $person->setMobile(trim($ctPerson->getMobile()));
        $person->setEmail(trim($ctPerson->getEmail()));
        $person->getAddress()->setStreet(trim($ctPerson->getStreet()));
        $person->getAddress()->setZip(trim($ctPerson->getZip()));
        $person->getAddress()->setCity(trim($ctPerson->getCity()));
        $person->getAddress()->setPhone(trim($ctPerson->getPhonePrivate()));

        return [$person, $ctPerson];
    }

    public function overrideChurchToolsPerson(?CtPerson $ctPerson, ?Person $person, bool $force = false): void
    {
        if (!$person && !$ctPerson) {
            return;
        }

        if (!$person) {
            PersonRequest::delete($ctPerson);
            return;
        }

        if (!$ctPerson) {
            $ctPerson = new CtPerson();
            $ctPerson->addDepartmentId(1);
            $ctPerson->setCampusId(0);
            $ctPerson->setStatusId(3);
            $ctPerson->setFirstName($person->getFirstname());
            $ctPerson->setLastName($person->getLastname() ?: $person->getAddress()->getFamilyName());
            $ctPerson->setSexId($person->isMale() ? '1' : '2');

            if ($person->getDob()) {
                $ctPerson->setBirthday($person->getDob()->format('Y-m-d'));
            }
        }

        $ctPerson->setMobile($person->getMobile() ?: '');
        $ctPerson->setEmail($person->getEmail() ?: '');
        $ctPerson->setStreet($person->getAddress()->getStreet() ?: '');
        $ctPerson->setZip($person->getAddress()->getZip() ?: '');
        $ctPerson->setCity($person->getAddress()->getCity() ?: '');
        $ctPerson->setPhonePrivate($person->getAddress()->getPhone() ?: '');

        if (!$ctPerson->getId()) {
            PersonRequest::create($ctPerson, force: $force);
            $this->uploadChurchToolsPersonImage($ctPerson, $person);
            return;
        }

        PersonRequest::update($ctPerson, [
            'mobile', 'email', 'street', 'zip', 'city', 'phonePrivate',
        ]);
    }


    public function uploadChurchToolsPersonImage(CtPerson $ctPerson, Person $person): void
    {
        if ($ctPerson->getImageUrl()) {
            return;
        }

        $filename = $this->personHelper->getPersonPhotoPath() . '/' . $this->personHelper->getPersonPhotoFilename($person);

        if (!file_exists($filename)) {
            return;
        }

        if (!is_readable($filename)) {
            throw new \RuntimeException(sprintf('The photo with the filename "%s" is not readable.', $filename));
        }

        FileRequest::forAvatar($ctPerson->getId())->upload($filename);
    }

    public function hasLocalPersonPhoto(Person $person): bool
    {
        $filename = $this->personHelper->getPersonPhotoPath() . '/' . $this->personHelper->getPersonPhotoFilename($person);

        return file_exists($filename);
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
            $a = self::getFlatPersonDatas($person);
        }

        if ($ctPerson !== null) {
            $b = [
                'lastname' => $ctPerson->getLastName(),
                'firstname' => $ctPerson->getFirstName(),
                'dob' => $ctPerson->getBirthday(),
                'mobile' => self::normalizePhoneNumber($ctPerson->getMobile()),
                'email' => $ctPerson->getEmail(),
                'street' => $ctPerson->getStreet(),
                'zip' => $ctPerson->getZip(),
                'city' => $ctPerson->getCity(),
                'phone' => self::normalizePhoneNumber($ctPerson->getPhonePrivate()),
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

            if (trim($a[$attr] ?? '') === trim($b[$attr] ?? '')) {
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
        
        $phoneNumber = trim(str_replace(['-', ' ', '/'], '', $phoneNumber));

        if (!empty($phoneNumber) && '0' === $phoneNumber[0]) {
            $phoneNumber = '+49' . substr($phoneNumber, 1);
        }

        return $phoneNumber;
    }

    public static function getFlatPersonDatas(Person $person): array
    {
        $address = $person->getAddress();

        return [
            'lastname' => $person->getLastname() ?: $address->getFamilyName(),
            'firstname' => $person->getFirstname(),
            'dob' => $person->getDob()->format('Y-m-d'),
            'mobile' => self::normalizePhoneNumber($person->getMobile()),
            'email' => $person->getEmail(),
            'street' => $address->getStreet(),
            'zip' => $address->getZip(),
            'city' => $address->getCity(),
            'phone' => self::normalizePhoneNumber($address->getPhone()),
        ];
    }
}
