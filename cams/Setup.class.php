<?php

/**
 * Колко да е продължителността на един клип в секунди
 */
defIfNot('cams_CLIP_DURATION', 5 * 60);

/**
 * Колко клипа да показва на страница при широк екран
 */
defIfNot('cams_CLIPS_PER_WIDE_PAGE', 144);


/**
 * Колко клипа да показва на страница при тесен екран
 */
defIfNot('cams_CLIPS_PER_NARROW_PAGE', 12);


/**
 * Колко клипа да показва на ред при широк екран
 */
defIfNot('cams_CLIPS_PER_WIDE_ROW', 6);


/**
 * Колко клипа да показва на ред при тесен екран
 */
defIfNot('cams_CLIPS_PER_NARROW_ROW', 1);


/**
 * Колко да е минималното дисково пространство
 */
defIfNot('cams_MIN_DISK_SPACE', 100 * 1024 * 1024 * 1024);


/**
 * class acc_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със счетоводството
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cams_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cams_Cameras';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Видео наблюдение и записване";
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
    
            // Колко да е продължителността на един клип в секунд
            'CAMS_CLIP_DURATION' => array ('int', 'mandatory'),
    
            // Колко клипа да показва на страница при широк екран
            'CAMS_CLIPS_PER_WIDE_PAGE'   => array ('int', 'mandatory'),
    
            // Колко клипа да показва на ред при широк екран
            'CAMS_CLIPS_PER_WIDE_ROW'   => array ('int', 'mandatory'),
    
            // Колко клипа да показва на страница при тесен екран
            'CAMS_CLIPS_PER_NARROW_PAGE'   => array ('int', 'mandatory'),
    
            // Колко клипа да показва на ред при тесен екран
            'CAMS_CLIPS_PER_NARROW_ROW'   => array ('int', 'mandatory'),
    
            // Колко да е минималното дисково пространство
            'CAMS_MIN_DISK_SPACE'   => array ('int', 'mandatory'),
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'cams_Cameras',
            'cams_Records',
            'cams_Positions'
        );
        
        // Роля за power-user на този модул
        $role = 'cams';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        core_Classes::add('cams_driver_UIC');
        core_Classes::add('cams_driver_Mockup');
        core_Classes::add('cams_driver_Edimax');
        core_Classes::add('cams_driver_UIC9272');
        
        $Menu = cls::get('bgerp_Menu');
        $Menu->addItem(3, 'Мониторинг', 'Камери', 'cams_Cameras', 'default', "{$role}, admin");
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}