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
use PHPUnit\Framework\Attributes\DataProvider;

class SynchronizerTest extends TestCase
{
    private ?Synchronizer $synchronizer = null;

    private ?AddressRepository $addressRepository = null;

    private ?PersonRepository $personRepository = null;

    private ?PersonHelper $personHelper = null;

    /** @var PhoneNumberUtil|MockObject|null */
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

    public function testOverrideLocalPerson_RemovesWhitespaces(): void
    {
        // PREPARE
        $address = new Address();
        $person = new Person($address);
        $person->setChurchToolsId(1234);

        $ctPerson = new CtPerson();
        $ctPerson->setId(1234);
        $ctPerson->setLastName(' test ');
        $ctPerson->setFirstName(' test ');
        $ctPerson->setEmail(' test@example.com ');
        $ctPerson->setStreet(' test ');
        $ctPerson->setZip(' test ');
        $ctPerson->setCity(' test ');

        // ACTION
        $this->synchronizer->overrideLocalPerson($person, $ctPerson);

        // ASSERTIONS
        $this->assertSame('test', $person->getFirstname());
        $this->assertSame('test@example.com', $person->getEmail());
        $this->assertSame('test', $address->getFamilyName());
        $this->assertSame('test', $address->getStreet());
        $this->assertSame('test', $address->getZip());
        $this->assertSame('test', $address->getCity());
    }

    public function testOverrideLocalPerson_HandlesNullValues(): void
    {
        // PREPARE
        $address = new Address();
        $person = new Person($address);
        $person->setChurchToolsId(1234);

        $ctPerson = new CtPerson();
        $ctPerson->setId(1234);
        $ctPerson->setLastName('test');
        $ctPerson->setFirstName('test');
        $ctPerson->setBirthday(null);
        $ctPerson->setMobile(null);
        $ctPerson->setPhonePrivate(null);
        $ctPerson->setEmail(null);
        $ctPerson->setStreet(null);
        $ctPerson->setZip(null);
        $ctPerson->setCity(null);

        // ACTION
        $this->synchronizer->overrideLocalPerson($person, $ctPerson);

        // ASSERTIONS
        $this->assertSame('test', $person->getFirstname());
        $this->assertSame('test', $address->getFamilyName());
        $this->assertNull($person->getMobile());
        $this->assertNull($person->getEmail());
        $this->assertNull($address->getPhone());
        $this->assertNull($address->getStreet());
        $this->assertNull($address->getZip());
        $this->assertNull($address->getCity());
    }

    public function testOverrideLocalPerson_ReplacesEmptyStringWithNull(): void
    {
        // PREPARE
        $address = new Address();
        $person = new Person($address);
        $person->setChurchToolsId(1234);

        $ctPerson = new CtPerson();
        $ctPerson->setId(1234);
        $ctPerson->setLastName('');
        $ctPerson->setFirstName('');
        $ctPerson->setBirthday('');
        $ctPerson->setMobile('');
        $ctPerson->setPhonePrivate('');
        $ctPerson->setEmail('');
        $ctPerson->setStreet('');
        $ctPerson->setZip('');
        $ctPerson->setCity('');

        // ACTION
        $this->synchronizer->overrideLocalPerson($person, $ctPerson);

        // ASSERTIONS
        $this->assertSame('', $person->getFirstname());
        $this->assertSame('', $address->getFamilyName());
        $this->assertNull($person->getDob());
        $this->assertNull($person->getMobile());
        $this->assertNull($person->getEmail());
        $this->assertNull($address->getPhone());
        $this->assertNull($address->getStreet());
        $this->assertNull($address->getZip());
        $this->assertNull($address->getCity());
    }
}
