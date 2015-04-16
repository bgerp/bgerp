<?php

/**
 * Драйвер за работа с .svg файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Svg extends fileman_webdrv_ImageT
{
    
    
    /**
     * Конвертиране в JPG формат
     * 
     * @param object $fRec - Записите за файла
     * 
     * @Override
     * @see fileman_webdrv_Image::convertToJpg
     */
    static function convertToJpg($fRec, $callBack = 'fileman_webdrv_Image::afterConvertToJpg')
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'fileman_webdrv_Svg::afterConvertToPng',
            'dataId' => $fRec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'png',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);

        // Проверявама дали няма извлечена информация или не е заключен
        if (fileman_Indexes::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определено време
        if (core_Locks::get($params['lockId'], 100, 0, FALSE)) {
            
            // Стартираме конвертирането към JPG
            static::startConvertingToPng($fRec, $params);    
        }
    }
    
    
    /**
     * Стартира конвертиране към PNG формат
     * 
     * @param object $fRec - Записите за файла
     * @param array $params - Допълнителни параметри
     */
    static function startConvertingToPng($fRec, $params)
    {
        // Инстанция на класа
        $Script = cls::get(fconv_Script);
        
        // Вземаме името на файла без разширението
        $name = fileman_Files::getFileNameWithoutExt($fRec->fileHnd);

        // Задаваме пътя до изходния файла
        $outFilePath = $Script->tempDir . $name . '.png';
        
        // Задаваме placeHolder' ите за входния и изходния файл
        $Script->setFile('INPUTF', $fRec->fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        // Скрипта, който ще конвертира файла в JPG формат
        $Script->lineExec('rsvg-convert --background-color=#ffffff [#INPUTF#] -o [#OUTPUTF#]');
        
        // Функцията, която ще се извика след приключване на обработката на файла
        $Script->callBack($params['callBack']);
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->fName = $name;
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fRec->fileHnd;

        // Стартираме скрипта Aсинхронно
        $Script->run();
    }
	
    
	/**
     * Функция, която получава управлението след конвертирането на файл в JPG формат
     * 
     * @param object $script - Обект със стойности
     * 
     * @return boolean TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterConvertToPng($script, &$fileHndArr = array())
    {
        // Десериализираме нужните помощни данни
        $params = unserialize($script->params);
        
        // Проверяваме дали е имало грешка при предишното конвертиране
        if (fileman_Indexes::haveErrors($script->outFilePath, $params['type'], $params)) {
            
            // Отключваме процеса
            core_Locks::release($params['lockId']);
            
            return FALSE;
        }
        
        // Инстанция на класа
        $Fileman = cls::get('fileman_Files');
        
        // Ако възникне грешка при качването на файла (липса на права)
        try {
            
            // Качваме файла в кофата и му вземаме манипулатора
            $fileHnd = $Fileman->addNewFile($script->outFilePath, 'fileIndex');
        } catch (core_exception_Expect $e) {
            
            // Създаваме запис в модела за грешка
            fileman_Indexes::createError($params);
    
            // Записваме грешката в лога
            fileman_Indexes::createErrorLog($params['dataId'], $params['type']);
        
        }
        
        // Ако се качи успешно записваме манипулатора в масив
        if ($fileHnd) {
            
            // Масив с манипулатора на файла
            $fileHndArr[$fileHnd] = $fileHnd;
            
            // Текстовата част
            $params['content'] = $fileHndArr;
    
            // Обновяваме данните за запис във fileman_Indexes
            $savedId = fileman_Indexes::saveContent($params);
        }
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        if ($savedId) {

            // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
            // и записа от таблицата fconv_Process
            return TRUE;
        }
    
    }
    
    
    /**
     * Връща информация за съдържанието на файла
     * Вика се от fileman_Indexes, за файлове, които нямат запис в модела за съответния тип
     * 
     * @param string $fileHnd
     * @param string $type
     */
    public static function getInfoContentByFh($fileHnd, $type)
    {
        if ($type != 'jpg') return FALSE;
        
        return fileman_Indexes::getInfoContentByFh($fileHnd, 'png');
    }
}
