<?php
 

class core_DbSessData extends core_Manager
{
    public $title = 'DB Сесии - данни';
    public $singleTitle = 'Сесийна променлива';

    public $canList   = 'admin';
    public $canAdd    = 'no_one';
    public $canEdit   = 'no_one';
    public $canDelete = 'admin';

    public $doReplication = false;
    public static $stopCaching = false;

    public $loadList = 'plg_Sorting,plg_RowTools';

 
    public function description()
    {
        $this->FLD('key',    'varchar(32)', 'caption=Ключ,notNull');
        $this->FLD('time',    'int', 'caption=Време');
        $this->FLD('data', 'text(1000000000)', 'caption=Данни');
        $this->setDbUnique('key');
    }

    public function setData($key, $data)
    {
        $key = md5($key);
        $rec = $this->fetch("#key = '{$key}'");

        if($rec) 
    }

    private function getTime()
    {
        $mod = 
    }

    
}
