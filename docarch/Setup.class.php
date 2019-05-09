<?php


/**
 * Период на който се прави проверка за томове с изтекъл срок за съхранение
 * Всеки петък в 4:00 през нощта
 */
defIfNot('STORAGETIME_CHECK_PERIOD', 1 * 24 * 60);


/**
 * Отместване за пълния бекъп
 */
defIfNot('STORAGETIME_CHECK_OFFSET', 0 * 0);


/**
 * class docarch_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със архивирането
 * и съхранението на документи
 *
 *
 * @category  bgerp
 * @package   docarch
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class docarch_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'docarch_Movement';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Управление на физически архиви';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'docarch_Archives',
        'docarch_Movements',
        'docarch_Volumes'
    
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('docarch'),
        array('docarchMaster', 'docarch'),
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.95, 'Документи', 'Архив', 'docarch_Movements', 'default', 'ceo,docarchMaster,docarch'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $Plugins = cls::get('core_Plugins');
        
        // Залагаме в cron
        $rec = new stdClass();
        $rec->systemId = 'DocarchChekOutOfStorageTime';
        $rec->description = 'Проверка за томове с изтекъл срок на съхранение';
        $rec->controller = 'docarch_Volumes';
        $rec->action = 'chekOutOfStorage';
        $rec->period = STORAGETIME_CHECK_PERIOD;
        $rec->offset = STORAGETIME_CHECK_OFFSET;
        $rec->delay = 0;
        $rec->timeLimit = 2400;
        $html .= core_Cron::addOnce($rec);
        
        $html .= $Plugins->installPlugin('Плъгин за архивиране на документи', 'docarch_plg_Archiving', 'core_Manager', 'family');
        
        return $html;
    }
}
