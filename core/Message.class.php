<?php



/**
 * Клас 'core_Message' - визуализиране на съобщения
 *
 * Класът core_Message представлява контролер, който визуализира съобщение,
 * което получава по URL, и предоставя възможност за потребителска реакция
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Message extends core_BaseClass
{
    
    
    /**
     * @todo Чака за документация...
     */
    function act_View()
    {
        // Дешифриране на съобщението
        $Crypt = cls::get('core_Crypt');
        $key = Mode::getPermanentKey();
        $msg = $Crypt->decodeVar(Request::get('msg'), $key);
        
        // Създаване на липсващо съобщение
        if (!$msg) {
            if (Request::get('msg')) {
                $msg->text = tr('Сгрешено или изтекло съобщение');
            } else {
                $msg->text = tr('Липсващо съобщение');
            }
            $msg->tpl = 'page_Error';
            $msg->next = NULL;
            $msg->cancel = NULL;
        }
        
        // Създаване на шаблона
        $tpl = cls::get($msg->tpl);
        
        // Попълване на шаблона
        $tpl->replace($msg->text, 'text');
        
        if ($msg->cancel || $msg->next) {
            $toolbar = cls::get('core_Toolbar');
            
            if ($msg->cancel)
            $toolbar->addBtn('Отказ', $msg->cancel);
            
            if ($msg->next)
            $toolbar->addBtn('Продължение', toUrl($msg->next));
            $tpl->replace($toolbar->renderHtml(), 'TOOLBAR');
        }
        
        if ($msg->wrapper) {
            MODE::set('wrapper', $msg->wrapper);
        }
        
        return $tpl;
    }
    
    
    /**
     * Създава съобщение и редиркетва към него
     */
    static function redirect($text, $tpl = 'error', $cancel = '', $next = '')
    {
        // Създава съобщението
        $msg = new stdClass();
        $msg->text = tr($text);
        $msg->tpl = $tpl;
        
        if ($next)
        $msg->next = $next;
        
        if ($cancel)
        $msg->cancel = $cancel;
        $msg->wrapper = Mode::get('wrapper');
        
        $Crypt = cls::get('core_Crypt');
        $key = Mode::getPermanentKey();
        $msg = $Crypt->encodeVar($msg, $key);
        
        redirect(array('core_Message', 'view', 'msg' => $msg));
    }
}