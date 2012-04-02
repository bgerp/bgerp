<?php



/**
 * Клас  'core_Redirect' ('Redirect') - Шаблон, който съдържа нова локация за браузъра
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
class core_Redirect extends core_ET
{
    
    
    /**
     * Конструктор на шаблона - редирект
     */
    function core_Redirect($url, $msg = NULL, $type = 'info')
    {
        if($msg) {
            $Nid = rand(1000000, 9999999);
            Mode::setPermanent('Notification_' . $Nid, $msg);
            Mode::setPermanent('NotificationType_' . $Nid, $type);
            $url = core_Url::addParams(toUrl($url), array('Nid' => $Nid));
        }
        
        $this->push(toUrl($url), '_REDIRECT_');
    }
}