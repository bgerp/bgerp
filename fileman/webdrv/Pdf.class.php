<?php


/**
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Pdf extends fileman_webdrv_Office
{
    
    
    /**
     * Връща всички табове, които ги има за съответния файл
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return array
     * 
     * @Override
     * @see fileman_webdrv_Office::getTabs
     */
    static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        //TODO само за тест
        $pdfUrl = toUrl(array('fileman_webdrv_Pdf', 'pdf', $fRec->fileHnd), TRUE);
        $tabsArr['pdf']->title = 'PDF';
        $tabsArr['pdf']->html = "<div> <iframe src='{$pdfUrl}'> </iframe> </div>";
        $tabsArr['pdf']->order = 2;

        return $tabsArr;
    }
    
    
    /**
     * Извлича текстовата част от файла
     * 
     * @param object $fRec - Записите за файла
     * 
     * @Override
     * @see fileman_webdrv_Office::extractText
     */
    static function extractText($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'fileman_webdrv_Pdf::afterExtractText',
            'dataId' => $fRec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'text',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = $params['type'] . $fRec->dataId;

        // Проверявама дали няма извлечена информация или не е заключен
        if (static::isProcessStarted($fRec, $params)) return ;
        
        // Заключваме процеса за определно време
        core_Locks::get($params['lockId'], 30, 0, FALSE);
        
        // Стартираме конвертирането
        static::convertPdfToTxt($fRec->fileHnd, $params);
    }
    
    
	/**
     * Конвертиране на pdf документи към txt с помощта на pdftotext
     * 
     * @param fileHandler $fileHnd - Манупулатора на файла, който ще се конвертира
     * @param array $params - Други параметри
     * 				$params['callBack'] - Класа и функцията, която ще се извикат след приключване на конвертирането
     * 				$params['asynch'] - Дали скрипта да се стартира асинхронно или не
     * 				и др.
     * 
     * @access protected
     */
    static function convertPdfToTxt($fileHnd, $params=array())
    {
        // Инстанция на класа
        $Script = cls::get(fconv_Script);
        
        // Пътя до файла, в който ще се записва получения текст
        $outFilePath = $Script->tempDir . $Script->id . '.txt';
        
        // Задаваме placeHolder' и за входящия и изходящия файл
        $Script->setFile('INPUTF', $fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        // Скрипта, който ще конвертира
        $Script->lineExec('pdftotext -enc UTF-8 -nopgbrk [#INPUTF#] [#OUTPUTF#]');
        
        // Функцията, която ще се извика след приключване на операцията
        $Script->callBack($params['callBack']);
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fileHnd;

        // Стартираме скрипта синхронно
        $Script->run($params['asynch']);
    }
    
    
    /**
     * Конвертиране в JPG формат
     * 
     * @param object $fRec - Записите за файла
     * 
     * @Override
     * @see fileman_webdrv_Office::convertToJpg
     */
    static function convertToJpg($fRec)
    {
        // Параметри необходими за конвертирането
        $params = array(
            'callBack' => 'fileman_webdrv_Pdf::afterConvertToJpg',
            'dataId' => $fRec->dataId,
        	'asynch' => TRUE,
            'createdBy' => core_Users::getCurrent('id'),
            'type' => 'jpg',
        );
        
        // Променливата, с която ще заключим процеса
        $params['lockId'] = static::getLockId($params['type'], $fRec);

        // Проверявама дали няма извлечена информация или не е заключен
        if (static::isProcessStarted($fRec, $params)) return ;
        
        // Заключваме процеса за определно време
        core_Locks::get($params['lockId'], 100, 0, FALSE);

        // Стартираме конвертирането
        static::convertPdfToJpg($fRec->fileHnd, $params);
    }
}