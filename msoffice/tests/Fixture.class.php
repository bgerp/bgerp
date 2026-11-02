<?php


/**
 * Самостоятелна XLSX тестова книга без Composer зависимости
 *
 * @category  tests
 * @package   msoffice
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license   GPL 3
 * @since     v 0.1
 */
class msoffice_tests_Fixture
{
    /** Създава синтетична книга с празни клетки, типове, два листа и формула с кеширана стойност. */
    public static function create($path, $largeRows = 0, $date1904 = false)
    {
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $rels = 'http://schemas.openxmlformats.org/package/2006/relationships';
        $office = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $files = array(
            '[Content_Types].xml' => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
            '_rels/.rels' => '<Relationships xmlns="' . $rels . '"><Relationship Id="rId1" Type="' . $office . '/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<workbook xmlns="' . $ns . '" xmlns:r="' . $office . '"><workbookPr date1904="' . (int) $date1904 . '"/><sheets><sheet name="Оферта" sheetId="1" r:id="rId1"/><sheet name="Скрит" sheetId="2" state="hidden" r:id="rId2"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<Relationships xmlns="' . $rels . '"><Relationship Id="rId1" Type="' . $office . '/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="' . $office . '/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId3" Type="' . $office . '/styles" Target="styles.xml"/><Relationship Id="rId4" Type="' . $office . '/sharedStrings" Target="sharedStrings.xml"/></Relationships>',
            'xl/sharedStrings.xml' => '<sst xmlns="' . $ns . '" count="1" uniqueCount="1"><si><t>Артикул</t></si></sst>',
            'xl/styles.xml' => '<styleSheet xmlns="' . $ns . '"><numFmts count="1"><numFmt numFmtId="164" formatCode="00000"/></numFmts><fonts count="1"><font><sz val="11"/><name val="Arial"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border/></borders><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/><xf numFmtId="14" fontId="0" fillId="0" borderId="0"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0"/></cellXfs></styleSheet>',
            'xl/worksheets/sheet2.xml' => '<worksheet xmlns="' . $ns . '"><dimension ref="A1:B1"/><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>Втори лист</t></is></c><c r="B1"><v>0</v></c></row></sheetData></worksheet>',
        );
        if ($largeRows) {
            $rows = '';
            for ($i = 1; $i <= $largeRows; $i++) {
                $rows .= '<row r="' . $i . '"><c r="A' . $i . '" t="inlineStr"><is><t>Продукт ' . $i . '</t></is></c><c r="B' . $i . '"><v>12.50</v></c></row>';
            }
            $dimension = 'A1:B' . $largeRows;
        } else {
            $rows = '<row r="2"><c r="B2" t="s"><v>0</v></c><c r="C2" t="inlineStr"><is><t>Цена</t></is></c></row>'
                . '<row r="3"><c r="B3" t="inlineStr"><is><t>00123</t></is></c><c r="C3"><v>12.5</v></c><c r="D3"><v>2</v></c><c r="E3"><f>C3*D3</f><v>25</v></c><c r="F3" s="1"><v>' . ($date1904 ? 43830 : 45292) . '</v></c><c r="G3" t="b"><v>0</v></c><c r="H3" s="2"><v>7</v></c></row>'
                . '<row r="5"><c r="B5" t="inlineStr"><is><r><t>Кирилица </t></r><r><t>&amp; апостроф &apos; C:\\temp</t></r></is></c><c r="C5" t="e"><v>#DIV/0!</v></c></row>';
            $dimension = 'A1:H5';
        }
        $files['xl/worksheets/sheet1.xml'] = '<worksheet xmlns="' . $ns . '"><dimension ref="' . $dimension . '"/><sheetData>' . $rows . '</sheetData></worksheet>';
        $zip = new ZipArchive();
        expect($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 'Cannot create XLSX fixture');
        foreach ($files as $name => $xml) $zip->addFromString($name, '<?xml version="1.0" encoding="UTF-8"?>' . $xml);
        expect($zip->close(), 'Cannot finish XLSX fixture');
    }
}
