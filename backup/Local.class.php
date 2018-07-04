<?php



/**
 * Четене и записване на локални файлове
 *
 *
 * @category  bgerp
 * @package   backup
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Локален файлов архив
 */
class backup_Local extends core_BaseClass
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'backup_StorageIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Архивиране в локалната файлова система';
    
    
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
    public static function getFile($sourceFile, $destFile)
    {
        $conf = core_Packs::getConfig('backup');
        $result = @copy($conf->BACKUP_LOCAL_PATH . '/' . $sourceFile, $destFile);
        
        return $result;
    }


    /**
     * Записва файл в локалния архив
     *
     * Част от интерфейса: backup_StorageIntf
     *
     * @param string $fileName
     *
     * @param  null $subDir
     * @return bool
     */
    public static function putFile($fileName, $subDir = null)
    {
        $conf = core_Packs::getConfig('backup');
        if ($subDir) {
            if (!is_dir($conf->BACKUP_LOCAL_PATH . '/' . $subDir)) {
                mkdir($conf->BACKUP_LOCAL_PATH . '/' . $subDir);
            }
            $destFileName = ($conf->BACKUP_LOCAL_PATH . '/' . $subDir . '/' . basename($fileName));
        } else {
            $destFileName = $conf->BACKUP_LOCAL_PATH . '/' . basename($fileName);
        }

        $result = @copy($fileName, $destFileName);
        
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
    public static function removeFile($fileName)
    {
        $conf = core_Packs::getConfig('backup');
        $result = @unlink($conf->BACKUP_LOCAL_PATH . '/' . basename($fileName));
        
        return $result;
    }
}
