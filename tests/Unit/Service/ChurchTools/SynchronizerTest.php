<?php

namespace Tests\Unit\Service\ChurchTools;

use App\Entity\Address;
use App\Entity\Person;
use App\Helper\PersonHelper;
use App\Repository\AddressRepository;
use App\Repository\PersonRepository;
use App\Service\ChurchTools\Synchronizer;
use libphonenumber\PhoneNumberUtil;
use CTApi\Models\Groups\Person\Person as CtPerson;
use PHPUnit\Framework\TestCase;

class SynchronizerTest extends TestCase
{
    private ?Synchronizer $synchronizer = null;

    private ?AddressRepository $addressRepository = null;

    private ?PersonRepository $personRepository = null;

    private ?PersonHelper $personHelper = null;

    private ?PhoneNumberUtil $phoneNumberUtil = null;

    protected function setUp(): void
    {
        $this->addressRepository = $this->createMock(AddressRepository::class);
        $this->personRepository = $this->createMock(PersonRepository::class);
        $this->personHelper = $this->createMock(PersonHelper::class);
        $this->phoneNumberUtil = $this->createMock(PhoneNumberUtil::class);
        
        $this->synchronizer = new Synchronizer(
            'https://example.church.tools/api',
            'testToken',
            $this->personRepository,
            $this->addressRepository,
            $this->personHelper,
            $this->phoneNumberUtil,
        );
    }


    public function testOverrideLocalPerson_WithNamePrefix(): void
    {
        // PREPARE
        $address = new Address();
        $address->setFamilyName('Hannover');
        $address->setNamePrefix('von');
        $person = new Person($address);
        $person->setChurchToolsId(1234);
        $person->setFirstname('Hans');

        $ctPerson = new CtPerson();
        $ctPerson->setId(1234);
        $ctPerson->setLastName('Lippe, von der');
        $ctPerson->setFirstName('Bernhard');

        // ACTION
        $this->synchronizer->overrideLocalPerson($person, $ctPerson);

        // ASSERTIONS
        $this->assertSame('Lippe', $person->getAddress()->getFamilyName());
        $this->assertSame('von der', $person->getAddress()->getNamePrefix());
        $this->assertSame('Bernhard', $person->getFirstname());
    }
}
