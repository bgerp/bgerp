<?php


/**
 * Възстановяване на базата , файловете и конфигурацията от bgERP бекъп
 *
 *
 * @category  bgerp
 * @package   backup
 *
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Възстановяване от bgERP бекъп
 */
class backup_Restore extends core_Manager
{

    private static $initialized = false;
    
    /**
     * Информация за бекъпа
     */
    const BGERP_RESTORE_ORIGIN = array (
                                        'Description'=>'Локална система',
                                        'type'=>'local',
                                        'path' => '/storage',
                                        'prefix' => 'bgerp.localhost'
                                        );
//     const BGERP_RESTORE_ORIGIN = array(
//                                             'Description'=>'FTP сървър',
//                                             'type'=>'ftp',
//                                             'address' => 'ftp.localhost.local',
//                                             'port' => '21',
//                                             'user' => 'user',
//                                             'password' => 'pass',
//                                             'path' => '/storage',
//                                         );
//     const BGERP_RESTORE_ORIGIN = array(
//                                         'Description'=>'Амазон AWS S3',
//                                         'type'=>'S3',
//                                         'AMAZON_KEY' => '',
//                                         'AMAZON_SECRET' => '',
//                                         'AMAZON_BUCKET' => ''
//                                        );
    
    
    /**
     * Инициализиране на обекта
     */
    public function init($array = array())
    {
        self::initialize();
    }
    
    
    /**
     * Инициализация при статичните извиквания
     */
    private static function initialize()
    {
        if (self::$initialized) {
            
            return;
        }
        
        self::$initialized = true;
    }
    
    
    /**
     * Стартиране на restore
     */
    public function act_Default()
    {
        $backup = json_encode(self::BGERP_RESTORE_ORIGIN); // това трябва да идва като параметър от ВЕБ или на ф-
        $backup = json_decode($backup);
        $confFileName = $backup->prefix . '_' . EF_DB_NAME . '_META';
        $storage = core_Cls::get('backup_' . $backup->type);
        $storage->getFile($confFileName,EF_TEMP_PATH . "/" . $confFileName);
        $meta = file_get_contents(EF_TEMP_PATH . "/" . $confFileName);
        $metaArr = unserialize($meta);
        // Махаме служебната за mySQL информация
        unset($metaArr['logNames']);
        
        $restoreArr = array_reverse($metaArr['backup'])[0];;
        //bp ($r);
        
        return $backup;
    }
    
}
