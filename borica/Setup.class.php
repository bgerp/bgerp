<?php


/**
 *
 *
 * @category  vendors
 * @package   borica
 *
 * @author    Yusein Yuseino <yyuusenov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class borica_Setup extends core_ProtoSetup
{
    
    /**
     * Необходими пакети
     */
    public $depends = 'peripheral=0.1';
    
    
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'ПОС на Борика';
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'borica_POS';
}
