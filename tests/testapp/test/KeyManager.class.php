<?php
class test_KeyManager extends test_Manager
{
    public function description()
    {
        $this->FLD('keyId', 'customKey(mvc=test_Key, key=code, select=title)');
    }
}