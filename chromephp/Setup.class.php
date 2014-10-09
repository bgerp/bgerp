<?php
/**
 * class chromephp_Setup
 *
 * Инсталиране/Деинсталиране на пакет chromephp
 *
 *
 * @category  vendors
 * @package   chromephp
 * @author    Stefan Stefanov <stefan.bg@gmail.com
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class chromephp_Setup extends core_ProtoSetup
{
    /**
     * Версия на компонента
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "ChromePHP - инспектиране на стойности. За разработчици";
    
    public function install()
    {
        core_Packs::setConfig('core', array('debugHandler'=>array('chromephp_ChromePHP', 'info')));
    }
}