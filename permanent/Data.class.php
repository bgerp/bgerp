<?php

/**
 * Хранилището за перманентни данни.
 *
 * В бъдеще може да използва NOSQL база данни
 *
 *
 * @category  vendors
 * @package   permanent
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Хранилище за данни
 */
class permanent_Data extends core_Manager
{
    
    
    /**
     * Титла
     */
    public $title = 'Хранилище за данни';
    
    
    /**
     * Права
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('key', 'varchar(64)', 'caption=Ключ');
        $this->FLD('data', 'blob(100000)');
        $this->FLD('isCompressed', 'enum(yes,no)');
        $this->FLD('isSerialized', 'enum(yes,no)');
        
        $this->setDbUnique('key');
        
        $this->load('plg_Created');
    }
    
    
    /**
     * Записва данните за посочения ключ.
     * Данните могат да бъдат скалар или обект или масив.
     * Изисква се или посочения ключ да го няма или редът под този ключ да не е заключен от друг потребител.
     */
    public static function write($key, $data)
    {
        $conf = core_Packs::getConfig('permanent');
        
        $rec = permanent_Data::fetch("#key = '{$key}'");
        
        if (!$rec) {
            $rec = new stdClass();
        }

        $rec->key = $key;
        
        if (is_object($data) || is_array($data)) {
            $rec->data = serialize($data);
            $rec->isSerialized = 'yes';
        } else {
            $rec->isSerialized = 'no';
            $rec->data = $data;
        }
        
        if (strlen($rec->data) > $conf->DATA_MAX_UNCOMPRESS) {
            $rec->data = gzcompress($rec->data);
            $rec->isCompressed = 'yes';
        } else {
            $rec->isCompressed = 'no';
        }
        
        permanent_Data::save($rec);
        
        // Изтриваме заключването
        core_Locks::release($key);
        
        return true;
    }
    
    
    /**
     * Връща данните за посочения ключ, като го заключва по подразбиране
     *
     * @param string $key
     */
    public static function read($key, $lock = true)
    {
        if ($lock && !core_Locks::get($key)) {
            self::logWarning('Грешка при четене - заключен обект');
            exit(1);
        }
        
        $rec = permanent_Data::fetch("#key = '{$key}'");
        
        if (!$rec) {
            return;
        }
        
        $data = $rec->data;
        
        if ($rec->isCompressed == 'yes') {
            $data = gzuncompress($data);
        }
        
        if ($rec->isSerialized == 'yes') {
            $data = unserialize($data);
        }
        
        return $data;
    }
    
    
    /**
     * Изтрива данните за посочения ключ
     *
     * @param string $key
     */
    public static function remove($key)
    {
        permanent_Data::delete("#key = '{$key}'");
    }
}
