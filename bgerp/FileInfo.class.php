<?php

// TODO Да се преместят в Setup.class.php
/**
 * Лимита при извличане на данни от базата
 */
defIfNot('FILEINFO_MAX_FETCHING_LIMIT', 1000);


/**
 * Лимита на стартиранете на оперции в едно стартиране
 */
defIfNot('FILEINFO_MAX_COUNT_PROCESS', 10);


/**
 * Минималната дължина на файла, до която ще се търси баркод
 * 15kB
 */
defIfNot(FILEINFO_MIN_FILE_LEN_BARCODE, 15360);


/**
 * Максималната дължина на файла, до която ще се търси баркод
 * 15kB
 */
defIfNot(FILEINFO_MAX_FILE_LEN_BARCODE, 1048576);
//TODO end

/**
 * Информация за всички файлове във fileman_Files
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_FileInfo extends core_Manager
{
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Информация за файловете";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'bgerp_Wrapper';
    
    
    /**
     * Брояч за стартираните операции за всяка сесия
     */
    static $counter=0;
    
    
    /**
     * 
     */
    function description()
    {
        $this->FLD('fileId', 'key(mvc=fileman_Files)', 'caption=Файлове');
        $this->FLD("dataId", "key(mvc=fileman_Data)", 'caption=Данни,notNull');
        $this->FLD('createdOn', 'datetime(format=smartTime)', 'caption=Създаване->На');
        $this->FLD('barcodes', 'blob', 'caption=Баркодове');
        $this->FLD('content', 'text', 'caption=Съдържание');
        $this->FLD('images', 'blob', 'caption=Изображения');
        $this->FLD('metaInfo', 'blob', 'caption=Мета информация');
        
        $this->setDbUnique('dataId');
    }
    
    
    /**
     * Стартираме обработването на файловете
     */
    static function startProcess()
    {
        // Определяме времето на създаване на последния обработен документ
        $lastFetchFileTime = static::getLastFetchTime();
        
        // Вземаме FILEINFO_MAX_FETCHING_LIMIT на броя запис от fileman_Files,
        // които са по нови от последния запис в модела
        $query = fileman_Files::getQuery();
        $query->where("#createdOn >= '{$lastFetchFileTime}'");
        $query->limit(FILEINFO_MAX_FETCHING_LIMIT);
        $qCount = $query->count();
        
        // Обикаляме всички открити записи докато не свършат или не сме надвиши броя за стартираните операции в сесията
        while (($rec = $query->fetch()) && (static::$counter < FILEINFO_MAX_COUNT_PROCESS)) {
            
            // Проверяваме дали намерения файл има данни
            if (!$rec->dataId) continue;
            
            // Променлива, която държи последния запис
            $lastRec = $rec;
            
            // Вземаме разширението на файла
            $ext = fileman_Files::getExt($rec->name);
            
            // Проверяваме дали разширението, е в допустимите, които ще се конвертират
            if (in_array($ext, array('pdf', 'rtf', 'odt'))) {
                
                // Данните, които ще запишем
                $nRec = new stdClass();
                $nRec->fileId = $rec->id;
                $nRec->dataId = $rec->dataId;
                $nRec->createdOn = $rec->createdOn;
                
                // Ако записа мине успешно
                if ($fileInfoId = bgerp_FileInfo::save($nRec, NULL, 'IGNORE')) {

                    // Увеличаваме брояча за броя на стартираните процеси
                    static::$counter++;
                    
                    // Стартираме обработката на файловете
                    static::startFileProcessing($rec->fileHnd, $fileInfoId, $ext);
                }    
            }
        }
        
        // Ако не можем да открием файл, който да обработим
        if ((!static::$counter) && ($lastRec->dataId) && ($qCount >= FILEINFO_MAX_FETCHING_LIMIT)) {
            
            // Записваме данните за последния файл, за да може следващия път да продължим от него
            $nRec = new stdClass();
            $nRec->fileId = $lastRec->id;
            $nRec->dataId = $lastRec->dataId;
            $nRec->createdOn = $lastRec->createdOn;
            bgerp_FileInfo::save($nRec, NULL, 'IGNORE');
        }
    }
    
    
    /**
     * Стартира обработката на файла
     * 
     * @param fileHnd $fh - Манипулатор на файла
     * @param integer $fileInfoId - id' то на записа от bgerp_FileInfo, в който ще запишем получената информация
     * @param string $ext - Разширението на файла
     * 
     * @access protected
     */
    static function startFileProcessing($fh, $fileInfoId, $ext)
    {
        // Ако разширението е от допустимите
        if (in_array($ext, array('pdf', 'rtf', 'odt'))) {
            
            // Опитваме се да определим съдържанието на файла
            static::getContent($fh, $fileInfoId, $ext);    
        }
    }
    
    
    /**
     * Функиция за определяне на начина за извличане на съдъжанието на файла
     * 
     * @param fileHnd $fh - Манипулатор на файла
     * @param integer $fileInfoId - id' то на записа от bgerp_FileInfo, в който ще запишем получената информация
     * @param string $ext - Разширението на файла
     * 
     * @access protected
     */
    static function getContent($fh, $fileInfoId, $ext)
    {
        // Ако разширението е pdf
        if (in_array($ext, array('pdf'))) {
            
            // Стартираме функцията за определяне на разширението на файла
            static::getContentFromPdf($fh, $fileInfoId, $ext);
        }
        
        // Ако разширението е pdf
        if (in_array($ext, array('rtf', 'odt'))) {
            
            // Стартираме функцията за определяне на разширението на файла
            static::getContentFromDoc($fh, $fileInfoId, $ext);
        }
    }
    
    
	/**
     * Определя на съдържанието на pdf документите
     * 
     * @param fileHnd $fh - Манипулатор на файла
     * @param integer $fileInfoId - id' то на записа от bgerp_FileInfo, в който ще запишем получената информация
     * @param string $ext - Разширението на файла
     * 
     * @access protected
     */
    static function getContentFromPdf($fh, $fileInfoId, $ext)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'bgerp_FileInfo::afterGetContentFrom',
            'ext' => $ext,
            'fileInfoId' => $fileInfoId,
        	'asynch' => FALSE,
        );
        
        // Стартираме конвертирането
        docoffice_Pdf::convertPdfToTxt($fh, $params);
    }
    
    
    /**
     * 
     */
    static function getContentFromDoc($fh, $fileInfoId, $ext)
    {
        // Конфигурационните константи
        $conf = core_Packs::getConfig('docoffice');
        
        // Класа, който ще конвертира
        $ConvClass = $conf->OFFICE_CONVERTER_CLASS;
        
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'bgerp_FileInfo::afterGetContentFrom',
            'ext' => $ext,
            'fileInfoId' => $fileInfoId,
        	'asynch' => FALSE,
        );
        
        // Стартираме конвертирането
        $ConvClass::convertDoc($fh, 'txt', $params);
    }

    
    /**
     * Функция, която получава управлението след записване на съдържанието на файла във временен файл
     * 
     * @param object $script - Обект със стойности
     * 
     * @return boolean TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterGetContentFrom($script)
    {
        // Вземаме съдъжанието на файла, който е генериран след обработката към .txt формат
        $text = file_get_contents($script->outFilePath);
        
        // Записваме получения текс в модела
        $rec = new stdClass();
        $rec->id = $script->fileInfoId;
        $rec->content = $text;
        bgerp_FileInfo::save($rec);

        // Ако разширението е едно от посочените
        if (in_array($script->ext, array('pdf'))) {
            
            // Стартираме конвертирането на файла
            static::convertFileToJpg($script->fh, $script->fileInfoId, $script->ext);
        }
        
        // Ако разширението е едно от посочените
        if (in_array($script->ext, array('rtf', 'doc'))) {
            
            // Отключваме офис пакета
            docoffice_Office::unlockOffice();
        }
        
        // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
        // и записа от таблицата fconv_Process
        return TRUE;
    }
    
    
    /**
     * Функиция за определяне на начина за конвертиране към JPG
     * 
     * @param object $script - Обект със стойности
     * 
     * @return boolean TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function convertFileToJpg($fh, $fileInfoId, $ext)
    {
        // Ако типа разширението е pdf
        if (in_array($ext, array('pdf'))) {
            
            // Стартираме конвертирането от PDF в JPG формат
            static::convertPdfToJpg($fh, $fileInfoId, $ext);
        }
    }
    
    
    /**
     * Конвертира PDF файл, в JPG формат
     * 
     * @param fileHnd $fh - Манипулатор на файла
     * @param integer $fileInfoId - id' то на записа от bgerp_FileInfo, в който ще запишем получената информация
     * @param string $ext - Разширението на файла
     * 
     * @access protected
     */
    static function convertPdfToJpg($fh, $fileInfoId, $ext)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'bgerp_FileInfo::afterGetContentFrom',
            'ext' => $ext,
            'fileInfoId' => $fileInfoId,
        	'asynch' => FALSE,
        );
        
        // Стартираме конвертирането
        docoffice_Pdf::convertPdfToJpg($fh, $params);
    }
    
    
    /**
     * 
     * 
     * @param object $script - Обект със стойности
     * 
     * @return boolean TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterConvertFileToJpg($script)
    {
        // Вземаме всички файлове във временната директория
        $files = scandir($script->tempDir);
        
        // Инстанция на класа
        $Fileman = cls::get('fileman_Files');
        
        // Брояч за файла
        $i=0;
        
        // Генерираме името на файла след конвертиране
        $fn = $script->fName . '-' .$i . '.jpg';

        // Докато има файл
        while (in_array($fn, $files)) {
            
            // Качваме файла в кофата и му вземаме манипулатора
            $fileHnd = $Fileman->addNewFile($script->tempDir . $fn, 'fileInfo'); 
            
            // Ако се качи успешно записваме манипулатора в масив
            if ($fileHnd) {
                $fileHndArr[$fileHnd] = $fileHnd;    
            }
            
            // Генерираме ново предположение за конвертирания файл, като добавяме единица
            $fn = $script->fName . '-' . ++$i . '.jpg';
        }
        
        // Ако има генерирани файлове, които са качени успешно
        if (count($fileHndArr)) {
            
            // Сериализираме масива и обновяваме данните за записа в bgerp_FileInfo
            $rec = new stdClass();
            $rec->id = $script->fileInfoId;
            $rec->images = serialize($fileHndArr);
            
            bgerp_FileInfo::save($rec);    
        }
        
        // Ако разширението на оригиналния файл е в допустимите
        if (in_array($script->ext, array('pdf'))) {
            
            // Сканираме получените файлове за наличие на баркод
            static::getBarcodes($fileHndArr, $script->fileInfoId, $script->ext);
        }
        
        // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
        // и записа от таблицата fconv_Process
        return TRUE;
    }
    
    
    /**
     * Намира баркодовете във подадените файлове
     * 
     * @param mixed $fh - Манипулатор на файла или масив от манипулатори на файла
     * @param integer $fileInfoId - id' то на записа от bgerp_FileInfo, в който ще запишем получената информация
     * @param string $originFileExt - Разширението на оригиналния файл
     * 
     * @access protected
     */
    static function getBarcodes($fileHnd, $fileInfoId, $originFileExt)
    {
        // Проверяваме дали оригиналния файл е с допустимите размери и разширение за определяне на баркод
        if (!static::canReadBarcodes($fileInfoId, $originFileExt)) {
            
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
            
            // Обикаляме всеки открит баркод
            foreach ($barcodes as $barcode) {
                
                // Вземаме cid'a на баркода
                $cid = log_Documents::getDocumentCidFromURL($barcode->code);
                
                // Ако не може да се намери cid, прескачаме
                if (!$cid) continue;

                // Вземаме записа за оригиналния файла в bgerp_FileInfo таблицата
                $fRec = bgerp_FileInfo::fetch($fileInfoId);
                
                // Ако има открито съдържание на файла
                if (str::trim($fRec->content)) continue;
                
                // Вземаме манипулатора на оригиналния файл
                $fhOriginal = fileman_Files::fetchField($fRec->fileId, 'fileHnd');
                
                // Създава документ на оригиналния файл
                $newDocId = doc_Incomings::createFromScannedFile($fhOriginal, $cid);
                
                // Ако създадем файл, прекъсваме изпълнението на цикъла
                if ($newDocId) break;
            }
            
            // Масив с всички баркодове
            $barcodesArr[] = $barcodes;
        }
        
        // Ако има открити баркодове
        if (count($barcodesArr)) {
            
            // Сериализираме масива и обновяваме данните за записа в bgerp_FileInfo
            $rec = new stdClass();
            $rec->id = $fileInfoId;
            $rec->barcodes = serialize($barcodesArr);
            
            bgerp_FileInfo::save($rec);    
        }
    }
    
    
	/**
     * Връща createdOn полето на последния запис в модела
     */
    static function getLastFetchTime()
    {
        $query = bgerp_FileInfo::getQuery();
        $query->limit(1);
        $query->orderBy('createdOn', 'DESC');
        
        $rec = $query->fetch();
        
        return $rec->createdOn;
    }

    
    /**
     * Проверяваме дали оригиналния файл е с допустимите размери за определяне на баркод
     */
    static function canReadBarcodes($fileInfoId, $ext)
    {
        // Проверяваме разширението на оригиналния файл, дали е допустим за създаване на баркод
        if (!in_array($ext, array('pdf'))) {
            
            return FALSE;
        }
        
        // Вземаме записа за оригиналния файла
        $fRec = bgerp_FileInfo::fetch($fileInfoId);
        
        // Вземаме размера на файла
        $dRec = fileman_Data::fetch($fRec->dataId);
        $fLen = $dRec->fileLen;
        
        // По голям или равен на 15kB
        // По малък или равен на 1mB
        // Проверяваме дали е в допустимите граници
        if (($fLen >= FILEINFO_MIN_FILE_LEN_BARCODE) && (($fLen <= FILEINFO_MAX_FILE_LEN_BARCODE))) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
	/**
     * Сваляне на информция за файловете
     * 
     * @todo Временно - За тестове
     * 
     */
    function act_startProcess()
    {
        $this->startProcess();
        
        return 'OK';
    }
    
    
    /**
     * Сваляне на информция за файловете по cron
     */
    function cron_startProcess()
    {
        $this->startProcess();
        
        return 'Свалянето на информацията приключи.';
    }
    
    
	/**
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $res .= "<p><i>Нагласяне на Cron</i></p>";
        
//        //Данни за работата на cron
//        $rec = new stdClass();
//        $rec->systemId = 'startFileProcessing';
//        $rec->description = 'Изпращане на много имейли';
//        $rec->controller = $mvc->className;
//        $rec->action = 'startProcess';
//        $rec->period = 3;
//        $rec->offset = 0;
//        $rec->delay = 0;
//        $rec->timeLimit = 100;
//        
//        $Cron = cls::get('core_Cron');
//        
//        if ($Cron->addOnce($rec)) {
//            $res .= "<li><font color='green'>Задаване на крон да извлича информация от файловете.</font></li>";
//        } else {
//            $res .= "<li>Отпреди Cron е бил нагласен да извлича информция от файловете.</li>";
//        }
        
        //Създаваме, кофа, където ще държим всички генерирани файлове
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('fileInfo', 'Информация за файлове', NULL, '104857600', 'user', 'user');
    }
}