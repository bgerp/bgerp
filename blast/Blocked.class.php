<?php 


/**
 * Блокирани имейли
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_Blocked extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Адреси, на които не се изпращат циркулярни имейли";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, blast';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo, blast';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo, blast';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo, blast';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo, blast';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo, blast';
    
    
    /**
     * Кой може да праша информационните съобщения?
     */
    var $canBlast = 'ceo, blast';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'blast_Wrapper';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('email', 'email', 'caption=Имейл, mandatory');
        
        $this->setDbUnique('email');
    }
}
