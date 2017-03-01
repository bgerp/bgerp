<?php


/**
 * OCR обработка на файлове с помощта на Tesseract
 *
 * @category  vendors
 * @package   tesseract
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tesseract_Converter extends core_Manager
{
    
    
    /**
     * Интерфейсни методи
     */
    var $interfaces = 'fileman_OCRIntf, fileman_FileActionsIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Tesseract OCR';
    
    
    /**
     * Кои потребители имат права за OCR на докуемент
     */
    static $canOCR = 'powerUser';
    
    
    /**
     * Позволените разширения
     */ 
    static $allowedExt = array('pdf', 'bmp', 'pcx', 'dcx', 'jpeg', 'jpg', 'tiff', 'tif', 'gif', 'png');
    
    
    /**
     * Масив с програмите и функциите за определяне на пътя до тях
     */
    public $fconvProgramPaths = array('tesseract' => 'tesseract_Setup::TESSERACT_PATH');
    
    
    /**
     * Кода, който ще се изпълнява
     */
    public $fconvLineExec = 'tesseract [#INPUTF#] [#OUTPUTF#] -l [#LANGUAGE#] -psm [#PSM#]';
	
	
    /**
     *
     */
    public $canOcr = 'powerUser';
	
	
    /**
     * Интерфейсен метод на fileman_FileActionsIntf
     *
     * Връща масив с действия, които могат да се извършат с дадения файл
     *
     * @param stdObject $fRec - Обект са данни от модела
     *
     * @return array|NULL $arr - Масив с данните
     * $arr['url'] - array URL на действието
     * $arr['title'] - Заглавието на бутона
     * $arr['icon'] - Иконата
     */
    static function getActionsForFile_($fRec)
    {
        $arr = NULL;
        
        if (self::haveRightFor('ocr') && self::canExtract($fRec)) {

            $btnParams = array();
            
            $btnParams['order'] = 60;
            $btnParams['title'] = 'Разпознаване на текст с tesseract';
            
            // Ако вече е извлечена текстовата част
            $procTextOcr = fileman_Indexes::isProcessStarted(array('type' => 'textOcr', 'dataId' => $fRec->dataId));
            if ($procTextOcr) {
                $btnParams['warning'] = 'Файлът е преминал през разпознаване на текст';
            } elseif (!self::haveTextForOcr($fRec)) {
                $btnParams['warning'] = 'Няма текст за разпознаване';
            }
            
            $arr = array();
            $arr['tesseract']['url'] = array(get_called_class(), 'getTextByOcr', $fRec->fileHnd, 'ret_url' => TRUE);
            $arr['tesseract']['title'] = 'OCR';
            $arr['tesseract']['icon'] = 'img/16/scanner.png';
            $arr['tesseract']['btnParams'] = $btnParams;
        }
        
        return $arr;
        
    }
	
    
	/**
     * Екшъна за извличане на текст чрез OCR
     * 
     * @see fileman_OCRIntf
     */
    function act_getTextByOcr()
    {
        // Манипулатора на файла
        $fh = Request::get('id');
        
        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fh);
        
        expect($fRec);
        
        // Очакваме да може да се извлича
        expect(static::canExtract($fRec));
        
        fileman_Files::requireRightFor('single', $fRec);
        
        $this->getTextByOcr($fRec);
        
        // URL' то където ще редиректваме
        $retUrl = getRetUrl();
        
        // Ако не може да се определи
        if (empty($retUrl)) {
            
            // URL' то където ще редиректваме
            $retUrl = array('fileman_Files', 'single', $fRec->fileHnd);
        }
        
        if ($fRec->dataId && ($dRec = fileman_Data::fetch((int) $fRec->dataId))) {
            fileman_Data::resetProcess($dRec);
        }
        
        return new Redirect($retUrl);
    }
    
    
    /**
     * 
     * 
     * @param stdObject|string $fRec
     * 
     * @return string|NULL
     * 
     * @see fileman_OCRIntf
     */
    function getTextByOcr($fRec)
    {
        // Инстанция на класа
        $me = get_called_class();
        
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => $me . '::afterGetTextByTesseract',
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'textOcr',
        );
        
        if (is_object($fRec)) {
            $params['dataId'] = $fRec->dataId;
            $params['asynch'] = TRUE;
            $file = $fRec->fileHnd;
        } else {
            $params['asynch'] = FALSE;
            $params['isPath'] = TRUE;
            $file = $fRec;
        }
        
        $lId = fileman_webdrv_Generic::prepareLockId($fRec);
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = fileman_webdrv_Generic::getLockId($params['type'], $lId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (core_Locks::isLocked($params['lockId'])) {
            
            if ($params['asynch']) {
                // Добавяме съобщение
                status_Messages::newStatus('|Процеса вече е бил стартиран');
            }
        } else {
        
            // Заключваме процеса за определено време
            if (core_Locks::get($params['lockId'], 300, 0, FALSE)) {
                
                fileman_Data::logWrite('OCR обработка на файл с tesseract', $fRec->dataId);
                fileman_Files::logWrite('OCR обработка на файл с tesseract', $fRec->id);
                
                // Стартираме извличането
                return static::getText($file, $params);
            }
        }
    }
    
    
    /**
     * Вземаме текстова част от подадения файл
     * 
     * @param string $fileHnd - Манипулатора на файла и път до файла
     * @param array $params - Допълнителни параметри
     * 
     * @return string
     */
    static function getText($fileHnd, $params)
    {
        if (!$params['isPath']) {
            // Вземам записа за файла
            $fRec = fileman_Files::fetchByFh($fileHnd);
            
            // Очакваме да има такъв запис
            expect($fRec);
            
            // Очакваме да може да се извлече информация от файла
            expect(static::canExtract($fRec));
            
            $ext = fileman_Files::getExt($fRec->name);
        } else {
            expect(static::canExtract($fileHnd));
            
            $ext = fileman_Files::getExt($fileHnd);
        }
        
        // Ако е pdf файл, тогава го преобразуваме в tiff
        if ($ext == 'pdf') {
            
            if (!$params['isPath']) {
                $pdfPath = fileman::extract($fileHnd);
            } else {
                $pdfPath = $fileHnd;
            }
            
            $tiffPath = $pdfPath . '.tiff';
            
            $pdfPathEsc = escapeshellarg($pdfPath);
            $tiffPathEsc = escapeshellarg($tiffPath);
            
            exec("convert -density 300 {$pdfPathEsc} -depth 8 {$tiffPathEsc}");
            
            if (is_file($tiffPath)) {
                $fileHnd = $tiffPath;
            }
            
            if (!$params['isPath']) {
                $params['delPath'] = $pdfPath;
            }
        }
        
        // Инстанция на класа
        $Script = cls::get(fconv_Script);
        
        // Пътя до файла, в който ще се записва получения текст
        $outputFile = $Script->tempDir . 'text';
        
        // Задаваме файловете и параметрите
        $Script->setFile('INPUTF', $fileHnd);
        $Script->setFile('OUTPUTF', $outputFile);
        
        // Задаваме параметрите
        $Script->setParam('LANGUAGE', tesseract_Setup::get('LANGUAGES'), TRUE);
        $Script->setParam('PSM', tesseract_Setup::get('PAGES_MODE'), TRUE);
        
        // Заместваме програмата с пътя от конфига
        $Script->setProgram('tesseract', tesseract_Setup::get('PATH'));
        $Script->setProgramPath(get_called_class(), 'fconvProgramPaths');
        
        $errFilePath = fileman_webdrv_Generic::getErrLogFilePath($outputFile);
        
        // Скрипта, който ще конвертира
        $Script->lineExec(get_called_class() . '::fconvLineExec', array('LANG' => 'en_US.UTF-8', 'HOME' => $Script->tempPath, 'errFilePath' => $errFilePath));
        
        // Функцията, която ще се извика след приключване на операцията
        $Script->callBack($params['callBack']);
        
        $params['errFilePath'] = $errFilePath;
        
        // Други допълнителни параметри
        $params['outFilePath'] = $outputFile . '.txt';
        if (!$params['isPath']) {
            $params['fh'] = $fileHnd;
        }
        $Script->params = $params;
        
        $Script->setCheckProgramsArr('tesseract');
        // Стартираме скрипта Aсинхронно
        if ($Script->run($params['asynch']) === FALSE) {
            fileman_Indexes::createError($params);
        }
        
        $text = '';
        if (!$params['asynch']) {
            $text = @file_get_contents($params['outFilePath']);
            $text = i18n_Charset::convertToUtf8($text, 'UTF-8');
        
            core_Locks::release($params['lockId']);
        } else {
            // Добавяме съобщение
            status_Messages::newStatus('|Стартирано е извличането на текст с OCR', 'success');
        }
        
        return $text;
    }
    
    
    /**
     * Изпълнява се след приключване на обработката
     * 
     * @param fconv_Script $script - Обект с данние
     * 
     * @param boolean
     */
    function afterGetTextByTesseract($script)
    {
        // Десериализираме нужните помощни данни
        $params = $script->params;
        
        // Вземаме съдържанието на файла
        $params['content'] = @file_get_contents($params['outFilePath']);
        
        $params['content'] = trim($params['content']);
        
        // Проверяваме дали е имало грешка при предишното конвертиране
        if (!$params['content'] && fileman_Indexes::haveErrors($params['outFilePath'], $params)) {
        
            // Отключваме процеса
            core_Locks::release($params['lockId']);
        
            return FALSE;
        }
        
        // Записваме данните
        $saveId = fileman_Indexes::saveContent($params);
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($saveId) {
			
            if ($params['delPath']) {
                fileman::deleteTempPath($params['delPath']);
            }
            
            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Проверява дали файл с даденото име може да се екстрактва
     * 
     * @param stdClass|string $fRec
     * 
     * @return boolean - Дали може да се екстрактва от файла
     * 
     * @see fileman_OCRIntf
     */
    static function canExtract($fRec)
    {
        $name = $fRec;
        if (is_object($fRec)) {
            $name = $fRec->name;
        }
        $ext = strtolower(fileman_Files::getExt($name));
        
        // Ако разширението е в позволените
        if ($ext && in_array($ext, self::$allowedExt)) {
            // Ако всичко е OK връщаме TRUE
            return TRUE;
        }
        
        return FALSE;
    }
    

    /**
     * Бърза проврка дали има смисъл от OCR-ване на текста
     *
     * @param stdObject|string $fRec
     * 
     * @see fileman_OCRIntf
     */
    public static function haveTextForOcr($fRec)
    {
        // @todo psm=0
        
        return TRUE;
    }
    
    
    /**
     * След началното установяване на този мениджър
     */
    static function loadSetupData()
    {
        // Вземаме конфига
    	$conf = core_Packs::getConfig('fileman');
    	
    	$data = array();
    	
    	// Ако няма запис в модела
    	if (!$conf->_data['FILEMAN_OCR']) {
    	    
            // Да използваме текущия клас
	        $data['FILEMAN_OCR'] = core_Classes::getId(get_called_class());

	        // Добавяме в записите
            core_Packs::setConfig('fileman', $data);
    	}
    }
}
