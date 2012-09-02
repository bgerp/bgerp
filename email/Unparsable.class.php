<?php 



/**
 * Имейли, които не могат да се парсират
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Unparsable extends core_Master
{
    
    
    /**
     * Плъгини за работа
     */
    var $loadList = 'email_Wrapper,  plg_Created,  plg_RowTools';
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Имейли, които не могат да се парсират";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, email';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,manager,';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,manager,officer,executive';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,manager,officer,executive';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, email';
    
    
    /**
     * Кой има права за
     */
    var $canEmail = 'ceo,manager,officer,executive';
    
    
     
     
     
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD("source", "text(2000000)", "caption=Сорс");
          
     }
    
 }
