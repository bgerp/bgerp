<?php


/**
 * OCR обработка на файлове с помощта на ABBYY
 *
 * @category  vendors
 * @package   abbyyocr
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class abbyyocr_Converter extends core_Manager
{
    
    
    /**
     * Интерфейсни методи
     */
    var $interfaces = 'fileman_OCRIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'abbyy';
    
    
    /**
     * Кои потребители имат права за OCR на докуемент
     */
    static $canOCR = 'powerUser';
    
    
    /**
     * Позволените разширения
     */ 
    static $allowedExt = array('pdf', 'bmp', 'pcx', 'dcx', 'jpeg', 'jpg', 'tiff', 'tif', 'gif', 'png');
    
    
    /**
     * Добавя бутон за стартиране на OCR процеса
     * 
     * @param core_Toolbar $toolbar
     */
    function addOcrBtn(&$toolbar, $rec)
    {
        try {
            
            // Ако не може да се извлече текстовата част, връщаме
            if (!static::canExtract($rec->name)) return ;
            
            // Ако вече е извлечена текстовата част
            if (static::isTextIsExtracted($rec)) {
                    
                // Правим бутона на disabled
                $btnParams['disabled'] = 'disabled';    
            }
            
            $btnParams['order'] = 60;
            
            // URL за създаване
            $url = toUrl(array(get_called_class(), 'getTextByOcr', $rec->fileHnd, 'ret_url' => TRUE)); 
             
            // Добавяме бутона
            $toolbar->addBtn('OCR', $url, 
            	'ef_icon = img/16/scanner.png', 
                $btnParams
            ); 
        } catch (core_exception_Expect $e) {}
    }
    

	/**
     * Екшъна за извличане на текст чрез OCR
     */
    function act_getTextByOcr()
    {
        // Манипулатора на файла
        $fh = Request::get('id');
        
        // Вземаме записа за файла
        $rec = fileman_Files::fetchByFh($fh);
        
        // Очакваме да може да се извлича
        expect(static::canExtract($rec->name));
        
        // Проверяваме дали имаме права за сингъла на файла
        fileman_Files::requireRightFor('single', $rec);
        
        // Инстанция на класа
        $me = get_called_class();
        
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => $me . '::afterGetTextByAbbyyOcr',
            'dataId' => $rec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'textOcr',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = fileman_webdrv_Generic::getLockId($params['type'], $rec->dataId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) {
            
            // Добавяме съобщение
            status_Messages::newStatus(tr('Процеса вече е бил стартиран'));
        } else {
            
            // Заключваме процеса за определено време
            if (core_Locks::get($params['lockId'], 300, 0, FALSE)) {
                
                // Стартираме извличането
                static::getText($rec->fileHnd, $params);
            }
        }
        
        // URL' то където ще редиректваме
        $retUrl = getRetUrl();
        
        // Ако не може да се определи
        if (!$retUrl) {
            
            // URL' то където ще редиректваме
            $retUrl = array('fileman_Files', 'single', $rec->fileHnd);
        }
        
        return Redirect($retUrl);
    }
    
    
    /**
     * Вземаме текстова част от подадения файл
     * 
     * @param fileHnd $fileHnd - Манипулатора на файла
     * @param array $params - Допълнителни параметри
     */
    static function getText($fileHnd, $params)
    {
        // Вземам записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        // Очакваме да има такъв запис
        expect($fRec);
        
        // Очакваме да може да се извлече информация от файла
        expect(static::canExtract($fRec->name));
        
        // Инстанция на класа
        $Script = cls::get(fconv_Script);
        
        // Пътя до файла, в който ще се записва получения текст
        $textPath = $Script->tempDir . 'text.txt';
        
        // Задаваме файловете и параметрите
        $Script->setFile('INPUTF', $fileHnd);
        $Script->setFile('OUTPUTF', $textPath);
        
        // Задаваме параметрите
        $Script->setParam('LANGUAGE', abbyyocr_Setup::get('LANGUAGES'), TRUE);
        
        // Заместваме програмата с пътя от конфига
        $Script->setProgram('abbyyocr9', abbyyocr_Setup::get('ABBYYOCR_PATH'));
        
        // Добавяме към изпълнимия скрипт
        $lineExecStr = "abbyyocr9 -rl [#LANGUAGE#] -if [#INPUTF#] -tet UTF8 -f Text -of [#OUTPUTF#]";
        
        $errFilePath = fileman_webdrv_Generic::getErrLogFilePath($textPath);
        
        // Скрипта, който ще конвертира
        $Script->lineExec($lineExecStr, array('LANG' => 'en_US.UTF-8', 'HOME' => $Script->tempPath, 'errFilePath' => $errFilePath));
        
        // Функцията, която ще се извика след приключване на операцията
        $Script->callBack($params['callBack']);
        
        $params['errFilePath'] = $errFilePath;
        
        // Други допълнителни параметри
        $Script->outFilePath = $textPath;
        $Script->params = serialize($params);
        $Script->fh = $fileHnd;
        
        // Стартираме скрипта
        $Script->run($params['asynch']);
        
        // Добавяме съобщение
        status_Messages::newStatus(tr('Стартирано е извличането на текст с OCR'), 'success');
    }
    
    
    /**
     * Изпълнява се след приключване на обработката
     * 
     * @param fconv_Script $sctipt - Обект с данние
     * 
     * @param boolean
     */
    function afterGetTextByAbbyyOcr($script)
    {
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);
        
        // Проверяваме дали е имало грешка при предишното конвертиране
        if (fileman_Indexes::haveErrors($script->outFilePath, $params)) {
            
            // Отключваме процеса
            core_Locks::release($params['lockId']);
            
            return FALSE;
        }
        
        // Вземаме съдържанието на файла
        $params['content'] = file_get_contents($script->outFilePath);
        
        // Записваме данните
        $saveId = fileman_Indexes::saveContent($params);
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($saveId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Проверява дали файл с даденото име може да се екстрактва
     * 
     * @param string $name - Името на файла
     * 
     * @return boolean - Дали може да се екстрактва от файла
     */
    static function canExtract($name)
    {
        //Разширението на файла
        $ext = strtolower(fileman_Files::getExt($name));
        
        // Ако разширението е в позволените
        if (in_array($ext, static::$allowedExt)) {
            
            // Проверяваме дали има права за екстрактване
            if (haveRole(static::$canOCR)) {
                
                // Ако всичко е OK връщаме TRUE
                return TRUE;
            }
        }
    }
    
    
    /**
     * Проверява дали текста е бил извличан преди
     * 
     * @param object $rec - Записа за файла
     * 
     * @return boolean
     */
    static function isTextIsExtracted($rec)
    {
    
        // Ако е извлечена текстовата част
//        $params['type'] = 'text';
//        $params['dataId'] = $rec->dataId;
//        $procText = fileman_Indexes::isProcessStarted($params, TRUE);
//       if (!$procText) {
            
            // Ако е извлечена текстовата част с OCR
            $paramsOcr['type'] = 'textOcr';
            $paramsOcr['dataId'] = $rec->dataId;
            $procTextOcr = fileman_Indexes::isProcessStarted($paramsOcr);
//        }
        
        // Ако текста е бил извличан по някой начин, връщаме истина
        // if ($procTextOcr || $procText) {
        if ($procTextOcr) {
            
            return TRUE;
        }
    }
    
    
    /**
     * След началното установяване на този мениджър
     */
    static function loadSetupData()
    {
        // Вземаме конфига
    	$conf = core_Packs::getConfig('fileman');
    	
    	// Ако няма запис в модела
    	if (!$conf->_data['FILEMAN_OCR']) {
    	    
            // Да използваме текущия клас
	        $data['FILEMAN_OCR'] = core_Classes::getId(get_called_class());

	        // Добавяме в записите
            core_Packs::setConfig('fileman', $data);
    	}
    }
}
