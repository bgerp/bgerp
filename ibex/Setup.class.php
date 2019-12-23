<?php


/**
 * Клас 'ibex_Setup'
 *
 * Исталиране/деинсталиране на ibex - пакет за получаване на данни от енергийната борса ibex.bg
 *
 *
 * @category  bgerp
 * @package   ibex
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class ibex_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Извличане на данни от енергийната борса ibex.bg';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'ibex_Register';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
   /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'ibex_Register',
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'ibex_Sensor';
    

    /**
     * Име на кофата за файлове
     */
    const BUCKET = 'IbexBG';


    /**
     * Настройки за Cron
     */
    public $cronSettings = array(
        array(
            'systemId' => 'Fetching ibex.bg',
            'description' => 'Извличане на данни от ibex.bg',
            'controller' => 'ibex_Register',
            'action' => 'Retrieve',
            'period' => 1440,
            'offset' => 840,
            'timeLimit' => 200,
        ));
    
    
    /**
     * Зареждане на начални данни
     */
    public function loadSetupData($itr = '')
    {
        $html = parent::loadSetupData($itr);
        
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket(self::BUCKET, 'Данни от енергийната борса', 'xls,csv/*', '1MB', 'user', 'every_one');
        
        return $html;
    }
}
