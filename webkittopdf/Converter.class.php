<?php 


/**
 * Дефинира име на папка в която ще се съхраняват временните данни данните
 */
defIfNot('WEBKIT_TO_PDF_TEMP_DIR', EF_TEMP_PATH . "/webkittopdf");


/**
 * Генериране на PDF файлове от HTML файл чрез web kit
 *
 *
 * @category  vendors
 * @package   webkittopdf
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
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
    	$conf = core_Packs::getConfig('webkittopdf');
    	
        //Генерираме унукално име на папка
        do {
            $randId = str::getRand();
            $tempPath = WEBKIT_TO_PDF_TEMP_DIR . '/' . $randId;
        } while (is_dir($tempPath));
        
        //Създаваме рекурсивно папката
        expect(mkdir($tempPath, 0777, TRUE));
        
        //Пътя до html файла
        $htmlPath = $tempPath . '/' . $randId . '.html';
        
        // Зареждаме опаковката 
        $wrapperTpl = cls::get('page_Print');
        
        // Изпращаме на изхода опаковано съдържанието
        $wrapperTpl->replace($html, 'PAGE_CONTENT');
        
        $html = $wrapperTpl->getContent();
        $html = "\xEF\xBB\xBF" . $html;
        
        //Записваме данните в променливата $html в html файла
        $fileHnd = fopen($htmlPath, 'w');
        fwrite($fileHnd, $html);
        fclose($fileHnd);
        
        //Пътя до pdf файла
        $pdfPath = $tempPath . '/' . $fileName;
        
        //Ако ще използва xvfb-run
        if ($conf->WEBKIT_TO_PDF_XVFB_RUN) {
            //Променливата screen
            $screen = '-screen 0 ' . $conf->WEBKIT_TO_PDF_SCREEN_WIDTH . 'x' . $conf->WEBKIT_TO_PDF_SCREEN_HEIGHT . 'x' . $conf->WEBKIT_TO_PDF_SCREEN_BIT;
            
            //Ескейпваме променливата
            $screen = escapeshellarg($screen);
            
            //Изпълнение на програмата xvfb-run
            $xvfb = "xvfb-run -a -s {$screen}";
        }
        
        //Ескейпваме всички променливи, които ще използваме
        $htmlPathEsc = escapeshellarg($htmlPath);
        $pdfPathEsc = escapeshellarg($pdfPath);
        $binEsc = escapeshellarg($conf->WEBKIT_TO_PDF_BIN);
        
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
