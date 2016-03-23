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
        if(isset($this->dump['mysqlErrCode']) && ($this->dump['mysqlErrCode'] ==  1062)) {
            $parts = explode('`', $this->dump['query']);
            $table = $parts[1];
            $query = "SELECT max(id) as m FROM `{$table}`";
            $dbRes = $link->query($query);  
            $res = $dbRes->fetch_object();
            $link->query("ALTER TABLE `{$table}` AUTO_INCREMENT = {$res->m}+10");
        }
        
        if(isset($this->dump['mysqlErrCode']) && in_array($this->dump['mysqlErrCode'], array(126, 127, 132, 134, 141, 144, 145)) ) {
            $parts = explode('`', $this->dump['query']);
            $table = $parts[1];
            $query = "REPAIR TABLE `{$table}`";
            $dbRes = $link->query($query);  
        }
    }
}
