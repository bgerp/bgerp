<?php
/**
 * class schema_Setup
 *
 * Инсталиране/Деинсталиране на пакета 'schema'
 *
 * @category  ef
 * @package   schema
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class schema_Setup extends core_ProtoSetup 
{
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    

    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'schema_Migrations';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Управление на схемата на базата данни";
    

    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'schema_Migrations',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        // Зареждаме мениджъра на плъгините
        /* @var $Plugins core_Plugins */
        $Plugins = cls::get('core_Plugins');
        $Plugins->deinstallPlugin('schema_plg_Wrapper');

        $Wrapper = $this->getWrapper('schema_Migrations');
        
        if ($Wrapper) {
            $wrapperName = cls::getClassName($Wrapper);
            
            // Инсталираме плъгина за интеграция на пакета в потребителския интерфейс
            $html .= $Plugins->forcePlugin('Миграции на БД', 'schema_plg_Wrapper', $wrapperName, 'private');
        } else {
            $html .= 'Липсва wrapper на класа schema_Migrations';
        }
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    }
    
    
    /**
     * Връща инстанция на wrapper-а на зададен мениджър
     * 
     * @param string $manager
     * @return plg_ProtoWrapper
     */
    protected function getWrapper($manager)
    {
        $manager = cls::get($manager);
        
        $loadList = arr::make($manager->loadList, TRUE);
        
        foreach ($loadList as $cls) {
            $cls = cls::get($cls);
            
            if ($cls instanceof plg_ProtoWrapper) {
                return $cls;
            }
        }
        
        return NULL;
    }
    
}