<?php


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
     * 
     */
    function description()
    {
        $this->FLD('fileId', 'key(mvc=fileman_Files)', 'caption=Файлове');
        $this->FLD("dataId", "key(mvc=fileman_Data)", 'caption=Данни');
        $this->FLD('createdOn', 'datetime(format=smartTime)', 'caption=Създаване->На');
        $this->FLD('barcodes', 'blob', 'caption=Баркодове');
        $this->FLD('keywords', 'blob', 'caption=Ключови думи');
        $this->FLD('images', 'blob', 'caption=Изображения');
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}