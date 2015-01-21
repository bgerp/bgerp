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
    	$tpl->push('needhelp/lib/style.css', 'CSS');
    	$tpl->push('needhelp/lib/script.js', 'JS');
    	$text = tr('Имате ли някакви въпроси') . '?';
    	jquery_Jquery::run($tpl, "needHelpActions('{$text}');", TRUE);;
    }
}