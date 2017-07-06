<?php


/**
 * Прародителя на всички драйвери за файловете
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Generic extends core_Manager
{
    
    
    /**
     * Кой таб да е избран по подразбиране
     */
    public static $defaultTab = 'info';
    
    
    /**
     * Zip инстанции на отворените архиви
     */
    public static $archiveInst = array();
    
    
    /**
     * Брой на документи, които да се показват в мета информацията, за "Съдържа се в:"
     */
    public static $metaInfoDocLimit = 20;
    
    
    /**
     * Суфикса за файла с грешките
     */
    protected static $errLogFileExt = '_err.log';
    
    
    /**
     * Кой може да разглежда драйвер
     */
    protected $canView = 'every_one';
    
    
    /**
     * Връща всички табове, които ги има за съответния файл
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return array
     */
    static function getTabs($fRec) 
    {
        // Масив с всички табове
        $tabsArr = array();
        
        // URL за показване на информация за файла
        $infoUrl = toUrl(array('fileman_webdrv_Generic', 'Info', $fRec->fileHnd), TRUE);
        // Таб за информация
        $tabsArr['info'] = (object) 
			array(
				'title' => 'Информация',
				'html'  => "<div class='webdrvTabBody'><div class='webdrvFieldset'><div class='legend'>" . tr("Мета информация") . "</div>
					<iframe src='{$infoUrl}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'> </iframe></div></div>",
				'order' => 1,
			);
        
		$tabsArr['__defaultTab'] = static::$defaultTab;	
			
        return $tabsArr;
    }
    
    
    /**
     * Стартира извличането на информациите за файла
     * 
     * @param object $fRec - Записите за файла
     */
    static function startProcessing($fRec)
    {
        // Извличане на мета информцията за всички файлове
        static::getMetaData($fRec);
        
        return ;
    }
    
    
    /**
     * Дали трябва да се показва съответния таб
     * 
     * @param string $fileHnd
     * @param string $type
     * @param string $strip
     * 
     * @return boolean
     */
    public static function canShowTab($fileHnd, $type, $strip=TRUE, $checkExist = FALSE)
    {
        $rArr = fileman_Indexes::getInfoContentByFh($fileHnd, $type);
        
        if ($checkExist === TRUE && $rArr === FALSE) return FALSE;
        
        if (is_array($rArr) && empty($rArr)) return FALSE;
        
        if (is_string($rArr) && $strip) {
            $rArr = strip_tags($rArr);
            
            if (!trim($rArr)) return FALSE;
        }
        
        return TRUE;
    }
    
        
    /**
     * Връща името на файла за грешките
     * 
     * @param string $outFilePath
     * 
     * @return string
     */
    public static function getErrLogFilePath($outFilePath)
    {
        
        return $outFilePath . self::$errLogFileExt;
    }
    
    
    /**
     * Екшън за показване текстовата част на файла
     */
    function act_Text()
    {
        // Очакваме да има права за виждане
        $this->requireRightFor('view');
        
        // Манипулатора на файла
        $fileHnd = Request::get('id');
        
        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        // Очакваме да има права за разглеждане на записа
        $this->requireRightFor('view', $fRec);
        
        // Вземаме текста
        $content = fileman_Indexes::getInfoContentByFh($fileHnd, 'text');
        
        // Вземаме текста извлечен от OCR
        $ocrContent = fileman_Indexes::getInfoContentByFh($fileHnd, 'textOcr');

        // Ако има OCR съдържание
        if ($ocrContent !== FALSE && !is_object($ocrContent)) {
            
            // Тогава съдържанието е равно на него
            $content = $ocrContent;
        } else {
            
            // Вземаме записа за съответния файл
            $rec = fileman_Files::fetchByFh($fileHnd);
            
            $paramsOcr = array();
            
            // Параметри за OCR
            $paramsOcr['dataId'] = $rec->dataId;
            $paramsOcr['type'] = 'textOcr';    
        }
        
        // Ако нама такъв запис
        if (($content === FALSE) || (($ocrContent === FALSE) && (fileman_Indexes::isProcessStarted($paramsOcr)))) {
            
            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');
            
            return ;
        }
        
        // Ако е обект и има съобщение за грешка
        if (is_object($content) && $content->errorProc) {
            
            // Сменяме мода
            Mode::set('wrapper', 'page_PreText');
            
            // Връщаме съобщението за грешка
            return tr($content->errorProc);       
        }

        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_PreText');
        
        // Ескейпваме текстовата част
        $content = type_Varchar::escape($content);
        
        // Връщаме съдържанието
        return $content;
    }
    
    
	/**
     * Екшън за показване превю
     */
    function act_Preview()
    {
        // Очакваме да има права за виждане
        $this->requireRightFor('view');
        
        // Манипулатора на файла
        $fileHnd = Request::get('id');
        
        if (!$fileHnd) {
            $fileHnd = Request::get('fileHnd');
        }
        
        $bigImg = Request::get('bigImg');
        
        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        // Очакваме да има права за разглеждане на записа
        $this->requireRightFor('view', $fRec);
        
        // Вземаме масива с изображенията
        $jpgArr = fileman_Indexes::getInfoContentByFh($fileHnd, 'jpg');

        // Ако няма такъв запис
        if ($jpgArr === FALSE) {
            
            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');
            
            return ;
        }
        
        // Ако е обект и има съобщение за грешка
        if (is_object($jpgArr) && $jpgArr->errorProc) {
            
            // Сменяме мода
            Mode::set('wrapper', 'page_PreText');
            
            // Връщаме съобщението за грешка
            return tr($jpgArr->errorProc);       
        }

        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_Empty');
        
        if (($jpgArr) && (count($jpgArr))) {
            
            // Вземаме височината и широчината
            $thumbWidthAndHeightArr = static::getPreviewWidthAndHeight();
            
            // Атрибути на thumbnail изображението
            $attr = array('class' => 'webdrv-preview', 'style' => 'margin: 0 auto 5px auto; display: block;');
            
            // Background' а на preview' то
            $bgImg = sbf('fileman/img/Preview_background.jpg');
            
            // Създаваме шаблон за preview на изображението
            $preview = new ET("<div style='background-image:url(" . $bgImg . "); padding: 5px 0; min-height: 590px;display: table;width: 100%;'><div style='margin: 0 auto;'>[#THUMB_IMAGE#]</div></div>");
			
            foreach ($jpgArr as $key => $jpgFh) {
                
                if ($key === 'otherPagesCnt') {
                    
                    $str = '<div style="margin: 5px 0 0 5px; background: #fff; display: inline-block; padding: 2px; color: #444;">' . tr('Още страници') . ': ' . $jpgFh . '</div>';
                    
                    $preview->append($str, 'THUMB_IMAGE');
                } else {
                    
                    $multiplier = fileman_Setup::get('WEBDRV_PREVIEW_MULTIPLIER');
                    
                    $width = $thumbWidthAndHeightArr['width'];
                    $height = $thumbWidthAndHeightArr['height'];
                    $verbalName = 'Preview';
                    if ($bigImg && ($multiplier > 1)) {
                        $width *= $multiplier;
                        $height *= $multiplier;
                        $verbalName .= ' X ' . $multiplier;
                        $attr['class'] .= ' webdrv-previewX2';
                    }
                    
                    $imgInst = new thumb_Img(array($jpgFh, $width, $height, 'fileman', 'verbalName' => $verbalName));
                    
                    // Вземаме файла
                    $thumbnailImg = $imgInst->createImg($attr);
                        
                    // В зависимост от текущото състояние добаваме линк за увеличаване/намаляне изборажението
                    if ($multiplier > 1) {
                        $aAttr = array();
                        $aAttr['href'] = toUrl(array($this, 'preview', 'bigImg' => !$bigImg, 'fileHnd' => $fileHnd));
                        
                        $thumbnailImg = ht::createElement('a', $aAttr, $thumbnailImg);
                    }
                    
                    // Добавяме към preview' то генерираното изображение
                    $preview->append($thumbnailImg, 'THUMB_IMAGE');
                }
            }

            $preview->push('js/wheelzoom.js', "JS");
            jquery_Jquery::run($preview, 'wheelzoom(document.querySelector(\'img.webdrv-preview\'));');

            return $preview;
        }
    }
    
    
    /**
     * Екшън за визуализране на баркодовете
     */
    function act_Barcodes()
    {
        // Очакваме да има права за виждане
        $this->requireRightFor('view');
        
        // Манипулатора на файла
        $fileHnd = Request::get('id');
        
        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        // Очакваме да има права за разглеждане на записа
        $this->requireRightFor('view', $fRec);
        
        // Вземаме баркодовете
        $barcodes = fileman_Indexes::getInfoContentByFh($fileHnd, 'barcodes');

        // Ако нама такъв запис
        if ($barcodes === FALSE) {
            
            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');
            
            return ;
        }
        
        // Ако е обект и има съобщение за грешка
        if (is_object($barcodes) && $barcodes->errorProc) {
            
            // Сменяме мода
            Mode::set('wrapper', 'page_PreText');
            
            // Връщаме съобщението за грешка
            return tr($barcodes->errorProc);       
        }
        
        // Ако е масив
        if (is_array($barcodes)) {
            
            // Обхождаме масива
            foreach ($barcodes as $barcode) {
                
                // Обхождаме вътрешния масив
                foreach ($barcode as $barcodeObj) {
                    
                    // TODO
                    
                    // Добавяме стринг
                    $barcodeStr .= "Тип: {$barcodeObj->type}\nБаркод: <span onmouseUp='selectInnerText(this);'>{$barcodeObj->code}</span>\n\n";
                }
            }
        }
        
        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_PreText');
        
        return $barcodeStr;
    }
    
    
     /**
     * Екшън за визуализране на информация
     */
    function act_Info()
    {
        // Очакваме да има права за виждане
        $this->requireRightFor('view');
        
        // Манипулатора на файла
        $fileHnd = Request::get('id');
        
        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        // Очакваме да има права за разглеждане на записа
        $this->requireRightFor('view', $fRec);
        
        // Вземаме текста
        $content = fileman_Indexes::getInfoContentByFh($fileHnd, 'metadata');
        
        // Ако нама такъв запис
        if ($content === FALSE) {
            
            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');
            
            return ;
        }
        
        // Ако е обект и има съобщение за грешка
        if (is_object($content) && $content->errorProc) {
            
            // Сменяме мода
            Mode::set('wrapper', 'page_PreText');
            
            // Връщаме съобщението за грешка
            return tr($content->errorProc);       
        }
        
        // Парсираме информцията и превеждаме таговете на редовете
        $content = static::parseInfo($content);
        
        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_PreText');
        
        // Записите за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        // Линк за сваляне
        $link = bgerp_F::getLink($fileHnd, $expireOn);
        $linkText = '';
        if (!empty($link)) {
            $linkText = tr("Линк|*: ");
            
            $expireOn = dt::mysql2verbal($expireOn, 'smartTime');
            
            $linkText .= tr("|*<span onmouseUp='selectInnerText(this);'>{$link}</span> <small>(|Изтича|*: {$expireOn})</small>");
            
            $linkText .= "\n";
        }
        
        try {
		    
		    // Опитваме се да вземем, документите, в които се използва файла
		    $documentWithFile = fileman_Files::getDocumentsWithFile($fRec, static::$metaInfoDocLimit);    
		} catch (core_exception_Expect $e) {
	        // Няма да се показват документите
		}
		
		$dangerRate = '';
		if (fileman_Files::isDanger($fRec)) {
		    $dangerRate = fileman_Files::getVerbal($fRec, 'dangerRate');
		    $dangerRate = '<span class = "dangerFile">' . tr("Ниво на опасност|*: ") . $dangerRate . "</span>\n";
		}
		
		// Ако сме намерили някой файлове, където се използва
		$containsIn = '';
        if ($documentWithFile) {
            
            // Добавяме към съдържанието на инфото
            $containsIn = tr("Съдържа се в|*: ") . $documentWithFile . "\n";
        }
        
        // Типа на файла
        $type = fileman_Mimes::getMimeByExt(fileman_Files::getExt($fRec->name));
        
        // Вербалния размер на файла
        $size = fileman_Data::getFileSize($fRec->dataId);
        
        $sizeText = '';
        
        // Ако има размер
        if ($size) {
            
            // Размера за показване
            $sizeText = tr("|Размер|*: {$size}");  
            
            // Добавяме към съдържанието на инфо
            $sizeText .= "\n";
        }
        
        // Информация за създаването
        $createdOn = fileman_Files::getVerbal($fRec, 'createdOn');
        $createdBy = fileman_Files::getVerbal($fRec, 'createdBy');
        
        // Показване на създаването
        $createdText = tr("|Добавен на|* : {$createdOn} |от|* {$createdBy}") . "\n";
        
        // Добавяме в текста
        $content = $dangerRate . $containsIn . $createdText . $sizeText . $linkText . core_Type::escape($content);
        
        // Инстанция на класа
        $pageInst = cls::get(Mode::get('wrapper'));
        
        // Линковете вътре в документа, да се отварят в родителската страница
        $pageInst->appendOnce('<base target="_parent" />', 'HEAD');
        
        // Добавяме стилове
        $pageInst->appendOnce('body{line-height:150%;}', 'STYLES');
        
        // Връщаме съдържанието
        return $pageInst->output($content);
    }
	
	
	/**
     * Екшън за показване HTML частта на файла
     */
    function act_Html()
    {
        // Очакваме да има права за виждане
        $this->requireRightFor('view');
        
        // Манипулатора на файла
        $fileHnd = Request::get('id');
        
        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        // Очакваме да има права за разглеждане на записа
        $this->requireRightFor('view', $fRec);
        
        // Вземаме текста
        $content = fileman_Indexes::getInfoContentByFh($fileHnd, 'html');
        
        // Ако нама такъв запис
        if ($content === FALSE) {
            
            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');
            
            return ;
        }
        
        // Ако е обект и има съобщение за грешка
        if (is_object($content) && $content->errorProc) {
            
            // Сменяме мода
            Mode::set('wrapper', 'page_PreText');
            
            // Връщаме съобщението за грешка
            return tr($content->errorProc);       
        }

        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_Html');
        
        // Връщаме съдържанието
        return $content;
    }
    
    
    /**
     * Подготвя стойността за заключване
     * 
     * @param string|stdObject $res
     * 
     * @return string|boolean
     */
    static function prepareLockId($res)
    {
        if (is_object($res)) {
            
            return $res->dataId;
        }
        
        if (is_file($res)) {
            
            return md5_file($res);
        }
        
        return FALSE;
    }
    
    
    /**
     * Генерира и връща уникален стринг за заключване на процес за даден файл
     *
     * @param string $type - Типа, който ще заключим
     * @param object $fRec - Записите за файлва
     * 
     * @return string $lockId - уникален стринг за заключване на процес за даден файл
     */
    static function getLockId($type, $dataId)
    {
        // Генерираме уникален стринг за заключване на процес за даден файл
        $lockId = $type . $dataId;
        
        return $lockId;
    }
    
    
    /**
     * Намира баркода и ги записва в базата
     * 
     * @param fconv_Script $script - 
     * @param array $fileHndArr - Масив от манипулатори
     * 
     * @return integet $savedId - fileman_Indexes id' то на записа в  
     */
    static function saveBarcodes($script, $fileHndArr)
    {
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);
        
        // Променливата, с която ще заключим процеса
        $params['type'] = 'barcodes';
        $params['lockId'] = static::getLockId($params['type'], $params['dataId']);
        
        try {
            
            // Вземаме баркодовете
            $barcodesArr = static::findBarcodes($fileHndArr, $params['dataId']); 
        } catch (fileman_Exception $e) {
            
            // Добавяме съобщението за грешка
            $barcodesArr = new stdClass();
            $barcodesArr->errorProc = $e->getMessage();
            
            // Записваме грешката
            fileman_Indexes::createErrorLog($params['dataId'], $params['type']);
        }
        
        // Съдържанието
        $params['content'] = $barcodesArr;
        
        // Обновяваме данните за запис във fileman_Indexes
        $savedId = fileman_Indexes::saveContent($params);

        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        return $savedId;
    }
    
    
    /**
     * Намира баркодовете във подадените файлове
     * 
     * @param mixed $fh - Манипулатор на файла или масив от манипулатори на файла
     * @param integer $fileInfoId - id' то на записа от fileman_Indexes, в който ще запишем получената информация
     * 
     * @access protected
     */
    static function findBarcodes($fileHnd, $dataId)
    {
        // Проверяваме дали оригиналния файл е с допустимите размери и разширение за определяне на баркод
        if (!static::canReadBarcodes($dataId)) {
            
            return ;
        }
        
        // Ако е подаден манипулатор, а не масив
        if (!is_array($fileHnd)) {
            
            $fileHndArr = array();
            
            // Създаваме масива
            $fileHndArr[$fileHnd] = $fileHnd;  
        } else {
            $fileHndArr = $fileHnd;
        }
        
        $barcodesArr = array();
        
        // Обхождаме масива с манупулаторите
        foreach ($fileHndArr as $fh) {
            if (!trim($fh)) continue;
            
            // Определяме баркодовете във файла
            $barcodes = zbar_Reader::getBarcodesFromFile($fh);

            // Ако няма открит баркод прескачаме
            if (!count($barcodes)) continue;
            
            // Масив с всички баркодове
            $barcodesArr[] = $barcodes;
        }
        
        return $barcodesArr;
    }
    
    
	/**
     * Проверяваме дали оригиналния файл е с допустимите размери за определяне на баркод
     */
    static function canReadBarcodes($dataId)
    {
        // Вземаме записа за оригиналния файла
        $dRec = fileman_Data::fetch($dataId);
        
        // Вземаме размера на файла
        $fLen = $dRec->fileLen;
        
        // Вземаме конфигурационните константи
        $conf = core_Packs::getConfig('fileman');
        
        // По голям или равен на 15kB
        // По малък или равен на 1mB
        // Проверяваме дали е в допустимите граници
        if (($fLen >= $conf->FILEINFO_MIN_FILE_LEN_BARCODE) && (($fLen <= $conf->FILEINFO_MAX_FILE_LEN_BARCODE))) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Извлича мета информацията
     * 
     * @param object $fRec - Записите за файла
     */
    static function getMetaData($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'fileman_webdrv_Generic::aftergetMetaData',
            'dataId' => $fRec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'metadata',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);

        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) return ;

        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            // Извличаме мета информцията с Apache Tika
            apachetika_Detect::extract($fRec->fileHnd, $params);
        }
    }
    
    
    /**
     * Получава управеленито след извличане на мета информцията
     * 
     * @param fconv_Script $script - Обект с нужните данни
     * 
     * @return boolean - Дали е изпълнен успешно
     */
    static function aftergetMetaData($script)
    {
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);
        
        // Проверяваме дали е имало грешка при предишното конвертиране
        if (fileman_Indexes::haveErrors($script->outFilePath, $params)) {
            
            // Отключваме процеса
            core_Locks::release($params['lockId']);
            
            return FALSE;
        }
        
        // Вземаме съдъжанието на файла, който е генериран след обработката към .txt формат
        $params['content'] = file_get_contents($script->outFilePath);

        // Обновяваме данните за запис във fileman_Indexes
        $saveId = fileman_Indexes::saveContent($params);

        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($saveId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        }
    }  
    
    
    /**
     * Взема баркодовете от файла
     * 
     * @param object $fRec - Записите за файла
     * @param string $callBack - Функцията, която ще се извика след приключване на процеса
     */
    static function getBarcodes($fRec, $callBack = 'fileman_webdrv_Generic::afterGetBarcodes')
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => $callBack,
            'dataId' => $fRec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'barcodes',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) return ;

        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            // Инстанция на класа
            $Script = cls::get(fconv_Script);
            
            // Функцията, която ще се извика след приключване на обработката на файла
            $Script->callBack($params['callBack']);
            
            // Други необходими променливи
            $Script->params = serialize($params);
            $Script->fName = $fRec->name;
            $Script->fh = $fRec->fileHnd;
            
            // Ако е подаден параметър за стартиране синхронно
            // Когато се геририра от офис документи PDF, и от полученич файл
            // се генерира JPG тогава трябва да се стартира синхронно
            // В другите случаи трябва да е асинхронно за да не чака потребителя
            if ($Script->run($params['asynch']) === FALSE) {
                static::afterGetBarcodes($Script);
            }
        }
    }
    
    
    /**
     * Получава управеленито след вземането баркодовете
     * 
     * @param fconv_Script $script - Обект с нужните данни
     * 
     * @return boolean - Дали е изпълнен успешно
     */
    static function afterGetBarcodes($script)
    {
        if (static::saveBarcodes($script, $script->fh)) return TRUE;
    }
    
    
    //
    //Begin: Функции за работа с архиви
    //
    
    
    /**
     * Връща съдържанието за записа в архива на текущата директория
     * 
     * @param fileman_Files $frec - Запис на архива
     * @param string $path - Директорията директорията във файла
     * 
     * @return string $dirsAndFilesStr - Стринг с всички директории и файлове в текущата директория
     */
    static function getArchiveContent($fRec, $path = NULL) 
    {
        // Опитваме се да вземем инстанция на архива
        try {
            
            // Инстанция на архива
            $zip = self::getArchiveInst($fRec);
        } catch (fileman_Exception $e) {
            
            // Връщаме грешката
            return $e->getMessage();
        }
        
        // Резултата, който ще върнем
        $dirsAndFilesStr = '';
        
        // Ако е зададен пътя
        if ($path) {
            
            // Създаваме линк от пътя, който да сочи към предишната директория
            $link = ht::createLink($path, self::getBackFolderLinkInArchive());
            
            // Иконата на файла
            $sbfIcon = sbf('/img/16/back16.png',"");

            // Добавяме към стринга линк с икона
            $dirsAndFilesStr = "<span class='linkWithIcon' style='background-image:url($sbfIcon);'>{$link}</span>";
        }
        
        // Броя на всички документи в архива
        $numFiles = $zip->numFiles;
        
        $zipContentArr = array();
        
        // Обхождаме всички документи в архива
        for ($i=0; $i < $numFiles; $i++) {
            
            // Създаваме масив с файлове в архива
            $zipContentArr[$i] = $zip->statIndex($i);
        }
        
        // Вземаме всики директории и файлове в текущада директория на архива
        $filesArr = self::getFilesInArchive($zipContentArr, $path);
        
        // Размерите на файловете
        $fileSizesArr = self::getFileSizesInArchive($zipContentArr);
        
        // Подговаме стринга с папките
        $dirsStr = self::prepareDirsInArchive((array)$filesArr['dirs'], $path);
        
        // Подготвяме стринга с файловете
        $filesStr = self::prepareFilesInArchive((array)$filesArr['files'], $fRec->fileHnd, $fileSizesArr);
        
        // Ако има папки
        if ($dirsStr) {
            
            // Ако се намираме в поддиреткрия, добавяме интервал преди папките
            if ($path) $dirsAndFilesStr .=  "\n";
            
            // Добавяме папките
            $dirsAndFilesStr .= $dirsStr;    
        }
        
        // Ако има файлове, добавяме ги към стринга
        if ($filesStr) {
            ($dirsAndFilesStr) ? ($dirsAndFilesStr .= "\n" . $filesStr) : ($dirsAndFilesStr .= $filesStr);
        }

        // Затваряме връзката
        $zip->close();
        
        // Връщаме стринга с файловете и документите
        return $dirsAndFilesStr;
    }
    
    
    /**
     * Екшън за абсорбиране на файлове от архива
     */
    function act_AbsorbFileInArchive()
    {
        // Очакваме да има права за виждане
        $this->requireRightFor('view');
        
        // Манипулатора на файла
        $fileHnd = Request::get('id');
        
        // Вземаме записа за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        // Очакваме да има права за разглеждане на записа
        $this->requireRightFor('view', $fRec);
        
        // Индекса на файла
        $index = Request::get('index', 'int');
        
        // Опитваме се да качим файла
        try {
            
            // Опитваме се да вземем манипулатора на файла
            $fileHnd = static::uploadFileFromArchive($fRec, $index);
        } catch (fileman_Exception $e) {
            
            return $e->getMessage();
        }
        
        // Редиреткваме към single'а на качения файл
        return new Redirect(array('fileman_Files', 'single', $fileHnd, '#' => 'fileDetail'));    
    }

    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако ще разглежда файла трябва да има права до сингъла му
        if ($rec && ($action == 'view')) {
            if (!fileman_Files::haveRightFor('single', $rec, $userId)) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Качва подадение файл от архива
     * 
     * @param fileman_Files $fRec - Записа за файла
     * @param integer $index - Индекса на файла, който ще се качва
     */
    static function uploadFileFromArchive($fRec, $index)
    {
        // Инстанция на архива
        $zip = self::getArchiveInst($fRec);
        
        $conf = core_Packs::getConfig('fileman');
        
        $stat = $zip->statIndex($index);
        
        // Очакваме размера да е в допустимите граници
        expect($stat['size'] < $conf->FILEINFO_MAX_ARCHIVE_LEN, tr('Размера след разархивиране е над допустимия'));
        
        // Вземаме съдържанието на файла
        $fileContent = $zip->getFromIndex($index);
        
        // Пътя до файла в архива
        $path = $zip->getNameIndex($index);
        
        // Името на файла
        $name = basename($path);
        
        // Затваряме връзката
        $zip->close();
        
        // Очакваме да има съдържание
        expect($fileContent, 'Файлът няма съдържание');
        
        // Добавяме файла в кофата
        $fh = fileman::absorbStr($fileContent, 'archive', $name);
        
        // Очакваме да няма грешка при добавянето
        expect($fh, 'Възникна грешка при обработката на файла');

        return $fh;
    }
    
    
    /**
     * Връща всики файлове и директории в текущия път
     * 
     * @param array $zipContentArr - Масив с всички файлове и директории в архива
     * @param string $path - Директорията в която търсим
     * 
     * @return array $dirAndFiles - Масив с всички директории и файлове в архива
     * 				 $dirAndFiles['dirs'] - Всички директории в текущата директория на архива
     * 				 $dirAndFiles['files'] - Всички файлове в текущата директория на архива
     */
    static function getFilesInArchive($zipContentArr, $path=NULL) 
    {
        // Масив с всички файлове и директории
        $dirAndFiles = array();
        
        // Масив с всички директории и поддиректории
        $filesArr = array();
        
        // Дълбочината на директорията
        $depth = 0;
        
        // Обхождаме масива с всички директории и файлове в архива
        foreach ((array)$zipContentArr as $zipContent) {
            
            // Създаваме масив с всички директории и поддиректории
            $filesArr[$zipContent['index']] = (explode('/', $zipContent['name']));
        }
        
        // Ако е зададен пътя, определяме дълбочината
        if ($path) {

            // Намираме дълбочината на директорията
            $pathArr = explode('/', $path);  
            $depth = count($pathArr);  
        }
        
        // Обхождаме всики директории и файлове
        foreach ($filesArr as $index=>$file) {
            
            // В зависимост от дълбочината обхождаме файловете
            for($i=0; $i<$depth; $i++) {
                
                // Дали да прескочи
                $continue = FALSE;

                // Ако пътя до файла е различен от директорията
                if ($file[$i] != $pathArr[$i]) {
                    
                    // Задаваме да се прескочи
                    $continue = TRUE;
                    
                    // Прескачаме вътрешния цикъл
                    break;
                }
            }
            
            // Ако не сме в зададената директория прескачаме
            if (($continue) || !$file[$depth]) continue;
            
            // Ако е директория
            if (isset($file[$depth+1])) {
                
                // Добавяме името на файла и индекса
                $dirAndFiles['dirs'][$file[$depth]] = $index;
            } else {
                
                // Ако не е директория, трябва да е файл
                $dirAndFiles['files'][$file[$depth]] = $index;
            }
        }
        
        return $dirAndFiles;
    }
    
    
    /**
     * Връща размера на файловете
     * 
     * @param array $zipContentArr - Масив с всички файлове и директории в архива
     * 
     * @return array
     */
    static function getFileSizesInArchive($zipContentArr) 
    {
        
        // Масив с размерите на файловете
        $sizesArr = array();
        
        // Обхождаме масива с всички директории и файлове в архива
        foreach ((array)$zipContentArr as $zipContent) {
            
            // Добавяме размера след разархивиране
            $sizesArr[$zipContent['index']] = $zipContent['size'];
        }
        
        return $sizesArr;
    }
    
    
    /**
     * Подготвя стринга с папките
     * 
     * @param array $filesArr - Масив с всики директории и файлове в текущада директория на архива
     * @param string $path - Пътя до файла в архива
     */
    static function prepareDirsInArchive($filesArr, $path)
    {
        // Обхождаме всики директории
        foreach ($filesArr as $file => $index) {
            
            // Иконата за папките
            $icon = "img/16/folder.png";
            
            // Генерираме новия път
            $newPath = ($path) ? $path . "/". $file : $path . $file;
            
            // Вземаме текущото URL
            $url = getCurrentUrl();
            
            // Променяме пътя
            $url['path'] = $newPath;
            
            $url['#'] = 'fileDetail';
            
            // Създаваме линк
            $link = ht::createLink($file, $url);
            
            // SBF иконата
            $sbfIcon = sbf($icon,"");
            
            // Създаваме стринга
            $foldersStr = "<span class='linkWithIcon' style='background-image:url($sbfIcon);'>{$link}</span>";
            $text .= ($text) ? "\n" . $foldersStr : $foldersStr;
        }
        
        return $text;
    }
    
    
    /**
     * Подготвя стринга с файловете
     * 
     * @param array $filesArr - Масив с всики директории и файлове в текущада директория на архива
     * @param string $fileHnd - Манипулатора на архива
     * @param array $sizeContentArr - Масив с размерите на файла
     */
    static function prepareFilesInArchive($filesArr, $fileHnd, $sizeContentArr=NULL)
    {
        $conf = core_Packs::getConfig('fileman');
        
        // Обхождаме вски файлове в текущата директория
        foreach ($filesArr as $file => $index) {
            
            //Разширението на файла
            $ext = fileman_Files::getExt($file);
            
            //Иконата на файла, в зависимост от разширението на файла
            $icon = "fileman/icons/16/{$ext}.png";
            
            //Ако не можем да намерим икона за съответното разширение, използваме иконата по подразбиране
            if (!is_file(getFullPath($icon))) {
                $icon = "fileman/icons/16/default.png";
            }
            
            // Иконата в SBF директорията
            $sbfIcon = sbf($icon,"");
            
            // Ако размера след разархивиране е под допустимия
            if ($sizeContentArr[$index] < $conf->FILEINFO_MAX_ARCHIVE_LEN) {
                $url = array(get_called_class(), 'absorbFileInArchive', $fileHnd, 'index' => $index);
            } else {
                $url = FALSE;
            }
            
            // Създаваме линк, който сочи към екшън за абсорбиране на файла
            $link = ht::createLink($file, $url, NULL, array('target'=>'_blank'));
            
            // Създаваме стринга
            $fileStr = "<span class='linkWithIcon' style='background-image:url($sbfIcon);'>{$link}</span>";
            $text .= ($text) ? "\n" . $fileStr : $fileStr;
        }
        
        return $text;
    }

    
    /**
     * Връща линка към предишната директория
     */
    static function getBackFolderLinkInArchive()
    {
        // Вземаме текущото URL
        $url = getCurrentUrl();
        
        $url['#'] = 'fileDetail';
        
        // Ако няма път, връщаме
        if (!$url['path']) return;
        
        // Ако има поддиретктория
        if(($slashPos = mb_strrpos($url['path'], '/')) !== FALSE) {
            
            // Преобразуваме пътя на нея
            $url['path'] = mb_substr($url['path'], 0, $slashPos);
        } else {
            
            // Ако няма поддиреткроя, тогава връщаме празен стринг
            $url['path'] = '';
        }
        
        return $url;        
    }
    
    
	/**
     * Връща инстанцията на архива
     */
    static function getArchiveInst($fRec)
    {
        // Ако не сме създали инстанция преди
        if (!self::$archiveInst[$fRec->fileHnd]) {
            
            // Проверяваме големината на архива
            self::checkArchiveLen($fRec->dataId);
            
            // Пътя до архива
            $filePath = fileman_Files::fetchByFh($fRec->fileHnd, 'path');
            
            try {
                // Създаваме инстанция
                $zip = new ZipArchive();
            } catch (Exception $e) {
                $zip = FALSE;
            }
            
            if (!$zip) {
                self::logWarning('Не е инсталиран разширението за ZipArchive');
                
                throw new fileman_Exception('Възникна грешка при отварянето на файла.');
            }
            
            // Отваряме архива да четем от него
            $open = $zip->open($filePath, ZIPARCHIVE::CHECKCONS);
            
            if ($open !== TRUE) {
                
                throw new fileman_Exception('Възникна грешка при отварянето на файла.');
            }
            
            self::$archiveInst[$fRec->fileHnd] = $zip;
        } else {
            
            // Вземаме инстанцията от предишното генерa
            $zip = self::$archiveInst[$fRec->fileHnd];
        }
    
        return $zip;
    }
	
	
	/**
     * Проверява дали архива е в допустимите размери за обработка
     */
    static function checkArchiveLen($dataId)
    {
        // Конфигурационните константи
        $conf = core_Packs::getConfig('fileman');
        
        // Записите за файла
        $dataRec = fileman_Data::fetch($dataId);
        
        // Дължината на файла
        $fLen = $dataRec->fileLen;
        
        // Ако дължината на файла е по голяма от максимално допустимата
        if ($fLen >= $conf->FILEINFO_MAX_ARCHIVE_LEN) {
            
            // Инстанция на класа
            $fileSizeInst = cls::get('fileman_FileSize');
            
            // Създаваме съобщение за грешка
            $text = tr("Архива е много голям|*: ") . fileman_Data::getVerbal($dataRec, 'fileLen');
            $text .= "\n" . tr("Допустимият размер е|*: ") . $fileSizeInst->toVerbal($conf->FILEINFO_MIN_FILE_LEN_BARCODE);
            
            // Очакваме да не сме влезли тука
            throw new fileman_Exception($text);
        }
    }
    
    
    //
    //END: Функции за работа с архиви
    //
    
    
    /**
     * Връща съдържанието на HTML таба
     * 
     * @param URL $htmlUrl - Линк към HTML файла
     * 
     * @return core_ET - Текста, за създаване на таб
     */
    static function getHtmlTabTpl($htmlUrl)
    {
        // Ако няма URL, връщаме FALSE
        if (!$htmlUrl) return FALSE;
        
        // Ако JS не е включен
        if (Mode::is('javascript', 'no')) {
            
            // HTML частта, ако не е включен JS
            $htmlPart = "<div class='webdrvTabBody'>
            				<div class='webdrvFieldset'><div class='legend'>" . tr("HTML изглед") . "</div>
                				<iframe src='{$htmlUrl}' SECURITY='restricted' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'></iframe>
                			</div>
            			</div>";    
        } else {
            
            // HTML частта, ако е включен JS
            $htmlTpl = new ET("
            					<div class='webdrvTabBody'>
                    				<div class='webdrvFieldset'><div class='legend'>" . tr("HTML изглед") . "</div>
                    					<iframe id=[#SANITIZEID#] SECURITY='restricted' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'></iframe>
                    					[#SANITIZEJS#]
                					</div>
                				</div>
            				");
            
            // HTML частта със заместениете данни
            $htmlPart = hclean_JSSanitizer::sanitizeHtml($htmlTpl, $htmlUrl);    
        }
        
        return $htmlPart;
    }
    
    
    /**
     * Парсира стринга, превежда стринговете, които се явяват таговете на реда
     * 
     * @param string $content - Стринга, който ще обработваме
     * 
     * @return string
     */
    static function parseInfo($content)
    {
        $newContent = '';
        
        // Опитваме се да оправим енкодинга
        $content = i18n_Charset::convertToUtf8($content);
        
        // Разделяме съдържанието в масив
        $contentArr = explode("\n", $content);
        
        // Обхождаме масива
        foreach ($contentArr as $contentLine) {
            
            // Ако няма съдържание прескачаме
            if (!trim($contentLine)) continue;
            
            // Ако има двуеточние с интервал
            if (strripos($contentLine, ': ') !== FALSE) {
                
                // Разделяме реда на тагове и стойност
                list($tag, $value) = explode(': ', $contentLine, 2);
            } else {
                
                // Стойността е цялото поле
                $value = $contentLine;
            }
            
            // Ако дължината е повече от 70
            if (mb_strlen($value) > 70) continue;
            
            // Създаваме ред от съдържанието
            $nLink = '';
            
            // Ако има таг
            if ($tag) {
                
                // Ако все още има двуеточние
                if (strripos($tag, ':') !== FALSE) {
                    
                    // Разделяма тага на части
                    $partTagArr = explode(':', $tag);  
                    
                    // Обхождаме всички части
                    foreach ($partTagArr as $partTag) {
                        
                        // Добавяме към реда и превеждаме
                        $nLink .= ($nLink) ? ":" . tr($partTag) : tr($partTag);
                    }
                    
                } else {
                    
                    // Ако няма двуеточни, само превеждаме и добавяме към реда
                    $nLink .= tr($tag);
                }
                
                // Към реда добавяме и стойността, без да я превеждаме
                $nLink .= ": " . $value;
            } else {
                
                // Ако няма таг
                
                // Добавяме стойността
                $nLink .= $value;
            }
            
            // Получения ред го добавяме към новото съдържание
            $newContent .= ($newContent) ? "\n" . $nLink : $nLink;
        }
        
        // Връщаме новото съдържание
        return $newContent;
    }
    
    
    /**
     * Връща масив с височината и ширината за прегледа на изображението
     * 
     * @return array
     */
    static function getPreviewWidthAndHeight()
    {
        //Вземема конфигурационните константи
        $conf = core_Packs::getConfig('fileman');
        
        // В зависимост от широчината на екрана вземаме размерите на thumbnail изображението
        if (mode::is('screenMode', 'narrow')) {
            $thumbWidth = $conf->FILEMAN_PREVIEW_WIDTH_NARROW;
            $thumbHeight = $conf->FILEMAN_PREVIEW_HEIGHT_NARROW;
        } else {
            $thumbWidth = $conf->FILEMAN_PREVIEW_WIDTH;
            $thumbHeight = $conf->FILEMAN_PREVIEW_HEIGHT;
        }
        
        // Добавяме в масива
        $arr = array();
        $arr['width'] = $thumbWidth;
        $arr['height'] = $thumbHeight;
        
        return $arr;
    }
    
    
    /**
     * Помощна функция за вземане на типа на подададения стринг
     * 
     * @param string $str
     * @param string $type
     * 
     * @return string
     */
    protected static function getFileTypeFromStr($str, $type = 'auto')
    {
        if ($type == 'auto') {
            $len = strlen($str);
            if (($len == FILEMAN_HANDLER_LEN) && (strpos($str, '/') === FALSE)) {
                $fileType = 'handler';
                $fRec = fileman_Files::fetchByFh($str);
        
                expect($fRec);
            } elseif ($len > 512) {
                $fileType = 'string';
            } else {
                $fileType = 'path';
            }
        } else {
            $fileType = $type;
        }
        
        expect(in_array($fileType, array('handler', 'string', 'path')));
        
        return $fileType;
    }
}
