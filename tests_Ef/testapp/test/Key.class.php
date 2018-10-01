<?php
class test_Key extends test_Manager
{
    public function description()
    {
        $this->FLD('code', 'varchar(16)');
        $this->FLD('title', 'varchar(64)');
    }
    
    
    /**
     * Зареждане на тестови данни (фикстури)
     *
     * @param test_Key $mvc
     */
    public function on_AfterSetupMvc($mvc)
    {
        $fixture = array(
            array('code' => 'CODE1', 'title' => 'title 1'),
            array('code' => 'CODE2', 'title' => 'title 2'),
            array('code' => 'CODE3', 'title' => 'title 3'),
            array('code' => 'CODE4', 'title' => 'CODE2'),
        );
        
        // Изтриване на (евентуални) стари данни
        $mvc->db->query("TRUNCATE TABLE `{$mvc->dbTableName}`");
        
        // Зареждане на "чисти" данни
        foreach ($fixture as $r) {
            $mvc->save((object) $r);
        }
    }
}
