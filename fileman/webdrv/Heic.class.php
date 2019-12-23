<?php


/**
 * Път до директорията на tifig
 * 
 * @see https://github.com/monostream/tifig
 */
defIfNot('TIFIG_PATH', '/home/yusein/Desktop/tifig');


/**
 * Драйвер за работа с .heic файлове.
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_webdrv_Heic extends fileman_webdrv_ImageT
{
    
    
    /**
     * Стартира конвертиране към JPG формат
     *
     * @param object $fRec   - Записите за файла
     * @param array  $params - Допълнителни параметри
     */
    public static function startConvertingToJpg($fRec, $params)
    {
        if (!defined('TIFIG_PATH') || !TIFIG_PATH) {
            fileman_webdrv_Heic::logAlert('Няма зададена стойност за "TIFIG_PATH"');
            
            core_Locks::release($params['lockId']);
            
            fileman_Indexes::createError($params);
            
            return ;
        }
        
        $tifigPath = rtrim(TIFIG_PATH, '/');
        
        // Инстанция на класа
        $Script = cls::get('fconv_Script');
        
        $Script->setProgram('tifig', rtrim(TIFIG_PATH, '/'));
        
        // Вземаме името на файла без разширението
        $name = fileman_Files::getFileNameWithoutExt($fRec->fileHnd);
        
        // Задаваме пътя до изходния файла
        $outFilePath = $Script->tempDir . $name . '-0.jpg';
        
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $fRec->fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        $errFilePath = self::getErrLogFilePath($outFilePath);
        
        // Скрипта, който ще конвертира файла в SVG формат
        $Script->lineExec('tifig -i [#INPUTF#] -o [#OUTPUTF#]', array('errFilePath' => $errFilePath));
        
        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack($params['callBack']);
        
        $params['errFilePath'] = $errFilePath;
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->fName = $name;
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fRec->fileHnd;
        
        $Script->setCheckProgramsArr('tifig');
        
        // Стартираме скрипта синхронно
        if ($Script->run() === false) {
            fileman_Indexes::createError($params);
        }
    }
}
