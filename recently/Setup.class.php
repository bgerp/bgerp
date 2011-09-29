<?php

/**
 *  class recently_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  Подсказки за инпут полетата
 *
 *
 */
class recently_Setup {
    
    
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'recently_Values';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';

    
    /**
     * Описание на модула
     */
    var $info = "Запомняне в избрани полета на последно въведените данни";

    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        // Установяваме мениджъра;
        $Values = cls::get('recently_Values');
        $html .= $Values->setupMVC();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме recently към формите
        $Plugins->installPlugin('Recently', 'recently_Plugin', 'core_Form', 'private');
        $html .= "<li>Закачане към формите (Активно)";
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        return "";
    }
}