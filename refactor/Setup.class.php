<?php


/**
 * class refactor_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с пакета php
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class refactor_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'refactor_Formater';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Рефакторинг';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'refactor_Formater',
    
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'developer';
    
    
}
