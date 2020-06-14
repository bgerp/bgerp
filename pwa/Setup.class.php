<?php

/**
 * Мобилното приложение активно ли е?
 */
defIfNot('PWA_ACTIVE', 'no');

/**
 * Клас 'pwa_Setup' -  bgERP progressive web application
 *
 *
 * @package   pwa
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pwa_Setup extends core_ProtoSetup
{
    public $info = 'bgERP progressive web application';
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'PWA_ACTIVE' => array('enum(no=Не,yes=Да)', 'caption=Мобилно приложение->Активиране,customizeBy=user'),
        );
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина към страницата
        $html .= $Plugins->installPlugin('bgERP PWA', 'pwa_Plugin', 'core_page_Active', 'family');
        
        $sw = getFileContent('pwa/js/sw.js');
        core_Webroot::register($sw, '', 'sw.js', 1);
        
        return $html;
    }
}
