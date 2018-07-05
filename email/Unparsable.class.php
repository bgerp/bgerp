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
class email_Unparsable extends email_ServiceEmails
{
    /**
     * Заглавие на таблицата
     */
    public $title = 'Имейли, които не могат да се парсират';
    

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->addFields();
    }

    
    
    /**
     * Добавяне на писмо, което не може да се парсира
     */
    public static function add($rawEmail, $accId, $uid)
    {
        $rec = new stdClass();
        $rec->data = $rawEmail;
        $rec->accountId = $accId;
        $rec->uid = $uid;
        $rec->createdOn = dt::verbal2mysql();

        self::save($rec);
    }
}
