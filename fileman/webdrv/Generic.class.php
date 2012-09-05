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
        $content = static::getInfoContentByFh($fileHnd, 'text');
        
        // Ако нама такъв запис
        if ($content === FALSE) {
            
            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');
            
            return ;
        }

        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_Empty'); // Тук може и да се използва page_PreText за подреден текст
        
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
        $jpgArr = static::getInfoContentByFh($fileHnd, 'jpg');

        // Ако няма такъв запис
        if ($jpgArr === FALSE) {
            
            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');
            
            return ;
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
        $barcodes = static::getInfoContentByFh($fileHnd, 'barcodes');

        // Ако нама такъв запис
        if ($barcodes === FALSE) {
            
            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');
            
            return ;
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
        Mode::set('wrapper', 'page_PreText'); // Тук може и да се използва page_PreText за подреден текст
        
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
        $content = static::getInfoContentByFh($fileHnd, 'metadata');
        
        // Ако нама такъв запис
        if ($content === FALSE) {
            
            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');
            
            return ;
        }

        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_PreText');
        
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
        $content = static::getInfoContentByFh($fileHnd, 'html');
        
        // Ако нама такъв запис
        if ($content === FALSE) {
            
            // Сменяме мода на page_Waiting
            Mode::set('wrapper', 'page_Waiting');
            
            return ;
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
     * Проверява дали файла е заключен или записан в БД
     * 
     * @param object $fRec - Данните за файла
     * @param array $params - Масив с допълнителни променливи
     * 
     * @return boolean - Връща TRUE ако файла е заключен или има запис в БД
     * 
     * @access protected
     */
    static function isProcessStarted($params)
    {
        // Проверяваме дали файла е заключен или има запис в БД
        if ((fileman_Indexes::fetch("#dataId = '{$params['dataId']}' AND #type = '{$params['type']}'")) 
            || (core_Locks::isLocked($params['lockId']))) return TRUE;
        
        return FALSE;
    }
    
    
    /**
     * Подготвяме content частта за по добър запис
     * 
     * @param string $text - Текста, който да променяме
     * 
     * @return string $text - Променения текст
     */
    static function prepareContent($text)
    {
        // Вземаме конфигурацията
        $conf = core_Packs::getConfig('fileman');
        
        // Променяме мемори лимита
        ini_set("memory_limit", $conf->FILEMAN_DRIVER_MAX_ALLOWED_MEMORY_CONTENT);

        // Сериализираме
        $text = serialize($text);
        
        // Компресираме
        $text = gzcompress($text);
        
        // Енкодваме
        $text = base64_encode($text);    
                
        return $text;
    }
    
    
    /**
     * Връща десериализараната информация за съответния файл и съответния тип
     * 
     * @param fileHandler $fileHnd - Манипулатор на файла
     * @param string $type - Типа на файла
     * 
     * @return mixed $content - Десериализирания стринг
     */
    static function getInfoContentByFh($fileHnd, $type)
    {
        // Определяме dataId от манупулатора
        $dataId = fileman_Files::fetchByFh($fileHnd, 'dataId');
        
        // Вземаме текстовата част за съответното $dataId
        $rec = fileman_Indexes::fetch("#dataId = '{$dataId}' AND #type = '{$type}'");

        // Ако няма такъв запис
        if (!$rec) return FALSE;

        // Декодваме
        $content = base64_decode($rec->content);
        
        // Декомпресираме
        $content = gzuncompress($content);
        
        // Десериализираме съдържанието
        $content = unserialize($content);        
        
        return $content;
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
        
        // Проверявама дали няма извлечена информация или не е заключен
        if (static::isProcessStarted($params)) return ;

        // Заключваме процеса за определно време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            // Вземаме баркодовете
            $barcodesArr = static::getBarcodes($fileHndArr, $params['dataId']);
        
            // Сериализираме масива и обновяваме данните за записа в fileman_Info
            $rec = new stdClass();
            $rec->dataId = $params['dataId'];
            $rec->type = 'barcodes';
            $rec->createdBy = $params['createdBy'];
            $rec->content = static::prepareContent($barcodesArr);
        
            $savedId = fileman_Indexes::save($rec);   

            // Отключваме процеса
            core_Locks::release($params['lockId']);
        } else {
            
            // Записваме грешката
            static::createErrorLog($params['dataId'], $params['type']);
        }
        
        return $savedId;
    }
    
    
    /**
     * Намира баркодовете във подадените файлове
     * 
     * @param mixed $fh - Манипулатор на файла или масив от манипулатори на файла
     * @param integer $fileInfoId - id' то на записа от fileman_Info, в който ще запишем получената информация
     * 
     * @access protected
     */
    static function getBarcodes($fileHnd, $dataId)
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
     * Записва в лога ако възникне греша при асинхронното обработване на даден файл
     * 
     * @param fileman_Data $dataId - id' то на данните на файла
     * @param string $type - Типа на файла
     */
    static function createErrorLog($dataId, $type)
    {
        core_Logs::log(tr("|Възникна грешка при обработката на файла с данни|* {$dataId} |в тип|* {$type}"));
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
        if (static::isProcessStarted($params)) return ;

        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            // Извличаме мета информцията с Apache Tika
            apachetika_Detect::extract($fRec->fileHnd, $params);
        } else {
            
            // Записваме грешката
            static::createErrorLog($params['dataId'], $params['type']);
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
        
        // Вземаме съдъжанието на файла, който е генериран след обработката към .txt формат
        $text = file_get_contents($script->outFilePath);
        
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);

        // Записваме получения текс в модела
        $rec = new stdClass();
        $rec->dataId = $params['dataId'];
        $rec->type = $params['type'];
        $rec->content = static::prepareContent($text);
        $rec->createdBy = $params['createdBy'];
        $saveId = fileman_Indexes::save($rec);
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($saveId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        } else {

            // 
            static::createErrorLog($params['dataId'], $params['type']);
        }
    }   
}