<?php


/**
 * Инсталиране/Деинсталиране на плъгина за хифенация
 *
 * @category  vendors
 * @package   hyphen
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hyphen_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Хифенация за пренасяне на дълги думи в текстови документи';
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме плъгина
        $html .= $Plugins->installPlugin('Hyphen', 'hyphen_Plugin', 'type_Richtext', 'private');
        
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
        
        // Деинсталираме toast съобщения
        if ($delCnt = $Plugins->deinstallPlugin('hyphen_Plugin')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на 'type_Richtext'";
        } else {
            $html .= '<li>Не са премахнати закачания на плъгина';
        }

        return $res;
    }
}
