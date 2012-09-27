<?php


/**
 * Плъгин за работа с файлове
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_File extends core_Plugin
{
    
    
    /**
     * Прихващаме генерирането на имейл.
     * Ако мода е xhtml, тогава сработва и прекъсва по нататъшното изпълнение на функцията.
     */
    function on_BeforeGenerateUrl($mvc, &$res, $fh)
    {
        // Ако не се изпраща имейла, да не сработва
        if (!Mode::is('text', 'xhtml')) return ;
        
        // Действието
        $action = log_Documents::getAction();

        // Ако не изпращаме имейла, да не сработва
        if ((!$action) || ($action->action != log_Documents::ACTION_SEND)) return ;
        
        // Името на файла
        $name = fileman_Files::fetchByFh($fh, 'name');

        //Генерираме връзката 
        $res = toUrl(array('F', 'S', doc_DocumentPlg::getMidPlace(), 'n' => $name), TRUE);

        return FALSE;
    }
}