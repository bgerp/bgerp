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
    var $loadList = 'email_Wrapper';
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Имейли, които не могат да се парсират";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, ceo, email';
    
    
    /**
     * Кой има право да променя?
     */
    var $canWrite = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin, email';
    
      
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('data', 'blob(compress)', 'caption=Данни');
        $this->FLD('accountId', 'key(mvc=email_Accounts,select=email)', 'caption=Сметка');
        $this->FLD('uid', 'int', 'caption=Имейл UID');
        $this->FLD('createdOn', 'datetime', 'caption=Създаване');
    }
    
    
    /**
     * Добавяне на писмо, което не може да се парсира
     */
    static function add($rawEmail, $accId, $uid)
    {
        $rec = new stdClass();
        $rec->data = $rawEmail;
        $rec->accountId = $accId;
        $rec->uid = $uid;
        $rec->createdOn = dt::verbal2mysql();

        self::save($rec);
    }
    
 }
