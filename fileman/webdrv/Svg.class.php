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
     * Преобразува подадения файл в PDF
     * 
     * @param string $file
     * 
     * @return string - манипулатор на новия файл
     */
    public static function toSvg($file)
    {
        if (!$file) return ;
        
        cls::load('fileman_Files');
        
        if ((strlen($file) == FILEMAN_HANDLER_LEN) && (strpos($file, '/') === FALSE)) {
            $fRec = fileman_Files::fetchByFh($file);
            
            expect($fRec);
    	}
        
        // Инстанция на класа
        $Script = cls::get('fconv_Script');
        
        // Вземаме името на файла без разширението
        $name = fileman_Files::getFileNameWithoutExt($file);
        
        // Пътя до изходния файла
        $outFilePath = $Script->tempDir . $name . '_to.svg';
        
        $Script->setFile('INPUTF', $file);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        $Script->setProgram('inkscape', INKSCAPE_PATH);
        
        $errFilePath = self::getErrLogFilePath($outFilePath);
        
        // Скрипта, който ще конвертира файла в .svg формат
        $Script->lineExec("inkscape [#INPUTF#] --export-plain-svg=[#OUTPUTF#] --export-area-drawing", array('errFilePath' => $errFilePath));
        
        // Стартираме скрипта синхронно
        $Script->run(FALSE);
        
        $resFileHnd = fileman::absorb($outFilePath, 'fileIndex');
        
        if ($resFileHnd) {
            if ($Script->tempDir) {
                // Изтриваме временната директория с всички файлове вътре
                core_Os::deleteDir($Script->tempDir);
            }
        } else {
            fileman_Indexes::haveErrors($outFilePath, array('type' => 'pdf', 'errFilePath' => $errFilePath));
        }
        
        return $resFileHnd;
    }
}
