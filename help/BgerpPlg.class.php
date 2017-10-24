<?php


/** 
 * Въпроси за bgerp
 * 
 * @category  bgerp
 * @package   help
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class help_BgerpPlg extends core_Plugin
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
     * @param object|NULL $data
     */
	public static function on_AfterRenderWrapping($mvc, &$res, $tpl, $data=NULL)
    {
        // Ако е зададено да не се показва
        if (!$data || !$data->__needHelp) return ;
        
        $conf = core_Packs::getConfig('help');
        
    	$baseUrl = $conf->BGERP_SUPPORT_URL;
    	$conf = core_Packs::getConfig('help');
    	
    	if($conf->BGERP_SUPPORT_URL && strpos($conf->BGERP_SUPPORT_URL, '//') !== FALSE) {
    		$email = email_Inboxes::getUserEmail();
    		if(!$email) {
    			$email = core_Users::getCurrent('email');
    		}
    		list($user, $domain) = explode('@', $email);
    		$currUrl = getCurrentUrl();
            $ctr = $currUrl['Ctr'];
            $act = $currUrl['Act'];
            $sysDomain = $_SERVER['HTTP_HOST'];
    		$name = core_Users::getCurrent('names');
    		$form = new ET("<form class='needHelpForm' style='display:inline' method='post' target='_blank' onSubmit=\"prepareBugReport(this, '{$user}', '{$domain}', '{$name}', '{$ctr}', '{$act}', '{$sysDomain}');\" action='" . $baseUrl . "'></form>");
    		$res->append($form);
    	}
    	$res->push('help/lib/style.css', 'CSS');
    	$res->push('help/lib/script.js', 'JS');

    	$inactiveTime = $conf->HELP_BGERP_INACTIVE_SECS;
    	
    	$text = tr('Имате ли въпроси за') . ' <span class="logo">bgERP</span>?';
    	
    	$closeUrl = toUrl(array('help_log', 'closeInfo', '-1'), 'local');
    	$closeUrl = urlencode($closeUrl);
    	
    	$seeUrl = toUrl(array('help_log', 'see', '-1'), 'local');
    	$seeUrl = urlencode($seeUrl);
    	
    	jquery_Jquery::run($res, "needHelpActions('{$text}', $inactiveTime, '{$closeUrl}', '{$seeUrl}');", TRUE);;
    }
}
