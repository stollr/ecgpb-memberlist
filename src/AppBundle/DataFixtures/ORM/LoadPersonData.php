<?php

namespace Ecgpb\MemberBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ecgpb\MemberBundle\Entity\Address;
use Ecgpb\MemberBundle\Entity\Person;

/**
 * Description of LoadPersonData
 *
 * @author christian
 */
class LoadPersonData implements FixtureInterface
{
    private $addresses = array();
    
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getAddresses() as $address) {
            $length = rand(1, rand(1, rand(1, 5)));
            $gender = rand(0, 1) ? 'm' : 'f';
            for ($i = 0; $i < $length; $i++) {
                $person = new Person();
                $person->setAddress($address);
                $person->setDob(new \DateTime(rand(1930, 1999) . '-' . rand(1, 12) . '-' . rand(1, 28)));
                $person->setGender($gender);
                if ('m' == $gender) {
                    $person->setFirstname($this->getRandomMaleFirstName());
                    $gender = 'f';
                } else {
                    $person->setFirstname($this->getRandomFemaleFirstName());
                    $person->setMaidenName($this->getRandomLastName());
                    $gender = 'm';
                }
                $person->setMobile('0' . rand(150, 179) . '-' . rand(100000, 999999));
                if (rand(0, 3)) {
                    $email = str_replace(
                        array('ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü'),
                        array('ae', 'oe', 'ue', 'ae', 'oe', 'ue'),
                        $person->getFirstname() . '.' . $address->getFamilyName()
                    );
                    $person->setEmail(strtolower($email) . '@example.com');
                }
                if (!rand(0, 5)) {
                    $phone = explode('-', $address->getPhone());
                    $person->setPhone2($phone[0] . '-' . rand(100000, 999999));
                }
                
                $manager->persist($person);
            }
            $manager->persist($address);
        }
        $manager->flush();
    }
    
    private function getAddresses()
    {
        $addresses = array();
        foreach ($this->getLastNames() as $familyName) {
            $length = rand(1, rand(1, 3));
            for ($i = 0; $i < $length; $i++) {
                $address = new Address();
                $address->setFamilyName($familyName);
                $address->setPhone('0525' . rand(1, 4) . '-' . rand(1000, 999999));
                $address->setStreet($this->getRandomStreetName() . ' ' . rand(1, 125));
                $address->setZip(rand(33098, 33106));
                $address->setCity('Paderborn');
                $addresses[] = $address;
            }
        }
        return $addresses;
    }
    
    private function getLastNames()
    {
        return array(
            'Lindauer', 'Balzer', 'Eberhart', 'Platt', 'Schleifer',
            'Schmitt', 'Schmidt', 'Müller', 'Meier', 'Band', 'Wagner', 'Wegener',
            'Ridder', 'Wald', 'Lichtenberg', 'Walker', 'Brandenberger', 'Berns',
            'Seeberg', 'Meinhardt', 'Thiemann', 'Stark', 'Burger'
        );
    }
    
    private function getRandomLastName()
    {
        $names = $this->getLastNames();
        return $names[rand(0, count($names) - 1)];
    }
    
    private function getRandomStreetName()
    {
        $streetNames = array(
            'Adlergasse',
            'Alfred-Althus-Straße',
            'Altmarkt',
            'Am Queckbrunnen',
            'Am Schießhaus',
            'Am See',
            'Am Zwingerteich',
            'Ammonstraße',
            'An der Frauenkirche',
            'An der Herzogin Garten',
            'An der Kreuzkirche',
            'An der Mauer',
            'Annenstraße',
            'Antonsplatz',
            'Augustusstraße',
            'Behringstraße',
            'Berliner Straße',
            'Bernhard-von-Lindenau-Platz',
            'Bräuergasse',
            'Bremer Straße',
            'Brühlscher Garten',
            'Devrientstraße',
            'Dr.-Külz-Ring',
            'Ehrlichstraße',
            'Ermischstraße',
            'Falkenstraße',
            'Frauenstr.',
            'Freiberger Platz',
            'Freiberger Straße',
            'Friedrichstraße',
            'Wilsdruffer Straße',
            'Zahnsgasse',
            'Breslauer Straße',
            'Budapester Straße',
            'Bürgerwiese',
            'Dore-Hoyer-Straße',
            'Dülferstraße',
            'Dürerstraße',
            'Egon-Erwin-Kisch-Straße',
            'Einsteinstraße',
            'Eisenstuckstraße',
            'Erlweinstraße',
            'Feldgasse',
            'Feldschlößchenstraße',
            'Ferdinandstraße',
            'Franklinstraße',
            'Fritz-Löffler-Straße',
            'Lingnerplatz',
            'Lothringer Straße',
            'Lukasplatz',
            'Mary-Wigman-Straße',
            'Mathildenstraße',
            'Mommsenstraße',
            'Mosczinskystraße',
            'Parkstraße',
            'Pestalozzistraße',
            'Pillnitzer Straße',
            'Pirnaischer Platz',
            'Prager Straße',
            'Rabenerstraße',
            'Räcknitzstraße',
            'Reichenbachstr.',
            'Reitbahnstraße',
            'Renkstraße',
            'Rietschelstraße',
            'Rohlfsstraße',
            'Rugestraße',
            'Schnorrstraße',
            'Schulgutstraße',
            'Schweizer Straße',
            'Seidnitzer Straße',
            'Semperstraße',
            'Sidonienstraße',
            'St. Petersburger Str.',
            'Steinstraße',
            'Strehlener Straße',
            'Struvestraße',
            'Terrassenufer',
            'Trompeterstraße',
            'Uhlandstraße',
            'Viktoriastraße',
            'Vitzthumstraße',
            'Weißbachstraße',
            'Werdauer Straße',
            'Wielandstraße',
            'Wiener Platz',
            'Wiener Straße',
            'Winckelmannstraße',
            'Zellescher Weg',
            'Zeunerstraße',
            'Ziegelstraße',
            'Zinzendorfstraße',
        );
        return $streetNames[rand(0, count($streetNames) - 1)];
    }
    
    private function getRandomMaleFirstName()
    {
        $names = array(
            'Tom', 'Thomas', 'Matthias', 'Stephan', 'Stefan', 'Timo', 'Oliver',
            'Patrick', 'Jan', 'Jannis', 'Jannes', 'James', 'Michael', 'Kevin',
            'Heinrich', 'Jakob', 'Johann', 'Waldemar', 'Willi', 'Peter',
            'Christian', 'Christoph', 'Finn', 'John', 'Gerd', 'Gerhard',
            'Alfred', 'Arthur', 'Viktor', 'Daniel', 'Ralph', 'Andreas', 'Lukas',
            'Markus', 'Jonas', 'Dieter', 'Sven', 'Benjamin', 'Elmar',
        );
        return $names[rand(0, count($names) - 1)];
    }
    
    private function getRandomFemaleFirstName()
    {
        $names = array(
            'Anita', 'Nelli', 'Esther', 'Jennifer', 'Tina', 'Lena', 'Olga',
            'Sarah', 'Katharina', 'Lydia', 'Viktoria', 'Carina', 'Eva',
            'Sabrina', 'Angelika', 'Stefanie', 'Yvonne', 'Diana', 'Maria',
            'Franziska', 'Jessica', 'Julia', 'Daniela', 'Manuela', 'Nicole',
            'Sophie', 'Anne', 'Sandra', 'Elisabeth', 'Leah', 'Silke', 'Ria',
            'Marie', 'Anna', 'Ann-Katrin', 'Svenja', 'Merle', 'Melanie',
            'Juliane', 'Evelyn', 'Veronika', 'Michelle',
        );
        return $names[rand(0, count($names) - 1)];
    }
}
