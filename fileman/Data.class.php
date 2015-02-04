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
    var $loadList = 'plg_Created,fileman_Wrapper,plg_RowTools';
    
    
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
        
        // 
        $this->FLD('archived', 'datetime', 'caption=Архивиран ли е?,input=none');
        
        $this->setDbUnique('fileLen,md5', 'DNA');
        
    }
    
    
    /**
     * Абсорбира данните от указания файл и
     * и връща ИД-то на съхранения файл
     */
    static function absorbFile($file, $create = TRUE)
    {
        $rec = new stdClass();
        $rec->fileLen = filesize($file);
        $rec->md5 = md5_file($file);
        
        $rec->id = static::fetchField("#fileLen = $rec->fileLen  AND #md5 = '{$rec->md5}'", 'id');
        
        if(!$rec->id && $create) {
            $path = self::getFilePath($rec);
            
            if(@copy($file, $path)) {
                $rec->links = 0;
                $status = static::save($rec);
            } else {
                error("@Не може да бъде копиран файла", $file, $path);
            }
        }
        
        return $rec->id;
    }
    
    
    /**
     * Абсорбира данните от от входния стринг и
     * връща ИД-то на съхранения файл
     */
    static function absorbString($string, $create = TRUE)
    {
        $rec = new stdClass();
        $rec->fileLen = strlen($string);
        $rec->md5 = md5($string);
        
        $rec->id = static::fetchField("#fileLen = $rec->fileLen  AND #md5 = '{$rec->md5}'", 'id');
        
        if(!$rec->id && $create) {
            
            $path = self::getFilePath($rec);
            
            expect(FALSE !== @file_put_contents($path, $string));
            
            $rec->links = 0;
            $status = static::save($rec);
        }
        
        return $rec->id;
    }
    
    
    /**
     * Изчислява пътя към файла
     */
    static function on_CalcPath($mvc, $rec)
    {
        $rec->path = self::getFilePath($rec);
    }
    
    
    /**
     * Увеличава с 1 брояча, отчиташ броя на свързаните файлове
     */
    static function increaseLinks($id)
    {
        $rec = static::fetch($id);
        
        if($rec) {
            $rec->links++;
            static::save($rec, 'links');
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
            $this->save($rec, 'links');
        }
    }
    
    
    /**
     * След начално установяване(настройка) установява папката за съхранение на файловете
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        if(!is_dir(FILEMAN_UPLOADS_PATH)) {
            if(!mkdir(FILEMAN_UPLOADS_PATH, 0777, TRUE)) {
                $res .= '<li class="red">' . tr('Не може да се създаде директорията') . ' "' . FILEMAN_UPLOADS_PATH . '"</li>';
            } else {
                $res .= '<li class="green">' . tr('Създадена е директорията') . ' "' . FILEMAN_UPLOADS_PATH . '"</li>';
            }
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
     * 
     * @param mixed $rec - id' на файла или записа на файла
     * 
     * @return string $path - Пътя на файла
     */
    static function getFilePath($rec)
    {
        if (is_numeric($rec)) {
            $rec = self::fetch($rec);
        }
        
        $path = FILEMAN_UPLOADS_PATH . "/" . static::getFileName($rec);
        
        return $path;
    }
    
    
    /**
     * Връща името на файла
     * 
     * @param mixed $rec - id' на файла или записа на файла
     * 
     * @return string $name - Името на файла
     */
    static function getFileName($rec)
    {
        // Ако не е обектс
        if (is_numeric($rec)) {
        
            // Вземаме записа
            $rec = static::fetch($rec);
        }    
        
        // Генерираме името
        $name = $rec->md5 . "_" . $rec->fileLen;
        
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

        // Ако не е имал такъв запис
        if (!$rec->id) {
            
            // Пътя до файла
            $path = self::getFilePath($rec);
            
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
        while ($res[] = $query->fetch());

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
    
}