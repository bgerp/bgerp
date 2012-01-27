<?php 


/**
 * Лог на изпращаните писма
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_ListSend extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Лог на изпращаните писма";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, blast';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin, blast';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, blast';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да праша информационните съобщения?
     */
    var $canBlast = 'admin, blast';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'blast_Wrapper, plg_Sorting';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'emailId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'listDetailId, sended';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 20;
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('listDetailId', 'key(mvc=blast_ListDetails, select=key)', 'caption=Е-мейл');
        $this->FLD('emailId', 'key(mvc=blast_Emails, select=subject)', 'caption=Бласт');
        $this->FLD('sended', 'datetime', 'caption=Дата, input=none');
        
        $this->setDbUnique('listDetailId,emailId');
    }
}
