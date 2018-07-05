<?php


/**
 * Версията на chartjs, която се използва
 */
defIfNot('CHARTJS_VERSION', '2.3.0');


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
    public $info = 'Изчертаване на графики';


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'chartjs_Adapter'
    );

    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CHARTJS_VERSION' => array('enum(2.3.0)', 'caption=Версия на chartjs->Версия')
    );
}
