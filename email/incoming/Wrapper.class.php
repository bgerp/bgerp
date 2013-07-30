<?php

/**
 * Клас 'email_incoming_Wrapper' - опаковка на моделите, съдържащи входящи писма
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_incoming_Wrapper extends email_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
        
        // Ако имаме роля ceo, само тогава да се показват съобщенията
        if(haveRole('ceo')) {
            $tabs->TAB('email_Incomings', 'Съобщения');
        }
        
        // Ако имаме роля admin, ceo или email тогава да се показват съобщенията
        if(haveRole('admin,ceo,email')) {
            $tabs->TAB('email_Returned', 'Върнати');
            $tabs->TAB('email_Receipts', 'Разписки');
            $tabs->TAB('email_Spam', 'Спам');
            $tabs->TAB('email_Unparsable', 'Непарсируеми');
            $tabs->TAB('email_Fingerprints', 'Отпечатъци');
        }

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        
        $mvc->currentTab = 'Входящи';
    }
}