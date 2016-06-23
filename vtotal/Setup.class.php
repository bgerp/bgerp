<?php


/**
 * Коя да е основната мярка на универсалните артикули
 */
defIfNot('VTOTAL_API_KEY', '');
defIfNot('VTOTAL_NUMBER_OF_ITEMS_TO_SCAN_BY_VIRUSTOTAL', '3');
defIfNot('VTOTAL_BETWEEN_TIME_SCANS', '864000'); // Десет дена



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
        'VTOTAL_API_KEY' => array("varchar", 'caption=Ключ за API системата на '),
        'VTOTAL_BETWEEN_TIME_SCANS' => array ('time(suggestions=5 дена|10 дена)', 'caption=Времете между което ще се пуска VirusTotal за неопределените, миналото
        сканирване файлове'),
        'VTOTAL_NUMBER_OF_ITEMS_TO_SCAN_BY_VIRUSTOTAL' => array("int", 'caption=По колко файла да се вземат от VirusTotal за сканирване'),
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
            'timeLimit' => 50
        ),

        array(
            'systemId' => "VTCheck",
            'description' => "Проверка на файловете с virustotal",
            'controller' => "vtotal_Checks",
            'action' => "VTCheck",
            'period' => 1,
            'delay' => 15,
            'timeLimit' => 40
        ),
    );



    /**
     * Проверява дали програмата е инсталирана в сървъра
     *
     * @return NULL|string
     */
    function checkConfig()
    {
        exec("avast -h", $output, $code);
        if ($code == 127) {
            $haveError = TRUE;
        }
        if ($haveError) {
            return "Avast Scan за Linux не е инсталирана.";
        }
    }
}
