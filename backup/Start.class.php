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
 * @title     Архивиране
 */
class backup_Start extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Стартира архивиране';
    
    
    /**
     * Име на семафора за стартиран процес на бекъп
     */
    private static $lockFileName;
    private static $conf;
    private static $backupFileName;
    private static $binLogFileName;
    private static $metaFileName;
    private static $storage;
    private static $confFileName;
    private static $initialized = false;
    
    
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
        
        self::$lockFileName = EF_TEMP_PATH . '/backupLock' . substr(md5(EF_USERS_PASS_SALT . EF_SALT), 0, 5) . '.tmp';
        self::$conf = core_Packs::getConfig('backup');
        $now = date('Y_m_d_H_i');
        self::$backupFileName = self::$conf->BACKUP_PREFIX . '_' . EF_DB_NAME . '_' . $now . '.full';
        self::$metaFileName = self::$conf->BACKUP_PREFIX . '_bgERP_backup_META';
        self::$confFileName = self::$conf->BACKUP_PREFIX . '_conf.tar.gz';
        self::$storage = core_Cls::get('backup_' . self::$conf->BACKUP_STORAGE_TYPE);
        self::$initialized = true;
    }
    
    
    /**
     * Стартиране на пълното архивиране на MySQL-a
     */
    private static function full()
    {
        $me = cls::get('backup_Setup');
        $confErr = $me->checkConfig(true);
        if (!is_null($confErr)) {
            self::logErr($confErr);
            
            return ('Грешка в конфигурацията!');
        }
        
        if (!self::lock()) {
            self::logWarning('Full Backup не може да вземе Lock!');
            
            shutdown();
        }
        
        // Заключваме цялата система
        core_SystemLock::block('Процес на архивиране на данните', 1800); // 30 мин.
        
        
        exec('mysqldump --max_allowed_packet=512M --lock-tables --delete-master-logs -u'
            . self::$conf->BACKUP_MYSQL_USER_NAME . ' -p' . self::$conf->BACKUP_MYSQL_USER_PASS . ' ' . EF_DB_NAME
            . ' >' . EF_TEMP_PATH . '/' . self::$backupFileName . ' 2>&1', $output, $returnVar);
        
        // Освобождаваме системата
        core_SystemLock::remove();
        
        // Архивираме файла
        exec('gzip -1 ' . EF_TEMP_PATH . '/' . self::$backupFileName);
        
        if ($returnVar !== 0) {
            self::logErr('Грешка при FullBackup: errNo: ' . $returnVar . " " . implode("|", $output));
            self::unLock();
            
            shutdown();
        }
        
        // Сваляме мета файла с описанията за бекъпите
        if (!self::$storage->getFile(self::$metaFileName, EF_TEMP_PATH . '/' . self::$metaFileName)) {
            // Ако го няма - създаваме го
            touch(EF_TEMP_PATH . '/' . self::$metaFileName);
            $metaArr = array();
        } else {
            $metaArr = unserialize(file_get_contents(EF_TEMP_PATH . '/' . self::$metaFileName));
        }
        
        if (!is_array($metaArr)) {
            self::logErr('Лоша информация в метафайла!');
            self::unLock();
            
            shutdown();
        }
        
        // Ако има дефинирана парола криптираме файловете с данните
        if (self::$conf->BACKUP_CRYPT == 'yes') {
            self::$backupFileName = self::crypt(self::$backupFileName . ".gz");
        }
        
        // Добавяме нов запис за пълния бекъп
        $metaArr['backup'][][0] = self::$backupFileName . ".gz";
        $metaArr['backupInfo']['CORE_LAST_DB_VERSION'] = core_Setup::get('LAST_DB_VERSION');
        $metaArr['backupInfo']['CORE_CODE_VERSION'] = core_Setup::get('CODE_VERSION');
        $metaArr['backupInfo']['BGERP_GIT_BRANCH'] = BGERP_GIT_BRANCH;
        if(defined('BGERP_PRIVATE_GIT_BRANCH')) {
            $metaArr['backupInfo']['PRIVATE_GIT_BRANCH'] = BGERP_PRIVATE_GIT_BRANCH;
        }
        $metaArr['backupInfo']['EF_DB_NAME'] = EF_DB_NAME;
        $db = cls::get(
            'core_Db',
            array('dbUser' => self::$conf->BACKUP_MYSQL_USER_NAME,
                'dbHost' => self::$conf->BACKUP_MYSQL_HOST,
                'dbPass' => self::$conf->BACKUP_MYSQL_USER_PASS,
                'dbName' => 'information_schema')
            );
        $dbRes = $db->query("SELECT SUM(TABLE_ROWS) AS rowsDb FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . EF_DB_NAME. "'");
        $tableRows = $db->fetchArray($dbRes);
        $metaArr['backupInfo']['rowsDb'] = $tableRows['rowsDb'];
        $metaArr['backupInfo']['confFileName'] = self::$confFileName;
        
        // Махаме бинлоговете
        unset($metaArr['logNames']);
        file_put_contents(EF_TEMP_PATH . '/' . self::$metaFileName, serialize($metaArr));
        
        // Качваме бекъп-а
        self::$storage->putFile(EF_TEMP_PATH . '/' . self::$backupFileName . ".gz");
        
        // Качваме и мета файла
        self::$storage->putFile(EF_TEMP_PATH . '/' . self::$metaFileName);
        
        // Изтриваме бекъп-а от temp-a и metata
        unlink(EF_TEMP_PATH . '/' . self::$backupFileName . ".gz");
        unlink(EF_TEMP_PATH . '/' . self::$metaFileName);
        self::saveConf();
        
        self::logInfo('FULL Backup OK!');
        self::unLock();
        
        return 'FULL Backup OK!';
    }
    
    
    /**
     * Взимане на МЕТА данните
     *
     * @return array
     */
    private static function getMETA()
    {
        // 1. сваля се метафайла
        if (!self::$storage->getFile(self::$metaFileName, EF_TEMP_PATH . '/' . self::$metaFileName)) {
            // Ако го няма - пропускаме - не е минал пълен бекъп
            self::logErr('ГРЕШКА при сваляне на метафайла!');
            self::unLock();
            
            shutdown();
        } else {
            $metaArr = unserialize(file_get_contents(EF_TEMP_PATH . '/' . self::$metaFileName));
        }
        
        return $metaArr;
    }
    
    
    /**
     * Съхраняване на бинарния лог на MySQL-a
     */
    private static function binLog()
    {
        if (!self::lock()) {
            self::logWarning('BinLog не може да вземе Lock.');
            
            shutdown();
        }
        
        $metaArr = self::getMETA();
        
        if (!is_array($metaArr)) {
            self::logErr('Лоша информация в метафайла!');
            self::unLock();
            
            shutdown();
        }
        
        // Взима бинарния лог
        $db = cls::get(
            'core_Db',
            array('dbUser' => self::$conf->BACKUP_MYSQL_USER_NAME,
                'dbHost' => self::$conf->BACKUP_MYSQL_HOST,
                'dbPass' => self::$conf->BACKUP_MYSQL_USER_PASS,
                'dbName' => 'information_schema')
        );
        
        // 2. взима списъка с имената на бинлоговете
        $dbRes = $db->query('SHOW MASTER LOGS');
        while ($logName = $db->fetchArray($dbRes)) {
            $resArr['logNames'][] = $logName['Log_name'];
        }
        
        // Log_name e колоната с имената
        // 3. флъшваме лог-а
        $db->query('FLUSH LOGS');
        
        $ungetedBinLogs = array_diff((array) $resArr['logNames'], (array) $metaArr['logNames']);
        
        // 4. взимаме съдържанието на binlogo-вете в temp-a, компресираме го и го качваме в сториджа
        foreach ($ungetedBinLogs as $binLogFileName) {
            $binLogFileNameGz = self::$conf->BACKUP_PREFIX . '_' . EF_DB_NAME . '_' . $binLogFileName . '.gz';
            
            $cmdBinLog = 'mysqlbinlog --database=' . EF_DB_NAME . ' --read-from-remote-server -u'
                . self::$conf->BACKUP_MYSQL_USER_NAME
                . ' -p' . self::$conf->BACKUP_MYSQL_USER_PASS . " {$binLogFileName} -h"
                . self::$conf->BACKUP_MYSQL_HOST . ' | gzip -1 > ' . EF_TEMP_PATH . '/' . $binLogFileNameGz;
            
            exec($cmdBinLog, $output, $returnVar);
            
            if ($returnVar !== 0) {
                self::logErr('ГРЕШКА при mysqlbinlog!');
                self::unLock();
                
                shutdown();
            }
            
            // 5. Ако има дефинирана парола криптираме файловете с данните
            if (self::$conf->BACKUP_CRYPT == 'yes') {
                $binLogFileNameGz = self::crypt($binLogFileNameGz);
            }
            
            // 6. добавя се инфо за бинлога
            $maxKey = max(array_keys($metaArr['backup']));
            $metaArr['backup'][$maxKey][] = $binLogFileNameGz;
            $metaArr['logNames'][] = $binLogFileName;
            file_put_contents(EF_TEMP_PATH . '/' . self::$metaFileName, serialize($metaArr));
            
            // 7. Качва се binlog-a с подходящо име
            self::$storage->putFile(EF_TEMP_PATH . '/' . $binLogFileNameGz);
            
            // 8. Качва се и мета файла
            self::$storage->putFile(EF_TEMP_PATH . '/' . self::$metaFileName);
            
            // 9. Изтриваме бекъп-а от temp-a и metata
            unlink(EF_TEMP_PATH . '/' . $binLogFileNameGz);
            unlink(EF_TEMP_PATH . '/' . self::$metaFileName);
        }
        
        self::logInfo('binLog Backup OK!');
        self::unLock();
        
        return 'binLog Backup OK!';
    }
    
    
    /**
     * Почистване на стария бекъп
     */
    private static function clean()
    {
        if (!self::lock()) {
            self::logWarning('Clean не може да вземе Lock.');
            
            shutdown();
        }
        
        // Взимаме мета данните
        $metaArr = self::getMETA();
        
        if (countR($metaArr['backup']) > self::$conf->BACKUP_CLEAN_KEEP) {
            // Има нужда от почистване
            $garbage = array_slice($metaArr['backup'], 0, countR($metaArr['backup']) - self::$conf->BACKUP_CLEAN_KEEP);
            $keeped['backup'] = array_slice($metaArr['backup'], countR($metaArr['backup']) - self::$conf->BACKUP_CLEAN_KEEP, countR($metaArr['backup']));
            $keeped['logNames'] = $metaArr['logNames'];
            $keeped['backupInfo'] = $metaArr['backupInfo'];
            file_put_contents(EF_TEMP_PATH . '/' . self::$metaFileName, serialize($keeped));
            
            // Качваме МЕТАТ-а в сториджа
            self::$storage->putFile(EF_TEMP_PATH . '/' . self::$metaFileName);
            
            // Отключваме бекъп-а, защото изтриването на файлове може да е бавна операция
            self::unLock();
        } else {
            // Нямаме работа по изтриване
            self::unLock();
            self::logInfo('Нищо за изтриване.');
            
            return;
        }
        
        // Изтриваме боклука
        $cnt = 0;
        
        foreach ($garbage as $backups) {
            foreach ($backups as $fileName) {
                self::$storage->removeFile($fileName);
                $cnt++;
            }
        }
        self::logInfo("Успешно изтрити {$cnt} файла.");
    }
    
    
    /**
     * Запазва конфигурация на bgERP
     *
     * @return bool
     */
    private static function saveConf()
    {
        $traceArr = debug_backtrace();
        $maxKey = max(array_keys($traceArr));
        
        // Директорията от където се изпълнява скрипта
        $confFiles = array();
        $confFiles[] = ' ' . dirname($traceArr[$maxKey]['file']) . '/index.cfg.php';
        $confFiles[] = ' ' . EF_CONF_PATH . '/' . EF_APP_NAME . '.cfg.php';
        
        $cmd = 'tar cfvz ' . EF_TEMP_PATH . '/' . self::$confFileName;
        
        foreach ($confFiles as $file) {
            $cmd .= $file;
        }
        
        exec($cmd, $output, $returnVar);
        
        if ($returnVar !== 0) {
            self::logErr('Лоша tar и/или gzip конфигурация!');
            
            shutdown();
        }
        
        // Ако има дефинирана парола криптираме файловете с данните
        if (self::$conf->BACKUP_CRYPT == 'yes') {
            self::$confFileName = self::crypt(self::$confFileName);
        }
        
        self::$storage->putFile(EF_TEMP_PATH . '/' . self::$confFileName);
        
        @unlink(EF_TEMP_PATH . '/' . self::$confFileName);
    }
    
    
    /**
     * Криптира зададен файл в темп директорията
     * със зададената парола и изтрива оригинала
     *
     * @param string $fileName
     *
     * @return string - името на новия файл
     */
    private static function crypt($fileName)
    {
        $command = 'openssl enc -aes-256-cbc -in '
        . EF_TEMP_PATH . '/' . $fileName .
        ' -out ' . EF_TEMP_PATH . '/' . $fileName . '.enc' . ' -k '
        . self::$conf->BACKUP_PASS . ' 2>&1';
        
        $output = array();
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            $err = implode(',', $output);
            self::logErr("ГРЕШКА при криптиране!: {$err}");
            self::unLock();
            
            shutdown();
        } else {
            // Разкарваме некриптирания файл
            @unlink(EF_TEMP_PATH . '/' . $fileName);
        }
        
        return $fileName . '.enc';
    }
    
    
    /**
     * Запазва файлове от fileMan-a
     *
     * @return bool
     */
    private static function saveFileMan()
    {
        $unArchived = fileman_Data::getUnArchived(self::$conf->BACKUP_FILEMAN_COUNT_FILES);
        
        foreach ($unArchived as $fileObj) {
            if (file_exists($fileObj->path)) {
                if (self::$storage->putFile($fileObj->path, BACKUP_FILEMAN_PATH)) {
                    fileman_Data::setArchived($fileObj->id);
                } else {
                    self::logErr("backup не записва файл {$fileObj->path} в " . 'backup_' . self::$conf->BACKUP_STORAGE_TYPE);
                    fileman_Data::setArchived($fileObj->id);
                }
            } else {
                self::logWarning("backup: несъществуващ файл във файлмен-а: {$fileObj->path}");
            }
        }
    }
    
    
    /**
     * Вдига семафор за стартиран бекъп
     * Връща false ако семафора е вече вдигнат
     *
     * @return bool
     */
    private static function lock()
    {
        if (self::isLocked()) {
            
            return false;
        }
        
        return touch(self::$lockFileName);
    }
    
    
    /**
     * Смъква семафора на бекъп-а
     *
     * @return bool
     */
    public static function unLock()
    {
        self::initialize();
        
        $res = false;
        
        if (file_exists(self::$lockFileName)) {
            $res = @unlink(self::$lockFileName);
        }
        
        return $res;
    }
    
    
    /**
     * Показва състоянието на семафора за бекъп
     *
     * @return bool
     */
    public static function isLocked()
    {
        self::initialize();
        
        // Ако заключването е по голямо от 120 мин отключваме backup-a
        if (file_exists(self::$lockFileName)) {
            if (time() - filemtime(self::$lockFileName) > 120 * 60) {
                self::unLock();
            }
        }
        
        return file_exists(self::$lockFileName);
    }
    
    
    /**
     * Стартиране от крон-а
     *
     * Прави пълен backup през крона
     */
    public static function cron_Full()
    {
        self::full();
    }
    
    
    /**
     * Прави binLog по крон
     */
    public static function cron_BinLog()
    {
        self::binLog();
    }
    
    
    /**
     * Изчиства старите beckup-пи чрез крон
     */
    public static function cron_Clean()
    {
        self::clean();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function cron_FileMan()
    {
        self::saveFileMan();
    }
    
    
    /**
     * Методи за извикване през WEB
     *
     * Прави пълен backup
     */
    public function act_Full()
    {
        $this->initialize();
        
        return $this->full();
    }
    
    
    /**
     * Прави binLog през Web
     */
    public function act_BinLog()
    {
        self::initialize();
        
        return self::binLog();
    }
    
    
    /**
     * Изчиства старите beckup-пи през Web
     */
    public function act_Clean()
    {
        self::initialize();
        
        return self::clean();
    }
    
    
    /**
     * Запазва конфигурационните файлове на bgerp-a
     */
    public function act_SaveConf()
    {
        self::initialize();
        
        return self::saveConf();
    }
    
    
    /**
     * Връща линк към подадения обект
     * Тук нямаме обект - предефинираме я за да се излиза коректно име в лог-а на класа
     *
     * @param int $objId
     *
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        return new ET(get_called_class());
    }
}
