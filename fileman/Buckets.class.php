<?php



/**
 * Клас 'fileman_Buckets' - Определя еднородни по права за достъп хранилища за файлове
 *
 *
 * @category  bgerp
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Buckets extends core_Manager {


    /**
     * Заглавие на модула
     */
    public $title = 'Кофи за файлове';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Кофа за файлове';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'admin';


	/**
	 * Кой може да добавя?
	 */
	public $canAdd = 'no_one';


	/**
	 * Кой може да редактира?
	 */
	public $canEdit = 'admin';


	/**
	 * Кой може да редактира данните добавени от системата?
	 */
	public $canEditsysdata = 'admin';


	/**
	 * Кой може да изтрива?
	 */
	public $canDelete= 'no_one';
	
	
	/**
	 * 
	 */
    public $loadList = 'plg_Translate, plg_Created, plg_Modified, Files=fileman_Files, plg_RowTools2, fileman_Wrapper';
    
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD("name", "varchar(255)", 'notNull,caption=Име');
        $this->FLD("info", "varchar", 'caption=Информация, translate');
        $this->FLD("extensions", "text", 'caption=Допустими разширения');
        $this->FLD("maxSize", "fileman_FileSize", 'caption=Макс. размер');
        $this->FLD("rolesForAdding", "keylist(mvc=core_Roles,select=role,groupBy=type)", 'caption=Роли->за добавяне');
        $this->FLD("rolesForDownload", "keylist(mvc=core_Roles,select=role,groupBy=type)", 'caption=Роли->за сваляне');
        $this->FLD("lifetime", "int", 'caption=Живот');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Проверява дали потребителя има права за добавяне в кофата
     * 
     * @param integer $bucketId
     * 
     * @return boolean
     */
    public static function canAddFileToBucket($bucketId)
    {
        $bRec = self::fetch((int)$bucketId);
        expect($bRec);
        
        if (!$bRec->rolesForAdding) return TRUE;
        
        if (haveRole($bRec->rolesForAdding)) return TRUE;
        
        return FALSE;
    }
    
	
    /**
     * Връща id на кофата, според името и
     */
    static function fetchByName($bucketName, $part = 'id')
    {
        // Името да е в долен регистър
        $bucketName = mb_strtolower($bucketName);
        $rec = static::fetch(array("LOWER(#name) = '[#1#]'", $bucketName));

        if($part == '*') {

            return $rec;
        } else {

            return $rec->{$part};
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        if ($data->form->rec->id) {
            $data->form->setReadOnly('name');
        }
    }


    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, &$res, $rec)
    {
        $Roles = cls::get('core_Roles');

        if(!$rec->rolesForDownload) {
            $rec->rolesForDownload = '|' . $Roles->fetchByName('user') . '|';
        }

        if(!$rec->rolesForAdding) {

            $rec->rolesForAdding = '|' . $Roles->fetchByName('user') . '|';
        }

        if(!($rec->lifetime>0)) {
            $rec->lifetime = 1000000000;
        }
    }


    /**
     * @todo Чака за документация...
     */
    function getAddFileInfo($bucketId)
    {
        // Проверка дали текущия потребител има права над тази папка
        $rec = $this->fetch($bucketId);

        $row = $this->recToVerbal($rec);
        $info = new stdClass();

        if($row->extensions) {
            $extArr = explode(',', $row->extensions);
            
            foreach($extArr as $ext) {
                $ext = trim($ext);
                
                if (!$ext) continue;
                
                if(strpos($ext, '/')) {
                    $mimeExtArr = fileman_Mimes::getExtByMime($ext);
                    $mimeExtArr = arr::make($mimeExtArr, TRUE);
                    
                    $extStrArr = arr::make($info->extensions, TRUE);
                    $extStrArr = arr::combine($extStrArr, $mimeExtArr);
                    $info->extensions = implode(', ', $extStrArr);
                    
                    $info->accept .= ($info->accept ? ', ' : '') . mb_strtolower(trim($ext));
                } else {
                    
                    $extMimeArr = fileman_Mimes::getMimeByExt($ext);
                    $extMimeArr = arr::make($extMimeArr, TRUE);
                    
                    $acceptArr = arr::make($info->accept, TRUE);
                    $acceptArr = arr::combine($acceptArr, $extMimeArr);
                    $acceptArr['.' . $ext] = '.' . $ext;
                    $info->accept = implode(',', $acceptArr);
                    
                    $info->extensions .= ($info->extensions ? ', ' : '') . mb_strtolower(trim($ext));
                }
            }
        }
        
        // Попълване на информацията
        $info->title = tr("Добавяне на файл(ове)");

        $info->maxFileSize = $row->maxSize;
        
        return $info;
    }


    /**
     * Дали дадения файл отговаря на условията в посочената папка?
     */
    function isValid(&$err, $bucketId, $fileName, $filePath)
    {
        $rec = $this->fetch($bucketId);

        $row = $this->recToVerbal($rec);

        if(trim($rec->extensions)) {
            $extensions = arr::make($rec->extensions, TRUE);

            if(($dotPos = mb_strrpos($fileName, '.')) !== FALSE) {
                $ext = mb_strtolower(mb_substr($fileName, $dotPos + 1));

                if($ext && !$extensions[$ext]) {
                    $err[] = "Разширението на файла |* <b>{$ext}</b> | не е в допустимите|*: {$row->extensions}";
                }
            }
        }

        if(filesize($filePath) > $rec->maxSize) {
            $err[] = "Допустимия размер за файл в кофата е|*: <b>{$row->maxSize}</b>.";
        }

        if(!$err) return TRUE;
    }


    /**
     * Показва съобщението след като файлът е добавен
     */
    function getInfoAfterAddingFile($fh)
    {
        // Линк към сингъла на файла
        $link = fileman::getLinkToSingle($fh, FALSE, array('target' => '_blank', 'limitName' => 32));
        
        return new ET("<div class='uploaded-title'> <b>{$link}</b> </div>");
    }


    /**
     * Създаване на 'Кофа'. Ако има съществуваща, със същото име, то тя се обновява
     */
    static function createBucket($name, $info = '', $extensions = '', $maxSize = NULL,
        $rolesForDownload = NULL, $rolesForAdding = NULL, $lifetime = NULL)
    {
        $rec = self::fetch(array("#name = '[#1#]'", $name));
        
        if (!$rec) {
            $rec = new stdClass();
        }
        
        $FileSize = cls::get('fileman_FileSize');
        $maxSize = $FileSize->fromVerbal($maxSize);
        
        if($rec->id) {
            $res = "<li> Съществуваща кофа за файлове \"{$name}\"</li>";
            $maxSize = max($maxSize, $rec->maxSize);
            
            if ($rec->modifiedBy != -1) {
                
                if ($maxSize > $rec->maxSize) {
                    $rec->maxSize = $maxSize;
                    self::save($rec, 'maxSize');
                    
                    $res .= "<li style='color:darkgreen;'> Променен допустим размер на файловете в кофата</li>";
                } else {
                    $res .= "<li style='color: #660000;'> Без промяна на настройките на кофата</li>";
                }
                
                return $res;
            }
        } else {
            $res = "<li style='color:green;'> Създаване на кофа за файлове \"{$name}\"</li>";
        }
        
        $rec->name = $name;
        $rec->info = $info;
        $rec->maxSize = $maxSize;
        $rec->rolesForDownload = core_Roles::getRolesAsKeylist($rolesForDownload);
        $rec->rolesForAdding = core_Roles::getRolesAsKeylist($rolesForAdding);
        $rec->lifetime = $lifetime;
        $rec->extensions = $extensions;

        self::save($rec);

        return $res;
    }


    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->extensions = str_replace(",", ", ", $row->extensions);
    }
    
    
    /**
     * Връща масив с позволените разширения за съответния подаден стринг
     * 
     * @param string $extensions - Разширенията
     * 
     * @return array $res - Масив с разширенията
     */
    static function getAllowedExtensionArr($extensions)
    {
        // Масива, който ще връщаме
        $resArr = array();
        
        // Ако няма текст, връщаме празен масив
        if (!trim($extensions)) return $resArr;
        
        // Разделяме масива
        $extensionsArr = explode(',', $extensions);
        
        // Обхождаме резултатите
        foreach ((array)$extensionsArr as $extension) {
            
            // Тримваме разширението
            $extension = trim($extension);
            
            // Ако няма разширение, прескачаме
            if (!$extension) continue;
            
            // Ако разширението няма наклонена черта
            if (strpos($extension, '/') === FALSE) {
                
                // Вземаме разширението в долен регистър
                $extension = mb_strtolower($extension);
                
                // Добавяме в масива
                $resArr[$extension] = $extension;
            } else {
                
                // Ако разширението има наклонене черта, следователно е mime
                
                // Вземаме масива с раширенията от MIME
                $mimeExtArr = fileman_Mimes::getExtByMime($extension);
                
                // Обхождаме масива
                foreach ((array)$mimeExtArr as $mimeExt) {
                    
                    // Тримваме разширението
                    $mimeExt = trim($mimeExt);
                    
                    // Ако няма прескачаме
                    if (!$mimeExt) continue;
                    
                    // Вземаме разширението в долен регистър
                    $mimeExt = mb_strtolower($mimeExt);
                    
                    // Добавяме в масива
                    $resArr[$mimeExt] = $mimeExt;
                }
            }
        }
        
        return $resArr;
    }
}
