<?php


/**
 * Път до външния пакет
 */


/**
 * Клас 'flipdown_Setup'
 *
 * Setup за пакета flipdown
 *
 * @category  bgerp
 * @package   flipdown
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 */
class flipdown_Setup extends core_ProtoSetup
{
    /**
     * Описание на модула
     */
    public $info = 'Адаптер за flipdown - намаляващ брояч';
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'flipdown_Driver';
}
