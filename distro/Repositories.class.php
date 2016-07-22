<?php


/**
 * Модел, който съдържа пътищата до хранилищата
 *
 * @category  vendors
 * @package   distro
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class distro_Repositories extends core_Master
{
    
    
    /**
     * Папка за системните файлове
     */
    protected static $systemPath = '.system';
    
    
    /**
     * Файл в който ще записва inotifywait
     */
    protected static $systemFile = '.system';
    
    
    /**
     * Файл, който ще се пуска по крон
     */
    protected static $autorunFile = 'autorun.sh';
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Път до хранилище";
    
    
    /**
     * 
     */
    var $singleTitle = "Хранилище";
    
    
    /**
     * Път към картинка 16x16
     */
    var $singleIcon = 'img/16/repository.png';
    
    
    /**
     * 
     */
    var $canSingle = 'admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'admin';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'admin';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'distro_Wrapper, plg_RowTools2, plg_Created, plg_State, plg_Rejected';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('hostId', 'key(mvc=ssh_Hosts, select=name)', 'caption=Хост,input,mandatory');
        $this->FLD('name', 'varchar', 'caption=Име, mandatory');
        $this->FLD('path', 'varchar', 'caption=Път на хранилището, mandatory');
        $this->FLD('info', 'richtext', 'caption=Информация');
        $this->FLD('lineHash', 'varchar(32)', 'caption=Хеш, input=none');
        
        $this->setDbUnique('hostId');
    }
    
    
    /**
     * Парсира и връща линиите от системния файл в отдалечената директория
     * 
     * @param integer $repoId
     * @param number $linesCnt
     * @param boolean $removeDuplicated
     * 
     * @return array
     */
    public static function parseLines($repoId, $linesCnt = 1000, $removeDuplicated = TRUE)
    {
        $linesArr = distro_Repositories::getLines($repoId, $linesCnt, $removeDuplicated);
        
        $resArr = array();
        
        foreach ($linesArr as $line) {
            $resArr[] = distro_Repositories::parseLine($repoId, $line);
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща масив с всички хранилища
     * 
     * @return array $reposArr - Масив с id-та на всички хранилища
     */
    public static function getReposArr()
    {
        // Масив с всички хранилища
        static $reposArr = array();
        
        // Ако не е генериран преди
        if (!$reposArr) {
            
            // Вземаме всички записи
            $query = static::getQuery();
            $query->where("#state != 'rejected'");
            
            // Обхождаме записите
            while ($rec = $query->fetch()) {
                
                // Добавяме в масива
                $reposArr[$rec->id] = $rec->id;
            }
        }
        
        return $reposArr;
    }
    
    
    /**
     * Създава директория в хранилището
     * 
     * @param integer $repoId
     * @param string|NULL $name
     * 
     * @return FALSE|string
     */
    public static function createDir($repoId, $name)
    {
        $rec = self::fetch((int) $repoId);
        
        $sshObj = self::connectToRepo($rec);
        
        if ($sshObj === FALSE) return FALSE;
        
        $oPath = rtrim($rec->path, '/');
        $oPath .= '/' . $name;
        $path = escapeshellarg($oPath);
        
        $sshObj->exec('mkdir -p ' . $path);
        
        return $oPath;
    }
    
    
    /**
     * Връща md5 стойността на файла
     * 
     * @param integer $repoId
     * @param string $dir
     * @param string $name
     * 
     * @return FALSE|string
     */
    public static function getFileMd5($repoId, $dir, $name)
    {
        $rec = self::fetch((int) $repoId);
        
        $sshObj = self::connectToRepo($rec);
        
        if ($sshObj === FALSE) return FALSE;
        
        $path = rtrim($rec->path, '/');
        $path .= '/' . $dir . '/' . $name;
        $path = escapeshellarg($path);
        
        $c = $sshObj->exec('md5sum ' . $path, $output);
        
        if ($output) {
            list($md5) = explode(' ', $output, 2);
            
            $md5 = trim($md5);
            
            return $md5;
        }
        
        return FALSE;
    }
	
    
    /**
     * Активира състоянието на хранилището
     * 
     * @param integer $id - id на хранилище
     * 
     * @return integer|NULL - id на записа, ако се е активирал
     */
    public static function activateRepo($id)
    {
        // Вземаем записа
        $rec = static::fetch($id);
        
        // Ако не е бил активиран
        if ($rec->state != 'active') {
            
            // Активираме
            $rec->state = 'active';
            
            return static::save($rec);
        }
    }
    
    
    /**
     * Задава стойност за хеша за реда
     * 
     * @param integer $repoId
     */
    public static function setLineHash($repoId, $lineHash)
    {
        $nRec = new stdClass();
        $nRec->id = $repoId;
        $nRec->lineHash = $lineHash;
        
        self::save($nRec, 'lineHash');
    }
    
    
    /**
     * Връща масив с хранилищата и хеша на последния обработен ред
     * 
     * @return array
     */
    public static function getLinesHash()
    {
        $resArr = array();
        
        $query = self::getQuery();
        $query->where("#state != 'rejected'");

        while ($rec = $query->fetch()) {
            $resArr[$rec->id] = $rec->lineHash;
        }
        
        return $resArr;
    }
    
    
    /**
     * Парсира подадения ред от файла
     * 
     * @param integer $repoId
     * @param string $line
     * 
     * @return array - [lineHash, rPath, date, name, isDir, act]
     */
    protected static function parseLine($repoId, $line)
    {
        $rec = self::fetch((int) $repoId);
        
        $line = trim($line, '"');
        
        if (!trim($line)) return array();
        
        list($path, $file, $act, $date) = explode('" "', $line);
        
        $path = str_replace($rec->path, '', $path);
        $path = trim($path, '/');
        
        $resArr = array();
        $resArr['lineHash'] = self::getLineHash($line);
        $resArr['rPath'] = $path;
        $resArr['date'] = $date;
        $resArr['name'] = $file;
        
        list($actName, $isDir) = explode(',', $act);
        
        $resArr['isDir'] = ($isDir == 'ISDIR') ? TRUE : FALSE;
        
        if ($actName == 'CREATE' || $actName == 'MOVED_TO') {
            $resArr['act'] = 'create';
        } elseif ($actName == 'DELETE' || $actName == 'MOVED_FROM') {
            $resArr['act'] = 'delete';
        } elseif ($actName == 'MODIFY') {
            $resArr['act'] = 'edit';
        } else {
            $resArr['act'] = 'unknown';
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща линиите от системния файл в отдалечената директория
     * 
     * @param integer $repoId
     * @param number $linesCnt
     * @param boolean $removeDuplicated
     * 
     * @return array
     */
    protected static function getLines($repoId, $linesCnt = 1000, $removeDuplicated = TRUE)
    {
        $rec = self::fetch((int) $repoId);
        
        $sshObj = self::connectToRepo($rec);
        
        if ($sshObj === FALSE) return array();
        
        $linesCnt = escapeshellarg($linesCnt);
        $path = self::getSystemFile($rec->path);
        $path = escapeshellarg($path);
        
        $cmd = "tail -n {$linesCnt} $path";
        
        $sshObj->exec($cmd, $resLines);
        
        $resLines = trim($resLines);
        
        $linesArr = explode("\n", $resLines);
        
        if ($removeDuplicated) {
            $linesArr = array_unique($linesArr);
        }
        
        $linesArr = array_reverse($linesArr);
        
        return $linesArr;
    }
    
    
    /**
     * Прави връзка към сървъра по SSH
     * 
     * @param stdObject|integer $rec
     * 
     * @return FALSE|ssh_Actions
     */
    protected static function connectToRepo($rec)
    {
        $rec = self::fetchRec($rec);
        
        $repoConnectArr = array();
        
        if (!isset($repoConnectArr[$rec->id])) {
            try {
                $repoConnectArr[$rec->id] = new ssh_Actions($rec->hostId);
            } catch (core_exception_Expect $e) {
                self::logWarning('Грешка при свързване към хост: ' . $e->getMessage(), $rec->id);
                reportException($e);
                
                $repoConnectArr[$rec->id] = FALSE;
            }
        }
        
        return $repoConnectArr[$rec->id];
    }
    
    
    /**
     * Връща хеша за стринга
     * 
     * @param string $line
     * 
     * @return string
     */
    protected static function getLineHash($line)
    {
        
        return md5($line);
    }
    
    
    /**
     * Връща стринг, който периодично ще спира/стартира inotifywait програмата в хранилището
     * 
     * @param string $path
     * 
     * @return string
     */
    protected static function getAutorunSh($path)
    {
        $tpl = getTplFromFile('/distro/tpl/InotifyAutorun.txt');
        
        $systemPath = self::getSystemFile($path);
        
        $nObj = new stdClass();
        $nObj->regExPath = preg_quote($path, '/');
        $nObj->path = escapeshellarg($path);
        $nObj->sysPath = escapeshellarg($systemPath);
        $nObj->hour = '03';
        $nObj->min = rand(10, 59);
        $nObj->sleep = rand(30, 40);
        $nObj->pipe = '|'; // Това е заради превеждането на шаблона
        
        $tpl->placeObject($nObj);
        
        return $tpl->getContent();
    }
    
    
    /**
     * Връща пътя до системния файл, където ще се записват данните от inotifywait
     * 
     * @param string $path
     * 
     * @return string
     */
    protected static function getSystemFile($path)
    {
        $systemPath = rtrim($path, '/');
        $systemPath .= '/' . self::$systemPath . '/' . self::$systemFile;
        
        return $systemPath;
    }
    
    
    /**
     * Връща стринг, който при стартиране добавя изпълнянието на файла в кронтаба
     * 
     * @param string $path
     * 
     * @return string
     */
    protected function getStringToAddCrontab($path)
    {
        $path = escapeshellarg($path);
        
        $res = 'crontab -l > cron.res' . "\n";
        $res .= 'echo "* * * * * ' . $path . '" >> cron.res' . "\n";
        $res .= 'crontab cron.res' . "\n";
        $res .= 'rm cron.res';
        
        return $res;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     * 
     * @param distro_Repositories $mvc
     * @param stdClass $rec
     * @param array $fields
     * @param NULL|string $mode
     */
    public static function on_AfterCreate($mvc, $rec, $fields, $mode)
    {
        $sysDir = $mvc->createDir($rec->id, self::$systemPath . '/');
        
        if ($sysDir === FALSE) return ;
        
        $sshObj = self::connectToRepo($rec);
        
        // Добавяме скрипта във файла
        $autorunSh = $mvc->getAutorunSh($rec->path, $sysDir);
        $autorunSh = escapeshellarg($autorunSh);
        
        $path = rtrim($sysDir, '/');
        $path .= '/' . self::$autorunFile;
        $ePath = escapeshellarg($path);
        $sshObj->exec("echo {$autorunSh} >> $ePath");
        $sshObj->exec("chmod +x {$ePath}");
        
        // Добавяме стартирането на файла в кронтаба
        $addCrontabStr = $mvc->getStringToAddCrontab($path);
        $sshObj->exec($addCrontabStr);
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако има запис и се опитваме да изтрием
        if ($rec && ($action == 'delete')) {
            
            // Ако състоянието е активно
            if ($rec->state == 'active' || $rec->state == 'rejected') {
            
				// Да не може да се изтрие
                $requiredRoles = 'no_one';
            }
        }
    }
}
