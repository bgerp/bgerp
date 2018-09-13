<?php


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
    public $info = 'Интеграция с ePay.bg';
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'epay_driver_OnlinePayment';


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'epay_Tokens',
    );
}
