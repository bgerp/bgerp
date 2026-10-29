<?php


/**
 * Плъгин за конвертиране на SASS файлве в CSS
 *
 * @category  vendors
 * @package   sass
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
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
        
        if (($pathArr['extension'] ?? null) == 'css') {
            $scssRelPath = $pathArr['dirname'] . '/' . $pathArr['filename'] . '.scss';
            $scssFile = getFullPath($scssRelPath);
            if ($scssFile && file_exists($scssFile)) {
                $scssFileArr = pathinfo($scssFile);
                $time = core_Os::getTimeOfLastModifiedFile(dirname($scssFile), '', "#[a-z0-9\-\/_]+(.scss)$#i");
                $newCompiler = sass_Setup::get('USE_COMPOSER') === 'yes' && sass_Setup::get('VERSION') === '1.13.0';
                
                // Проверка дали файла вече не съществува в sbf
                $sbfPath = core_Sbf::getSbfPathByTime($path, $time);
                if ($newCompiler) {
                    // Отделен SBF файл пази стария CSS при връщане на компилатора.
                    $sbfPath = substr($sbfPath, 0, -4) . '_scssphp1_13_0.css';
                }
                if (file_exists($sbfPath)) {
                    $res = $sbfPath;
                    
                    return false;
                }
                
                // Път до конвертирания файл
                $cssPath = $scssFileArr['dirname'] . '/' . $pathArr['basename'];
                if ($newCompiler || !file_exists($cssPath) || $time > filemtime($cssPath)) {
                    $cssCode = sass_Converter::convert($scssFile, true);
                    $cssWritable = !$newCompiler && (file_exists($cssPath) ? is_writable($cssPath) : is_writable(dirname($cssPath)));
                    if (!($cssWritable && file_put_contents($cssPath, $cssCode))) {
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
