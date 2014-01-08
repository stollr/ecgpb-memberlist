<?php

namespace Ecgpb\MemberBundle\PdfGenerator;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Bridge\Twig\TwigEngine;

/**
 * Ecgpb\MemberBundle\PdfGenerator\MemberListGenerator
 *
 * @author naitsirch
 */
class MemberListGenerator extends Generator implements GeneratorInterface
{
    private $doctrine;
    private $translator;
    private $templating;
    private $parameters;
    
    public function __construct(
        RegistryInterface $doctrine,
        TranslatorInterface $translator,
        TwigEngine $templating,
        array $parameters
    ) {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->templating = $templating;
        $this->parameters = $parameters;
    }
    
    /**
     * @return string
     */
    public function generate()
    {
        $addresses = $this->getAddresses();
        
//        $templating = $this->get('templating'); /* @var $templating TwigEngine */
//        
//        $html = $templating->render('EcgpbMemberBundle:Print:pdf.html.twig', array(
//            'date' => new \DateTime(),
//            'addresses' => $addresses,
//        ));
        $pdf = new \TCPDF('P', 'mm', 'A5', true, 'UTF-8', false);
        $pdf->SetTitle('ECGPB Member List');
        $pdf->SetMargins(9, 9, 9);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->SetAutoPageBreak(true, 9);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->SetFont('dejavusans', '', 10);
        
        $this->addCover($pdf);
        $this->addPage1($pdf);
        $this->addPage2($pdf);
        $this->addAddressPages($pdf);

//        foreach (explode('<!--PAGE_BREAK-->', $html) as $htmlPage) {
//            $pdf->AddPage();
//            $pdf->writeHTML($html, true, false, true, false, '');
//        }

        return $pdf->Output(null, 'S');
    }
    
    private function addCover(\TCPDF $pdf)
    {
        $pdf->AddPage();
        
        // initiate XY positions
        $margins = $pdf->getMargins();
        $pdf->SetX($margins['left']);
        $pdf->SetY($margins['top']);
        
        // TODO: add logo
        
        $pdf->SetLineWidth(0.75);
        $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->getPageWidth() - $pdf->GetX(), $pdf->GetY());
        
        $pdf->SetX($pdf->GetX() + 3);
        $pdf->Text($pdf->GetX(), $pdf->GetY(), $this->parameters['ecgpb.contact.name'], false, false, true, 0, 1);
        
