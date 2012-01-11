<?php
/**
 * Мениджър на публични домейни
 * 
 * Информацията дали един домейн е публичен се използва при рутирането на входящи писма.
 * 
 * @category   BGERP
 * @package    email
 * @author	   Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 * @see https://github.com/bgerp/bgerp/issues/108
 */
class email_PublicDomains extends core_Manager
{   
    var $loadList = 'plg_Created, plg_State, email_Wrapper';

    var $title    = "Публични домейни";

    var $listFields = 'id, domain, state';

    var $canRead   = 'admin,email';

    var $canWrite  = 'admin,email';
    

    function description()
    {
        $this->FLD('domain', 'varchar(255)', 'caption=Домейн,mandatory');
    }

}
