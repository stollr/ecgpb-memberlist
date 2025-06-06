<?php

namespace App\Service\ChurchTools;

use App\Entity\Address;
use App\Entity\Person;
use App\Helper\PersonHelper;
use App\Repository\AddressRepository;
use App\Repository\PersonRepository;
use CTApi\CTConfig;
use CTApi\Models\Groups\Person\Person as CtPerson;
use CTApi\Models\Common\File\FileRequest;
use CTApi\Models\Groups\Person\PersonRequest;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

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

    private PhoneNumberUtil $phoneUtil;

    public function __construct(
        string $apiBaseUrl,
        string $apiToken,
        PersonRepository $personRepo,
        AddressRepository $addressRepo,
        PersonHelper $personHelper,
        PhoneNumberUtil $phoneUtil,
    ) {
        $this->apiBaseUrl = $apiBaseUrl;
        $this->apiToken = $apiToken;
        $this->personRepo = $personRepo;
        $this->addressRepo = $addressRepo;
        $this->personHelper = $personHelper;
        $this->phoneUtil = $phoneUtil;

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
            $ctPersons = PersonRequest::where('status_ids', [3, 8]) // id 3 = member, 8 = guest member // we only compare members
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

            $person->setChurchToolsId($ctPerson->getId());

            if ($ctPerson->getSexId()) {
                $person->setGender($ctPerson->getSexId() === '2' ? Person::GENDER_FEMALE : Person::GENDER_MALE);
            }

            $this->personRepo->add($person);
            $this->addressRepo->add($person->getAddress());
        }

        // Change of the name is only possible if the churchtools ID matches.
        if ($person->getChurchToolsId() === (int) $ctPerson->getId()) {
            $person->setFirstname(trim($ctPerson->getFirstName() ?: ''));

            $lastName = trim($ctPerson->getLastName() ?: '');
            $namePrefix = null;
            $splittedLastName = explode(',', $lastName);

            if (count($splittedLastName) > 1) {
                $namePrefix = trim(array_pop($splittedLastName));
                $lastName = trim(implode(',', $splittedLastName));
            }

            $person->getAddress()->setFamilyName($lastName);
            $person->getAddress()->setNamePrefix($namePrefix);
        }

        if ($ctPerson->getBirthday()) {
            $person->setDob(new \DateTime($ctPerson->getBirthday()));
        }

        $mobile = trim($ctPerson->getMobile() ?: '') ?: null;
        $mobile && ($mobile = $this->phoneUtil->parse($mobile, 'DE'));

        $phone = trim($ctPerson->getPhonePrivate() ?: '') ?: null;
        $phone && ($phone = $this->phoneUtil->parse($phone, 'DE'));

        $person->setMobile($mobile);
        $person->setEmail(trim($ctPerson->getEmail() ?: '') ?: null);
        $person->getAddress()->setStreet(trim($ctPerson->getStreet() ?: '') ?: null);
        $person->getAddress()->setZip(trim($ctPerson->getZip() ?: '') ?: null);
        $person->getAddress()->setCity(trim($ctPerson->getCity() ?: '') ?: null);
        $person->getAddress()->setPhone($phone);

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
            $ctPerson->setSexId($person->isMale() ? '1' : '2');
        }

        if (!$ctPerson->getId() || $person->getChurchToolsId() === (int) $ctPerson->getId()) {
            $lastName = $person->getLastname() ?: $person->getAddress()->getFamilyName();

            if ($person->getAddress()->getNamePrefix()) {
                // Churchtools does not support a name prefix, so we append it to the lastname
                // to ensure correct sorting
                $lastName .= ', ' . $person->getAddress()->getNamePrefix();
            }

            $ctPerson->setLastName($lastName);
            $ctPerson->setFirstName($person->getFirstname());
        }

        if ($person->getDob()) {
            $ctPerson->setBirthday($person->getDob()->format('Y-m-d'));
        }

        $address = $person->getAddress();
        $mobile = $person->getMobile() ? $this->phoneUtil->format($person->getMobile(), PhoneNumberFormat::E164) : '';
        $phone = $address->getPhone() ? $this->phoneUtil->format($address->getPhone(), PhoneNumberFormat::E164) : '';

        $ctPerson->setMobile($mobile);
        $ctPerson->setEmail($person->getEmail() ?: '');
        $ctPerson->setStreet($address->getStreet() ?: '');
        $ctPerson->setZip($address->getZip() ?: '');
        $ctPerson->setCity($address->getCity() ?: '');
        $ctPerson->setPhonePrivate($phone);

        if (!$ctPerson->getId()) {
            PersonRequest::create($ctPerson, force: $force);

            $person->setChurchToolsId($ctPerson->getId());
            $this->uploadChurchToolsPersonImage($ctPerson, $person);
            return;
        }

        PersonRequest::update($ctPerson, [
            'firstName', 'lastName', 'mobile', 'email', 'street', 'zip', 'city', 'phonePrivate', 'birthday'
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
    public function diff(?Person $person, ?CtPerson $ctPerson): array
    {
        if ($person === null && $ctPerson === null) {
            return [];
        }

        $a = $b = [];

        if ($person !== null) {
            $a = $this->getFlatPersonDatas($person);
        }

        if ($ctPerson !== null) {
            $splittedLastName = explode(',', $ctPerson->getLastName());
            
            if (count($splittedLastName) > 1) {
                $namePrefix = trim(array_pop($splittedLastName));
                $lastName = trim(implode(',', $splittedLastName));
            } else {
                $namePrefix = null;
                $lastName = $ctPerson->getLastName();
            }

            $b = [
                'lastname' => $lastName,
                'namePrefix' => $namePrefix,
                'firstname' => $ctPerson->getFirstName(),
                'dob' => $ctPerson->getBirthday(),
                'mobile' => $this->normalizePhoneNumber($ctPerson->getMobile()),
                'email' => strtolower($ctPerson->getEmail()),
                'street' => $ctPerson->getStreet(),
                'zip' => $ctPerson->getZip(),
                'city' => $ctPerson->getCity(),
                'phone' => $this->normalizePhoneNumber($ctPerson->getPhonePrivate()),
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

    private function normalizePhoneNumber(null|string|PhoneNumber $phoneNumber): string
    {
        if (null === $phoneNumber) {
            return '';
        } elseif ($phoneNumber instanceof PhoneNumber) {
            return $this->phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
        }
        
        $phoneNumber = trim(str_replace(['-', ' ', '/', "\u{00A0}"], '', $phoneNumber));

        if (!empty($phoneNumber) && '0' === $phoneNumber[0]) {
            $phoneNumber = '+49' . substr($phoneNumber, 1);
        }

        return $phoneNumber;
    }

    public function getFlatPersonDatas(Person $person): array
    {
        $address = $person->getAddress();

        return [
            'lastname' => $person->getLastname() ?: $address->getFamilyName(),
            'namePrefix' => $address->getNamePrefix(),
            'firstname' => $person->getFirstname(),
            'dob' => $person->getDob()?->format('Y-m-d'),
            'mobile' => $this->normalizePhoneNumber($person->getMobile()),
            'email' => strtolower($person->getEmail()),
            'street' => $address->getStreet(),
            'zip' => $address->getZip(),
            'city' => $address->getCity(),
            'phone' => $this->normalizePhoneNumber($address->getPhone()),
        ];
    }
}
