<?php


/**
 * Плъгин за конвертиране на SASS файлве в CSS
 *
 * @category  vendors
 * @package   sass
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sass_Plugin extends core_Plugin
{
    /**
     * Прихваща извикването на AfterConvertSass и конвертира SASS към CSS
     */
    public function on_BeforeGetSbfFilePath($mvc, &$res, &$path)
    {
        $pathArr = pathinfo($path);
        
        if ($pathArr['extension'] == 'css') {
            $scssRelPath = $pathArr['dirname'] . '/' . $pathArr['filename'] . '.scss';
            $scssFile = getFullPath($scssRelPath);
            if (file_exists($scssFile)) {
                $scssFileArr = pathinfo($scssFile);
                $time = core_Os::getTimeOfLastModifiedFile(dirname($scssFile), '', "#[a-z0-9\-\/_]+(.scss)$#i");
                
                // Проверка дали файла вече не съществува в sbf
                $sbfPath = core_Sbf::getSbfPathByTime($path, $time);
                if (file_exists($sbfPath)) {
                    $res = $sbfPath;
                    
                    return false;
                }
                
                // Път до конвертирания файл
                $cssPath = $scssFileArr['dirname'] . '/' . $pathArr['basename'];
                if (!file_exists($cssPath) || $time > filemtime($cssPath)) {
                    $cssCode = sass_Converter::convert($scssFile, true);
                    if (!@file_put_contents($cssPath, $cssCode)) {
                        // Ако не успеем да запишем в проекта файла, записваме го директно в sbf
                        if (core_Sbf::saveFile($cssCode, $sbfPath, true)) {
                            $res = $sbfPath;
                            
                            return false;
                        }
                        
                        expect(false, $cssPath, $cssCode);
                    }
                }
            }
        }
    }
}
