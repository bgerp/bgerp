<?php


/**
 * Коя да е основната мярка на универсалните артикули
 */
defIfNot('VTOTAL_VIRUSTOTAL_API_KEY', '');





/**
 * class vtotal_Setup
 * Setup VitusTotal
 *
 * @category  bgerp
 * @package   cond
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class vtotal_Setup extends core_ProtoSetup
{

    function install()
    {
        $html = parent::install();

        return $html;
    }

    var $managers = array(
        'vtotal_Checks'
    );
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        'VTOTAL_VIRUSTOTAL_API_KEY' => array("varchar", 'caption=Ключ за API системата на '),
    );

    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
        array(
            'systemId' => "MoveFilesFromFilemanLog",
            'description' => "Преместване на съмнителните файлове в vtotal_Checks",
            'controller' => "vtotal_Checks",
            'action' => "MoveFilesFromFilemanLog",
            'period' => 1,
            'timeLimit' => 200
        ),

        array(
            'systemId' => "VTCheck",
            'description' => "Прошерка на файлошете с virustotal",
            'controller' => "vtotal_Checks",
            'action' => "VTCheck",
            'period' => 1.2,
            'timeLimit' => 200
        ),
    );
}