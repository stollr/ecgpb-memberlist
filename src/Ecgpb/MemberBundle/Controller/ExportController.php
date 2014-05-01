<?php

namespace Ecgpb\MemberBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Ecgpb\MemberBundle\Exception\WorkingGroupWithoutLeaderException;

/**
 * Ecgpb\MemberBundle\Controller\ExportController
 *
 * @author naitsirch
 *
 * @Security("is_granted('ROLE_ADMIN')")
 */
class ExportController extends Controller
{   
    public function pdfAction()
    {
        $generator = $this->get('ecgpb.member.pdf_generator.member_list_generator');
        /* @var $generator \Ecgpb\MemberBundle\PdfGenerator\MemberListGenerator */
        
        $pdf = $generator->generate();
        return new Response($pdf, 200, array(
            'Content-Type' => 'application/pdf',
            //'Content-Type' => 'application/octet-stream',
            //'Content-Disposition' => 'attachment; filename="ECGPB Member List.pdf"',
        ));
    }

    public function birthdayExcelXmlAction()
    {
        $repo = $this->getDoctrine()->getManager()->getRepository('EcgpbMemberBundle:Person');
        $persons = $repo->findAllForBirthdayList();

        // build xml
        $xw = new \XMLWriter();
        $xw->openMemory();
        $xw->startDocument('1.0', 'UTF-8');
            $xw->startElement('Workbook');
                $xw->startElementNs('ss', 'Worksheet', 'urn:schemas-microsoft-com:office:spreadsheet');
                    $xw->startElement('Table');

                    foreach ($persons as $person) {
                        $xw->startElement('Row');
                            $xw->startElement('Cell');
                                $xw->startElement('Data');
                                    $xw->writeAttributeNs('ss', 'Type', 'urn:schemas-microsoft-com:office:spreadsheet', 'String');
                                    $xw->text($person->getDob()->format('d.m.Y'));
                                $xw->endElement();
                            $xw->endElement();
                            $xw->startElement('Cell');
                                $xw->startElement('Data');
                                    $xw->writeAttributeNs('ss', 'Type', 'urn:schemas-microsoft-com:office:spreadsheet', 'String');
                                    $xw->text($person->getAddress()->getFamilyName());
                                $xw->endElement();
                            $xw->endElement();
                            $xw->startElement('Cell');
                                $xw->startElement('Data');
                                    $xw->writeAttributeNs('ss', 'Type', 'urn:schemas-microsoft-com:office:spreadsheet', 'String');
                                    $xw->text($person->getFirstname());
                                $xw->endElement();
                            $xw->endElement();
                        $xw->endElement();
                    }

                    $xw->endElement();
                $xw->endElement();
            $xw->endElement();
        $xw->endDocument();

        $xml = $xw->outputMemory(true);

        return new Response($xml, 200, array(
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="dob.xml"',
            'Content-Length' => strlen($xml),
        ));

/*
<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:c="urn:schemas-microsoft-com:office:component:spreadsheet"
          xmlns:html="http://www.w3.org/TR/REC-html40" xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x2="http://schemas.microsoft.com/office/excel/2003/xml"
          xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <OfficeDocumentSettings xmlns="urn:schemas-microsoft-com:office:office">
        <Colors>
            <Color>
                <Index>3</Index>
                <RGB>#c0c0c0</RGB>
            </Color>
            <Color><Index>4</Index><RGB>#ff0000</RGB></Color>
        </Colors>
    </OfficeDocumentSettings>
    <ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">
        <WindowHeight>9000</WindowHeight>
        <WindowWidth>13860</WindowWidth>
        <WindowTopX>240</WindowTopX>
        <WindowTopY>75</WindowTopY>
        <ProtectStructure>False</ProtectStructure>
        <ProtectWindows>False</ProtectWindows>
    </ExcelWorkbook>
    <Styles>
        <Style ss:ID="Default" ss:Name="Default"/>
        <Style ss:ID="Result" ss:Name="Result">
            <Font ss:Bold="1" ss:Italic="1" ss:Underline="Single"/>
        </Style>
        <Style ss:ID="Result2" ss:Name="Result2">
            <Font ss:Bold="1" ss:Italic="1" ss:Underline="Single"/>
            <NumberFormat ss:Format="Euro Currency"/>
        </Style>
        <Style ss:ID="Heading" ss:Name="Heading">
            <Font ss:Bold="1" ss:Italic="1" ss:Size="16"/>
        </Style>
        <Style ss:ID="Heading1" ss:Name="Heading1">
            <Font ss:Bold="1" ss:Italic="1" ss:Size="16"/>
        </Style>
        <Style ss:ID="co1"/>
        <Style ss:ID="ta1"/>
        <Style ss:ID="ce1"/>
    </Styles>
    <ss:Worksheet ss:Name="Tabelle1">
        <Table ss:StyleID="ta1">
            <Column ss:Span="1" ss:Width="64,0063"/>
            <Row ss:Height="12,1039">
                <Cell ss:StyleID="ce1">
                    <Data ss:Type="String">Name</Data>
                </Cell>
                <Cell ss:StyleID="ce1">
                    <Data ss:Type="String">Vorname</Data>
                </Cell>
            </Row>
            <Row ss:Height="12,8126">
                <Cell>
                    <Data ss:Type="String">Stoller</Data>
                </Cell>
                <Cell>
                    <Data ss:Type="String">Christian</Data>
                </Cell>
            </Row>
            <Row ss:Height="12,8126">
                <Cell>
                    <Data ss:Type="String">Stoller</Data>
                </Cell>
                <Cell>
                    <Data ss:Type="String">Anita</Data>
                </Cell>
            </Row>
        </Table>
        <x:WorksheetOptions/>
    </ss:Worksheet>
</Workbook>
         */
    }
}
