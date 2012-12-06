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
        $infoUrl = toUrl(array('fileman_webdrv_Office', 'info', $fRec->fileHnd), TRUE);
        // Таб за информация
        $tabsArr['info'] = (object) 
			array(
				'title' => 'Информация',
				'html'  => "<div class='webdrvTabBody'><fieldset  class='webdrvFieldset'><legend>Мета информация</legend>
					<iframe src='{$infoUrl}' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'> </iframe></fieldset></div>",
				'order' => 9,
			);
        
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
     * Екшън за показване текстовата част на файла
     */
    function act_Text()
    {
        // Манупулатора на файла
        $fileHnd = Request::get('id'); 
        
        // Вземаме текста
        $content = fileman_Indexes::getInfoContentByFh($fileHnd, 'text');
        
        // Вземаме текста извлечен от OCR
        $ocrContent = fileman_Indexes::getInfoContentByFh($fileHnd, 'textOcr');

        // Ако има OCR съдържание
        if ($ocrContent !== FALSE) {
            
            // Тогава съдържанието е равно на него
            $content = $ocrContent;
        } else {
            
            // Вземаме записа за съответния файл
            $rec = fileman_Files::fetchByFh($fileHnd);
        
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
            return $content->errorProc;       
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
        // Манипулатора на файла
        $fileHnd = Request::get('id');
        
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
            return $jpgArr->errorProc;       
        }

        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_Empty');
        
        if (($jpgArr) && (count($jpgArr))) {
            
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
            
            // Атрибути на thumbnail изображението
            $attr = array('baseName' => 'Preview', 'isAbsolute' => FALSE, 'qt' => '', 'style' => 'margin: 5px auto; display: block;');
            
            // Background' а на preview' то
            $bgImg = sbf('fileman/img/Preview_background.jpg');
            
            // Създаваме шаблон за preview на изображението
            $preview = new ET("<div style='background-image:url(" . $bgImg . "); padding: 5px 0; min-height: 590px;'><div style='margin: 0 auto; display:table;'>[#THUMB_IMAGE#]</div></div>");
            
            foreach ($jpgArr as $jpgFh) {
                
                //Размера на thumbnail изображението
                $size = array($thumbWidth, $thumbHeight);
                
                //Създаваме тумбнаил с параметрите
                $thumbnailImg = thumbnail_Thumbnail::getImg($jpgFh, $size, $attr);    
                
                if ($thumbnailImg) {
                
                    // Добавяме към preview' то генерираното изображение
                    $preview->append($thumbnailImg, 'THUMB_IMAGE');
                
                }
            }
            
            return $preview;
        }
    }
    
    
    /**
     * Екшън за визуализране на баркодовете
     */
    function act_Barcodes()
    {
        
        // Манупулатора на файла
        $fileHnd = Request::get('id'); 
        
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
            return $barcodes->errorProc;       
        }
        
        // Ако е масив
        if (is_array($barcodes)) {
            
            // Обхождаме масива
            foreach ($barcodes as $barcode) {
                
                // Обхождаме вътрешния масив
                foreach ($barcode as $barcodeObj) {
                    
                    // TODO
                    
                    // Добавяме стринг
                    $barcodeStr .= "Тип: {$barcodeObj->type}\nБаркод: {$barcodeObj->code}\n\n";
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
        // Манупулатора на файла
        $fileHnd = Request::get('id'); 
        
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
            return $content->errorProc;       
        }

        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_PreText');
        
        // Записите за файла
        $fRec = fileman_Files::fetchByFh($fileHnd);
    
        // Ако има активен линк за сваляне
        if (($dRec = fileman_Download::fetch("#fileId = {$fRec->id}")) && (dt::mysql2timestamp($dRec->expireOn)>time())) {
            
            // Линк за сваляне
            $link = fileman_Download::getSbfDownloadUrl($dRec, TRUE);
            
            // До кога е активен линка
            $expireOn = dt::mysql2Verbal($dRec->expireOn, 'smartTime');
            
            // Линка, който ще се показва
            $linkText = tr("|Линк|*: <span id='selectable' onmouseUp='onmouseUpSelect();'>{$link}</span> <small>(|Изтича|*: {$expireOn})</small>");
            
            // Добавяме към съдържанието на инфо
            $contentInfo = $linkText . "\n";
        }
        
        try {
		    
		    // Опитваме се да вземем, документите, в които се използва файла
		    $documentWithFile = fileman_Files::getDocumentsWithFile($fRec);    
		} catch (Exception $e) {}
		
		// Ако сме намерили някой файлове, където се използва
        if ($documentWithFile) {
            
            // Добавяме към съдържанието на инфото
            $contentInfo .= $documentWithFile . "\n";    
        }
        
        // Типа на файла
        $type = fileman_Mimes::getMimeByExt(fileman_Files::getExt($fRec->name));
        
        // Вербалния размер на файла
        $size = fileman_Data::getFileSize($fRec->dataId);
        
        // Ако има размер
        if ($size) {
            
            // Размера за показване
            $sizeText = tr("|Размер|*: {$size}");  
            
            // Добавяме към съдържанието на инфо
            $contentInfo .= $sizeText . "\n";
        }
        
        // Информация за създаването
        $createdOn = fileman_Files::getVerbal($fRec, 'createdOn');
        $createdBy = fileman_Files::getVerbal($fRec, 'createdBy');
        
        // Показване на създаването
        $createdText = tr("|Добавен на|* : {$createdOn} |от|* {$createdBy}");
        
        // Добавяме към съдържанието на инфо
        $contentInfo .= $createdText . "\n";
        
        // Добавяме в текста
        $content = $contentInfo . core_Type::escape($content);
        
        // Връщаме съдържанието
        return $content;
    }
	
	
	/**
     * Екшън за показване HTML частта на файла
     */
    function act_Html()
    {
        // Манупулатора на файла
        $fileHnd = Request::get('id'); 
        
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
            return $content->errorProc;       
        }

        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_Html');
        
        // Връщаме съдържанието
        return $content;
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
        } catch (core_exception_Expect $e) {
            
            // Съобщението въведено в expect
            $debug = $e->getDebug();
            
            // Добавяме съобщението за грешка
            $barcodesArr = new stdClass();
            $barcodesArr->errorProc = $debug[1];
            
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
            
            // Създаваме масива
            $fileHndArr[$fileHnd] = $fileHnd;  
        } else {
            $fileHndArr = $fileHnd;
        }
        
        // Обхождаме масива с манупулаторите
        foreach ($fileHndArr as $fh) {
            
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
        if (fileman_Indexes::haveErrors($script->outFilePath, $params['type'], $params)) {
            
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
            $Script->run($params['asynch']);
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
        // Конфигурационните константи
        $conf = core_Packs::getConfig('fileman');
        
        // Записите за файла
        $dataRec = fileman_Data::fetch($fRec->dataId);
        
        // Дължината на файла
        $fLen = $dataRec->fileLen;
        
        // Ако дължината на файла е по голяма от максимално допустимата
        if ($fLen >= $conf->FILEINFO_MAX_ARCHIVE_LEN) {
            
            // Инстанция на класа
            $fileSizeInst = cls::get('fileman_FileSize');
            
            // Създаваме съобщение за грешка
            $text = "Архива е много голям: " . fileman_Data::getVerbal($dataRec, 'fileLen');
            $text .= "\nДопустимият размер е: " . $fileSizeInst->toVerbal($conf->FILEINFO_MIN_FILE_LEN_BARCODE);
            
            return $text;
        }
        
        // Създаваме инстанция
        $zip = new ZipArchive();
        
        // Очакваме да може да се създане инстация
        expect($zip);
        
        // Резултата, който ще върнем
        $dirsAndFilesStr = '';
        
        // Ако е зададен пътя
        if ($path) {
            
            // Създаваме линк от пътя, който да сочи към предишната директория
            $link = ht::createLink($path, static::getBackFolderLinkInArchive());
            
            // Иконата на файла
            $sbfIcon = sbf('/img/16/back16.png',"");

            // Добавяме към стринга линк с икона
            $dirsAndFilesStr = "<span class='linkWithIcon' style='background-image:url($sbfIcon);'>{$link}</span>";
        }

        // Пътя до архива
        $filePath = fileman_Files::fetchByFh($fRec->fileHnd, 'path');
        
        // Отваряме архива да четем от него
        $open = $zip->open($filePath, ZIPARCHIVE::CHECKCONS);

        // Очакваме да няма грешки при отварянето
        expect(($open === TRUE), 'Възникна грешка при отварянето на файла.');
        
        // Броя на всички документи в архива
        $numFiles = $zip->numFiles;
        
        // Обхождаме всички документи в архива
        for ($i=0; $i < $numFiles; $i++) {
            
            // Създаваме масив с файлове в архива
            $zipContentArr[$i] = $zip->statIndex($i);
        }
        
        // Вземаме всики директории и файлове в текущада директория на архива
        $filesArr = static::getFilesInArchive($zipContentArr, $path);
        
        // Подговаме стринга с папките
        $dirsStr = static::prepareDirsInArchive((array)$filesArr['dirs'], $path);
        
        // Подготвяме стринга с файловете
        $filesStr = static::prepareFilesInArchive((array)$filesArr['files'], $fRec->fileHnd);
        
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
        // Манипулатора на архива
        $fh = $this->db->escape(Request::get('id'));
        
        // Индекса на файла
        $index = Request::get('index', 'int');
        
        // Записите за съответния архив
        $rec = fileman_Files::fetchByFh($fh);
        
        // Изискваме да име права за single
        fileman_Files::requireRightFor('single', $rec);
        
        // Инстанция на класа
        $zip = new ZipArchive();
        
        // Очакваме да няма грешка
        expect($zip);
        
        //Пътя до файла
        $filePath = fileman_Files::fetchByFh($fh, 'path');
        
        // Отваряме файла за четене
        $open = $zip->open($filePath, ZIPARCHIVE::CHECKCONS);

        // Очакваме да няма проблеми при отварянето
        expect(($open === TRUE), 'Възникна грешка при отварянето на файла.');
        
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
        
        // Инстанция на fileman
        $filesInst = cls::get('fileman_Files');
    
        // Добавяме файла в кофата
        $fh = $filesInst->addNewFileFromString($fileContent, 'archive', $name);
        
        // Очакваме да няма грешка при добавянето
        expect($fh, 'Възникна грешка при обработката на файла');
        
        // Редиреткваме към single'а на качения файл
        return new Redirect(array('fileman_Files', 'single', $fh, '#' => 'fileDetail'));    
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
        foreach ($zipContentArr as $zipContent) {
            
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
     */
    static function prepareFilesInArchive($filesArr, $fileHnd)
    {
        // Обхождаме вски файлове в текущата директория
        foreach ($filesArr as $file => $index) {
            
            //Разширението на файла
            $ext = fileman_Files::getExt($file);
            
            //Иконата на файла, в зависимост от разширението на файла
            $icon = "fileman/icons/{$ext}.png";
            
            //Ако не можем да намерим икона за съответното разширение, използваме иконата по подразбиране
            if (!is_file(getFullPath($icon))) {
                $icon = "fileman/icons/default.png";
            }
            
            // Иконата в SBF директорията
            $sbfIcon = sbf($icon,"");
            
            // Създаваме линк, който сочи към екшън за абсорбиране на файла
            $link = ht::createLink($file, array('fileman_webdrv_Archive', 'absorbFileInArchive', $fileHnd, 'index' => $index), NULL, array('target'=>'_blank'));
            
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
    
    //
    //END: Функции за работа с архиви
    //
    
    
    /**
     * Връща съдържанието на HTML таба
     * 
     * @param URL $htmlUrl - Линк към HTML файла
     * 
     * @return $htmlPart - Текста, за създаване на таб
     */
    static function getHtmlTabTpl($htmlUrl)
    {
        // Ако JS не е включен
        if (Mode::is('javascript', 'no')) {
            
            // HTML частта, ако не е включен JS
            $htmlPart = "<div class='webdrvTabBody'>
            				<fieldset class='webdrvFieldset'><legend>HTML изглед</legend>
                				<iframe src='{$htmlUrl}' SECURITY='restricted' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'></iframe>
                			</fieldset>
            			</div>";    
        } else {
            
            // HTML частта, ако е включен JS
            $htmlTpl = new ET("
            					<div class='webdrvTabBody'>
                    				<fieldset class='webdrvFieldset'><legend>HTML изглед</legend>
                    					<iframe id=[#SANITIZEID#] SECURITY='restricted' frameBorder='0' ALLOWTRANSPARENCY='true' class='webdrvIframe'></iframe>
                    					[#SANITIZEJS#]
                					</fieldset>
                				</div>
            				");
            
            // HTML частта със заместениете данни
            $htmlPart = hclean_JSSanitizer::sanitizeHtml($htmlTpl, $htmlUrl);    
        }
        
        return $htmlPart;
    }
}
