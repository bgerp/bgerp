<?php



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
 * @title     Локален файлов обмен
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
     * Връща файл
     *
     * Част от интерфейса: backup_StorageIntf
     */
    static function getFile($fileName)
    {
    
        return $result;
    }    
    
    /**
     * Записва файл
     *
     * Част от интерфейса: backup_StorageIntf
     */
    static function putFile($fileName)
    {
    
        return $result;
    }
    
}