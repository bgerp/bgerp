<?php


/**
 * Клас 'ograph_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   ograph
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class ograph_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Пакет за работа с Open Graph Protocol елементи';
    
    

    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('Facebook Ograph Meta', 'ograph_Plugin', 'cms_Content', 'private');
                
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
        
        // Инсталираме клавиатурата към password полета
        if ($delCnt = $Plugins->deinstallPlugin('ograph_Plugin')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на 'ograph_Plugin'";
        } else {
            $html .= '<li>Не са премахнати закачания на плъгина';
        }
        
        return $html;
    }

}
