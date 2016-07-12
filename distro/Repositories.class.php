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
    var $canRead = 'adminplg_Rejected';
    
    
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
        
        foreach ($linesArr as $line) {
            $resArr[] = distro_Repositories::parseLine($repoId, $line);
        }
        
        return $resArr;
    }
    
    
    /**
     * Парсира подадения ред от файла
     * 
     * @param integer $repoId
     * @param string $line
     * 
     * @return array[hash, rPath, date, name, isDir, act]
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
        $resArr['hash'] = self::getLineHash($line);
        $resArr['rPath'] = $path;
        $resArr['date'] = $date;
        $resArr['name'] = $file;
        
        list($actName, $isDir) = explode(',', $act);
        
        $resArr['isDir'] = ($isDir == 'ISDIR') ? TRUE : FALSE;
        
        if ($actName == 'CREATE') {
            $resArr['act'] = 'create';
        } elseif ($actName == 'MOVED_TO') {
            $resArr['act'] = 'add';
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
        
        try {
            $sshObj = new ssh_Actions($rec->hostId);
        } catch (core_exception_Expect $e) {
            self::logWarning('Грешка при свързване към хост: ' . $e->getMessage());
            reportException($e);
            
            return array();
        }
        
        $linesCnt = escapeshellarg($linesCnt);
        $path = rtrim($rec->path, '/');
        $path .= '/.system';
        $path = escapeshellarg($path);
        
        $cmd = "tail -n {$linesCnt} $path";
        
        $sshObj->exec($cmd, $lines);
        
        $lines = trim($lines);
        
        $linesArr = explode("\n", $lines);
        
        if ($removeDuplicated) {
            $linesArr = array_unique($linesArr);
        }
        
        $linesArr = array_reverse($linesArr);
        
        return $linesArr;
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
}
