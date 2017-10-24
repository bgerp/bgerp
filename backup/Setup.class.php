<?php



/**
 * Уникален префикс за имената на архивираните файлове
 */
defIfNot('BACKUP_PREFIX', 'bgerp.localhost');


/**
 * Използван клас за реализиране на архива - локална система, FTP, rsync, Amazon ...
 */
defIfNot('BACKUP_STORAGE_TYPE', 'local');


/**
 * Период на който се прави пълен бекъп
 * Всеки петък в 4:00 през нощта
 */
defIfNot('BACKUP_FULL_PERIOD', 5 * 24 * 60);


/**
 * Отместване за пълния бекъп
 */
defIfNot('BACKUP_FULL_OFFSET', 4 * 60);


/**
 * Период в който се прави binlog бекъп-a
 */
defIfNot('BACKUP_BINLOG_PERIOD', 7);


/**
 * Отместване за бинлог бекъп-а
 */
defIfNot('BACKUP_BINLOG_OFFSET', 0);


/**
 * Потребител с права за бекъп на mysql сървъра
 */
defIfNot('BACKUP_MYSQL_USER_NAME', 'backup');


/**
 * Парола на потребителя за бекъп
 */
defIfNot('BACKUP_MYSQL_USER_PASS', 'swordfish');


/**
 * MySql host за бекъп
 */
defIfNot('BACKUP_MYSQL_HOST', 'localhost');


/**
 * Брой пълни бекъпи, които да се пазят
 */
defIfNot('BACKUP_CLEAN_KEEP', 4);


/**
 * Период на почистването
 */
defIfNot('BACKUP_CLEAN_PERIOD', 24 * 60);


/**
 * Отместване на почистването в крон-а
 */
defIfNot('BACKUP_CLEAN_OFFSET', 53);


/**
 * Период на почистването
 */
defIfNot('BACKUP_FILEMAN_PERIOD', 13);


/**
 * Отместване в крон-а на архивирането на Fileman-a
 */
defIfNot('BACKUP_FILEMAN_OFFSET', 0);


/**
 * Поддиректория където ще се архивират файловете от fileman-a
 */
defIfNot('BACKUP_FILEMAN_PATH', 'fileman');

/**
 * Поддиректория където ще се архивират файловете от fileman-a
 */
defIfNot('BACKUP_FILEMAN_COUNT_FILES', 100);


/**
 * Път до масива за съхранение на файлове
 */
defIfNot('BACKUP_LOCAL_PATH', '/storage');


/**
 * Дали да се криптират съхранените файлове
 */
defIfNot('BACKUP_CRYPT', 'no');


/**
 * Парола за криптиране на съхранените файлове
 */
defIfNot('BACKUP_PASS', '');


/**
 * Данни за Amazon S3
 */
defIfNot('AMAZON_KEY', '');


/**
 * Данни за Amazon S3
 */
defIfNot('AMAZON_SECRET', '');


/**
 * Кофа на Amazon
 */
defIfNot('AMAZON_BUCKET', '');

