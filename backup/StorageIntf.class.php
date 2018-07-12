<?php


/**
 * Интерфейс за класовете обслужващи архивирането
 *
 *
 * @category  bgerp
 * @package   backup
 *
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Интерфейс за архивиране
 */
class backup_StorageIntf
{
    /**
     * Връща ключ под който ще се запишат данните
     *
     * @param string $fileName
     */
    public function getFile($fileName)
    {
        return $this->class->getFile($fileName);
    }
    
    
    /**
     * Качва файл в сториджа
     *
     * @param string $fileName
     */
    public function putFile($fileName)
    {
        return $this->class->putFile($fileName);
    }
    
    
    /**
     * Изтрива файл от сториджа
     *
     * @param string $fileName
     */
    public function removeFile($fileName)
    {
        return $this->class->removeFile($fileName);
    }
}
