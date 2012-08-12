<?php



/**
 * Имейли - опаковка
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на опаковката
     */
    function description()
    {
   
        //Показва таба за постинги, само ако имаме права за листване
        $this->TAB('email_Outgoings', 'Изходящи');
        $this->TAB('email_Incomings', 'Входящи', 'admin,ceo');
        $this->TAB('email_Inboxes', 'Кутии', 'ceo,manager,officer,executive');
        $this->TAB('email_Accounts', 'Сметки', 'admin');
        $this->TAB('email_Filters', 'Рутиране', 'admin');
        $this->TAB('email_Sent', 'Изпращания', 'admin');
        
        $this->title = 'Имейли';
    }
}
