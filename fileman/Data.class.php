<?php

/**
 * Пътя до директорията за файловете е общ за всички инсталирани приложения
 */
defIfNot('FILEMAN_UPLOADS_PATH', substr(EF_UPLOADS_PATH, 0, strrpos(EF_UPLOADS_PATH, '/')) . "/fileman");


/**
 * Клас 'fileman_Data' - Указател към данните за всеки файл
 *
 *
 * @category   Experta Framework
 * @package    fileman
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class fileman_Data extends core_Manager {
    
    
    /**
     *  Заглавие на модула
     */
    var $title = 'Данни';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        
        // хеш на съдържанието на файла
        $this->FLD("md5", "varchar(32)", array('caption' => 'MD5') );
        
        // Дължина на файла в байтове 
        $this->FLD("fileLen", "fileman_FileSize", array( 'caption' => 'Дължина'));
        
        // Тип на файла
        $this->FLD("typeId", "key(mvc=fileman_Types)", 'caption=Тип');
        
        // Път до файла
        $this->FNC("path", "varchar(10)", array('caption' => 'Път') );
        
        // Връзки към файла
        $this->FLD("links", "int", 'caption=Връзки,notNull');
        
        // Анализ на файла
        $this->FLD("analyze", "text", array( 'caption' => 'Анализ'));
        
        // От кога е анализа на файла?
        $this->FLD("lastAnalyze", "datetime", array( 'caption' => 'Последен анализ'));
        
        // Кога последно е използван този dataFile?
        $this->FLD("lastUsedOn", "datetime", array( 'caption' => 'Последно използване'));
        
        // Състояние на файла
        $this->FLD("state", "enum(draft=Чернова,active=Активен,deleted=Изтрит)", array('caption' => 'Състояние'));
        
        // Указател към FileData с икона на файла (64х64)
        $this->FLD("iconId", "int", array('caption' => 'Икона'));
        
        // Изглед, прослушване
        $this->FLD("previewId", "int", array( 'caption' => 'Превю'));
        
        $this->setDbUnique('fileLen,md5', 'DNA');
        
        $this->load('plg_Created,fileman_Wrapper');
    }
    
    
    /**
     * Абсорбира данните от указания файл и
     * и връща ИД-то на съхранения файл
     */
    function absorbFile($file)
    {
        $rec->fileLen = filesize($file);
        $rec->md5 = md5_file($file);
        
        $rec->id = $this->fetchField("#fileLen = $rec->fileLen  AND #md5 = '{$rec->md5}'", 'id');
        
        if(!$rec->id) {
            $path = FILEMAN_UPLOADS_PATH . "/" . $rec->md5 ."_" . $rec->fileLen;
            if(@copy($file, $path)) {
                $rec->links = 0;
                $status = $this->save($rec);
            } else {
                error("Не може да бъде копиран файла", array($file, $dir) );
            }
        }
                
        return $rec->id;
    }
    
    
    /**
     * Абсорбира данните от от входния стринг и
     * връща ИД-то на съхранения файл
     */
    function absorbString($string)
    {
        $rec->fileLen = strlen($string);
        $rec->md5 = md5($string);
        
        $rec->id = $this->fetchField("#fileLen = $rec->fileLen  AND #md5 = '{$rec->md5}'", 'id');
        
        if(!$rec->id) {

            $path = FILEMAN_UPLOADS_PATH . "/" . $rec->md5 ."_" . $rec->fileLen;

            if(@file_put_contents($path, $string)) {
                $rec->links = 0;
                $status = $this->save($rec);
            } else {
                error("Не може да бъдат записани данните файла", array($string, $path) );
            }
        }
                
        return $rec->id;
    }

    
    /**
     * Изчислява пътя към файла
     */
    function on_CalcPath($mvc, $rec )
    {
        $rec->path = FILEMAN_UPLOADS_PATH . "/" . $rec->md5 ."_" . $rec->fileLen;
    }
    
    
    /**
     * Увеличава с 1 брояча, отчиташ броя на свързаните файлове
     */
    function increaseLinks($id)
    {
        $rec = $this->fetch($id);
        
        if($rec) {
            $rec->links++;
            $this->save($rec, 'links');
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
     * След сетъп установява папката за съхранение на файловете
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        if(!is_dir(FILEMAN_UPLOADS_PATH)) {
            if( !mkdir(FILEMAN_UPLOADS_PATH, 0777, TRUE) ) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') . ' "' . FILEMAN_UPLOADS_PATH . '"</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' . FILEMAN_UPLOADS_PATH . '"</font>';
            }
        }
        //TODO да се премахне
        $res .= $this->renameFilesInUploadPath();
        
    }
    
    
    /**
     * Преименува всички файлове в директорията на fileman, които са с грешно име ($md5.space_.$len) на ($md5._.$len)
     * TODO да се премахне
     */
    function renameFilesInUploadPath()
    {
        if(!Request::get('Full')) return;

    	$files = scandir(FILEMAN_UPLOADS_PATH);
    	
    	$query = fileman_Data::getQuery();
		$query->where("1=1");
		$i=0;
		while ($rec = $query->fetch()) {
			
			$oldName = FILEMAN_UPLOADS_PATH . "/" . $rec->md5 ." _" . $rec->fileLen;
			$newName = FILEMAN_UPLOADS_PATH . "/" . $rec->md5 ."_" . $rec->fileLen;
			
			if (is_file($oldName)) {
				if (rename($oldName, $newName)) {
					$res .= "\n<li> Успешно преименуване на файла с id: {$rec->id} на {$newName}</li>";
				} else {
					$res .= "\n<li style='color:red'> Не може да се преименува файла {$oldName} с id: {$rec->id}</li>";
				}
			} else {
				if (!is_file($newName)) {
					$i++;
					$res .= "\n<li style='color:red'> Внимание! Файлът липсва. Файлът с id {$rec->id} липсва.</li>";
				}
			}
			
		}
    	
		if ($i) {
			$res .= "\n<li style='background-color:red'> Внимание! Имате {$i} записа в модела, които нямат аналог във файловата система.</li>";
		}
		
		$res .= "\n<li style='color:green'> Преименуването завърши. </li>";
		
    	return $res;
    }
}