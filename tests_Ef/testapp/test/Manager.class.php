<?php
class test_Manager extends core_Manager
{
    public function drop()
    {
        return $this->db->query("DROP TABLE {$this->dbTableName}");
    }
}
