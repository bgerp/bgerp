<?php


/**
 * Клас 'color_Setup' - Избор на цвят от палитра
 *
 *
 * @category  vendors
 * @package   color
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class color_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Поддръжка на цветове: въвеждане и конвертиране';
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
}
