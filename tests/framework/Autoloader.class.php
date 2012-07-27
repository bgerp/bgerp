<?php
class framework_Autoloader
{
    static public function load($className)
    {
        require dirname(__DIR__)
            . DIRECTORY_SEPARATOR 
            . static::getClassPath($className) 
            . '.class.php';
    }
    
    static public function getClassPath($className)
    {
        $parts = explode('_', $className);
        
        return implode(DIRECTORY_SEPARATOR, $parts);
    }
}

spl_autoload_register('framework_Autoloader::load', true);