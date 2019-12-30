<?php



/**
 * Клас 'core_SystemData' - Глобални системни данни
 *
 *
 * @category  bgerp
 * @package   core
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_SystemData
{
    public static $isLocked = false;
    
    
    /**
     * Задава съдържанието на файла
     */
    public static function set($name, $value = 1)
    {
        return @file_put_contents(self::getPath($name), $value);
    }
    

    /**
     * Добавя към съдържанието на файла
     */
    public static function append($name, $value)
    {
        return @file_put_contents(self::getPath($name), $value, FILE_APPEND);
    }
    

    /**
     * Връща съдържанието на файла
     */
    public static function get($name)
    {
        return @file_get_contents(self::getPath($name));
    }
    

    /**
     * Дали съществува файла
     */
    public static function isExists($name)
    {
        return file_exists(self::getPath($name));
    }
    

    /**
     * Връща размера на файла
     */
    public static function getSize($name)
    {
        return @filesize(self::getPath($name));

    }
    
    
    /**
     * Връща съдържанието от файла и го изпразва
     */
    public static function take($name)
    {
 
    }

    /**
     * Премахва файла
     */
    public static function remove($name)
    {
        return @unlink(self::getPath($name));
    }
    
    
    /**
     * Връща пътя до файла
     */
    private static function getPath($name)
    {
        core_Os::forceDir($dir = core_Os::normalizeDir(EF_UPLOADS_PATH) . '/data');

        $path = $dir . '/' .  $name;

        return $path;
    }
}
