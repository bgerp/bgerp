<?php

include __DIR__ . '/FirePHPCore-0.3.2/lib/FirePHPCore/fb.php';

class firephp_FirePHP
{
    public static function info() 
    {
        $args = func_get_args();
        fb($args);
    }
}
