<?php

/**
 * Колко секунди да се пазят записите в таблицата минимално
 */
defIfNot('EDITWATCH_REC_LIFETIME', 5 * 60);


/**
 * Клас 'editwatch_Setup' -
 *
 *
 * @category  vendors
 * @package   editwatch
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class editwatch_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'editwatch_Editors';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Предупреждение при паралелно редактиране на един запис';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
    
            // Колко секунди да пази записите в таблицата минимално
            'EDITWATCH_REC_LIFETIME' =>
                array('time(suggestions=15 сек.|30 сек.|60 сек.|2 мин.|3 мин.|4 мин.|5 мин.|10 мин.)',
                    'mandatory, caption=Колко време да пази записите в таблицата минимално->Секунди'),
        
        );
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Установяваме страните;
        $Editors = cls::get('editwatch_Editors');
        $html .= $Editors->setupMVC();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталиране към всички полета, но без активиране
        $html .= $Plugins->installPlugin('Editwatch', 'editwatch_Plugin', 'core_Manager', 'family', 'active');
        
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
        
        if ($delCnt = $Plugins->deinstallPlugin('editwatch_Plugin')) {
            $html .= "<li>Премахнати са {$delCnt} закачания на плъгина";
        } else {
            $html .= '<li>Не са премахнати закачания на плъгина';
        }
        
        return $html;
    }
}
