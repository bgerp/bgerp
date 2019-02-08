<?php


/**
 * Път до външния пакет
 */
defIfNot('JSSOR_VERSION', '1.0');


/**
 * Клас 'jssor_setup'
 *
 * Setup за пакета jssor
 *
 * @category  bgerp
 * @package   jssor
 *
 * @author    Nevena Georgieva <nevena@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @link     https://www.jssor.com/
 */
class jssor_Setup extends core_ProtoSetup
{
    /**
     * Описание на модула
     */
    public $info = 'Адаптер за jssor - слайдер за картинки';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'JSSOR_VERSION' => array('enum(1.0)', 'mandatory, caption=Версията на програмата->Версия')
    
    );
    
  
}