        $pdf->SetFontSize(40);
        $pdf->Text($pdf->GetX(), $pdf->GetY() + 50, "Mitgliederliste", false, false, true, 0, 1, 'C');
        $pdf->Text($pdf->GetX(), $pdf->GetY() + 10, date('Y'), false, false, true, 0, 1, 'C');
    }
    
    private function addPage1(\TCPDF $pdf)
    {
        $pdf->AddPage();
        
        // TODO: add church image
        
        $this->useFontSizeL($pdf);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.name']);
        $this->useFontWeightNormal($pdf);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.street']);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.zip'] . ' ' . $this->parameters['ecgpb.contact.city']);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.main_phone']);
        $this->useFontSizeM($pdf);
        $this->writeText($pdf, 'Homepage: www.ecgpb.de');
        
        $pdf->SetY($pdf->GetY() + 10);
        $this->useFontSizeL($pdf);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'Bankverbindung');
        $this->useFontWeightNormal($pdf);
        $this->writeText($pdf, 'Sparkasse Paderborn');
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('Kontonummer:')->setWidth(50)->end()
                    ->newCell($this->parameters['ecgpb.contact.bank.account_number'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Bankleitzahl:')->setWidth(50)->end()
                    ->newCell($this->parameters['ecgpb.contact.bank.code'])->setWidth(50)->end()
                ->end()
            ->end()
        ;

        $pdf->SetY($pdf->GetY() + 10);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'Stand: 01.01.' . date('Y'));
        $this->useFontWeightNormal($pdf);
        
        $pdf->SetY(190);
        $this->useFontSizeS($pdf);
        $this->writeText($pdf, 'Alle Änderungen bitte unverzüglich bei ' .
            $this->parameters['ecgpb.contact.memberlist.responsible'] .
            ' melden!'
        );
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'gemeindeliste@ecgpb.de');
        $this->useFontWeightNormal($pdf);
    }
    
    private function addPage2(\TCPDF $pdf)
    {
        $pdf->AddPage();
        
        $this->useFontSizeL($pdf);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'Telefonverbindungen des Gemeindehauses');
        $pdf->SetY($pdf->GetY() + 5);
        $this->useFontWeightNormal($pdf);
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('Haupteingang')->setWidth(70)->end()
                    ->newCell($this->parameters['ecgpb.contact.main_phone'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Büro')->setWidth(70)->end()
                    ->newCell($this->parameters['ecgpb.contact.office_phone'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('LifeTime')->setWidth(70)->end()
                    ->newCell($this->parameters['ecgpb.contact.lifetime_phone'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Gefährdetenhilfe PB')->setWidth(70)->end()
                    ->newCell($this->parameters['ecgpb.contact.gfh_phone'])->setWidth(50)->end()
                ->end()
            ->end()
        ;
        
        // TODO: library logo
        
        $pdf->SetY($pdf->GetY() + 10);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'Bibliothek');
        $pdf->SetY($pdf->GetY() + 5);
        $this->useFontWeightNormal($pdf);
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('Telefon')->setWidth(35)->end()
                    ->newCell($this->parameters['ecgpb.contact.library_phone'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Email')->setWidth(35)->end()
                    ->newCell($this->parameters['ecgpb.contact.library_email'])->setWidth(50)->end()
                ->end()
            ->end()
        ;
        
        $pdf->SetY($pdf->GetY() + 5);
        $this->useFontSizeM($pdf);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'Öffnungszeiten');
        $this->useFontWeightNormal($pdf);
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('Mittwoch:')->setWidth(35)->end()
                    ->newCell('18:30 - 20:30 Uhr')->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Donnerstag:')->setWidth(35)->end()
                    ->newCell('18:00 - 20:30 Uhr')->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Sonntag:')->setWidth(35)->end()
                    ->newCell('11:45 - 12:30 Uhr')->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('An Ferien- und Feiertagen geschlossen')->setColspan(2)->end()
                ->end()
            ->end()
        ;
        
        $pdf->SetLineWidth(0.25);
        $pdf->SetY($pdf->GetY() + 5);
        $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->getPageWidth() - $pdf->GetX(), $pdf->GetY());
        
        $pdf->SetY($pdf->GetY() + 10);
        $this->useFontSizeL($pdf);
        $this->useFontWeightBold($pdf);
        $this->writeText($pdf, 'Mitgliederstand am 01.01.' . date('Y'));
        $pdf->SetY($pdf->GetY() + 5);
        $this->useFontWeightNormal($pdf);
        $this->useFontSizeM($pdf);
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('Gesamtmitgliederzahl')->setWidth(60)->end()
                    ->newCell('?')->setWidth(30)->end()
                ->end()
                ->newRow()
                    ->newCell('Davon männlich:')->end()
                    ->newCell('?')->end()
                ->end()
                ->newRow()
                    ->newCell('Davon weiblich:')->end()
                    ->newCell('?')->end()
                ->end()
                ->newRow()
                    ->newCell('Mitglieder ab 65 Jahren:')->end()
                    ->newCell('?')->end()
                ->end()
                ->newRow()
                    ->newCell('Mitglieder bis 25 Jahren:')->end()
                    ->newCell('?')->end()
                ->end()
            ->end()
        ;
    }
    
    public function addAddressPages(\TCPDF $pdf)
    {
        $addresses = $this->getAddresses();
        
        $table = null;
        $totalHeight = 0;
        
        foreach ($addresses as $address) {
            /* @var $address \Ecgpb\MemberBundle\Entity\Address */
            if (0 == $totalHeight) {
                $pdf->AddPage();
                $table = $this->addTable($pdf);
                $table
                    ->newRow()
                        ->newCell()
                            ->setText($this->translator->trans('Name, Address, Phone'))
                            ->setBorder(1)
                            ->setFontSize(self::FONT_SIZE_S)
                            ->setColspan(2)
                        ->end()
                        ->newCell()
                            ->setText($this->translator->trans('First Name'))
                            ->setBorder(1)
                            ->setFontSize(self::FONT_SIZE_S)
                        ->end()
                        ->newCell()
                            ->setText($this->translator->trans('DOB'))
                            ->setBorder(1)
                            ->setFontSize(self::FONT_SIZE_S)
                        ->end()
                        ->newCell()
                            ->setText($this->translator->trans('Mobile, Email'))
                            ->setBorder(1)
                            ->setFontSize(self::FONT_SIZE_S)
                        ->end()
                    ->end()
                ;
            }
            $personRowHeight = 0;
            $persons = $address->getPersons();
            if (count($persons) == 1) {
                $persons[] = null; // dummy entry for second row
            }
            foreach ($persons as $index => $person) {
                /* @var $person \Ecgpb\MemberBundle\Entity\Person */
                $row = $table->newRow();
                if (0 == $index) {
                    $row->newCell()
                            ->setText($address->getFamilyName() . "\n" . $address->getPhone())
                            ->setBorder('LTR')
                            ->setFontWeight('bold')
                            ->setWidth(35.5)
                        ->end()
                    ;
                } else if (1 == $index) {
                    $row->newCell()
                            ->setText($address->getStreet() . "\n" . $address->getZip() . ' ' . $address->getCity())
                            ->setBorder(count($persons) <= 2 ? 'LRB' : 'LR')
                            ->setFontWeight('normal')
                            ->setWidth(35.5)
                        ->end()
                    ;
                } else {
                    $row->newCell()
                            ->setText(' ')
                            ->setBorder(count($persons) - 1 == $index ? 'LRB' : 'LR')
                            ->setWidth(35.5)
                        ->end()
                    ;
                }
                $row
                    ->newCell('img')
                        ->setBorder(1)
                        ->setWidth(10.5)
                    ->end()
                    ->newCell()
                        ->setText($person ? $person->getFirstname() : '')
                        ->setBorder(1)
                        ->setWidth(22)
                        ->setFontWeight('bold')
                    ->end()
                    ->newCell()
                        ->setText($person ? $person->getDob()->format('d.m.Y') : '')
                        ->setBorder(1)
                        ->setWidth(19)
                        ->setFontWeight('normal')
                    ->end()
                    ->newCell()
                        ->setText(
                            ($person && $person->getPhone2() ? $person->getPhone2() . "\n" : '') .
                            ($person && $person->getMobile() ? $person->getMobile() . "\n" : '') .
                            ($person && $person->getEmail() ? $person->getEmail() : '')
                        )
                        ->setBorder(1)
                        ->setWidth(44.5)
                        ->setFontWeight('normal')
                    ->end()
                ;
                $personRowHeight += 2
                    + ($person && $person->getPhone2() ? 4.9 : 0)
                    + ($person && $person->getMobile() ? 4.9 : 0)
                    + 4.9
                ;
                $row->end();
                
            }
            
            if ($totalHeight > 60) {
                $table->end();
                $totalHeight = 0;
            } else {
                $totalHeight += $personRowHeight > 27.9 + 2
                    ? $personRowHeight
                    : 27.9 + 2
                ;
            }
        }
    }
    
    /**
     * Returns all addresses with the corresponding persons.
     * @return \Ecgpb\MemberBundle\Entity\Address[]
     */
    private function getAddresses()
    {
        $em = $this->doctrine->getManager();

        $repo = $em->getRepository('EcgpbMemberBundle:Address');
        /* @var $repo \Doctrine\Common\Persistence\ObjectRepository */
        
        $builder = $repo->createQueryBuilder('address')
            ->select('address', 'person')
            ->leftJoin('address.persons', 'person')
            ->orderBy('address.familyName', 'asc')
            ->addOrderBy('person.dob', 'asc')
        ;
        
        return $builder->getQuery()->getResult();
    }
}
