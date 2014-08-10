<?php



/**
 * Клас 'core_Sbf'
 *
 *
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Sbf extends core_Mvc
{

    static function convertUrlToPath($url)
    {
        list($first, $last) = explode('/' . EF_SBF . '/' . EF_APP_NAME . '/', $url);
        $path = EF_SBF_PATH . '/' . $last;
        
        return $path;
    }


    /**
     * Записва посоченото съдържание на указания път
     * Връща FALSE при грешка или пълния път до новозаписания файл
     */
    static function saveFile_($content, $path, $isFullPath = FALSE)
    {
        if(!$isFullPath) {
            $path = EF_SBF_PATH . '/' . $path;
        }
 

        if(file_put_contents($path, $content) !== FALSE) {

            return $path;
        }

        return FALSE;
    }
    
}