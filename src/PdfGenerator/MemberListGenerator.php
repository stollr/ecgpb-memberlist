<?php

namespace App\PdfGenerator;

use App\Entity\Address;
use App\Entity\Ministry\Category;
use App\Entity\Person;
use App\Entity\WorkingGroup;
use App\Helper\PersonHelper;
use App\PdfGenerator\MemberListTcpdf;
use App\Statistic\StatisticService;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tcpdf\Extension\Table\Table;
use Tcpdf\Extension\Table\Cell;
use Tcpdf\Extension\Helper;

/**
 * App\PdfGenerator\MemberListGenerator
 *
 * @author naitsirch
 */
class MemberListGenerator extends Generator implements GeneratorInterface
{
    const GRID_ROW_MIN_HEIGHT = 13; // mm
    const GRID_PICTURE_CELL_WIDTH = 10.5; // mm

    private $doctrine;
    private $translator;
    private $statisticService;
    private $personHelper;
    private $parameters;

    public function __construct(
        RegistryInterface $doctrine,
        TranslatorInterface $translator,
        PersonHelper $personHelper,
        StatisticService $statisticService,
        array $parameters
    ) {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->personHelper = $personHelper;
        $this->statisticService = $statisticService;
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function generate(array $options = array())
    {
        // default options
        $options = array_replace(array(
            'pages_with_member_placeholders' => 1,
            'pages_for_notes' => 2,
        ), $options);

        // set up tcpdf
        $pdf = new MemberListTcpdf('P', 'mm', 'A5', true, 'UTF-8', false);
        $pdf->SetTitle('ECGPB Member List');
        $pdf->SetMargins(9, 9, 9);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(true);
        $pdf->setFooterMargin(10);
        $pdf->setFooterFont(['dejavusans', '', 7]);
        $pdf->SetAutoPageBreak(true, 9);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->SetFont('dejavusans', '', 10);

        $this->addCover($pdf);
        $this->addPage1($pdf);
        $this->addPage2($pdf);
        $this->addAddressPages($pdf);
        $this->addAddressPlaceholders($pdf, $options['pages_with_member_placeholders']);
        $this->addWorkingGroups($pdf);
        $this->addMinistryCategories($pdf);
        $this->addBuildingUsageCosts($pdf);
        $this->addPersonalNotes($pdf, $options['pages_for_notes']);
        $this->addLastPage($pdf);

        return $pdf->Output(null, 'S');
    }

    private function addCover(\TCPDF $pdf)
    {
        $pdf->AddPage();

        // initiate XY positions
        $margins = $pdf->getMargins();
        $pdf->SetX($margins['left']);
        $pdf->SetY($margins['top']);

        // logo
        $src = realpath($this->parameters['kernel.project_dir'] . '/public/img/logo.png');
        if (!$src) {
            throw new \Exception('The logo file disappeared.');
        }
        //$pdf->Image($file, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border, $fitbox, $hidden, $fitonpage, $alt, $altimgs);
        $pdf->Image($src, $pdf->GetX() + 3.5, $pdf->GetY(), 30, 19, 'PNG', null, 'N', true, 300);

        $pdf->SetY($pdf->GetY() + 3);
        $pdf->SetLineWidth(0.75);
        $pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->getPageWidth() - $pdf->GetX(), $pdf->GetY());

        $pdf->SetXY($pdf->GetX() + 3, $pdf->GetY() + 2);
        $pdf->Text($pdf->GetX(), $pdf->GetY(), $this->parameters['ecgpb.contact.name'], false, false, true, 0, 1);

        $pdf->SetFontSize(40);
        $pdf->Text($pdf->GetX(), $pdf->GetY() + 50, "Mitgliederliste", false, false, true, 0, 1, 'C');
        $pdf->Text($pdf->GetX(), $pdf->GetY() + 10, date('Y'), false, false, true, 0, 1, 'C');
    }

