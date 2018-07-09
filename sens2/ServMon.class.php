<?php


/**
 * Драйвер за наблюдение състоянието на сървъра
 *
 *
 * @category  bgerp
 * @package   sens
 *
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Сървърен мониторинг
 */
class sens2_ServMon extends sens2_ProtoDriver
{
    /**
     * Заглавие на драйвера
     */
    public $title = 'Сървърен мониторинг';
    
    
    /**
     * Интерфейси, поддържани от всички наследници
     */
    public $interfaces = 'sens2_DriverIntf';
    
    
    public function getInputPorts($config = null)
    {
        return array(
            'freeRam' => (object) array('caption' => 'Свободна RAM', 'uom' => '%'),
            'freeDir1' => (object) array('caption' => 'Свободна памет в Dir1', 'uom' => 'B'),
            'freeDir2' => (object) array('caption' => 'Свободна памет в Dir2', 'uom' => 'B'),
            'mysqlCnt' => (object) array('caption' => 'MySQL връзки'),
            
            'proc1cnt' => (object) array('caption' => 'Стартирани Proc1'),
            'proc2cnt' => (object) array('caption' => 'Стартирани Proc2'),
            'proc3cnt' => (object) array('caption' => 'Стартирани Proc3'),
            
            'conn1' => (object) array('caption' => 'Връзка до conn1'),
            'conn2' => (object) array('caption' => 'Връзка до conn2'),
            'conn3' => (object) array('caption' => 'Връзка до conn3'),
            
            'cpuLoad' => (object) array('caption' => 'Натоварване процесор'),
        
        );
    }
    
    
    public function prepareConfigForm($form)
    {
        $form->FLD('dir1', 'varchar', 'caption=Пътища->Dir1');
        $form->FLD('dir2', 'varchar', 'caption=Пътища->Dir1');
        
        $form->FLD('proc1', 'identifier(allowed=.)', 'caption=Процеси->Proc1');
        $form->FLD('proc2', 'identifier(allowed=.)', 'caption=Процеси->Proc2');
        $form->FLD('proc3', 'identifier(allowed=.)', 'caption=Процеси->Proc3');
        
        $form->FLD('conn1', 'varchar', 'caption=Връзки->Conn1');
        $form->FLD('conn2', 'varchar', 'caption=Връзки->Conn2');
        $form->FLD('conn3', 'varchar', 'caption=Връзки->Conn3');
        $form->FLD('cpuLoad', 'varchar', 'caption=Процесор->cpuLoad');
    }
    
    
    public function checkConfigForm($form)
    {
    }
    
    
    /**
     * Прочитане на входовете
     */
    public function readInputs($inputs, $config, &$persistentState)
    {
        if ($inputs['freeRam']) {
            $os = cls::get('core_Os');
            $res['freeRam'] = $os->getMemoryUsage();
        }
        
        if ($inputs['freeDir1']) {
            $res['freeDir1'] = $this->getFreeDiskSpace($config->dir1);
        }
        
        if ($inputs['freeDir2']) {
            $res['freeDir2'] = $this->getFreeDiskSpace($config->dir2);
        }
        
        if ($inputs['mysqlCnt']) {
            $res['mysqlCnt'] = $this->countMysqlConnections();
        }
        
        // Проверка на броя процеси
        if ($inputs['proc1cnt']) {
            $os = cls::get('core_Os');
            $res['proc1cnt'] = $os->countProc($config->proc1);
        }
        
        if ($inputs['proc2cnt']) {
            $os = cls::get('core_Os');
            $res['proc2cnt'] = $os->countProc($config->proc2);
        }
        
        if ($inputs['proc3cnt']) {
            $os = cls::get('core_Os');
            $res['proc3cnt'] = $os->countProc($config->proc3);
        }
        
        
        // Проверка на връзката към отдалечени сървъри
        if ($inputs['conn1']) {
            $res['conn1'] = $this->checkConnection($config->conn1);
        }
        
        if ($inputs['conn2']) {
            $res['conn2'] = $this->checkConnection($config->conn2);
        }
        
        if ($inputs['conn3']) {
            $res['conn3'] = $this->checkConnection($config->conn3);
        }
        
        // Проверка натовареността на процесора
        $res['cpuLoad'] = self::getServerLoad();
        
        return $res;
    }
    
    
    /**
     * Връща натоварването на сървъра
     */
    public static function getServerLoad()
    {
        if (stristr(PHP_OS, 'win')) {
            exec('wmic cpu get LoadPercentage', $p);
            
            return $p[2];
        }
        
        $sysLoad = sys_getloadavg();
        
        if ($inputs['cpuLoad']) {
            $load = $sysLoad[0];
        }
        
        return (int) $load;
    }
    
    
    /**
     * Проверява дали имаме http връзка с даден адрес
     */
    public function checkConnection($url)
    {
        list($domain, $port) = explode(':', $url);
        
        if (!$port) {
            $port = '80';
        }
        
        $res = @fsockopen($domain, round($port), $errno, $errstr, 3) ? 1 : 0;
        
        return $res;
    }
    
    
    /**
     * Връща броя на MySQL връзките
     */
    public function countMysqlConnections()
    {
        $db = cls::get('core_Db');
        $dbRes = $db->query("SHOW STATUS WHERE `variable_name` = 'Threads_connected'");
        $res = $db->fetchObject($dbRes);
        
        return $res->Value;
    }
    
    
    /**
     * Проверява колко свободно място има на дадената директория
     */
    public function getFreeDiskSpace($path)
    {
        if (file_exists($path)) {
            $res = disk_free_space($path);
        } else {
            $res = "Не съществуваща директория ${path}";
        }
        
        return $res;
    }
    
    
    /**
     * Записва стойностите на изходите на контролера
     *
     * @param array $outputs         масив със системните имена на изходите и стойностите, които трябва да бъдат записани
     * @param array $config          конфигурациони параметри
     * @param array $persistentState персистентно състояние на контролера от базата данни
     *
     * @return array Масив със системните имена на изходите и статус (TRUE/FALSE) на операцията с него
     */
    public function writeOutputs($outputs, $config, &$persistentState)
    {
    }
}
