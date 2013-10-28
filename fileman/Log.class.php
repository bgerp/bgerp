<?php

/**
 * 
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Log extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Последни файлове";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'user';
    
    
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
    var $canView = 'user';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'user';
    
    
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
    var $loadList = 'fileman_Wrapper';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('fileId', 'key(mvc=fileman_Files)', 'caption=Файл,notNull');
        $this->FLD('fileSize', "fileman_FileSize", 'caption=Размер');
        $this->FLD('action', 'enum(upload=Качване, preview=Разглеждане, extract=Екстрактване)', 'caption=Действие');
        $this->FLD('userId', 'user', 'caption=Потребител');
        $this->FLD('lastOn', 'dateTime(format=smartTime)', 'caption=Последно');
        
        $this->setDbUnique('fileId,userId');
    }
    
    
    /**
     * Обновява информацията за използването на файла
     * 
     * @param mixed $fileHnd - Запис от fileman_Files или манипулатор на файла
     * @param string $action - Съответнотното действие: upload, preview, extract
     * @param integer $userId - id на потребитля
     */
    static function updateLogInfo($fileHnd, $action, $userId=NULL)
    {
        // Ако не е подадено id на потребител
        if (!$userId) {
            
            // Вземаме id' то на текущия потребител
            $userId = core_Users::getCurrent();
        }
        
        // Ако системния потребител, връщаме
        if ($userId < 1) return FALSE;
        
        // Ако е подаден запис всмето манипулатор
        if (is_object($fileHnd)) {
            
            // Използваме го
            $fRec = $fileHnd;
        } else {
            
            // Ако е подаден манипулатор
            
            // Вземаме записа
            $fRec = fileman_Files::fetchByFh($fileHnd);
        }
        
        // Ако няма манипулатор на файла
        if (!$fRec->fileHnd) return FALSE;
        
        // Вземаме предишния запис
        $nRec = static::fetch(array("#fileId = '[#1#]' AND #userId=[#2#]", $fRec->id, $userId));
        
        // Ако този файл не е бил използван от съответния потребител
        if (!$nRec) {
            
            // Създаваме обект
            $nRec = new stdClass();
            
            // Добавяме id на файла
            $nRec->fileId = $fRec->id;
            
            // Добавяме id на потребителя
            $nRec->userId = $userId;
        }
        
        // Вземаме meta данните
        $meta = fileman::getMeta($fRec->fileHnd);
        
        // Добавяме размера
        $nRec->fileSize = $meta['size'];
        
        // Добавяме съответното действие
        $nRec->action = $action;
        
        // Добавяме текущото време
        $nRec->lastOn = dt::now();
        
        // Упдейтваме записа
        static::save($nRec, NULL, 'UPDATE');
        
        // Връщаме записа
        return $nRec;
    }
    
    
	/**
     * 
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        // Сортиране по най-ново използване
        $data->query->orderBy("#lastOn", 'DESC');  
    }
 }
 