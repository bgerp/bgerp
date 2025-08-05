<?php


/**
 * Път до външния пакет
 */


/**
 * Клас 'flip_Setup'
 *
 * Setup за пакета flip
 *
 * @category  bgerp
 * @package   flip
 *
 * @author    Nevena Vitkinova <nevena@experta.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 */
class flip_Setup extends core_ProtoSetup
{
    /**
     * Описание на модула
     */
    public $info = 'Адаптер за flip - намаляващ брояч';
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'flip_Driver';
}
