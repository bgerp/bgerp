<?php


/**
 * Минимален брой групи, необходими за да се покаже страничната навигация
 */
defIfNot('EPAY_MIN', '');


/**
 * Минимален брой групи, необходими за да се покаже страничната навигация
 */
defIfNot('EPAY_CHECKSUM', '');


/**
 * Пакет за интеграция с ePay.bg
 *
 * @category  bgerp
 * @package   epay
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class epay_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Интеграция с ePay.bg, In development';
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'epay_driver_OnlinePayment';


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'EPAY_MIN' => array('varchar', 'caption=MIN'),
        'EPAY_CHECKSUM' => array('varchar', 'caption=CHECKSUM'),
    );
    

    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'epay_Tokens',
    );
}
