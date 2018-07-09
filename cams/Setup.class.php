<?php


/**
 * Колко да е продължителността на един клип в секунди
 */
defIfNot('CAMS_CLIP_DURATION', 5 * 60);


/**
 * Колко клипа да показва на страница при широк екран
 */
defIfNot('CAMS_CLIPS_PER_WIDE_PAGE', 144);


/**
 * Колко клипа да показва на страница при тесен екран
 */
defIfNot('CAMS_CLIPS_PER_NARROW_PAGE', 12);


/**
 * Колко клипа да показва на ред при широк екран
 */
defIfNot('CAMS_CLIPS_PER_WIDE_ROW', 6);


/**
 * Колко клипа да показва на ред при тесен екран
 */
defIfNot('CAMS_CLIPS_PER_NARROW_ROW', 1);


/**
 * Колко да е минималното дисково пространство
 */
defIfNot('CAMS_MIN_DISK_SPACE', 100 * 1024 * 1024 * 1024);


/**
 * class acc_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със счетоводството
 *
 *
 * @category  bgerp
 * @package   cams
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cams_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'cams_Cameras';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Видео наблюдение и записване на IP камери';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        // Колко да е продължителността на един клип в секунд
        'CAMS_CLIP_DURATION' => array('time(suggestions=1 мин.|2 мин.|3 мин.|4 мин.|5 мин.|10 мин.)', 'mandatory, caption=Колко да е продължителността на един запис?->Продължителност'),
        
        // Колко да е минималното дисково пространство
        'CAMS_MIN_DISK_SPACE' => array('fileman_FileSize', 'mandatory, caption=Колко да е минималното дисково пространство?->Размер'),
        
        // Колко клипа да показва на ред при широк екран
        'CAMS_CLIPS_PER_WIDE_ROW' => array('int', 'mandatory, caption=Колко записа да се показват при широк екран?->Колони в един ред'),
        
        // Колко клипа да показва на страница при широк екран
        'CAMS_CLIPS_PER_WIDE_PAGE' => array('int', 'mandatory, caption=Колко записа да се показват при широк екран?->На една страница'),
        
        // Колко клипа да показва на ред при тесен екран
        'CAMS_CLIPS_PER_NARROW_ROW' => array('int', 'mandatory, caption=Колко записа да се показват при мобилен режим?->Колони в един ред'),
        
        // Колко клипа да показва на страница при тесен екран
        'CAMS_CLIPS_PER_NARROW_PAGE' => array('int', 'mandatory, caption=Колко записа да се показват при мобилен режим?->На една страница'),
    
    
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'cams_Cameras',
        'cams_Records',
        'cams_Positions'
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'cams';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.4, 'Мониторинг', 'Камери', 'cams_Cameras', 'default', 'cams, ceo, admin'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $html .= core_Classes::add('cams_driver_UIC');
        $html .= core_Classes::add('cams_driver_UIC9272');
        $html .= core_Classes::add('cams_driver_Edimax');
        $html .= core_Classes::add('cams_driver_EdimaxIC9000');
        $html .= core_Classes::add('cams_driver_Hikvision');
        $html .= core_Classes::add('cams_driver_Mockup');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
