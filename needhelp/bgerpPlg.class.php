<?php


/** Въпроси
 * 
 * @category  bgerp
 * @package   needhelp
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class needhelp_bgerpPlg extends core_Plugin
{    

    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        if (Request::get('ajax_mode')) return ;
        
        $currUserId = core_Users::getCurrent();
        
        if ($currUserId <= 0) return ;
        
        if (help_Log::getDisplayMode('-1', $currUserId, FALSE) != 'open') return ;
        
        $data->__needHelp = TRUE;
    }
    
    
    /**
     * 
     * 
     * @param core_Manager $mvc
     * @param core_ET $res
     * @param core_ET $tpl
     * @param object $data
     */
	public static function on_AfterRenderWrapping($mvc, &$res, $tpl, $data)
    {
        // Ако е зададено да не се показва
        if (!$data->__needHelp) return ;
        
    	cls::get(page_InternalFooter);
    	$baseUrl = BGERP_SUPPORT_URL;
    	$conf = core_Packs::getConfig('needhelp');
    	$typeId = $conf->NEEDHELP_TYPEID;
    	$url = core_Url::addParams($baseUrl, array('typeId' => $typeId));
    	
    	if(defined('BGERP_SUPPORT_URL') && strpos(BGERP_SUPPORT_URL, '//') !== FALSE) {
    		$email = email_Inboxes::getUserEmail();
    		if(!$email) {
    			$email = core_Users::getCurrent('email');
    		}
    		list($user, $domain) = explode('@', $email);
    		$name = core_Users::getCurrent('names');
    		$form = new ET("<form class='needHelpForm' style='display:inline' method='post' target='_blank' onSubmit=\"prepareBugReport(this, '{$user}', '{$domain}', '{$name}');\" action='" . $url . "'></form>");
    		$res->append($form);
    	}
    	$res->push('needhelp/lib/style.css', 'CSS');
    	$res->push('needhelp/lib/script.js', 'JS');

    	$inactiveTime = $conf->NEEDHELP_INACTIVE_SECS;
    	
    	$text = tr('Имате ли въпроси за') . ' <span class="logo">bgERP</span>?';
    	
    	$closeUrl = toUrl(array('help_log', 'closeInfo', '-1'), 'local');
    	$closeUrl = urlencode($closeUrl);
    	
    	$seeUrl = toUrl(array('help_log', 'see', '-1'), 'local');
    	$seeUrl = urlencode($seeUrl);
    	
    	jquery_Jquery::run($res, "needHelpActions('{$text}', $inactiveTime, '{$closeUrl}', '{$seeUrl}');", TRUE);;
    }
}
