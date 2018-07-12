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
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Message extends core_BaseClass
{
    /**
     * @todo Чака за документация...
     */
    public function act_View()
    {
        try {
            
            // Ако няма протокол
            if (!($protocol = $_SERVER['SERVER_PROTOCOL'])) {
                
                // Използваме този
                $protocol = 'HTTP/1.1';
            }
            
            // Сетваме хедърите
            header("{$protocol} 404 Not Found");
            
            // Забранява кеширането
            header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
            header('Pragma: no-cache'); // HTTP 1.0.
            header('Expires: 0'); // Proxies.
            
            // Дешифриране на съобщението
            $Crypt = cls::get('core_Crypt');
            $key = Mode::getPermanentKey();
            $msg = $Crypt->decodeVar(Request::get('msg'), $key);
            
            // Създаване на липсващо съобщение
            if (!$msg) {
                $msg = new stdClass();
                if (Request::get('msg')) {
                    $msg->text = tr('Сгрешено или изтекло съобщение');
                } else {
                    $msg->text = tr('Липсващо съобщение');
                }
                $msg->tpl = 'page_Error';
                $msg->next = null;
                $msg->cancel = null;
            }
            
            // Създаване на шаблона
            $tpl = cls::get($msg->tpl);
            
            // Попълване на шаблона
            $tpl->replace($msg->text, 'text');
            
            if ($msg->cancel || $msg->next) {
                $toolbar = cls::get('core_Toolbar');
                
                if ($msg->cancel) {
                    $toolbar->addBtn('Отказ', $msg->cancel);
                }
                
                if ($msg->next) {
                    $toolbar->addBtn('Продължение', toUrl($msg->next));
                }
                $tpl->replace($toolbar->renderHtml(), 'TOOLBAR');
            }
            
            if ($msg->wrapper) {
                MODE::set('wrapper', $msg->wrapper);
            }
            
            return $tpl;
        } catch (ErrorException $e) {
            $err = new core_exception_Expect('Грешка при рендиране на съобщение за грешка');
            
            $err->class = 'core_Message';
            
            throw $err;
        }
    }
    
    
    /**
     * Създава съобщение и редиркетва към него
     */
    public static function redirect($text, $tpl = 'error', $cancel = '', $next = '')
    {
        $errorUrl = static::getErrorUrl($text, $tpl, $cancel, $next);
        redirect($errorUrl);
    }
    
    
    /**
     * Връща url към съобщение за грешка
     */
    public static function getErrorUrl($text, $tpl = 'error', $cancel = '', $next = '')
    {
        // Създава съобщението
        $msg = new stdClass();
        $msg->text = tr($text);
        $msg->tpl = $tpl;
        $msg->wrapper = Mode::get('wrapper');
        
        if ($next) {
            $msg->next = $next;
        }
        
        if ($cancel) {
            $msg->cancel = $cancel;
        }
        
        $Crypt = cls::get('core_Crypt');
        $key = Mode::getPermanentKey();
        $msg = $Crypt->encodeVar($msg, $key);
        
        return array('core_Message', 'view', 'msg' => $msg);
    }
}
