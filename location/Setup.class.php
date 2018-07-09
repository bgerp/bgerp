<?php


defIfNot('LOCATION_DEFAULT_REGION', '');


/**
 *
 *
 * @category  bgerp
 * @package   location
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2012 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class location_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Локация';
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'LOCATION_DEFAULT_REGION' => array('varchar', 'mandatory, caption=Кой регион да се използва по подрабиране->Регион'),
    );
}
