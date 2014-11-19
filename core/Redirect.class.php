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
    function core_Redirect($url, $msg = NULL, $type = 'notice')
    {
        if ($msg) {
            $hitId = str::getRand();
            $url['hit_id'] = $hitId;
            core_Statuses::newStatus($msg, $type, NULL, 60, $hitId);
        }
        
        $this->push(toUrl($url), '_REDIRECT_');
    }
}