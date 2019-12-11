<?php


/**
 * Път до външния пакет
 */
defIfNot('SLICK_VERSION', '1.9');


/**
 * Клас 'slick_Setup'
 *
 * Setup за пакета slick
 *
 * @category  bgerp
 * @package   slick
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @link      http://fancybox.net/
 */
class slick_Setup extends core_ProtoSetup
{
    /**
     * Описание на модула
     */
    public $info = 'Адаптер за slick - слайдер за картинки';
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'slick_Driver';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'SLICK_VERSION' => array('enum(1.8,1.9)', 'mandatory, caption=Версията на програмата->Версия')
    
    );
}
