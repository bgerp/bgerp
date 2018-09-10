<?php


/**
 * Аналогов изход
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_ioport_AO extends sens2_ioport_Abstract
{
    /**
     * Типът слотове за сензорите от този вид
     */
    const SLOT_TYPES = 'AO';
    
    
    /**
     * Описание на порта
     */
    protected $description = array(
        'ao' => array(
            'subname' => null,
            'uom' => null,
            'options' => null,
            'min' => 0,
            'max' => 10,
            'readable' => false,
            'writable' => true,
        ),
    );
}
