<?php


/**
 * Версията на chartjs, която се използва
 */
defIfNot('CHARTJS_VERSION', '1.0.2');


/**
 * 
 * 
 * @category  bgerp
 * @package   chartjs
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class chartjs_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Описание на модула
     */
    public $info = "Изчертаване на графики";


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        'chartjs_Adapter'
    );

    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'JQUERY_VERSION' => array ('enum(1.0.2)', 'caption=Версия на chartjs->Версия')
    );


}
