<?php


/**
 * Клас 'color_Setup' - Избор на цвят от палитра
 *
 *
 * @category  vendors
 * @package   color
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class color_Setup extends core_ProtoSetup 
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = '';
    
    
    /**
     * Описание на модула
     */
    var $info = "Избор на цвят от палитра";
    
    
	/**
	 * Пакет без инсталация
	 */
	public $noInstall = TRUE;
}
