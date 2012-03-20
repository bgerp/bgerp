<?php 


/**
 * Дефинира име на папка в която ще се съхраняват временните данни данните
 */
defIfNot('WEBKIT_TO_PDF_TEMP_DIR', EF_TEMP_PATH . "/webkittopdf");


/**
 * Изпълнимия файл на програмата
 */
defIfNot('WEBKIT_TO_PDF_BIN', "/usr/bin/wkhtmltopdf");


/**
 * Оказва дали да се изплни помпщната програма (xvfb-run)
 */
defIfNot('WEBKIT_TO_PDF_XVFB_RUN', TRUE);


/**
 * xvfb-run - Ширина на екрана
 */
defIfNot('WEBKIT_TO_PDF_SCREEN_WIDTH', "640");


/**
 * xvfb-run - Височина на екрана
 */
defIfNot('WEBKIT_TO_PDF_SCREEN_HEIGHT', "480");


/**
 * xvfb-run - Дълбочина на цвета
 */
defIfNot('WEBKIT_TO_PDF_SCREEN_BIT', "16");


/**
 * Генериране на PDF файлове от HTML файл чрез web kit
 * 
 * @category  vendors
 * @package   webkittopdf
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class webkittopdf_Converter extends core_Manager
{
    
    
    /**
     * Конвертира html към pdf файл
     * 
     * @param string $html - HTML стинга, който ще се конвертира
     * @param string $fileName - Името на изходния pdf файл
     * @param string $bucketName - името на кофата, където ще се записват данните
     * 
     * @return string $fh - Файлов манипулатор на новосъздадения pdf файл
     */
    static function convert($html, $fileName, $bucketName)
    {
        //Генерираме унукално име на папка
        do {
            $randId = str::getRand();
            $tempPath = WEBKIT_TO_PDF_TEMP_DIR . '/' . $randId; 
        } while (is_dir($tempPath));
        
        //Създаваме рекурсивно папката
        expect(mkdir($tempPath, 0777, TRUE));
        
        //Пътя до html файла
        $htmlPath = $tempPath . '/' . $randId . '.html';
        
        //Добавяме в началото за да се счупи UTF-8
        //TODO ако не е UTF-8?
        $html = "\xEF\xBB\xBF" . $html; 
        
        //Записваме данните в променливата $html в html файла
        $fileHnd = fopen($htmlPath, 'w');
        fwrite($fileHnd, $html);
        fclose($fileHnd);
        
        //Пътя до pdf файла
        $pdfPath = $tempPath . '/' . $fileName;

        //Ако ще използва xvfb-run
        if (WEBKIT_TO_PDF_XVFB_RUN) {
            //Променливата screen
            $screen = '-screen 0 ' . WEBKIT_TO_PDF_SCREEN_WIDTH . 'x' . WEBKIT_TO_PDF_SCREEN_HEIGHT . 'x' . WEBKIT_TO_PDF_SCREEN_BIT;   
            
            //Ескейпваме променливата
            $screen = escapeshellarg($screen);
            
            //Изпълнение на програмата xvfb-run
            $xvfb = "xvfb-run -a -s {$screen}";
        }
        
        //Ескейпваме всички променливи, които ще използваме
        $htmlPathEsc = escapeshellarg($htmlPath);
        $pdfPathEsc = escapeshellarg($pdfPath);
        $binEsc = escapeshellarg(WEBKIT_TO_PDF_BIN);
        
        //Скрипта, който ще се изпълнява
        $wk = "{$binEsc} {$htmlPathEsc} {$pdfPathEsc}";
        $exec = ($xvfb) ? "{$xvfb} {$wk}" : $wk;

        //Стартираме скрипта за генериране на pdf файл от html файл
        shell_exec($exec);

        //Качвания новосъздадения PDF файл
        $Fileman = cls::get('fileman_Files');
        $fh = $Fileman->addNewFile($pdfPath, $bucketName, $fileName);

        //Изтриваме временната директория заедно с всички създадени папки
        core_Os::deleteDir($tempPath);
        
        //Връщаме манипулатора на файла
        return $fh;
    }
}
   