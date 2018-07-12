<?php
class test_Setup
{
    public function install()
    {
        $managers = array(
            'test_Key',
            'test_KeyManager',
        );
        
        foreach ($managers as $manager) {
            $instances[$manager] = cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        return $html;
    }
}
