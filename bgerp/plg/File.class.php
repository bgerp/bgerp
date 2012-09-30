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
        
        // TODO това може да се добави, когато в opened (При преглеждане на документа '/L/S')
        // имаме добавен action. В момента няма да работи коректно.
        // При изпращане на имейла имаме линк към файл, който не е валиден.
        // Ако се премахнат коментарите, в преглеждаме документа в '/L/S' ще имаме линк към сингъла на файла
//        // Действието
//        $action = log_Documents::getAction();
//
//        // Ако не изпращаме имейла, да не сработва
//        if ((!$action) || ($action->action == )) return ;
        
        // Името на файла
        $name = fileman_Files::fetchByFh($fh, 'name');

        //Генерираме връзката 
        $res = toUrl(array('F', 'S', doc_DocumentPlg::getMidPlace(), 'n' => $name), TRUE);

        return FALSE;
    }
}