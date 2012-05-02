<?php



/**
 * Клас  'type_Emails' - Тип за много имейли
 *
 * Тип, който ще позволява въвеждането на много имейл-а в едно поле
 *
 *
 * @category  ef
 * @package   type
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class lab_Hora extends core_Manager {
    
 
    /**
     * Заглавие на таблицата
     */
    var $title = "Тест";
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Тест";
    var $loadList = 'plg_RowTools';
    
    function description()
    {
        $this->FLD('emails', 'emails', 'caption=Имейли');
    }
    
    function on_AfterRenderListToolbar($mvc, $tpl, $data)
    {
//        bp(type_Email::isValidEmail('lichen?nobug@mail.com'));
    }
    
    function on_BeforeSave($mvc, $id, $rec, $fields = NULL)
    {
        bp($id);
    }
}