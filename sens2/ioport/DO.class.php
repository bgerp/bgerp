<?php


/**
 * Цифров изход
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_ioport_DO extends sens2_ioport_Abstract
{
    /**
     * Типът слотове за сензорите от този вид
     */
    const SLOT_TYPES = 'DO,RO';
    
    
    /**
     * Описание на порта
     */
    protected $description = array(
        'do' => array(
            'name' => null,
            'uom' => null,
            'options' => array(0,1),
            'min' => 0,
            'max' => 1,
            'readable' => true,
            'writable' => true,
        ),
    );
}
