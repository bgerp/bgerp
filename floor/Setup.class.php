<?php


/**
 * class floor_Setup
 *
 * Инсталиране/Деинсталиране на пакета floor
 *
 *
 * @category  bgerp
 * @package   floor
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class floor_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    public $depends = '';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    public $startCtr = 'floor_Plans';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Планове на помещения';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'floor_Plans',
        'floor_Objects',
    );
    
    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('floor'),
    );
}
