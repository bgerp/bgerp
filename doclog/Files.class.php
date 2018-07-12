<?php


/**
 * Лог на файловете
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doclog_Files extends core_Manager
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Лог на файловете';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin, doc';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin, doc';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin, doc';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    public $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created';
    
    
    /**
     * @todo Чака за документация...
     */
    public $listFields = 'cid, fileHnd, createdOn=Свален->На, createdBy=Свален->От, seenFromIp=Свален->Ip';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'log_Files';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD(
            'fileHnd',
            'varchar(' . strlen(FILEMAN_HANDLER_PTR) . ')',
            array('notNull' => true, 'caption' => 'Манипулатор')
        );
        
        $this->FLD('cid', 'key(mvc=doc_Containers)', 'caption=Контейнер,notNull,value=0');
        
        $this->FLD('seenFromIp', 'ip', 'input=none', 'caption=IP,value=0');
    }
    
    
    /**
     * Записваме информация за свалянето на съответния файл
     */
    public static function downloaded($fileHnd, $cid)
    {
        // Създаваме обект
        $rec = new stdClass();
        
        // IP то на потребителя, който сваля
        $rec->seenFromIp = core_Users::getRealIpAddr();
        
        // Манипулатора на файла
        $rec->fileHnd = $fileHnd;
        
        // Контейнера, от където е файла
        $rec->cid = $cid;
        
        // Записваме
        static::save($rec);
    }
}
