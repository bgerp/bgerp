<?php

include __DIR__ . '/ChromePhp.php';

class chromephp_ChromePHP
{
    public static function info()
    {
        call_user_func_array(array('ChromePhp', 'info'), func_get_args());
    }
}