<?php


/**
 * Версията на JQueryUI, която се използва
 */
defIfNot(JQUERYUI_VERSION, '1.8.2');


/**
 * Клас 'jqueryui_Ui' - Работа с JQuery UI библиотеката
 *
 * 
 * @category  vendors
 * @package   jqueryui
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class jqueryui_Setup extends core_ProtoSetup
{
    
    
	/**
	 * Пътища до JS файлове
	 */
	var $commonJS = "jqueryui/[#JQUERYUI_VERSION#]/js/jquery-ui-1.8.2.custom.min.js";
    
    
	/**
	 * Пътища до CSS файлове
	 */
	var $commonCSS = "jqueryui/[#JQUERYUI_VERSION#]/css/custom-theme/jquery-ui-1.8.2.custom.css";
}
