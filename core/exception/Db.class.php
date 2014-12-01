<?php

class core_exception_Db extends core_exception_Expect
{
    /**
     * Изключение за липсваща база данни ли е?
     */
    public function isNotExistsDB()
    {
        $res = isset($this->dump['mysqlErrCode']) && ($this->dump['mysqlErrCode'] == 1049);

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
}