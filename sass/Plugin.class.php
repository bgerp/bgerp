<?php


/**
 * Плъгин за конвертиране на SASS файлве в CSS
 *
 * @category  vendors
 * @package   sass
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sass_Plugin extends core_Plugin
{
    
    
    /**
     * Прихваща извикването на AfterConvertSass и конвертира SASS към CSS
     */
    function on_BeforeGetSbfFilePath($mvc, &$res, &$path)
    { 
        
        $pathArr = pathinfo($path);

        if($pathArr['extension'] == 'css') {
            $scssRelPath = $pathArr['dirname'] . '/' . $pathArr['filename'] . '.scss';
            $scssFile = getFullPath($scssRelPath);
            if(file_exists($scssFile)) {
                $scssFileArr = pathinfo($scssFile);
                $time = core_Os::getTimeOfLastModifiedFile(dirname($scssFile), '', "#[a-z0-9\-\/_]+(.scss)$#i"); 
                $cssPath = $scssFileArr['dirname'] . '/' . $pathArr['basename']; 
                if(!file_exists($cssPath) || $time > filemtime($cssPath)) {
                    $cssCode = sass_Converter::convert($scssFile, TRUE);
                    file_put_contents($cssPath, $cssCode);  
                }
            }
        }

    }
}