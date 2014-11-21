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
        $hitId = Request::get('hit_id');
        if ($msg) {
            if (!$hitId) {
                $hitId = str::getRand();
            }
            
            core_Statuses::newStatus($msg, $type, NULL, 60, $hitId);
        }
        
        if ($hitId) {
            if (is_array($url)) {
                $url['hit_id'] = $hitId;
            } else if ($url) {
                $url = core_Url::addParams($url, array('hit_id' => $hitId));
            }
        }
        
        $this->push(toUrl($url), '_REDIRECT_');
    }
}