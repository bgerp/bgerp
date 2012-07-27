<?php

// TODO Да се преместят в Setup.class.php
// TODO Да се добавят и позволените разширения
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
        $this->FLD('dataId', 'key(mvc=fileman_Data)', 'caption=Данни,notNull');
        $this->FLD('barcodes', 'blob', 'caption=Баркодове');
        $this->FLD('content', 'text', 'caption=Съдържание');
        $this->FLD('images', 'blob', 'caption=Изображения');
        $this->FLD('metaInfo', 'blob', 'caption=Мета информация');
        
        $this->setDbUnique('dataId');
    }
    
    
    /**
     * Връща информацията за файла
     * 
     * @param stdObject $fileRec - Обект със запис за файла от fileman_Files
     * 
     * @return stdObject $rec - Обект със запис от bgerp_FileInfo
     */
    static function getFileInfo($fileRec)
    {    
        // Проверяваме дали има вече извлечени данни за съответния файл
        if (!($rec = static::fetch("#dataId = '{$fileRec->dataId}'"))) {
            
            // Намираме информацията за файла
            $rec = static::startFileProcessing($fileRec);    
        }
        
        return $rec;
    }

    
    /**
     * Стартираме събирането на различна информация за файла
     * 
     * @param stdObject $fileRec - Обект със запис за файла от fileman_Files
     * 
     * @return stdObject $rec - Обект със запис от bgerp_FileInfo
     */
    static function startFileProcessing($fileRec)
    {
        // Разширението на файла
        $ext = fileman_Files::getExt($fileRec->name);
        
        //TODO само за допустимите разширения
        // Данните, които ще запишем
        $nRec = new stdClass();
        $nRec->fileId = $fileRec->id;
        $nRec->dataId = $fileRec->dataId;
        
        // Записваме данните и вземаме id' то на записа
        $fileInfoId = bgerp_FileInfo::save($nRec);
        
        // Ако разширението е от допустимите
        if (in_array($ext, array('pdf', 'rtf', 'odt'))) {
            
            // Опитваме се да определим съдържанието на файла
            static::getContent($fileRec->fileHnd, $fileInfoId, $ext);  
        }
        
        // Ако разширението е едно от посочените
        if (in_array($ext, array('pdf', 'rtf', 'odt'))) {
            
            // Стартираме конвертирането на файла
            static::convertFileToJpg($fileRec->fileHnd, $fileInfoId, $ext);
        }
        
        // Ако разширението на оригиналния файл е в допустимите
        if (in_array($ext, array('pdf'))) {
            
            // Вземаме генерираните изображения
            $fileHndArr = unserialize(static::fetchField("#dataId = '{$fileRec->dataId}'", 'images'));
            
            // Сканираме получените файлове за наличие на баркод
            static::getBarcodes($fileHndArr, $fileInfoId);
        }
        
        // Правим опит да определим мета информацията за файла
        static::findMetaInfo($fileRec->fileHnd, $fileInfoId, $ext);
        
        // Вземаме записа от БД и го връщаме
        $rec = static::fetch("#dataId = '{$fileRec->dataId}'");
            
        return $rec;
    }
    
    
	/**
     * Функиция за определяне на начина за извличане на съдъжанието на файла
     * 
     * @param fileHnd $fh - Манипулатор на файла
     * @param integer $fileInfoId - id' то на записа от bgerp_FileInfo, в който ще запишем получената информация
     * @param string $ext - Разширението на файла
     */
    static function getContent($fh, $fileInfoId, $ext)
    {
        // Ако разширението е pdf
        if (in_array($ext, array('pdf'))) {
            
            // Стартираме функцията за определяне на разширението на файла
            static::getContentFromPdf($fh, $fileInfoId);
        }
        
        // Ако разширението е pdf
        if (in_array($ext, array('rtf', 'odt'))) {
            
            // Стартираме функцията за определяне на разширението на файла
            static::getContentFromDoc($fh, $fileInfoId);
        }
    }
    
    
	/**
     * Определя на съдържанието на pdf документите
     * 
     * @param fileHnd $fh - Манипулатор на файла
     * @param integer $fileInfoId - id' то на записа от bgerp_FileInfo, в който ще запишем получената информация
     */
    static function getContentFromPdf($fh, $fileInfoId)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'bgerp_FileInfo::afterGetContentFrom',
            'fileInfoId' => $fileInfoId,
        	'asynch' => FALSE,
        );
        
        // Стартираме конвертирането
        docoffice_Pdf::convertPdfToTxt($fh, $params);
    }
    
    
    /**
     * Определя на съдържанието на офис документите
     * 
     * @param fileHnd $fh - Манипулатор на файла
     * @param integer $fileInfoId - id' то на записа от bgerp_FileInfo, в който ще запишем получената информация
     * 
     * @access protected
     */
    static function getContentFromDoc($fh, $fileInfoId)
    {
        // Конфигурационните константи
        $conf = core_Packs::getConfig('docoffice');
        
        // Класа, който ще конвертира
        $ConvClass = $conf->OFFICE_CONVERTER_CLASS;
        
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'bgerp_FileInfo::afterGetContentFrom',
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
        
        // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
        // и записа от таблицата fconv_Process
        return TRUE;
    }    
        
        
	/**
     * Функиция за определяне на начина за конвертиране към JPG
     * 
     * @param fileHnd $fh - Манипулатор на файла
     * @param integer $fileInfoId - id' то на записа от bgerp_FileInfo, в който ще запишем получената информация
     * @param string $ext - Разширението на файла
     */
    static function convertFileToJpg($fh, $fileInfoId, $ext)
    {
        // Ако типа на разширението е pdf
        if (in_array($ext, array('pdf'))) {
            
            // Стартираме конвертирането от PDF в JPG формат
            static::convertPdfToJpg($fh, $fileInfoId);
        }
        
        // 
        if (in_array($ext, array('odt', 'rtf'))) {
            
            // Стартираме конвертирането от PDF в JPG формат
            static::convertDocToJpg($fh, $fileInfoId);
        }
    }    
        
        
	/**
     * Конвертира PDF файл, в JPG формат
     * 
     * @param fileHnd $fh - Манипулатор на файла
     * @param integer $fileInfoId - id' то на записа от bgerp_FileInfo, в който ще запишем получената информация
     */
    static function convertPdfToJpg($fh, $fileInfoId)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'bgerp_FileInfo::afterConvertFileToJpg',
            'fileInfoId' => $fileInfoId,
        	'asynch' => FALSE,
        );
        
        // Стартираме конвертирането
        docoffice_Pdf::convertPdfToJpg($fh, $params);
    }
     
    
    /**
     * Конвертира DOC файл, в JPG формат
     * 
     * @param fileHnd $fh - Манипулатор на файла
     * @param integer $fileInfoId - id' то на записа от bgerp_FileInfo, в който ще запишем получената информация
     */
    static function convertDocToJpg($fh, $fileInfoId)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'bgerp_FileInfo::afterConvertDocToPdf',
            'fileInfoId' => $fileInfoId,
        	'asynch' => FALSE,
        ); 
        
        // Конфигурационните константи
        $conf = core_Packs::getConfig('docoffice');
        
        // Класа, който ще конвертира
        $ConvClass = $conf->OFFICE_CONVERTER_CLASS;
        
        // Стартираме конвертирането към PDF
        $ConvClass::convertDoc($fh, 'pdf', $params);
    }
    
    
    /**
     * Функция, която получава управлението след конвертирането от DOC в PDF формат - Междинна стъпка при конвертиране в JPG
     * 
     * @param object $script - Обект със стойности
     * 
     * @return boolean TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterConvertDocToPdf($script)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'bgerp_FileInfo::afterConvertFileToJpg',
            'fileInfoId' => $script->fileInfoId,
        	'asynch' => FALSE,
        );
        
        // Стартираме конвертирането
        docoffice_Pdf::convertPdfToJpg($script->outFilePath, $params);   

        // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
        // и записа от таблицата fconv_Process
        return TRUE;
    }
    

	/**
     * Функция, която получава управлението след конвертирането на файл в JPG формат
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
        
//        // Генерираме името на файла след конвертиране (ако няма брояч)
//        $fn = $script->fName . '.jpg';
//        
//        if (in_array($fn, $files)) {
//            
//            // Качваме файла в кофата и му вземаме манипулатора
//            $fileHnd = $Fileman->addNewFile($script->tempDir . $fn, 'fileInfo'); 
//            
//            // Ако се качи успешно записваме манипулатора в масив
//            if ($fileHnd) {
//                $fileHndArr[$fileHnd] = $fileHnd;    
//            }    
//        }
        
        // Ако има генерирани файлове, които са качени успешно
        if (count($fileHndArr)) {
            
            // Сериализираме масива и обновяваме данните за записа в bgerp_FileInfo
            $rec = new stdClass();
            $rec->id = $script->fileInfoId;
            $rec->images = serialize($fileHndArr);
            
            bgerp_FileInfo::save($rec);    
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
     * 
     * @access protected
     */
    static function getBarcodes($fileHnd, $fileInfoId)
    {
        // Проверяваме дали оригиналния файл е с допустимите размери и разширение за определяне на баркод
        if (!static::canReadBarcodes($fileInfoId)) {
            
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
            
            // TODO това няма да става автоматично, а ръчно при създаване на документа
//            // Обикаляме всеки открит баркод
//            foreach ($barcodes as $barcode) {
//                
//                // Вземаме cid'a на баркода
//                $cid = log_Documents::getDocumentCidFromURL($barcode->code);
//                
//                // Ако не може да се намери cid, прескачаме
//                if (!$cid) continue;
//
//                // Вземаме записа за оригиналния файла в bgerp_FileInfo таблицата
//                $fRec = bgerp_FileInfo::fetch($fileInfoId);
//                
//                // Ако има открито съдържание на файла
//                if (str::trim($fRec->content)) continue;
//                
//                // Вземаме манипулатора на оригиналния файл
//                $fhOriginal = fileman_Files::fetchField($fRec->fileId, 'fileHnd');
//                
//                // Създава документ на оригиналния файл
//                $newDocId = doc_Incomings::createFromScannedFile($fhOriginal, $cid);
//            }
            
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
     * Функиция за определяне на мета информацията за файла
     * 
     * @param fileHnd $fh - Манипулатор на файла
     * @param integer $fileInfoId - id' то на записа от bgerp_FileInfo, в който ще запишем получената информация
     * @param string $ext - Разширението на файла
     */
    static function findMetaInfo($fh, $fileInfoId, $ext)
    {    
        // Ако има друга функция за определяне на мета информация за дадено разширение
        if (in_array($ext, array())) {
            $flag = TRUE;    
        }

        // Ако няма определяна функция
        if (!$flag) {
            
            // Намираме дефаулт мета информацията
            $metaInfo = fileman_Files::findDefMetaInfo($fh);
        }
        
        // Записваме получения текс в модела
        $rec = new stdClass();
        $rec->id = $fileInfoId;
        $rec->metaInfo = serialize($metaInfo);
        bgerp_FileInfo::save($rec);
    }
    
    
    /**
     * Проверяваме дали оригиналния файл е с допустимите размери за определяне на баркод
     */
    static function canReadBarcodes($fileInfoId)
    {
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
     * Изпълнява се след създаването на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        //Създаваме, кофа, където ще държим всички генерирани файлове
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('fileInfo', 'Информация за файлове', NULL, '104857600', 'user', 'user');
    }   
}