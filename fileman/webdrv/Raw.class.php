<?php


/**
 * Драйвер за работа с .raw файлове.
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Raw extends fileman_webdrv_ImageT
{



    /**
     * Конвертиране в JPG формат
     *
     * @param object $fRec - Записите за файла
     *
     * @Override
     * @see fileman_webdrv_Image::convertToJpg
     */
    public static function convertToJpg($fRec, $callBack = 'fileman_webdrv_Image::afterConvertToJpg')
    {
        $className = get_called_class();
    
        // Параметри необходими за конвертирането
        $params = array(
                'callBack' => "{$className}::afterConvertToJpg",
                'dataId' => $fRec->dataId,
                'asynch' => true,
                'createdBy' => core_Users::getCurrent('id'),
                'type' => 'jpg',
        );
    
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);
    
        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) {
            return ;
        }
    
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 250, 0, false)) {
    
            // Стартираме конвертирането към JPG
            static::startConvertingToJpg($fRec, $params);
        }
    }
    
    
    /**
     * Стартира конвертиране към PNG формат
     *
     * @param object $fRec   - Записите за файла
     * @param array  $params - Допълнителни параметри
     */
    public static function startConvertingToJpg($fRec, $params)
    {
        // Инстанция на класа
        $Script = cls::get(fconv_Script);
    
        // Вземаме името на файла без разширението
        $name = fileman_Files::getFileNameWithoutExt($fRec->fileHnd);
    
        // Задаваме пътя до изходния файла
        $outFilePath = $Script->tempDir . $name . '-%d.jpg';
    
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $fRec->fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        $wAndH = self::getPreviewWidthAndHeight();
        
        $width = escapeshellarg($wAndH['width']);
        $height = escapeshellarg($wAndH['height']);
        
        $errFilePath = self::getErrLogFilePath($outFilePath);
    
        // Скрипта, който ще конвертира файла в PNG формат
        $Script->lineExec("dcraw -w -c [#INPUTF#] | convert - -resize {$width}x{$height} [#OUTPUTF#]", array('errFilePath' => $errFilePath));
    
        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack($params['callBack']);
    
        $params['errFilePath'] = $errFilePath;
    
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->fName = $name;
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fRec->fileHnd;
    
        $Script->setCheckProgramsArr('dcraw, convert');
        // Стартираме скрипта синхронно
        if ($Script->run() === false) {
            fileman_Indexes::createError($params);
        }
    }
}
