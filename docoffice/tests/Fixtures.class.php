<?php

/**
 * Синтетични Office документи с кирилица, таблици и числови стойности.
 *
 * @category tests
 * @package docoffice
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class docoffice_tests_Fixtures
{
    /** Създава входовете без база данни и без външни PHP библиотеки. */
    public static function create($dir)
    {
        $text = 'България София 00123 Общо 37,50 EUR';
        $html = '<!doctype html><html><head><meta charset="UTF-8"><style>body{font-family:"DejaVu Sans";font-size:12pt}table{border-collapse:collapse}td,th{border:1px solid #555;padding:6px}th{background:#ddd}</style></head><body><h1>Тестова фактура</h1><p>' . $text . '</p><table><tr><th>Артикул</th><th>Сума</th></tr><tr><td>Стока</td><td>37,50 EUR</td></tr></table></body></html>';
        file_put_contents($dir . '/invoice.html', $html);
        $large = str_replace('</body></html>', '', $html);
        for ($i = 1; $i <= 500; $i++) $large .= '<p>Ред ' . $i . ': ' . $text . '</p>';
        file_put_contents($dir . '/large.html', $large . '<p>КРАЙ 500</p></body></html>');
        $rtf = '{\rtf1\ansi\deff0{\fonttbl{\f0 DejaVu Sans;}}\uc1\pard\f0\fs24 ';
        foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) as $char) {
            $code = mb_ord($char, 'UTF-8');
            $rtf .= $code > 127 ? '\u' . $code . '?' : $char;
        }
        file_put_contents($dir . '/invoice.rtf', $rtf . '\par}');
        $types = '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>';
        $rels = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        self::zip($dir . '/invoice.docx', array(
            '[Content_Types].xml' => $types . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
            '_rels/.rels' => $rels . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
            'word/document.xml' => '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:rPr><w:b/><w:sz w:val="32"/></w:rPr><w:t>Тестова фактура</w:t></w:r></w:p><w:p><w:r><w:t>' . $text . '</w:t></w:r></w:p><w:tbl><w:tblPr><w:tblBorders><w:top w:val="single" w:sz="6"/><w:bottom w:val="single" w:sz="6"/><w:insideH w:val="single" w:sz="6"/><w:insideV w:val="single" w:sz="6"/></w:tblBorders></w:tblPr><w:tblGrid><w:gridCol w:w="4000"/><w:gridCol w:w="2000"/></w:tblGrid><w:tr><w:tc><w:p><w:r><w:t>Стока</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>37,50 EUR</w:t></w:r></w:p></w:tc></w:tr></w:tbl><w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1440" w:bottom="1440" w:left="1440" w:right="1440"/></w:sectPr></w:body></w:document>',
        ));
        copy($dir . '/invoice.docx', $dir . "/Кирилица ' тест.docx");
        self::zip($dir . '/invoice.xlsx', array(
            '[Content_Types].xml' => $types . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
            '_rels/.rels' => $rels . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Тест" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => $rels . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
            'xl/worksheets/sheet1.xml' => '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="1" width="55" customWidth="1"/><col min="2" max="2" width="15" customWidth="1"/></cols><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>' . $text . '</t></is></c></row><row r="2"><c r="A2" t="inlineStr"><is><t>Стока</t></is></c><c r="B2"><v>12.5</v></c></row><row r="3"><c r="B3"><v>25</v></c></row><row r="4"><c r="A4" t="inlineStr"><is><t>Сума</t></is></c><c r="B4"><f>SUM(B2:B3)</f><v>37.5</v></c></row></sheetData></worksheet>',
        ));

        return array('invoice.html', 'invoice.rtf', 'invoice.docx', "Кирилица ' тест.docx", 'invoice.xlsx', 'large.html');
    }


    /** Пакетира минимален OOXML документ със собствено съдържание. */
    private static function zip($path, $entries)
    {
        $zip = new ZipArchive();
        expect($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        foreach ($entries as $name => $content) $zip->addFromString($name, $content);
        $zip->close();
    }
}
