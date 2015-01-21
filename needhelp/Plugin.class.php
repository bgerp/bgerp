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
class needhelp_Plugin extends core_Plugin
{    
    
	public static function on_AfterRenderWrapping($mvc, &$tpl)
    {
    	cls::get(page_InternalFooter);
    	$baseUrl = BGERP_SUPPORT_URL;
    	$conf = core_Packs::getConfig('needhelp');
    	$id = $conf->NEEDHELP_TYPEID;
    	$url = core_Url::addParams($baseUrl, array('typeId' => $id));
    	
    	if(defined('BGERP_SUPPORT_URL') && strpos(BGERP_SUPPORT_URL, '//') !== FALSE) {
    		$email = email_Inboxes::getUserEmail();
    		if(!$email) {
    			$email = core_Users::getCurrent('email');
    		}
    		list($user, $domain) = explode('@', $email);
    		$name = core_Users::getCurrent('names');
    		$form = new ET("<form class='needHelpForm' style='display:inline' method='post' target='_blank' onSubmit=\"prepareBugReport(this, '{$user}', '{$domain}', '{$name}');\" action='" . $url . "'></form>");
    		$tpl->append($form);
    	}
    	$tpl->push('needhelp/lib/style.css', 'CSS');
    	$tpl->push('needhelp/lib/script.js', 'JS');

    	$inactiveTime = $conf->NEEDHELP_INACTIVE_SECS;
    	
    	$text = tr('Имате ли въпроси за') . ' <span class="logo">bgERP</span>?';
    	jquery_Jquery::run($tpl, "needHelpActions('{$text}', $inactiveTime);", TRUE);;
    }
}