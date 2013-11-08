<?php

/**
 * Път до масива за съхранение на файлове
 */
defIfNot('STORAGE_FOLDER',  '/storage');


/**
 * Четене и записване на локални файлове
 *
 *
 * @category  bgerp
 * @package   backup
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Локален файлов архив
 */
class backup_Local extends core_Master
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'backup_StorageIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Архивиране в локалната файлова система';
    
    
    /**
     * Копира файл съхраняван в сторидж на локалната файлова система в 
     * посоченото в $fileName място
     *
     * Част от интерфейса: backup_StorageIntf
     * 
     * @param string $fileName
     * 
     * @return boolean
     */
    static function getFile($fileName)
    {
        $result = @copy(STORAGE_FOLDER . '/' . $fileName, EF_TEMP_PATH . "/" . $fileName);
        
        return $result;
    }    
    
    /**
     * Записва файл в локалния архив
     *
     * Част от интерфейса: backup_StorageIntf
     *
     * @param string $fileName
     * 
     * @return boolean
     */
    static function putFile($fileName)
    {
        $result = @copy(EF_TEMP_PATH . "/" . $fileName, STORAGE_FOLDER . '/' . $fileName);
        
        return $result;
    }
    
    /**
     * Изтрива файл в локалния архив
     *
     * Част от интерфейса: backup_StorageIntf
     *
     * @param string $fileName
     * 
     * @return boolean
     */
    static function removeFile($fileName)
    {
        $result = @unlink(EF_TEMP_PATH . "/" . $fileName, STORAGE_FOLDER . '/' . $fileName);
        
        return $result;
    }
    
}