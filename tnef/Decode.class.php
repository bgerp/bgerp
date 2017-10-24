<?php


/**
 * Декодиране на TNEF файлове и екстрактване на съдържанието им
 *
 * @category  bgerp
 * @package   tnef
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tnef_Decode extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Декодиране на tnef файлове';
    
    
    /**
     * Кой може да го разглежда?
     */
    protected $canList = 'admin, debug, ceo';
    
    
    /**
     * Кой може да добавя
     */
    protected $canAdd = 'no_one';
	
    
    /**
     * Кой може да го редактира
     */
    protected $canEdit = 'no_one';
    
    
    /**
     * Кой може да изтрива
     */
    protected $canDelete = 'admin, debug, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created';
    
    
    /**
     * Полета, които ще се показват в листовия изглед
     */
    public $listFields = 'id, fileHnd, extractedFilesHnd, createdOn, createdBy';
    
    
    /**
     * Папката, в която ще се добавят изходните файлове
     */
    protected static $outputFolderName = 'resFolder';
    
    
    /**
     * Кофата, в която ще се добавят изходните файлове
     */
    public static $bucket = 'tnefDecoded';
    
    
    /**
     * Разделител на файловете
     */
    protected static $filesDelimiter = ',';
    
    
    /**
     * 
     */
    public function description()
    {
        cls::get('fileman_Files');
        $this->FLD('fileHnd', 'varchar(' . strlen(FILEMAN_HANDLER_PTR) . ')', 'caption=Файл->Източник');
        $this->FLD('extractedFilesHnd', 'varchar', 'caption=Файл->Резултати');
        $this->FLD("dataId", "key(mvc=fileman_Data)", 'caption=Данни');
        
        $this->setDbUnique('dataId');
    }
    
    
    /**
     * Декодира подадения tnef файл и извлича всички файлове от него
     * 
     * @param string $fileHnd
     * 
     * @return array
     */
    public static function decode($fileHnd)
    {
        $fRec = fileman_Files::fetchByFh($fileHnd);
        
        if (!$fRec) return FALSE;
        
        $fileHndArr = array();
        
        // Ако е за същия файл е бил извличан преди
        $rec = self::fetch("#dataId = '{$fRec->dataId}'");
        if ($rec) {
            
            $fileHndArr = explode(self::$filesDelimiter, $rec->extractedFilesHnd);
            
            return $fileHndArr;
        }
        
        // Инстанция на класа
        $Script = cls::get('fconv_Script');
        
        // Пътя до файла, в който ще се записва получения текст
        $Script->setFolders('OUTPUTF', self::$outputFolderName);
        
        // Задаваме placeHolder' за входящия
        $Script->setFile('INPUTF', $fileHnd);
        
        $conf = core_Packs::getConfig('tnef');
        $Script->setProgram('tnef', $conf->TNEF_PATH);
        
        $Script->outputPath = $Script->tempDir . self::$outputFolderName . '/';
        
        $errFilePath = fileman_webdrv_Generic::getErrLogFilePath($Script->outputPath . 'err');
        
        // Скрипта, който ще конвертира
        $Script->lineExec('tnef [#INPUTF#] -C [#OUTPUTF#]');
        
        $Script->setCheckProgramsArr('tnef');
        
        // Стартираме скрипта синхронно синхронно
        if ($Script->run(FALSE) === FALSE) {
            fileman_Indexes::createError($params);
        } else {
            
            $fileHndArr = self::uploadResFiles($Script);
            
            if (!$fileHndArr) {
                fileman_Indexes::haveErrors($Script->outputPath, array('type' => 'tnef', 'errFilePath' => $errFilePath));
            }
            
            $rec = new stdClass();
            $rec->fileHnd = $fileHnd;
            $rec->dataId = $fRec->dataId;
            $rec->extractedFilesHnd = implode(self::$filesDelimiter, (array)$fileHndArr);
            $savedId = self::save($rec, NULL, 'IGNORE');
        }
        
        return $fileHndArr;
    }
    
    
	/**
     * Качва резултатните файлове
     * 
     * @param object $script - Обект със стойности
     * 
     * @return array 
     */
    protected static function uploadResFiles($script)
    {
        $fileHndArr = array();
        
        // Вземаме всички файлове във временната директория
        $files = scandir($script->outputPath);
        
        if (!$files) return $fileHndArr;
        
        // Обхождаме всички отркити файлове
        foreach ($files as $file) {
            
            if ($file == '.' || $file == '..') continue;
            
            // Ако възникне грешка при качването на файла (липса на права)
            try {
                // Качваме файла в кофата и му вземаме манипулатора
                $fileHnd = fileman::absorb($script->outputPath . $file, self::$bucket);
            } catch (core_exception_Expect $e) {
                continue;
            }
            
            // Ако се качи успешно записваме манипулатора в масив
            if ($fileHnd) {
                $fileHndArr[$fileHnd] = $fileHnd;    
            }
        }
        
        // Изтриваме временните папки
        if (core_Os::deleteDir($script->tempDir)) {
            fconv_Processes::delete(array("#processId = '[#1#]'", $script->id));
        }
        
        return $fileHndArr;
    }
    
    
    /**
     * 
     * 
     * @param tnef_Decode $mvc
     * @param object $row
     * @param object $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Името на файла да е линк към singле' a му
        $row->fileHnd = fileman_Files::getLink($rec->fileHnd);
        
        $filesArr = explode(self::$filesDelimiter, $rec->extractedFilesHnd);
        
        $row->extractedFilesHnd = '';
        
        foreach ((array)$filesArr as $fileHnd) {
            $link = fileman_Files::getLink($fileHnd);
            
            if (!$link) continue;
            
            $row->extractedFilesHnd .= ($row->extractedFilesHnd) ? '<br>' . $link : $link;
        }
    }
    
    
    /**
     * 
     * 
     * @param tnef_Decode $mvc
     * @param string $res
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        $conf = core_Packs::getConfig('tnef');
        $res .= fileman_Buckets::createBucket(self::$bucket, 'Файлове от TNEF', '', $conf->TNEF_MAX_SIZE, 'user', 'user');
    }
}
