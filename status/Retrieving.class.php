<?php 


/**
 * Клас 'status_Retrieving'
 *
 * @category  vendors
 * @package   status
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class status_Retrieving extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Изтегляния';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
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
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'status_Wrapper';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('messageId', 'key(mvc=status_Messages)', 'caption=Съобщение');
        $this->FLD('userId', 'user', 'caption=Потребител');
        $this->FLD('retTime', 'datetime', 'caption=Изтегляне');
        $this->FLD('hitTime', 'datetime', 'caption=Заявка');
        
        $this->setDbUnique('messageId, userId, hitTime');
    }
    
    
    
    
    
    
    
    
    
    
    
    
}