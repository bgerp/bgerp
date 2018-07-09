<?php


/**
 * Клас 'payment_Setup' - Пакет за работа с електронни банкови извлечения
 *
 *
 * @category  bgerp
 * @package   payment
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class payment_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Обработка на платежни банкови документи';
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'payment_ImportDriver';
}
