<?php


/**
 * Клас 'ssh_Setup'
 *
 * Исталиране/деинсталиране на ssh
 *
 *
 * @category  bgerp
 * @package   ssh
 *
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class ssh_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    public $startCtr = 'ssh_Hosts';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    public $startAct = 'default';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'ssh_Hosts',
    
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'remote';
    
    
    /**
     * Описание на модула
     */
    public $info = 'SSH машини';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
       // Махане на ssh машини от менюто
       // array(1.8, 'Система', 'Машини', 'ssh_Hosts', 'default', 'ceo, remote, admin'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        return $html;
    }
}
