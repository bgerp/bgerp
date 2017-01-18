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
     * Заглавие
     */
    var $title = 'webkittopdf';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_ConvertToPdfIntf';
    
    
    /**
     * Конвертира html към pdf файл
     * 
     * @param string $html - HTML стинга, който ще се конвертира
     * @param string $fileName - Името на изходния pdf файл
     * @param string $bucketName - Името на кофата, където ще се записват данните
     * @param array $jsArr - Масив с JS и JQUERY_CODE
     *
     * @return string|NULL $fh - Файлов манипулатор на новосъздадения pdf файл
     */
    static function convert($html, $fileName, $bucketName, $jsArr=array())
    {
        // Вземаме конфигурационните данни
    	$conf = core_Packs::getConfig('webkittopdf');
        
    	if (!webkittopdf_Setup::isEnabled()) {
            
    	    self::logAlert("Не е инсталирана програмата '{$conf->WEBKIT_TO_PDF_BIN}'");
    	    
            throw new core_exception_Expect("Не е инсталирана програмата '{$conf->WEBKIT_TO_PDF_BIN}'");
        }
        
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
        
        // Ако е зададено да се използва JS
        if ($conf->WEBKIT_TO_PDF_USE_JS == 'yes') {
            
            // Обхождаме масива с JS файловете
            foreach ((array)$jsArr['JS'] as $js) {
                
                // Добавяме в шаблона
                $wrapperTpl->push($js, 'JS');
            }
            
            // Обхождаме масива с JQUERY кодовете
            if ($jsArr['JQUERY_CODE'] && count((array)$jsArr['JQUERY_CODE'])) {
                
                // Обхождаме JQuery кодовете
                foreach ((array)$jsArr['JQUERY_CODE'] as $jquery) {
                    
                    // Добавяме кодовете
                    jquery_Jquery::run($wrapperTpl, $jquery);
                }
            }
            
            // Променлива за стартиране на JS
            $jsScript = '--enable-javascript';
            
            // Добавяме забавянето
            $jsScript .= " --javascript-delay " . escapeshellarg($conf->WEBKIT_TO_PDF_JS_DELAY);
            
            // Ако е No
            if ($conf->WEBKIT_TO_PDF_JS_STOP_SLOW_SCRIPT == 'no') {
                
                // Добавяме към променливите за JS
                $jsScript .= " --no-stop-slow-scripts";
            }
        } elseif ($conf->WEBKIT_TO_PDF_USE_JS == 'no') {
            
            // Ако е зададено да не се изпълнява
            $jsScript = "--disable-javascript";
        }
        
        // Изпращаме на изхода опаковано съдържанието
        $wrapperTpl->replace($html, 'PAGE_CONTENT');
        
        // Вземаме съдържанието
        // Трети параметър трябва да е TRUE, за да се вземе и CSS
        $html = $wrapperTpl->getContent(NULL, "CONTENT", TRUE);
        $html = "\xEF\xBB\xBF" . $html;
        
        //Записваме данните в променливата $html в html файла
        $fileHnd = fopen($htmlPath, 'w');
        fwrite($fileHnd, $html);
        fclose($fileHnd);
        
        //Пътя до pdf файла
        $pdfPath = $tempPath . '/' . $fileName;
        
        //Ако ще използва xvfb-run
        if ($conf->WEBKIT_TO_PDF_XVFB_RUN == 'yes') {
            
            //Променливата screen
            $screen = '-screen 0 ' . $conf->WEBKIT_TO_PDF_SCREEN_WIDTH . 'x' . $conf->WEBKIT_TO_PDF_SCREEN_HEIGHT . 'x' . $conf->WEBKIT_TO_PDF_SCREEN_BIT;
            
            //Ескейпваме променливата
            $screen = escapeshellarg($screen);
            
            //Изпълнение на програмата xvfb-run
            $xvfb = "xvfb-run -a -s {$screen}";
        } else {
            
            // Флаг указващ да се използва XServer в пакета
            $useXServer = TRUE;
        }
        
        //Ескейпваме всички променливи, които ще използваме
        $htmlPathEsc = escapeshellarg($htmlPath);
        $pdfPathEsc = escapeshellarg($pdfPath);
        $binEsc = escapeshellarg($conf->WEBKIT_TO_PDF_BIN);
        
        // Скрипта за wkhtmltopdf
        $wk = $binEsc;
        
        // Ако е вдигнат флага
        if ($useXServer) {
            
            // Добавяме в настройките
            $wk .= " --use-xserver";
        }
        
        // Ако е зададено да се използва медиа тип за принтиране
        if ($conf->WEBKIT_TO_PDF_USE_PRINT_MEDIA_TYPE == 'yes') {
            
            // Добавяме в настройките
            $wk .= " --print-media-type";
        }
    
        // Ако е зададено да се използва grayscale
        if ($conf->WEBKIT_TO_PDF_USE_GRAYSCALE == 'yes') {
            
            // Добавяме в настройките
            $wk .= " --grayscale";
        }
        
    
        // Ако е зададен енкодинг за текущия файл
        if ($conf->WEBKIT_TO_PDF_INPUT_ENCODING) {
            
            // Добавяме в настройките
            $wk .= " --encoding " . escapeshellarg($conf->WEBKIT_TO_PDF_INPUT_ENCODING);
        }
        
        // Ако има променливи за JS
        if ($jsScript) {
            
            // Добавяме към скрипта
            $wk .= " " . $jsScript;
        }
        
        // Добавяме изходните файлове
        $wk .= " {$htmlPathEsc} {$pdfPathEsc}";
        
        //Скрипта, който ще се изпълнява
        $exec = ($xvfb) ? "{$xvfb} {$wk}" : $wk;
        
        //Стартираме скрипта за генериране на pdf файл от html файл
        $res = shell_exec($exec);
        
        self::logDebug("Резултат от изпълнението на '{$exec}': " . $res);
        
        // Ако възникне грешка при качването на файла (липса на права)
        try {
            
            expect(is_file($pdfPath));
            
            // Качваме файла в кофата и му вземаме манипулатора
            $fh = fileman::absorb($pdfPath, $bucketName, $fileName); 
        } catch (core_exception_Expect $e) {
            $fh = NULL;
            reportException($e);
            self::logErr("Грешка при изпълнени на '{$exec}': " . $res);
        }
        
        //Изтриваме временната директория заедно с всички създадени папки
        core_Os::deleteDir($tempPath);
        
        //Връщаме манипулатора на файла
        return $fh;
    }
    
    
    /**
     * Проверява дали има функция за конвертиране
     * 
     * @return boolean
     */
    public static function isEnabled()
    {
        
        return (boolean)webkittopdf_Setup::isEnabled();
    }
    
    
    /**
     * След началното установяване на този мениджър, ако е зададено -
     * той сетъпва външния пакет, чрез който ще се генерират pdf-те
     * 
     * @param webkittopdf_Converter $mvc
     * @param string $res
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $res .= static::checkConfig();
    }
    
    
    /**
     * Проверява дали е инсталирана програмата, дали версията е коректна
     * 
     * @return string
     */
    static function checkConfig()
    {
        // Версиите на пакета
        $versionArr = webkittopdf_Setup::getVersionAndSubVersion();
        
        // В зависимост от версията активира използването на JS
        if (static::checkForActivateJS($versionArr)) {
            
            // Добавяме съобщение
            $res .= "<li style='color: green;'>" . 'Активирано е използване на JS при генериране на PDF' . "</li>";
        }
        
        // В зависимост от версията активира използването на printing media type
        if (static::checkForActivatePrintMediaType($versionArr)) {
            
            // Добавяме съобщение
            $res .= "<li style='color: green;'>" . 'Активирано е използване на printing media type при генериране на PDF' . "</li>";
        }
        
        return $res;
    }
    
    
    /**
     * В зависимост от версията активира използването на JS
     * 
     * @param array $versionArr
     */
    static function checkForActivateJS($versionArr)
    {
        // Ако версията е над 0,11 (включително)
        if (($versionArr['version'] > 0) || ($versionArr['subVersion'] >= 11)) {
            
            return core_Packs::setIfNotConfigKey('webkittopdf', 'WEBKIT_TO_PDF_USE_JS', 'yes');
        }
    }
    
    
    /**
     * В зависимост от версията активира използването на JS
     * 
     * @param array $versionArr
     */
    static function checkForActivatePrintMediaType($versionArr)
    {
        // Ако версията е над 0,11 (включително)
        if (($versionArr['version'] > 0) || ($versionArr['subVersion'] >= 11)) {
            
            return core_Packs::setIfNotConfigKey('webkittopdf', 'WEBKIT_TO_PDF_USE_PRINT_MEDIA_TYPE', 'yes');
        }
    }
}
