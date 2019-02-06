<?php


/**
 * Път до външния пакет
 */
defIfNot('SWIPER_VERSION', '4.4.6');


/**
 * Клас 'swiper_setup'
 *
 * Setup за пакета swiper
 *
 * @category  bgerp
 * @package   swiper
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @link      http://fancybox.net/
 */
class swiper_Setup extends core_ProtoSetup
{
    /**
     * Описание на модула
     */
    public $info = 'Адаптер за swiper - слайдер за картинки';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'SWIPER_VERSION' => array('enum(4.4.6)', 'mandatory, caption=Версията на програмата->Версия')
    
    );
    
  
}
