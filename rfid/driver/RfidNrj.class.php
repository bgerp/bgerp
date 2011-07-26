<?php

/**
 * Драйвер за RFID четец - съдържа данни за връзка с NRJ Mysql сървър за синхронизиране на данните с входа и изхода
 */
class rfid_driver_RfidNrj extends rfid_driver_IpDeviceMysql
implements intf_IpRfid {
    
    
    /**
     * Връща масив със стойностите на четците след определена дата в MySQL формат
     */
    function getData($date="2011-06-01 00:00:00")
    {
        
        $dbNrj = cls::get('core_Db');
        
        $dbNrj->init(array(dbName=>"{$this->dbName}",
            dbUser=>"{$this->dbUser}",
            dbPass=>"{$this->dbPass}",
            dbHost=>"{$this->dbHost}")
        );
        $dbNrj->connect();
        $dbNrj->query("SELECT AES_DECRYPT(`controller`.IP, \"r38gt5\"), `controller`.PORT, `log`.RFID_READER, `log`.RFID_CARD, `log`.DATE_TIME_
                       FROM `log` left join `controller` on `log`.ID_CONTROLLER = `controller`.ID
                       WHERE `log`.DATE_TIME_ >='" . $date . "' 
                       AND AES_DECRYPT(`controller`.IP, \"r38gt5\")='" . $this->ip ."' 
                       AND RFID_READER='" . $this->type ."'");
        
        while ($res = $dbNrj->fetchObject()) {
            $r[] = $res;
        }
        
        return $r;
    }
    
    
    /**
     *  Инициализиране на обекта
     */
    function init($params)
    {
        $initParams = array(dbName=>'nrj_base001',
            dbUser=>'nrjsoft',
            dbPass=>'nrjsoft',
            dbHost=>'10.1.0.108');
        
        $params += $initParams;
        
        parent::init($params);
    }
}