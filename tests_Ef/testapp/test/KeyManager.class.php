<?php
class test_KeyManager extends test_Manager
{
    public function description()
    {
        $this->FLD('keyIdSelect', 'customKey(mvc=test_Key, key=code, select=title)');
        $this->FLD('keyIdCombo', 'customKey(mvc=test_Key, key=code, select=title)');
    }
}
