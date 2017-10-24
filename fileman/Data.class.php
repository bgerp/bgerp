<?php



/**
 * Пътя до директорията за файловете е общ за всички инсталирани приложения
 */
defIfNot('FILEMAN_UPLOADS_PATH', substr(EF_UPLOADS_PATH, 0, strrpos(EF_UPLOADS_PATH, '/')) . "/fileman");


/**
 * Клас 'fileman_Data' - Указател към данните за всеки файл
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Data extends core_Manager {
    
    
    /**
     * Заглавие на модула
     */
    var $title = 'Данни';
    
	
	/**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin, debug';
    
	
	/**
	 * 
	 */
	public $canWrite = 'no_one';
	
	
	/**
	 * 
	 */
    var $loadList = 'plg_Created,fileman_Wrapper,plg_RowTools2,plg_Search';
    
    
    /**
     * 
     */
    public $searchFields = 'searchKeywords';
    
    
    /**
     * 
     */
    protected static $processFilesSysId = 'processFiles';
    
    
    /**
     * Да не се попълват ключовите думи при инициализация
     * 
     * @see plg_Search
     */
    public $fillSearchKeywordsOnSetup = FALSE;
    
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // хеш на съдържанието на файла
        $this->FLD("md5", "varchar(32)", array('caption' => 'MD5'));
        
        // Дължина на файла в байтове 
        $this->FLD("fileLen", "fileman_FileSize", array('caption' => 'Дължина'));
        
        // Път до файла
        $this->FNC("path", "varchar(10)", array('caption' => 'Път'));
        
        // Връзки към файла
        $this->FLD("links", "int", 'caption=Връзки,notNull');
        
        $this->FLD('archived', 'datetime(format=smartTime)', 'caption=Архивиран ли е?,input=none');
        
        $this->FLD('lastUse', 'datetime(format=smartTime)', 'caption=Последно, input=none');
        
        $this->FLD('processed', 'enum(no,yes)', 'caption=Извличане на ключови думу,column=none,single=none,input=none');
        
        $this->setDbUnique('fileLen,md5', 'DNA');
        
    }
    
    
    /**
     * Обновява времето на последно използване
     * 
     * @param integer $id
     * @param NULL|datetime $lastUse
     * 
     * @return boolean|NULL
     */
    public static function updateLastUse($id, $lastUse = NULL)
    {
        if (!$id) return FALSE;
        
        if (!($rec = self::fetch($id))) return FALSE;
        
        $lastUse = is_null($lastUse) ? dt::now() : $lastUse;
        
        if (!$rec->lastUse || ($lastUse > $rec->lastUse)) {
            
            $rec->lastUse = $lastUse;
            
            self::save($rec, 'lastUse');
            
            return TRUE;
        }
    }
    
    
    /**
     * Изчислява пътя към файла
     */
    static function on_CalcPath($mvc, $rec)
    {
        $rec->path = self::getGoodFilePath($rec, FALSE);
    }
    
    
    /**
     * Увеличава с 1 брояча, отчиташ броя на свързаните файлове
     */
    static function increaseLinks($id)
    {
        $rec = static::fetch($id);
        
        if($rec) {
            $rec->links++;
            self::resetProcess($rec);
        }
    }
    
    
    /**
     * Намалява с 1 брояча, отчиташ броя на свързаните файлове
     */
    function decreaseLinks($id)
    {
        $rec = $this->fetch($id);
        
        if($rec) {
            $rec->links--;
            
            if($rec->links < 0) $rec->links = 0;
            
            self::resetProcess($rec);
        }
    }


    /**
     * Връща размера на файла във вербален вид
     * 
     * @param numeric $id - id' то на файла
     * 
     * @return string $verbalSize - Вербалното представяне на файла
     */
    static function getFileSize($id)
    {
        // Размера в битове
        $sizeBytes = fileman_Data::fetchField($id, 'fileLen');
        
        // Инстанция на класа за определяне на размера
        $FileSize = cls::get('fileman_FileSize');
        
        // Вербалното представяне на файла
        $verbalSize = $FileSize->toVerbal($sizeBytes);
        
        return $verbalSize;
    }
    
    
    /**
     * Връща пътя до файла на съответния запис
     * Първо проверява с поддиректория, след това 
     * 
     * @param stdObject $rec
     * @param bolean $createDir - Създва директорията, ако липсва
     * 
     * @return string
     */
    public static function getGoodFilePath($rec, $createDir = TRUE)
    {
        $path = self::getFilePath($rec, TRUE, $createDir);
        
        // Ако директорията е на старото място - не е с поддиректории
        if (!is_file($path)) {
            $nPath = self::getFilePath($rec, FALSE, $createDir);
            
            if (is_file($nPath)) {
                $path = $nPath;
            }
        }
        
        return $path;
    }
    
    
    /**
     * Връща пътя до файла на съответния запис
     * 
     * @param mixed $rec - id' на файла или записа на файла
     * @param bolean $subDir - дали името да се раздели на поддиректрии
     * @param bolean $createDir - Създва директорията, ако липсва
     * 
     * @return string $path - Пътя на файла
     */
    static function getFilePath($rec, $subDir = TRUE, $createDir = TRUE)
    {
        if (is_numeric($rec)) {
            $rec = self::fetch($rec);
        }
        
        $path = FILEMAN_UPLOADS_PATH . "/" . static::getFileName($rec, $subDir);
        
        // Ако няма такава директория/поддиректория я създаваме
        if ($createDir) {
            $dirName = dirname($path);
            
            if ($dirName && !is_dir($dirName)) {
                if (!@mkdir($dirName, 0777, TRUE)) {
                    self::logErr("Грешка при създаване на директория: '{$dirName}'");
                }
            }
        }
        
        return $path;
    }
    
    
    /**
     * Връща името на файла
     * 
     * @param mixed $rec - id' на файла или записа на файла
     * @param bolean $subDir - дали името да се раздели на поддиректрии
     * 
     * @return string $name - Името на файла
     */
    static function getFileName($rec, $subDir = TRUE)
    {
        // Ако не е обектс
        if (is_numeric($rec)) {
        
            // Вземаме записа
            $rec = static::fetch($rec);
        }    
        
        $md5 = $rec->md5;
        
        // Ако ще се използват поддиректории
        if ($subDir) {
            $md5 = substr_replace($md5, '/', 4, 0);
            $md5 = substr_replace($md5, '/', 2, 0);
        }
        
        // Генерираме името
        $name = $md5 . "_" . $rec->fileLen;
        
        return $name;
    }
    

    /**
     * Абсорбира данните и връща обект с id' то или дали е създаден нов файл
     * 
     * @param string $data - Данните, които ще се абсорбират
     * @param string $type - Типа. Стринг или файл
     * 
     * @return object $res - Обект с id' то на данните и дали е създаден нов или е използван съществуващ
     * $res->id - id на данните
     * $res->new - Нов запис
     * $res->exist - Съществуващ запис
     */
    public static function absorb($data, $type='file') 
    {
        // Записа за даните
        $rec = new stdClass();
        
        // Резултата
        $res = new stdClass();
        
        // В зависимост от типа
        switch ($type) {
            case 'file':
                // Ако типа на данните е файл
                $rec->fileLen = filesize($data); 
                $rec->md5 = md5_file($data);  
            break;
            
            case 'string':
                // Ако типа е стринг
                $rec->fileLen = strlen($data);    
                $rec->md5 = md5($data);
            break;
            
            default:
                // Типа трябва да е от посочените
                expect(FALSE, 'Очаква се валиден тип.');
            break;
        }
        
        // Намираме id' то на файла, ако е съществувал
        $rec->id = static::fetchField("#fileLen = $rec->fileLen  AND #md5 = '{$rec->md5}'", 'id');
        
        $path = self::getGoodFilePath($rec);
        
        // Ако не е имал такъв запис
        if (!$rec->id || !@file_exists($path) || (@filesize($path) != $rec->fileLen)) {
            
            // Проверка за права в директорията
            $dir = pathinfo($path, PATHINFO_DIRNAME);
            if (!is_writable($dir)) {
                if (!@mkdir($dir, 0777, TRUE) || !is_writable($dir)) {
                    self::logErr("Няма права за запис в директорията '{$dir}'", $rec->id);
                }
            }
            
            // Ако типа е файл
            if ($type == 'file') {
                
                // Копираме файла
                expect(@copy($data, $path), "Не може да бъде копиран файла");
            } else {
                
                // Ако е стринг, копираме стринга
                expect(FALSE !== @file_put_contents($path, $data), "Не може да бъде копиран файла");
            }
            
            // Броя на ликовете да е нула
            $rec->links = 0;
            
            // Записваме
            $res->id = static::save($rec);
            
            // Отбелязваме, че е нов файл
            $res->new = TRUE;
        } else {
            
            // Ако е бил записан вземаме id' то
            $res->id = $rec->id;
            
            self::resetProcess($rec);
            
            // Отбелязваме, че е съществуващ файл
            $res->exist = TRUE;
        }
        
        // Връщаме резултата
        return $res;
    }
    
    
    /**
     * Връща най-новите n неархивирани файла
     *
     * @param int $n - броя на файлове
     *
     * @return array $res - Масив с md5 на най-новите n неархивирани файла
     */
    public static function getUnArchived($n = 10)
    {
        $fm = cls::get('fileman_Data');
        $query = $fm->getQuery();
        $query->where("#archived is NULL");
        $query->orderBy("createdOn", 'DESC');
        $query->limit($n);
        $res = array();
        while ($rec = $query->fetch()) {
            if ($rec) {
                $res[] = $rec;
            }
        }

        return ($res);
    }
    
    
    /**
     * Маркира неархивиран файл като архивиран
     *
     * @param int $id
     *
     */
    public static function setArchived($id)
    {
        $fm = cls::get('fileman_Data');
        $query = $fm->getQuery();
        //$query->where("#md5 = '[#1#]'", $md5);
        $rec = $query->fetch("$id");
        $rec->archived = dt::verbal2mysql();
        static::save($rec);
    }
    
    
    /**
     * Когато искаме да ресетнем, че файлът е преминал през обработка
     * 
     * @param integer|stdObject $rec
     */
    public static function resetProcess($rec)
    {
        $rec = self::fetchRec($rec);
        
        if (!$rec) return FALSE;
        
        if ($rec->processed == 'yes') {
            $rec->processed = 'no';
            fileman_Data::save($rec, 'processed');
        }
    }
    
    
    /**
     * Преди подготовка на ключовите думи
     */
    public static function on_BeforeGetSearchKeywords($mvc, &$searchKeywords, $rec)
    {
        $searchKeywords = $rec->searchKeywords;
        
        return FALSE;
    }
    
    
    /**
     * Пуска обработки на файла
     */
    function cron_ProcessFiles()
    {
        $timeLimit = core_Cron::getTimeLimit(self::$processFilesSysId);
        $endOn = dt::addSecs($timeLimit);
        core_App::setTimeLimit($timeLimit + 50);
        ini_set("memory_limit", fileman_Setup::get('DRIVER_MAX_ALLOWED_MEMORY_CONTENT'));
        
        $classesArr = core_Classes::getOptionsByInterface('fileman_ProcessIntf');
        
        $query = self::getQuery();
        $query->where("#processed != 'yes'");
        $query->orWhere("#processed IS NULL");
        
        // Данните с processed==no да са с по-голям приоритет
        $query->orderBy('processed', 'DESC');
        
        // По случаен принцип, с по-малък приоритет понякога да почва и от началото
        if (rand(0, 4) != 2) {
            $query->orderBy('lastUse', 'DESC');
            $query->orderBy('createdOn', 'DESC');
        } else {
            $query->orderBy('lastUse', 'ASC');
            $query->orderBy('createdOn', 'ASC');
        }
        
        $query->limit(100);
        
        while ($rec = $query->fetch()) {
            
            if (dt::now() >= $endOn) break;
            
            $procSuccess = NULL;
            foreach ($classesArr as $classId => $clsName) {
                
                if (dt::now() >= $endOn) break;
                
                $clsIntf = cls::getInterface('fileman_ProcessIntf', $classId);
                $procSuccess = $clsIntf->processFile($rec, $endOn);
                
                if ($procSuccess === FALSE) break;
            }
            
            if ($procSuccess !== FALSE && $rec->processed != 'yes') {
                $rec->processed = 'yes';
                self::save($rec, 'processed');
            }
        }
        
        $cnt = $query->count();
        $query->show('id');
        if ($cnt > 100) {
            fileman_Data::logDebug("Файлове за обработка: {$cnt}");
        }
    }
    
    
    /**
     * След начално установяване(настройка) установява папката за съхранение на файловете
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        if(!is_dir(FILEMAN_UPLOADS_PATH)) {
            if(!mkdir(FILEMAN_UPLOADS_PATH, 0777, TRUE)) {
                $res .= '<li class="debug-error">' . tr('Не може да се създаде директорията') . ' "' . FILEMAN_UPLOADS_PATH . '"</li>';
            } else {
                $res .= '<li class="debug-new">' . tr('Създадена е директорията') . ' "' . FILEMAN_UPLOADS_PATH . '"</li>';
            }
        }
        
        $rec = new stdClass();
        $rec->systemId = self::$processFilesSysId;
        $rec->description = 'Обработка на файловете';
        $rec->controller = $mvc->className;
        $rec->action = 'ProcessFiles';
        $rec->period = 3;
        $rec->offset = rand(0, 2);
        $rec->delay = 0;
        $rec->timeLimit = 60;
        
        $res .= core_Cron::addOnce($rec);
    }
    
    
    /**
     * Абсорбира данните от указания файл и
     * и връща ИД-то на съхранения файл
     * 
     * @deprecated
     */
    static function absorbFile($file, $create = TRUE, $source = 'path')
    {
        wp('deprecated');
        
        $rec = new stdClass();
        $rec->fileLen = filesize($file);
        $rec->md5 = md5_file($file);
        
        $rec->id = static::fetchField("#fileLen = $rec->fileLen  AND #md5 = '{$rec->md5}'", 'id');
        
        $path = self::getGoodFilePath($rec);

        if($create && ((!$rec->id) || !file_exists($path))) {
            if(@copy($file, $path)) {
                $rec->links = 0;
                $status = static::save($rec);
            } else {
                error("@Не може да бъде копиран файла", $file, $path);
            }
        } elseif ($rec->id) {
            self::resetProcess($rec);
        }
        
        return $rec->id;
    }
    
    
    /**
     * Абсорбира данните от от входния стринг и
     * връща ИД-то на съхранения файл
     * 
     * @deprecated
     */
    static function absorbString($string, $create = TRUE)
    {
        wp('deprecated');
        
        $rec = new stdClass();
        $rec->fileLen = strlen($string);
        $rec->md5 = md5($string);
        
        $rec->id = static::fetchField("#fileLen = $rec->fileLen  AND #md5 = '{$rec->md5}'", 'id');
        $path = self::getGoodFilePath($rec);
        
        if($create && ((!$rec->id) || !file_exists($path))) {
            
            expect(FALSE !== @file_put_contents($path, $string), $path, $rec);
            
            $rec->links = 0;
            $status = static::save($rec);
        } elseif ($rec->id) {
            self::resetProcess($rec);
        }
        
        return $rec->id;
    }
}
