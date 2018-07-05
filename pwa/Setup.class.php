<?php


/**
 * FileHandler на логото за PWA
 */
defIfNot('PWA_IMAGE', '');


/**
 * Клас 'pwa_Setup' -  bgERP progressive web application
 *
 *
 * @package   pwa
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pwa_Setup extends core_ProtoSetup
{
    public $info = 'bgERP progressive web application';


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'PWA_IMAGE' => array('fileman_FileType(bucket=gallery_Pictures)', 'caption=Икона за приложението (512x512px)->Изображение'),
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
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        $html = parent::deinstall();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        $Plugins->deinstallPlugin('pwa_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'bgERP PWA'";
        
        return $html;
    }
}
