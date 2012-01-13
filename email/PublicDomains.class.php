<?php

/**
 * Мениджър на публични домейни
 *
 * Информацията дали един домейн е публичен се използва при рутирането на входящи писма.
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/108
 */
class email_PublicDomains extends core_Manager
{
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State, email_Wrapper';
    
    
    /**
     * Заглавие
     */
    var $title = "Публични домейни";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, domain, state';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,email';
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'admin,email';
    
    function description()
    {
        $this->FLD('domain', 'varchar(255)', 'caption=Домейн,mandatory');
    }
    
    
    
    /**
     * Проверка дали един домейн е публичен или не
     *
     * @param string $domain
     * @return boolean TRUE - публичен, FALSE - не е публичен
     */
    function isPublic($domain)
    {
        return (boolean)static::fetch("#domain = '{$domain}' AND #state = 'active'");
    }
}
