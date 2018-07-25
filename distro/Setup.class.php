<?php


/**
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с distro
 *
 * @category  bgerp
 * @package   distro
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class distro_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'distro_Group';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Разпределена файлова група';
    
    
    /**
     * Необходими пакети
     */
    public $depends = 'ssh=0.1';
    
    
    /**
     * Мениджъри за инсталиране
     */
    public $managers = array(
        'distro_Group',
        'distro_Files',
        'distro_Automation',
        'distro_Repositories',
        'distro_Actions',
        'distro_RenameDriver',
        'distro_DeleteDriver',
        'distro_CopyDriver',
        'distro_AbsorbDriver',
        'distro_ArchiveDriver',
    );
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.9, 'Документи', 'Дистрибутив', 'distro_Group', 'default', 'admin'),
    );
}
