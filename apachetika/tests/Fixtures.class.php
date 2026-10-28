<?php


/**
 * Синтетични файлове за проверка на извличането с Apache Tika.
 *
 * @category tests
 * @package apachetika
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 * @since v 0.1
 */
class apachetika_tests_Fixtures
{
    /** Създава собствен набор от файлове, без данни от инсталацията. */
    public static function create($dir)
    {
        $text = 'България София 00123 Общо 37,50 EUR';
        $title = 'Тестов документ';
        $html = '<!doctype html><html><head><meta charset="UTF-8"><title>' . $title
            . '</title><meta name="author" content="Tika Test"></head><body><h1>' . $text
            . '</h1><table><tr><th>Артикул</th><th>Сума</th></tr><tr><td>Стока</td><td>37,50</td></tr></table></body></html>';
        file_put_contents($dir . '/sample.txt', $text);
        file_put_contents($dir . '/кирилица.txt', $text);
        file_put_contents($dir . '/sample.html', $html);
        file_put_contents($dir . "/quote ' and space.txt", $text);
        $rtf = '{\\rtf1\\ansi\\deff0{\\fonttbl{\\f0 Arial;}}\\uc1\\pard\\f0\\fs24 ';
        foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) as $char) {
            $code = mb_ord($char, 'UTF-8');
            $rtf .= $code > 127 ? '\\u' . $code . '?' : $char;
        }
        file_put_contents($dir . '/sample.rtf', $rtf . '\\par}');
        file_put_contents($dir . '/sample.eml', "From: sender@example.com\r\nTo: recipient@example.com\r\n"
            . "Subject: Tika test\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n" . $text);
        $core = '<?xml version="1.0" encoding="UTF-8"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>'
            . $title . '</dc:title><dc:creator>Tika Test</dc:creator></cp:coreProperties>';
        $types = '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>';
        $rels = '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>';
        self::zip($dir . '/sample.docx', array(
            '[Content_Types].xml' => $types . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
            '_rels/.rels' => $rels . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
            'docProps/core.xml' => $core,
            'word/document.xml' => '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>' . $text . '</w:t></w:r></w:p><w:tbl><w:tr><w:tc><w:p><w:r><w:t>Стока</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>37,50</w:t></w:r></w:p></w:tc></w:tr></w:tbl></w:body></w:document>',
        ));
        self::zip($dir . '/sample.xlsx', array(
            '[Content_Types].xml' => $types . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => $rels . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'docProps/core.xml' => $core,
            'xl/workbook.xml' => '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Тест" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0"/></cellStyleXfs><cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs></styleSheet>',
            'xl/worksheets/sheet1.xml' => '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>' . $text . '</t></is></c><c r="B1"><v>37.5</v></c></row></sheetData></worksheet>',
        ));
        $pdf = new tcpdf_Instance();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetTitle($title);
        $pdf->SetAuthor('Tika Test');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->AddPage();
        $pdf->Write(10, $text);
        file_put_contents($dir . '/sample.pdf', $pdf->Output('', 'S'));
        $large = '<html><head><meta charset="UTF-8"></head><body>';
        for ($i = 1; $i <= 5000; $i++) {
            $large .= '<p>Ред ' . $i . ': ' . $text . '</p>';
        }
        file_put_contents($dir . '/large.html', $large . '<p>КРАЙ 5000</p></body></html>');

        return array('sample.txt', 'кирилица.txt', "quote ' and space.txt", 'sample.html', 'sample.rtf',
            'sample.eml', 'sample.docx', 'sample.xlsx', 'sample.pdf', 'large.html');
    }


    /** Пакетира минимални OOXML примери със синтетични текст и метаданни. */
    private static function zip($path, $entries)
    {
        $zip = new ZipArchive();
        expect($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
    }
}
