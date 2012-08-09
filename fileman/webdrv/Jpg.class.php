<?php

/**
 * Драйвер за работа с .jpg файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Jpg extends fileman_webdrv_Image
{
    
	
	/**
     * Връща всички табове, които ги има за съответния файл
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return array
     * 
     * @Override
     * @see fileman_webdrv_Image::getTabs
     */
    static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        $barcodeUrl = toUrl(array('fileman_webdrv_Jpg', 'barcodes', $fRec->fileHnd), TRUE);
        
        $tabsArr['barcodes']->title = 'Баркодове';
        $tabsArr['barcodes']->html = "<div> <iframe src='{$barcodeUrl}' class='webdrvIframe'> </iframe> </div>";
        $tabsArr['barcodes']->order = 3;

        return $tabsArr;
    }
    
    
	/**
     * Конвертиране в JPG формат
     * 
     * @param object $fRec - Записите за файла
     */
    static function convertToJpg($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
//            'callBack' => 'fileman_webdrv_Jpg::afterConvertToJpg',
            'dataId' => $fRec->dataId,
//        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'jpg',
            'fileHnd' => $fRec->fileHnd,
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec->dataId);

        // Проверявама дали няма извлечена информация или не е заключен
        if (static::isProcessStarted($params)) return ;
        
        // Заключваме процеса за определено време
        core_Locks::get($params['lockId'], 50, 0, FALSE);
        
        $script = new stdClass();
        $script->params = serialize($params);
        
        // Това е направено с цел да се запази логиката на работа на системата и възможност за раширение в бъдеще
        static::afterConvertToJpg($script);
    }
    
	
	
	/**
     * Функция, която получава управлението след конвертирането на файл в JPG формат
     * 
     * @param object $script - Обект със стойности
     * @param output $fileHndArr - Масив, в който след обработката ще се запишат получените файлове
     * 
     * @return boolean TRUE - Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
     * и записа от таблицата fconv_Process
     * 
     * @access protected
     */
    static function afterConvertToJpg($script, &$fileHndArr=array())
    {
        // Масива с параметрите
        $params = unserialize($script->params);
        
        // Масив с манупулатора на файла
        $fileHndArr[$params['fileHnd']] = $params['fileHnd'];
        
        // Сериализираме масива и обновяваме данните за записа в fileman_Info
        $rec = new stdClass();
        $rec->dataId = $params['dataId'];
        $rec->type = $params['type'];
        $rec->content = serialize($fileHndArr);
        $rec->createdBy = $params['createdBy'];
        
        fileman_Info1::save($rec);    
        
        // Записваме извличаме и записваме баркодовете
        static::saveBarcodes($script, $fileHndArr);
        
        // Отключваме процеса
        core_Locks::release($params['lockId']);
        
        // Връща TRUE, за да укаже на стартиралия го скрипт да изтрие всики временни файлове 
        // и записа от таблицата fconv_Process
        return TRUE;
    }
}