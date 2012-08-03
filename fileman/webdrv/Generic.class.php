<?php


/**
 * Прародителя на всички драйвери за файловете
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Generic extends core_Manager
{
    
    /**
     * Връща всички табове, които ги има за съответния файл
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return array
     */
    static function getTabs($fRec) {
        
        return array();
    }
    
    
    /**
     * Стартира извличането на информациите за файла
     * 
     * @param object $fRec - Записите за файла
     */
    static function startProcessing($fRec)
    {
        
        return ;
    }
    
    
    /**
     * Екшън за показване текстовата част на файла
     */
    function act_Text()
    {
        // Манупулатора на файла
        $fileHnd = Request::get('id'); 
        
        // Определяме dataId от манупулатора
        $dataId = fileman_Files::fetchByFh($fileHnd, 'dataId');
        
        // Вземаме текстовата част за съответното $dataId
        $rec = fileman_Info1::fetch("#dataId = '{$dataId}' AND #type = 'text'");
        
        // Десериализираме съдържанието
        $content = unserialize($rec->content);
        
        // Сменяма wrapper'а да е празна страница
        Mode::set('wrapper', 'page_Empty'); // Тук може и да се използва page_PreText за подреден текст
        
        // Връщаме съдържанието
        return $content;
    }
    
    
    /**
     * Генерира и връща уникален стринг за заключване на процес за даден файл
     *
     * @param string $type - Типа, който ще заключим
     * @param object $fRec - Записите за файлва
     * 
     * @return string $lockId - уникален стринг за заключване на процес за даден файл
     */
    static function getLockId($type, $fRec)
    {
        // Генерираме уникален стринг за заключване на процес за даден файл
        $lockId = $type . $fRec->dataId;
        
        return $lockId;
    }
    
    
    /**
     * Проверява дали файла е заключен или записан в БД
     * 
     * @param object $fRec - Данните за файла
     * @param array $params - Масив с допълнителни променливи
     * 
     * @return boolean - Връща TRUE ако файла е заключен или има запис в БД
     * 
     * @access protected
     */
    static function isProcessStarted($fRec, $params)
    {
        // Проверяваме дали файла е заключен или има запис в БД
        if ((fileman_Info1::fetch("#dataId = '{$fRec->dataId}' AND #type = '{$params['type']}'")) 
            || (core_Locks::isLocked($params['lockId']))) return TRUE;
        
        return FALSE;
    }
}