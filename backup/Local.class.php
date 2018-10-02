<?php


/**
 * Четене и записване на локални файлове
 *
 *
 * @category  bgerp
 * @package   backup
 *
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
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
     * Локален път
     */
    private $path;
    
    /**
     * Инициализиране на обекта
     */
    public function init($array = array())
    {
        if (isset($array['path'])) {
            $this->path = $array['path'];
        } else { // търсим пътят от конфигурацията
            $conf = core_Packs::getConfig('backup');
            $this->path = $conf->BACKUP_LOCAL_PATH;
        }
    }
    
    /**
     * Копира файл съхраняван в сторидж на локалната файлова система в
     * посоченото в $fileName място
     *
     * Част от интерфейса: backup_StorageIntf
     *
     * @param string $fileName
     *
     * @return bool
     */
    public function getFile($sourceFile, $destFile)
    {
        $result = @copy($this->path . '/' . $sourceFile, $destFile);
        
        return $result;
    }
    
    
    /**
     * Записва файл в локалния архив
     *
     * Част от интерфейса: backup_StorageIntf
     *
     * @param string $fileName
     * @param null   $subDir
     *
     * @return bool
     */
    public function putFile($fileName, $subDir = null)
    {
        if ($subDir) {
            if (!is_dir($this->path . '/' . $subDir)) {
                if (!@mkdir($this->path . '/' . $subDir)) {
                    $this->logWarning('Не може да се създаде път за backup-a');
                }
            }
            $destFileName = ($this->path . '/' . $subDir . '/' . basename($fileName));
        } else {
            $destFileName = $this->path . '/' . basename($fileName);
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
     * @return bool
     */
    public function removeFile($fileName)
    {
        $result = @unlink($this->path . '/' . basename($fileName));
        
        return $result;
    }
}
