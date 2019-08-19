<?php

/**
 * Работна директория за бекъпите
 */
defIfNot('BGERP_BACKUP_WORK_DIR', EF_UPLOADS_PATH . '/backup_work');


/**
 * Път до текущия и миналия бекъп
 */
defIfNot('BGERP_BACKUP_PATH', EF_UPLOADS_PATH . '/backup');


/**
 * Кога е началото на всички бекъпи
 */
defIfNot('BGERP_BACKUP_START', false);


/**
 * Колко минути е периода за флъшване на SQL лога
 */
defIfNot('BGERP_BACKUP_FLUSH_PERIOD', 60);


/**
 * Колко колко минути е периода за пълен бекъп?
 */
defIfNot('BGERP_BACKUP_FULL_PERIOD', 60*168);


/**
 * Клас 'core_Backup' - добавя бекъп възможности към ядрото
 *
 *
 * @category  bgerp
 * @package   core
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class core_Backup extends core_Mvc
{

    /**
     * Показва, кои полета от базата не се използват (няма ги в моделите)
     */
    public function act_Fields()
    {
        requireRole('debug');

        $mvcArr = core_Classes::getOptionsByInterface('core_ManagerIntf');
        $extra = array();

        foreach($mvcArr as $classId => $className) {
            $inst = null;
            $inst = cls::get($className);
            
            if(!$inst->dbTableName || !$this->db->tableexists($inst->dbTableName)) continue;

            $fields = $this->db->getFields($inst->dbTableName);
            
            $model = array();
            foreach($inst->fields as $name => $obj) {
                if($obj->kind != 'FLD') continue;
                $f = str::phpToMysqlName($name);
                $model[$f] = $f;
            }

            foreach($fields as $name => $object) {
                $f = str::phpToMysqlName($name);
                
                if(!$model[$f]) {
                    $extra[$inst->dbTableName . '::' . $f] = 1;
                }
            }
        }

        bp($extra);
    }



    /**
     * Тестов екшън
     */
    public function act_Roll()
    {
        requireRole('debug');
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        bp(dt::mysql2timestamp(), time());

        // Текущата минута
        $currentTms = floor(dt::mysql2timestamp()/60) * 60;
        
        // Минутата на първия бекъп
        $firstTms = floor(dt::mysql2timestamp(BGERP_BACKUP_START)/60) * 60;

        // Минутата на последния бекъп
        $lastBackupTms = $firstTms + floor(($currentTms - $firstTms) / (60 * BGERP_BACKUP_FULL_PERIOD)) * BGERP_BACKUP_FULL_PERIOD * 60;

        //bp(dt::timestamp2mysql($currentMin), dt::timestamp2mysql($firstTms), dt::timestamp2mysql($lastBackupTms));
        
        // форсираме директориите
        core_Os::forceDir(BGERP_BACKUP_WORK_DIR);
        core_Os::forceDir(BGERP_BACKUP_PATH . '/current');
        core_Os::forceDir(BGERP_BACKUP_PATH . '/past');

 
        // Работна директория
        // Четен бекъп
        // Нечетен бекъп
        // Бекъп от тази седмица:
        //$thisWeek = date('Y-\WW');

 
//        bp(self::getFiles("C:/xampp/htdocs/ef_root/uploads/fileman/e0/", '/66/', true));
        

        $mvcArr = core_Classes::getOptionsByInterface('core_ManagerIntf');
        $instArr = array();
        $files = array();


        // Определяме всички mvc класове, на които ще правим бекъп
        foreach($mvcArr as $classId => $className) {
            $mvc = cls::get($className);
            if(!$mvc->dbTableName || !$mvc->doReplication || !$this->db->tableExists($mvc->dbTableName) || !$mvc->count() || isset($instArr[$mvc->dbTableName])) continue;
            $instArr[$mvc->dbTableName] = $mvc;
            $lockTables .= ",`$mvc->dbTableName` WRITE";
        }
        
        $lockTables = trim($lockTables, ',');

        $this->db->query("LOCK TABLES {$lockTables}");

        foreach($instArr as $table => $inst) {
            
            $path = BGERP_BACKUP_WORK_DIR . '/' . $table . '.csv';
            $dest = BGERP_BACKUP_PATH . '/current/' . $table . '.csv.zip';
            $past = BGERP_BACKUP_PATH . '/past/' . $table . '.csv.zip';

            if (file_exists($path)) unlink($path);
            if (file_exists($dest)) unlink($dest);

            if (file_exists($past)) {
                $lmtTable = $this->db->getLMT($table);

                // Таблицата не е променяна, нама да променяме и ZIP файла
                if($lmtTable < filemtime($past)) {
                    copy($past, $dest);
                    continue;
                }
            }

            $query = "SELECT * 
                      INTO OUTFILE '{$path}'
                      FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
                      LINES TERMINATED BY '\n'
                      FROM `{$table}` WHERE id > 0";
 
            $this->db->query($query);
            $files[$path] = $path;
        }

        // Освеобождаваме LOCK-а на таблиците
        $this->db->query('UNLOCK TABLES');


        foreach($files as $path) {
            archive_Adapter::compressFile($path, $path . '.zip', '', '-sdel');
        }
    }


    /**
     * Добавя mySQL заявките в SQL лога
     */
    public static function addSqlLog($sql)
    {
        if(defined('BGERP_BACKUP_START') && BGERP_BACKUP_START > '2000-01-01 00:00:00' &&
           defined('BGERP_BACKUP_WORK_DIR') && is_dir(BGERP_BACKUP_WORK_DIR) && 
           defined('BGERP_BACKUP_WORK_DIR') && is_writable(BGERP_BACKUP_WORK_DIR)) {
            // Текущата минута
            $currentTms = floor(dt::mysql2timestamp()/60) * 60;
            
            // Минутата на първия бекъп
            $firstTms = floor(dt::mysql2timestamp(BGERP_BACKUP_START)/60) * 60;

            // Минутата на последния бекъп
            $lastBackupTms = $firstTms + floor(($currentTms - $firstTms) / (60 * BGERP_BACKUP_FLUSH_PERIOD)) * BGERP_BACKUP_FLUSH_PERIOD * 60;
            
            $path = BGERP_BACKUP_WORK_DIR . '/' . date('Y-m-d-H_i', $lastBackupTms) . '.sql';

            @file_put_contents($path, $sql . ";\n\r", FILE_APPEND);
        }
    }


    /**
     * Прави бекъп-а
     */
    public function cron_Increment()
    {
        // BGERP_BACKUP_DAY
        // BGERP_BACKUP_TIME
        // Правим ротация на бекъпа, ако е необходимо. Преименуване на директорията current

        // Ако нямаме директория за текъщ бекъп - създаваме го: папка, конфиг, CSV дъмп

        // Добавяме всички недобавени SQL лог файлове към текущия бекъп
    }


    /**
     * Връща имената на всички файлове от посочената директория, които отговарят на регулярния израз
     *
     * @param $dir Директория, където ще се търсят файловете
     * @param $pattern Регулярен израз, на който трбва да отговарят файловете
     * @param $recursive Дали да се търси рекурсивно в под-директориите
     *
     * @return array
     */
    public static function getFiles($dir, $pattern = '', $recursive = false)
    {  
        $files = array();
        
        $dir = rtrim(str_replace('\\', '/', trim($dir)), '/') . '/';
         
        $directories = array($dir);
        
        while (sizeof($directories)) {
            
            $dir = array_pop($directories);
            
            if ($handle = opendir($dir)) {  
                while (false !== ($file = readdir($handle))) {
                    
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    
                    $path = $dir . $file;
                     
                    if (is_dir($path)) {
                        if($recursive) {
                            array_push($directories, $path . '/');
                        }
                    } elseif (is_file($path)) {
                        if ($pattern && !preg_match($pattern, $file)) {
                            continue;
                        }
                        
                        $files[] = $file;
                    }
                }
                closedir($handle);
            }
        }
        
        return $files;
    }
}