/**
 * Клас 'backup_Setup' - Начално установяване на пакета 'backup'
 *
 *
 * @category  bgerp
 * @package   backup
 * @author    Dimitar Minekov<mitko@extrapack.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class backup_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Архивиране на системата: база данни, конфигурация, файлове";
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'fileman=0.1';
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array (
        
        'BACKUP_PREFIX'   => array ('varchar', 'caption=Имена на архивираните файлове->Префикс'),
        'BACKUP_STORAGE_TYPE'   => array ('enum(local=Локално, amazon=S3Amazon)', 'caption=Място за съхранение на архива->Тип'),
        'BACKUP_LOCAL_PATH' => array ('varchar', 'notNull, value=/storage, caption=Локален архив->Път'),
        'BACKUP_FILEMAN_COUNT_FILES' => array ('int', 'caption=По колко файла да се архивират наведнъж->Брой'),
        'BACKUP_MYSQL_USER_NAME'   => array ('varchar', 'caption=Връзка към MySQL (с права за бекъп)->Потребител, hint=(SELECT, RELOAD, SUPER)'),
        'BACKUP_MYSQL_USER_PASS'   => array ('password', 'caption=Връзка към MySQL (с права за бекъп)->Парола'),
        'BACKUP_MYSQL_HOST'     => array ('varchar', 'caption=Връзка към MySQL->Хост'),
        'BACKUP_CLEAN_KEEP'     => array ('int', 'caption=Колко пълни бекъп-и да се пазят?->Брой'),
        'BACKUP_CRYPT'     => array ('enum(yes=Да, no=Не)', 'notNull,value=no,maxRadio=2,caption=Сигурност на архивите->Криптиране'),
        'BACKUP_PASS'     => array ('password', 'caption=Сигурност на архивите->Парола'),
        "AMAZON_KEY" => array ('password(show)', 'caption=Амазон->Ключ'),
        "AMAZON_SECRET"    => array ('password(show)', 'caption=Амазон->Секрет'),
        "AMAZON_BUCKET"  => array('varchar', 'caption=Амазон->Кофа'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
        // Архивиране в локалната файлова система;
        //'backup_Local',
        // Архивиране на Amazon
        //'backup_Amazon'
        
        // Архивиране по ftp;
        //'backup_Ftp',
        
        // Архивиране по rsync
        //'backup_Rsync',
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {

        $html = parent::install();
        
        // Отключваме процеса, ако не е бил легално отключен
        backup_Start::unLock();
        
        $cfgRes = $this->checkConfig();

        // Имаме грешка в конфигурацията - не добавяме задачите на крона
        if (!is_null($cfgRes)) {
            
            return $html;
        }
        
        
        // Залагаме в cron
        $rec = new stdClass();
        $rec->systemId = 'BackupStartFull';
        $rec->description = 'Архивиране на пълните данни на MySQL';
        $rec->controller = 'backup_Start';
        $rec->action = 'full';
        $rec->period = BACKUP_FULL_PERIOD;
        $rec->offset = BACKUP_FULL_OFFSET;
        $rec->delay = 7;
        $rec->timeLimit = 2400;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'BackupStartBinLog';
        $rec->description = 'Архивиране на binlog-а на MySQL';
        $rec->controller = 'backup_Start';
        $rec->action = 'binlog';
        $rec->period = BACKUP_BINLOG_PERIOD;
        $rec->offset = BACKUP_BINLOG_OFFSET;
        $rec->delay = 9;
        $rec->timeLimit = 50;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'BackupClean';
        $rec->description = 'Изтриване на стари бекъпи';
        $rec->controller = 'backup_Start';
        $rec->action = 'clean';
        $rec->period = BACKUP_CLEAN_PERIOD;
        $rec->offset = BACKUP_CLEAN_OFFSET;
        $rec->delay = 15;
        $rec->timeLimit = 50;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'BackupFileman';
        $rec->description = 'Архивиране на файловете от fileman-a';
        $rec->controller = 'backup_Start';
        $rec->action = 'fileman';
        $rec->period = BACKUP_FILEMAN_PERIOD;
        $rec->offset = BACKUP_FILEMAN_OFFSET;
        $rec->delay = 5;
        $rec->timeLimit = 70;
        $html .= core_Cron::addOnce($rec);
        
        return $html;
    }
    

    /**
     * Проверява дали MySql-а е конфигуриран за binlog логове
     *
     * @return NULL|string
     */
    public function checkConfig()
    {

        $conf = core_Packs::getConfig('backup');

        $storage = core_Cls::get("backup_" . $conf->BACKUP_STORAGE_TYPE);
        
        // Проверяваме дали имаме права за писане в сториджа
        $touchFile = tempnam(EF_TEMP_PATH, "bgERP");
        file_put_contents($touchFile, "1");
        
        if (@$storage->putFile($touchFile) && @$storage->removeFile(basename($touchFile))) {
            unlink($touchFile);
        } else {
            unlink($touchFile);
            return "|*<li class='debug-error'>|Няма права за писане в |*" . get_class($storage) . "</li>";
        }
        
        // проверка дали всичко е наред с mysqldump-a
        $cmd = "mysqldump --no-data --no-create-info --no-create-db --skip-set-charset --skip-comments -h"
                . $conf->BACKUP_MYSQL_HOST . " -u"
                        . $conf->BACKUP_MYSQL_USER_NAME . " -p"
                                . $conf->BACKUP_MYSQL_USER_PASS . " " . EF_DB_NAME . " 2>&1";
        @exec($cmd, $output ,  $returnVar);
        
        if ($returnVar !== 0) {

            return "|*<li class='debug-error'>|mysqldump грешка при свързване|*!</li>";
        }
        
        // Проверка дали gzip е наличен
        @exec("gzip --version", $output,  $returnVar);
        if ($returnVar !== 0) {

            return "<li class='debug-error'>липсва gzip!</li>";
        }
        
        // Проверка дали tar е наличен
        @exec("tar --version", $output,  $returnVar);
        if ($returnVar !== 0) {
        
            return "<li class='debug-error'>липсва tar!</li>";
        }
        
        // Проверка дали МySql сървъра е настроен за binlog
        $res = @exec("mysql -u" . EF_DB_USER . "  -p" . EF_DB_PASS . " -N -B -e \"SHOW VARIABLES LIKE 'log_bin'\"");
        // Премахваме всички табулации, нови редове и шпации - log_bin ON
        $res = strtolower(trim(preg_replace('/[\s\t\n\r\s]+/', '', $res)));
        if ($res != 'log_binon') {
    
            return "<li class='debug-error'>MySQL-a не е настроен за binlog.</li>";
        }
    
        $res = @exec("mysql -u" . EF_DB_USER . "  -p" . EF_DB_PASS . " -N -B -e \"SHOW VARIABLES LIKE 'server_id'\"");
        // Премахваме всички табулации, нови редове и шпации - server_id 1
        $res = strtolower(trim(preg_replace('/[\s\t\n\r\s]+/', '', $res)));
        if ($res != 'server_id1') {

            return "<li class='debug-error'>MySQL-a не е настроен за binlog.</li>";
        }
        
        return NULL;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
