<?php


/**
 * Драйвер за работа с .svg файлове.
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Svg extends fileman_webdrv_Inkscape
{
    
    
    /**
     * Преобразува подадения файл в SVG
     *
     * @param string $file
     * @param string $type
     * @param string $name
     *
     * @return string - манипулатор на новия файл
     */
    public static function toSvg($file, $type = 'auto', $name = '')
    {
        if (!$file) {
            return ;
        }
        
        cls::load('fileman_Files');
        
        $fileType = self::getFileTypeFromStr($file, $type);
        
        if ($fileType == 'string') {
            $name = ($name) ? $name : 'file.pdf';
            $file = fileman::addStrToFile($file, $name);
        }
        
        // Инстанция на класа
        $Script = cls::get('fconv_Script');
        
        if (!$name) {
            // Вземаме името на файла без разширението
            $name = fileman_Files::getFileNameWithoutExt($file);
        } else {
            $nameAndExt = fileman_Files::getNameAndExt($name);
            $name = $nameAndExt['name'];
        }
        
        // Пътя до изходния файла
        $outFilePath = $Script->tempDir . $name . '_to.svg';
        
        $Script->setFile('INPUTF', $file);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        $Script->setProgram('inkscape', fileman_Setup::get('INKSCAPE_PATH'));
        
        $errFilePath = self::getErrLogFilePath($outFilePath);
        
        // Скрипта, който ще конвертира файла в .svg формат
        $Script->lineExec('inkscape [#INPUTF#] --export-plain-svg=[#OUTPUTF#] --export-area-drawing', array('errFilePath' => $errFilePath));
        
        // Стартираме скрипта синхронно
        $Script->run(false);
        
        fileman_Indexes::haveErrors($outFilePath, array('type' => 'pdf', 'errFilePath' => $errFilePath));
        
        $resFileHnd = null;
        
        if (is_file($outFilePath)) {
            $resFileHnd = fileman::absorb($outFilePath, 'fileIndex');
        }
        
        if ($resFileHnd) {
            if ($Script->tempDir) {
                // Изтриваме временната директория с всички файлове вътре
                core_Os::deleteDir($Script->tempDir);
            }
            
            if ($fileType == 'string') {
                fileman::deleteTempPath($file);
            }
        } else {
            if (is_file($errFilePath)) {
                $err = @file_get_contents($errFilePath);
                self::logErr('Грешка при конвертиране: ' . $err);
            }
        }
        
        return $resFileHnd;
    }
}
