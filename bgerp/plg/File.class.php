<?php



/**
 * Плъгин за работа с файлове
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_File extends core_Plugin
{
    
    
    /**
     * Прихващаме генерирането на имейл.
     * Ако мода е xhtml, тогава сработва и прекъсва по нататъшното изпълнение на функцията.
     */
    static function on_BeforeGenerateUrl($mvc, &$res, $fh, $isAbsolute)
    {
        $fRec = fileman_Files::fetchByFh($fh);
        
        if ($fRec) {
            // Добавяме файла към списъка
            if ($cId = Mode::get('saveObjectsToCid')) {
                doc_UsedInDocs::addObject(array($fh => $fRec->name), $cId, 'files');
            }
        }
        
        $mode = Mode::get('text');
        
        // Ако не се изпраща имейла, да не сработва
        if ($mode != 'xhtml' && $mode != 'plain') return ;
        
        // Действието
        $action = doclog_Documents::getAction();
        
        // Ако не изпращаме имейла, да не сработва
        //        if ((!$action) || in_array($action->action, array(doclog_Documents::ACTION_DISPLAY, doclog_Documents::ACTION_RECEIVE, doclog_Documents::ACTION_RETURN))) return ;
        if (!$action) return ;
        
        //Генерираме връзката 
        $res = toUrl(array('F', 'S', doc_DocumentPlg::getMidPlace(), 'b' => $fRec->bucketId, 'n' => $fRec->name), $isAbsolute, TRUE, array('b', 'n'));
        
        return FALSE;
    }
}
