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
        
        $storage = core_Cls::get('backup_' . $backup->type);
        // Взимаме конфиг. файла
        $confFileName = $backup->prefix . '_' . EF_DB_NAME . '_conf.tar.gz';
        $storage->getFile($confFileName, EF_TEMP_PATH . "/" . $confFileName);
        //eval ("DEFINE('C','444');"); echo (C); die;
        $consts = array('EF_SALT', 'EF_USERS_PASS_SALT', 'EF_USERS_HASH_FACTOR');
        try {
            $phar = new PharData(EF_TEMP_PATH . "/" . $confFileName);
            foreach (new RecursiveIteratorIterator($phar) as $file) {
                echo $file . "<br />";
                echo ('<pre>');
                print_r(file($file)); die;
                //echo "<pre>" . htmlspecialchars(file_get_contents($file)) . "</pre>";
                //eval (file_get_contents($file)); echo (EF_USERS_HASH_FACTOR); die;
            }
            
            // if ($phar->current()->isDir());
            //$phar->extractTo(EF_TEMP_PATH, array()); // extract all files
        } catch (Exception $e) {
            bp($e->getMessage());
        }
        
        // Взимаме МЕТА файла
        $metaFileName = $backup->prefix . '_' . EF_DB_NAME . '_META';
        $storage->getFile($metaFileName, EF_TEMP_PATH . "/" . $metaFileName);
        $meta = file_get_contents(EF_TEMP_PATH . "/" . $metaFileName);
        $metaArr = unserialize($meta);
        // Махаме служебната за mySQL информация
        unset($metaArr['logNames']);
        
        // Взимаме последния бекъп
        $restoreArr = array_reverse($metaArr['backup'])[0];;
        bp($restoreArr);
        
        return $backup;
    }
    
}