    private function addPage1(\TCPDF $pdf)
    {
        $pdf->AddPage();

        $this->useFontSizeXL($pdf);
        $this->useFontStyleBold($pdf);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.name']);
        $this->addHeadlineMargin($pdf);
        $this->useFontStyleNormal($pdf);
        $this->useFontSizeL($pdf);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.street']);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.zip'] . ' ' . $this->parameters['ecgpb.contact.city']);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.main_phone']);
        $this->writeText($pdf, 'Homepage: www.ecgpb.de');

        $this->addParagraphMargin($pdf);
        $this->useFontSizeXL($pdf);
        $this->useFontStyleBold($pdf);
        $this->writeText($pdf, 'Bankverbindung');
        $this->addHeadlineMargin($pdf);
        $this->useFontStyleNormal($pdf);
        $this->useFontSizeL($pdf);
        $this->writeText($pdf, 'Sparkasse Paderborn');
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('IBAN:')->setWidth(13)->end()
                    ->newCell($this->parameters['ecgpb.contact.bank.iban'])->setWidth(70)->end()
                ->end()
                ->newRow()
                    ->newCell('BIC:')->end()
                    ->newCell($this->parameters['ecgpb.contact.bank.bic'])->end()
                ->end()
            ->end()
        ;

        $this->addParagraphMargin($pdf);
        $this->useFontSizeXL($pdf);
        $this->useFontStyleBold($pdf);
        $this->writeText($pdf, 'Stand: 01.' . date('m.Y'));
        $this->useFontStyleNormal($pdf);

        $pdf->SetY(190);
        $this->useFontSizeS($pdf);
        $this->writeText($pdf, 'Alle Änderungen bitte umgehend bei ' .
            $this->parameters['ecgpb.contact.memberlist.responsible'] .
            ' melden!'
        );
        $this->useFontStyleBold($pdf);
        $this->writeText($pdf, 'gemeindeliste@ecgpb.de');
        $this->useFontStyleNormal($pdf);
    }

    private function addPage2(\TCPDF $pdf)
    {
        $pdf->AddPage();

        $this->useFontSizeL($pdf);
        $this->useFontStyleBold($pdf);
        $this->writeText($pdf, 'Telefonverbindungen des Gemeindehauses');
        $this->addHeadlineMargin($pdf);
        $this->useFontStyleNormal($pdf);
        $this->useFontSizeM($pdf);
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('Haupteingang')->setWidth(60)->end()
                    ->newCell($this->parameters['ecgpb.contact.main_phone'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell($this->parameters['ecgpb.contact.office_phone_label'])->setWidth(70)->end()
                    ->newCell($this->parameters['ecgpb.contact.office_phone'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Küche')->setWidth(60)->end()
                    ->newCell($this->parameters['ecgpb.contact.kitchen_phone'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('Geschichten-Telefon')->setWidth(60)->end()
                    ->newCell($this->parameters['ecgpb.contact.story_phone'])->setWidth(50)->end()
                ->end()
            ->end()
        ;

        // library logo
        $this->addParagraphMargin($pdf);
        $src = realpath($this->parameters['kernel.project_dir'] . '/public/img/library_logo.png');
        $pdf->Image($src, $pdf->GetX() + 10, $pdf->GetY(), 40, 18, 'PNG', null, 'T', true, 300, 'R');

        // library

        $pdf->SetY($pdf->GetY() - 1);
        $this->useFontSizeL($pdf);
        $this->useFontStyleBold($pdf);
        $this->writeText($pdf, 'Bibliothek');
        $this->addHeadlineMargin($pdf);
        $this->useFontStyleNormal($pdf);
        $this->useFontSizeM($pdf);
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('Telefon')->setWidth(35)->end()
                    ->newCell($this->parameters['ecgpb.contact.library_phone'])->setWidth(50)->end()
                ->end()
                ->newRow()
                    ->newCell('E-Mail')->setWidth(35)->end()
                    ->newCell($this->parameters['ecgpb.contact.library_email'])->setWidth(50)->end()
                ->end()
            ->end()
        ;

        $pdf->SetY($pdf->GetY() + 5);
        $this->useFontSizeM($pdf);
        $this->useFontStyleBold($pdf);
        $this->writeText($pdf, 'Öffnungszeiten');
        $this->useFontStyleNormal($pdf);
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

        $this->addParagraphMargin($pdf);

        // Add information about ChurchTools

        $this->useFontSizeL($pdf);
        $this->useFontStyleBold($pdf);
        $this->writeText($pdf, 'ChurchTools');
        $this->addHeadlineMargin($pdf);
        $this->useFontSizeM($pdf);
        $this->useFontStyleNormal($pdf);
        $msg = "Auf https://ecgpb.church.tools/ ist die Mitgliederliste in digitaler Form zu\n finden. ";
        $msg .= "Wer einen Zugang benötigt, kann sich an {$this->parameters['ecgpb.contact.churchtools_admin.name']} wenden.\n\n";
        $msg .= "Die App für das Smartphone findet man in den jeweiligen App-Stores.";
        $this->writeText($pdf, $msg);

        $this->addParagraphMargin($pdf);

        // technique contact

        $this->useFontSizeL($pdf);
        $this->useFontStyleBold($pdf);
        $this->writeText($pdf, 'Technik und Beamerteam');
        $this->addHeadlineMargin($pdf);
        $this->useFontStyleNormal($pdf);
        $this->useFontSizeM($pdf);
        $msg = "Präsentationen können der Technik bzw. dem Beamerteam über folgende\n"
             . 'E-Mail-Adresse zugesandt werden:';
        $this->writeText($pdf, $msg);
        $pdf->SetY($pdf->GetY() + 3);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.technique.email']);

        $this->addParagraphMargin($pdf);

        // statistics

        $statistics = $this->statisticService->getPersonStatistics();

        $this->useFontSizeL($pdf);
        $this->useFontStyleBold($pdf);
        $this->writeText($pdf, 'Mitgliederstand am 01.' . date('m.Y'));
        $this->addHeadlineMargin($pdf);
        $this->useFontStyleNormal($pdf);
        $this->useFontSizeM($pdf);
        $this->addTable($pdf)
                ->newRow()
                    ->newCell('Gesamtmitgliederzahl')->setWidth(60)->end()
                    ->newCell($statistics->getTotal())->setWidth(30)->end()
                ->end()
                ->newRow()
                    ->newCell('Davon männlich:')->end()
                    ->newCell($statistics->getMaleTotal())->end()
                ->end()
                ->newRow()
                    ->newCell('Davon weiblich:')->end()
                    ->newCell($statistics->getFemaleTotal())->end()
                ->end()
                ->newRow()
                    ->newCell('Mitglieder ab 65 Jahren:')->end()
                    ->newCell($statistics->getAtLeast65YearsOld())->end()
                ->end()
                ->newRow()
                    ->newCell('Mitglieder bis 25 Jahren:')->end()
                    ->newCell($statistics->getAtMost25YearsOld())->end()
                ->end()
                ->newRow()
                    ->newCell('Höchstes Alter:')->end()
                    ->newCell($statistics->getHighestAge())->end()
                ->end()
                ->newRow()
                    ->newCell('Niedrigstes Alter:')->end()
                    ->newCell($statistics->getLowestAge())->end()
                ->end()
                ->newRow()
                    ->newCell('Altersdurchschnitt:')->end()
                    ->newCell(round($statistics->getAverageAge()))->end()
                ->end()
            ->end()
        ;
    }

    public function addAddressPages(\TCPDF $pdf)
    {
        $addresses = $this->getAddresses();
        $personRepo = $this->doctrine->getRepository(Person::class); /* @var $personRepo \App\Repository\PersonRepository */

        $pdf->SetLineWidth(0.25);

        $table = null;
        $totalHeight = 0;
        foreach ($addresses as $index => $address) {
            /* @var $address \App\Entity\Address */

            // calculate address row height and check if address fitts on this page
            $addressRowHeight = 0;
            foreach ($address->getPersons() as $person) {
                $personRowHeight = 0;
                if ($person->getMobile()) {
                    $personRowHeight += 4;
                }
                if ($person->getEmail()) {
                    $personRowHeight += 4;
                }
                $addressRowHeight += $personRowHeight < self::GRID_ROW_MIN_HEIGHT ? self::GRID_ROW_MIN_HEIGHT : $personRowHeight;
            }
            if (count($address->getPersons()) == 1) {
                $addressRowHeight += self::GRID_ROW_MIN_HEIGHT;
            }
            $totalHeight += $addressRowHeight;

            // end current page and start a new page
            if ($totalHeight > 185 || 0 == $index) {
                if ($index > 0) {
                    $table->end();
                }
                $totalHeight = $addressRowHeight;

                $pdf->AddPage();
                $table = $this->addTable($pdf);
                $table
                    ->setFontSize(self::FONT_SIZE_XS)
                    ->newRow()
                        ->newCell()
                            ->setText($this->translator->trans('Name, Address, Phone'))
                            ->setBorder(1)
                            ->setColspan(2)
                            ->setPadding(0.75)
                        ->end()
                        ->newCell()
                            ->setText($this->translator->trans('First Name'))
                            ->setBorder(1)
                            ->setPadding(0.75)
                        ->end()
                        ->newCell()
                            ->setText($this->translator->trans('DOB'))
                            ->setAlign('C')
                            ->setBorder(1)
                            ->setPadding(0.75)
                        ->end()
                        ->newCell()
                            ->setText($this->translator->trans('Mobile, E-Mail'))
                            ->setAlign('C')
                            ->setBorder(1)
                            ->setPadding(0.75)
                        ->end()
                    ->end()
                ;
            }

            // add rows and cells to table
            $persons = $address->getPersons();
            if (count($persons) == 1) {
                $persons[] = null; // dummy entry for second row
            }
            foreach ($persons as $index => $person) {
                /* @var $person \App\Entity\Person */
                $row = $table->newRow();
                if (0 == $index) {
                    $displayLastname = $address->getNamePrefix() ? $address->getNamePrefix() . ' ' : '';
                    $displayLastname .= $address->getFamilyName();

                    if (strlen($displayLastname) < 21 && strlen($address->getPhone()) < 21) {
                        $fontSize = self::FONT_SIZE_XS + 0.5;
                    } else {
                        $fontSize = self::FONT_SIZE_XS;
                    }
                    $row->newCell()
                            ->setText($displayLastname . "\n" . $address->getPhone())
                            ->setBorder('LTR')
                            ->setFontSize($fontSize)
                            ->setFontWeight('bold')
                            ->setLineHeight($fontSize >= self::FONT_SIZE_XS + 0.5 ? 1.3 : 1)
                            ->setWidth(35.5)
                            ->setPadding(0.5, 0.75, 0, 0.75)
                        ->end()
                    ;
                } else if (1 == $index) {
                    if (strlen($address->getStreet()) < 22 && strlen($address->getZip().' '.$address->getCity()) < 22) {
                        $fontSize = self::FONT_SIZE_XS + 0.5;
                    } else {
                        $fontSize = self::FONT_SIZE_XS;
                    }
                    $row->newCell()
                            ->setText(
                                $address->getStreet() . "\n" .
                                ($address->getZip() ? $address->getZip() . ' ' : '') . $address->getCity()
                            )
                            ->setBorder(count($persons) <= 2 ? 'LRB' : 'LR')
                            ->setFontSize($fontSize)
                            ->setFontWeight('normal')
                            ->setLineHeight($fontSize >= self::FONT_SIZE_XS + 0.5 ? 1.3 : 1)
                            ->setWidth(35.5)
                            ->setPadding(0, 0.5, 0.75, 0.75)
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

                $email = $person && $person->getEmail() ? str_replace('@googlemail.com', '@gmail.com', $person->getEmail()) : false;

                $row
                    ->newCell()
                        ->getBackground()
                            ->setDpi(300)
                            ->setFormatter($this->getPersonPictureFormatter($person))
                        ->end()
                        ->setBorder(1)
                        ->setWidth(self::GRID_PICTURE_CELL_WIDTH) // 10.5 mm
                    ->end()
                    ->newCell()
                        ->setText($person ? $person->getFirstname() : '')
                        ->setBorder(1)
                        ->setFontSize(self::FONT_SIZE_XS + 0.5)
                        ->setFontWeight('bold')
                        ->setPadding(0.75)
                        ->setWidth(22)
                    ->end()
                    ->newCell()
                        ->setText($person ? $person->getDob()->format('d.m.Y') : '')
                        ->setAlign('C')
                        ->setBorder(1)
                        ->setFontSize(self::FONT_SIZE_XS)
                        ->setFontWeight('normal')
                        ->setPadding(0.75)
                        ->setWidth(19)
                    ->end()
                    ->newCell()
                        ->setText(
                            ($person && $person->getMobile() ? $person->getMobile() . "\n" : '') .
                            ($person && $person->getEmail() ? $email : '')
                        )
                        ->setAlign('C')
                        ->setBorder(1)
                        ->setFontSize($person && strlen($person->getEmail()) < 27 ? self::FONT_SIZE_XS : self::FONT_SIZE_XXS + 0.5)
                        ->setFontWeight('normal')
                        ->setMinHeight(self::GRID_ROW_MIN_HEIGHT)
                        ->setPadding(0.75)
                        ->setWidth(44.5)
                    ->end()
                ;
                $row->end();
            }
        }

        // dirty workaround to get empty templates
        while ($totalHeight + (2 * self::GRID_ROW_MIN_HEIGHT) < 185) {
            for ($i = 0; $i < 2; $i++) {
                $row = $table->newRow();
                $row->newCell()
                        ->setBorder($i % 2 == 0 ? 'LTR' : 'LRB')
                        ->setWidth(35.5)
                    ->end()
                    ->newCell()
                        ->setBorder(1)
                        ->setWidth(self::GRID_PICTURE_CELL_WIDTH) // 10.5 mm
                    ->end()
                    ->newCell()
                        ->setBorder(1)
                        ->setWidth(22)
                    ->end()
                    ->newCell()
                        ->setBorder(1)
                        ->setWidth(19)
                    ->end()
                    ->newCell()
                        ->setBorder(1)
                        ->setMinHeight(self::GRID_ROW_MIN_HEIGHT)
                        ->setWidth(44.5)
                    ->end()
                ;
                $row->end();
            }
            $totalHeight += 2 * self::GRID_ROW_MIN_HEIGHT;
        }

        if ($table) {
            $table->end();
        }
    }

    private function addAddressPlaceholders(\TCPDF $pdf, $numberOfPages = 1)
    {
        if (empty($numberOfPages)) {
            return;
        }

        for ($p = 0; $p < $numberOfPages; $p++) {
            $pdf->AddPage();
            $table = $this->addTable($pdf);
            for ($i = 0; $i < 14; $i++) {
                $row = $table->newRow();
                $row->newCell()
                        ->setBorder($i % 2 == 0 ? 'LTR' : 'LRB')
                        ->setWidth(35.5)
                    ->end()
                    ->newCell()
                        ->setBorder(1)
                        ->setWidth(self::GRID_PICTURE_CELL_WIDTH) // 10.5 mm
                    ->end()
                    ->newCell()
                        ->setBorder(1)
                        ->setWidth(22)
                    ->end()
                    ->newCell()
                        ->setBorder(1)
                        ->setWidth(19)
                    ->end()
                    ->newCell()
                        ->setBorder(1)
                        ->setMinHeight(self::GRID_ROW_MIN_HEIGHT)
                        ->setWidth(44.5)
                    ->end()
                ;
                $row->end();
            }
            $table->end();
        }
    }

    private function addWorkingGroups(\TCPDF $pdf)
    {
        $groupTypes = array();
        foreach ($this->getWorkingGroups() as $group) {
            $groupTypes[$group->getGender()][] = $group;
        }
        $personRepo = $this->doctrine->getRepository(Person::class); /* @var $personRepo \App\Repository\PersonRepository */

        $margins = $pdf->getMargins();
        $halfWidth = ($pdf->getPageWidth() - $margins['left'] - $margins['right']) / 2;
        if ($pdf->GetY() > $margins['top']) {
            $pdf->AddPage();
        }

        $t = 0;
        foreach ($groupTypes as $gender => $groups) {
            $topY = 0;
            foreach ($groups as $index => $group) {
                // page header
                if ($index % 4 == 0) {
                    if ($index > 0) {
                        $pdf->AddPage();
                    }
                    $txt = sprintf('Arbeitsgruppen (%s)', Person::GENDER_FEMALE == $gender ? 'Frauen' : 'Männer');
                    $this->useFontStyleBold($pdf);
                    $this->useFontSizeS($pdf);
                    $pdf->MultiCell(0, 0, $txt, 1, 'C');
                    $pdf->SetY($nextY = $pdf->GetY() + 3);
                }
                if ($index % 2 == 0) {
                    $x = $margins['left'];
                    $y = $nextY;
                } else {
                    $x = $margins['left'] + (($pdf->getPageWidth() - $margins['left'] - $margins['right']) / 2);
                }

                // group name
                $txt = sprintf('Gruppe %s', $group->getNumber());
                $this->useFontStyleBold($pdf);
                $this->useFontSizeS($pdf);
                $pdf->MultiCell($halfWidth, 0, $txt, 0, 'L', false, 1, $x, $y);

                // group leader
                if ($group->getLeader()) {
                    $leaderId = $group->getLeader()->getId();
                    $born = $group->getLeader()->getDob()->format('Y');
                    $bornText = $personRepo->isNameUnique($group->getLeader()) ? '' : 'geb. ' . $born . ', ';
                    $phone = $group->getLeader()->getAddress()->getPhone() ?: $group->getLeader()->getMobile();
                    $txt = $group->getLeader()->getLastnameAndFirstname() . ' (' . $bornText . 'verantwortlich, Tel. ' . $phone . ')';
                } else {
                    $leaderId = 0;
                    $txt = '-';
                }
                $pdf->SetY($pdf->GetY() + 2);
                $this->useFontStyleUnderlined($pdf);
                $pdf->MultiCell($halfWidth, 0, $txt, 0, 'L', false, 1, $x);
                $this->useFontStyleNormal($pdf);

                // group members
                foreach ($group->getPersons() as $person) {
                    if ($person->getId() == $leaderId) {
                        continue;
                    }
                    if ($person->getAge() >= 65 || $person->getWorkerStatus() != Person::WORKER_STATUS_DEPENDING) {
                        continue;
                    }
                    $born = $person->getDob()->format('Y');
                    $nameSuffix = $personRepo->isNameUnique($person) ? '' : ' (geb. ' . $born . ')';
                    $txt = $person->getLastnameAndFirstname() . $nameSuffix;
                    $pdf->MultiCell($halfWidth, 0, $txt, 0, 'L', false, 1, $x);
                }

                if ($pdf->GetY() > $nextY) {
                    $nextY = $pdf->GetY() + 7;
                }
            }
            $t++;
            if ($t < count($groupTypes)) {
                $pdf->AddPage();
            }
        }
    }

    private function addMinistryCategories(\TCPDF $pdf)
    {
        $categories = $this->getMinistryCategories();

        if (count($categories) == 0) {
            return;
        }

        $pdf->AddPage();

        $drawHeaderCallback = function(Table $table) {
            $table->setFontSize(self::FONT_SIZE_XS - 0.5)
                ->newRow()
                    ->newCell()
                        ->setText($this->translator->trans('Category [Ministry] [PDF]'))
                        ->setAlign('C')
                        ->setVerticalAlign('middle')
                        ->setBorder(1)
                        ->setPadding(0.5)
                        ->setWidth(25)
                        ->setFontWeight('bold')
                    ->end()
                    ->newCell()
                        ->setText($this->translator->trans('Subcategory [Ministry] [PDF]'))
                        ->setAlign('C')
                        ->setVerticalAlign('middle')
                        ->setBorder(1)
                        ->setPadding(0.5)
                        ->setWidth(30)
                        ->setFontWeight('bold')
                    ->end()
                    ->newCell()
                        ->setText($this->translator->trans('Tasks'))
                        ->setAlign('L')
                        ->setVerticalAlign('middle')
                        ->setBorder(1)
                        ->setPadding(0.5)
                        ->setWidth(45)
                        ->setFontWeight('bold')
                    ->end()
                    ->newCell()
                        ->setText($this->translator->trans('Responsible Persons [PDF]'))
                        ->setAlign('C')
                        ->setVerticalAlign('middle')
                        ->setBorder(1)
                        ->setPadding(0.5)
                        ->setWidth(25)
                        ->setFontWeight('bold')
                    ->end()
                ->end()
            ;
        };

        $table = $this->addTable($pdf);

        $drawHeaderCallback($table);
        $table->setPageBreakCallback($drawHeaderCallback);

        foreach ($categories as $index => $category) {
            $rowspan = count($category->getMinistries());
            if ($category->getResponsible()) {
                $rowspan++;
            }

            $row = $table->newRow();
            $row->newCell()
                    ->setText($category->getName())
                    ->setRowspan($rowspan)
                    ->setAlign('C')
                    ->setVerticalAlign('middle')
                    ->setBorder(1)
                    ->setFontSize(self::FONT_SIZE_XS)
                    ->setPadding(0.5, 0.5, 0, 0.5)
                ->end()
            ;
            if ($category->getResponsible()) {
                $name = $category->getResponsible()->getFirstnameAndLastname();
                $row->newCell()
                        ->setText('Hauptverantwortlicher')
                        ->setAlign('L')
                        ->setVerticalAlign('middle')
                        ->setColspan(2)
                        ->setBorder(1)
                        ->setFontSize(self::FONT_SIZE_XS - 0.5)
                        ->setPadding(1, 1, 1, 1)
                    ->end()
                    ->newCell()
                        ->setText($name)
                        ->setAlign('C')
                        ->setBorder(1)
                        ->setFontSize(self::FONT_SIZE_XS - 0.5)
                        ->setPadding(1, 1, 1, 1)
                    ->end()
                ;
                $row->end();
                $row = $table->newRow();
            }

            // add rows and cells to table
            foreach ($category->getMinistries() as $index => $ministry) {
                /* @var $ministry \App\Entity\Ministry */
                $responsibles = array();
                foreach ($ministry->getResponsibles() as $person) {
                    $responsibles[] = $person->getFirstnameAndLastname();
                }
                $row
                    ->newCell()
                        ->setText($ministry->getName())
                        ->setAlign('C')
                        ->setVerticalAlign('middle')
                        ->setBorder(1)
                        ->setFontSize(self::FONT_SIZE_XS - 0.5)
                        ->setPadding(0.5)
                    ->end()
                    ->newCell()
                        ->setText($ministry->getDescription() ?: ' ')
                        ->setAlign('L')
                        ->setVerticalAlign('middle')
                        ->setBorder(1)
                        ->setFontSize(self::FONT_SIZE_XXS)
                        ->setFontWeight('normal')
                        ->setPadding(0.5, 1, 0.5, 1)
                    ->end()
                    ->newCell()
                        ->setText(implode(",\n", $responsibles))
                        ->setAlign('C')
                        ->setVerticalAlign('middle')
                        ->setBorder(1)
                        ->setFontSize(self::FONT_SIZE_XS - 0.5)
                        ->setFontWeight('normal')
                        ->setPadding(0.5)
                    ->end()
                ;
                $row->end();

                if ($index < count($category->getMinistries()) - 1) {
                    $row = $table->newRow();
                }
            }
        }

        $table->end();
    }

    private function addBuildingUsageCosts(\TCPDF $pdf)
    {
        $pdf->AddPage();

        // headline
        $this->useFontSizeXL($pdf);
        $this->useFontStyleBold($pdf);
        $pdf->Write(10, 'Nutzung der Gemeinderäumlichkeiten', false, false, 'L', 1);
        $this->addHeadlineMargin($pdf);

        // description text
        $this->useFontSizeL($pdf);
        $this->useFontStyleNormal($pdf);
        $pdf->Write(4, "Die Räumlichkeiten unseres Gemeindehauses können auch für private Veranstaltungen gegen einen entsprechenden Kostenbeitrag genutzt werden.");
        $this->addParagraphMargin($pdf);

        // private parties
        $this->useFontStyleBold($pdf);
        $pdf->Write(4, "1. Privatfeiern, wie z.B. Weihnachtsfeiern, Geburtstagsfeiern oder Hochzeiten (Küche und Trauung)");
        $this->useFontStyleNormal($pdf);
        $this->addParagraphMargin($pdf);
        $pdf->SetX($pdf->GetX() + 10);
        $table = $this->addTable($pdf);
        $table
            ->newRow()
                ->newCell('Für Gemeindeglieder:')->setWidth(50)->end()
                ->newCell('1,50 EUR/Pers.')->setWidth(50)->end()
            ->end()
            ->newRow()
                ->newCell('Für Auswärtige:')->end()
                ->newCell('3,00 EUR/Pers.')->end()
            ->end()
            ->newRow()
                ->newCell("\nNur Trauung\n\n")->setColspan(2)->end()
            ->end()
            ->newRow()
                ->newCell('Für Gemeindeglieder:')->end()
                ->newCell('Kostenfrei')->end()
            ->end()
            ->newRow()
                ->newCell('Für Auswärtige:')->end()
                ->newCell('100,00 EUR pauschal')->end()
            ->end()
        ;
        $table->end();
        $this->addParagraphMargin($pdf);
        $pdf->SetY($pdf->GetY() - 5);

        // funeral
        $this->useFontStyleBold($pdf);
        $pdf->Write(4, '2. Beerdigung');
        $this->useFontStyleNormal($pdf);
        $this->addParagraphMargin($pdf);
        $pdf->SetX($pdf->GetX() + 10);
        $table = $this->addTable($pdf);
        $table
            ->newRow()
                ->newCell('Für Gemeindeglieder:')->setWidth(50)->end()
                ->newCell('1,00 EUR/Pers.')->setWidth(50)->end()
            ->end()
            ->newRow()
                ->newCell("")->setColspan(2)->end()
            ->end()
            ->newRow()
                ->newCell('Für Auswärtige bis 150 Personen:')->end()
                ->newCell('3,00 EUR/Pers.')->setVerticalAlign(Cell::VERTICAL_ALIGN_BOTTOM)->end()
            ->end()
            ->newRow()
                ->newCell("")->setColspan(2)->end()
            ->end()
            ->newRow()
                ->newCell('Ab 150 Personen:')->end()
                ->newCell('500,00 EUR pauschal')->end()
            ->end()
        ;
        $table->end();
        $this->addParagraphMargin($pdf);
        $pdf->SetY($pdf->GetY() - 5);

        // kitchen with external party
        $this->useFontStyleBold($pdf);
        $pdf->Write(4, '3. Nutzung der Küche mit auswärtiger Feier (nur Gemeindeglieder)');
        $this->useFontStyleNormal($pdf);
        $this->addParagraphMargin($pdf);
        $pdf->SetX($pdf->GetX() + 10);
        $table = $this->addTable($pdf);
        $table
            ->newRow()
                ->newCell('Pauschal:')->setWidth(50)->end()
                ->newCell('300,00 EUR')->setWidth(50)->end()
            ->end()
            ->newRow()
                ->newCell("\nFür den Transport des Essens sorgt der Veranstalter der Feier")->setColspan(2)->end()
            ->end()
        ;
        $table->end();
    }

    private function addPersonalNotes(\TCPDF $pdf, $numberOfPages = 3)
    {
        if (empty($numberOfPages)) {
            return;
        }

        $margins = $pdf->getMargins();
        $pageWidth = $pdf->getPageWidth();

        for ($i = 0; $i < $numberOfPages; $i++) {
            $pdf->AddPage();

            $this->useFontSizeXL($pdf);
            $this->useFontStyleBold($pdf);

            $pdf->Write(10, 'Persönliche Notizen', false, false, 'C', 1);
            $pdf->SetY($pdf->GetY() + 2);

            while (Helper::getRemainingYPageSpace($pdf, $pdf->getPage(), $pdf->GetY()) > 12) {
                $pdf->SetY($y = $pdf->GetY() + 6);
                $pdf->Line($margins['left'], $y, $pageWidth - $margins['right'], $y);
            }
        }
    }

    public function addLastPage(MemberListTcpdf $pdf)
    {
        $pdf->AddPage();

        $pdf->lastPage = true;

        $pdf->SetY(153);

        $this->useFontSizeS($pdf);
        $this->useFontStyleBold($pdf);
        $this->writeText($pdf, 'Herausgeber:');

        $this->useFontStyleNormal($pdf);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.name']);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.street']);
        $this->writeText($pdf, $this->parameters['ecgpb.contact.zip'] . ' '. $this->parameters['ecgpb.contact.city']);
        $pdf->SetFont('', 'U');
        $this->writeText($pdf, 'www.ecgpb.de');

        $pdf->SetY($pdf->GetY() + 6);
        $this->useFontStyleBold($pdf);
        $this->writeText($pdf, $this->translator->trans('For private use only!') . ' '
            . $this->translator->trans('The passing on of data to third parties is not permitted for reasons of data protection!')
        );
        $pdf->SetY($pdf->GetY() + 4);
        $this->writeText($pdf, $this->translator->trans(
            'This booklet must be shredded before disposal. If this is not possible, please return it.'
        ));
    }

    /**
     * Returns all addresses with the corresponding persons.
     * @return \App\Entity\Address[]
     */
    private function getAddresses()
    {
        $em = $this->doctrine->getManager();

        $repo = $em->getRepository(Address::class);
        /* @var $repo \Doctrine\Common\Persistence\ObjectRepository */

        $builder = $repo->createQueryBuilder('address')
            ->select('address', 'person')
            ->leftJoin('address.persons', 'person')
            ->orderBy('address.familyName', 'asc')
            ->addOrderBy('person.dob', 'asc')
        ;

        return $builder->getQuery()->getResult();
    }

    /**
     * Returns all addresses with the corresponding persons.
     * @return \App\Entity\Ministry\Category[]
     */
    private function getMinistryCategories()
    {
        $em = $this->doctrine->getManager();

        $repo = $em->getRepository(Category::class);
        /* @var $repo \App\Repository\Ministry\CategoryRepository */
        $categories = $repo->findAllForListing();

        return $categories;
    }

    /**
     * Returns all working groups
     * @return \App\Entity\WorkingGroup[]
     */
    private function getWorkingGroups()
    {
        $repo = $this->doctrine->getManager()->getRepository(WorkingGroup::class);
        /* @var $repo \App\Repository\WorkingGroupRepository */

        return $repo->findAllForMemberPdf();
    }

    public function getPersonPictureFormatter(Person $person = null)
    {
        if (!$person) {
            return null;
        }

        $filename = $this->personHelper->getPersonPhotoFilename($person);
        $filenameOriginal = $this->personHelper->getPersonPhotoPath() . '/' . $filename;
        $photoPathOptimized = $this->personHelper->getPersonPhotoPathOptimized();

        return function(\Tcpdf\Extension\Attribute\BackgroundFormatterOptions $options) use ($filename, $filenameOriginal, $photoPathOptimized) {
            if (!file_exists($filenameOriginal)) {
                $options->setImage(null);
                return;
            } else if (!is_readable($filenameOriginal)) {
                throw new \Exception(sprintf('Image file "%s" is not readable.', $filenameOriginal));
            }

            $filenameOptimized = $photoPathOptimized . '/'
                . number_format(round($options->getMaxWidth(), 4), 4) . 'x'
                . number_format(round($options->getMaxHeight(), 4), 4) . '/' . $filename
            ;
            if (!is_dir(dirname($filenameOptimized)) && !mkdir(dirname($filenameOptimized), 0777, true)) {
                throw new \RuntimeException('No permissions to create the directory "'.dirname($filenameOptimized).'".');
            }

            $options->setImage($filenameOptimized);

            if (!file_exists($filenameOptimized) || filemtime($filenameOriginal) > filemtime($filenameOptimized)) {
                list($widthOriginal, $heightOriginal) = getimagesize($filenameOriginal);
                $dpi = $widthOriginal / ($options->getMaxWidth() / 25.4);
                if ($dpi > 300) {
                    $dpi = 300;
                }

                $sizeFactor = 300 / $dpi;
                $dstWidth = $options->getMaxWidth() / 25.4 * $dpi * $sizeFactor;
                $dstHeight = $options->getMaxHeight() / 25.4 * $dpi * $sizeFactor;

                $factor = $dstWidth / $dstHeight;

                if ($factor > $widthOriginal / $heightOriginal) {
                    $width = $widthOriginal;
                    $height = $widthOriginal / $factor;
                } else {
                    $width = $heightOriginal * $factor;
                    $height = $heightOriginal;
                }

                $imageOriginal = imagecreatefromjpeg($filenameOriginal);
                $imageSnippet = imagecreatetruecolor($width, $height);
                imagecopy($imageSnippet, $imageOriginal, 0, 0, ($widthOriginal - $width) / 2, ($heightOriginal - $height) / 2, $width, $height);
                imagedestroy($imageOriginal);
                $imageOptimized = imagecreatetruecolor($dstWidth, $dstHeight);
                //imagecopyresized($imageOptimized, $imageSnippet, 0, 0, 0, 0, $dstWidth, $dstHeight, $width, $height);
                imagecopyresampled($imageOptimized, $imageSnippet, 0, 0, 0, 0, $dstWidth, $dstHeight, $width, $height);
                imagedestroy($imageSnippet);
                imagejpeg($imageOptimized, $filenameOptimized, 95);
                imagedestroy($imageOptimized);

                $options->setDpi($dpi);

                // file stats must be cleared, otherwise filemtime returns outdated timestamp
                clearstatcache(true, $filenameOptimized);
            }
        };
    }
}
