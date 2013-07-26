<?php

include __DIR__ . '/ChromePhp.php';

class chromephp_ChromePHP
{
    public static function setup()
    {
        core_App::$debugHandler = array('ChromePhp', 'info');
    }
}