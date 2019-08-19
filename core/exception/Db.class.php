<?php


class core_exception_Db extends core_exception_Expect
{
    /**
     * Изключение за липсваща база данни ли е?
     */
    public function isNotExistsDB()
    {
        $res = isset($this->dump['mysqlErrCode']) && ($this->dump['mysqlErrCode'] == 1046 || $this->dump['mysqlErrCode'] == 1049);
        
        return $res;
    }
    
    
    /**
     * Изключение за липсваща база данни ли е?
     */
    public function isNotInitializedDB()
    {
        $res = isset($this->dump['mysqlErrCode']) && ($this->dump['mysqlErrCode'] == 1146 || $this->dump['mysqlErrCode'] == 1054);
        
        return $res;
    }
    
    
    /**
     * Връща MYSQLI линк, в който е възникнало изключението
     */
    public function getDbLink()
    {
        $res = $this->dump['dbLink'];
        
        return $res;
    }
    
    
    /**
     * Опит за самопоправка на DB
     */
    public function repairDB($link)
    {
        $tableName = null;
        
        if (strlen($this->dump['query']) && isset($this->dump['mysqlErrCode'])) {
            list($l, $r) = explode('FROM', $this->dump['query']);
            $q = $r ? $r : $l;
            $parts = explode('`', $q);
            $tableName = $parts[1];
        }
        
        if (isset($tableName) && ($this->dump['mysqlErrCode'] == 1062)) {
            $query = "SELECT max(id) as m FROM `{$tableName}`";
            $dbRes = $link->query($query);
            $res = $dbRes->fetch_object();
            $autoIncrement = $res->m + 10;
            $link->query("ALTER TABLE `{$tableName}` AUTO_INCREMENT = {$autoIncrement}");
        }
        
        if (isset($tableName) && in_array($this->dump['mysqlErrCode'], array(126, 127, 132, 134, 141, 144, 145, 1194))) {
            $query = "REPAIR TABLE `{$tableName}`";
            $dbRes = $link->query($query);
        }
    }
}
