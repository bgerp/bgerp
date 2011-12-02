<?php


/**
 * Клас 'fileman_Folders' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    fileman
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class fileman_Folders extends core_Manager {
    
    
     /**
     *  Заглавие на модула
     */
    var $title = 'Папки';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        
        // Име на папката
        $this->FLD("name", "varchar(255)", array('notNull' => TRUE, 'caption' => 'Име'));
        
        // Информация за папката
        $this->FLD("info", "varchar", array('caption' => 'Информация') );
        
        // Тип на папката
        $this->FLD("allowedExtensions", "varchar", array('caption' => 'Допустими разширения') );
        
        // Максимален размер на файловете в папката
        $this->FLD("maxSize", "fileman_FileSize", array('caption' => 'Макс. размер на файл') );
        
        // Собственик на папката
        $this->FLD("ownerId", "key(mvc=core_Users)", array('caption' => 'Собственик'));
        
        // Състояние на папката
        $this->FLD("state", "enum(active=Активна,rejected=Оттеглена)", array('caption' => 'Състояние'));
        
        // Плъгини за контрол на записа и модифицирането
        $this->load('plg_Created,plg_Modified,Files=fileman_Files,plg_RowTools,fileman_Wrapper');
    }
    
    
    /**
     * Връща id на папка, според името и и собственика и.
     * Ако папката липсва - създава я.
     */
    function fetchByName($folder, $maxSize, $fileExt, $user)
    {
        $user = (int) $user;
        $fileExt = strtolower($fileExt);
        
        $rec = $this->fetch("#name = '{$folder}' AND #ownerId = $user");
        
        if(!$rec || ($rec->allowedExtensions != $fileExt) || ($rec->maxSize != $maxSize)) {
            //unset($rec->id);
            $rec->name = $folder;
            $rec->ownerId = $user;
            $rec->allowedExtensions = $fileExt;
            $rec->maxSize = $maxSize;
            
            $this->save($rec);
        }
        
        return $rec->id;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getAddFileInfo($id)
    {
        // Проверка дали текущия потребител има права над тази папка
        $rec = $this->fetch($id);
        
        $row = $this->recToVerbal($rec);
        
        if(!$row->allowedExtensions) {
            $row->allowedExtensions = 'All';
        }
        
        $row->allowedExtensions = str_replace(',' , ', ', $row->allowedExtensions);
        
        // ...
        $info->title = tr('Добавяне на файл в') . ' &quot;' . tr($row->name) . '&quot;';
        $info->allowedExtensions = $row->allowedExtensions;
        $info->maxFileSize = $row->maxSize;
        
        return $info;
    }
    
    
    /**
     * Дали дадения файл отговаря на условията в посочената папка?
     */
    function isValid(&$err, $folderId, $fileName, $filePath)
    {
        $rec = $this->fetch($folderId);
        
        $row = $this->recToVerbal($rec);
        
        if(trim($rec->allowedExtensions)) {
            $allowedExtensions = arr::make($rec->allowedExtensions, TRUE);
            
            if( ($dotPos = strrpos($fileName, '.')) !== FALSE ) {
                $ext = strtolower(mb_substr($fileName, $dotPos+1));
                
                if(!$allowedExtensions[$ext]) {
                    $err[] = "File extension|*<b> {$ext} </b>|is not allowed";
                }
            }
        }
        
        if(filesize($filePath) > $rec->maxSize) {
            $err[] = "File size exceeded the maximum size of|*<b>{$row->maxSize}</b>.";
        }
        
        if(!$err) return TRUE;
    }
    
    
    /**
     * Има ли права дадения потребител /или текущия да добавя файлове?
     */
    function haveRightToAddFiles($id, $userId = NULL)
    {
        return TRUE;
    }
    
    
    /**
     * Показва съобщението след като файлът е добавен
     */
    function addFile($folderId, $fh)
    {
        $rec = $this->Files->fetch("#fileHnd = '{$fh}'");
        
        $folderRec = $this->fetch($folderId);
        
        return new ET("<div style='padding:5px;border:solid 1px #ccc; background-color:#ffe;margin-bottom:5px;'> <center> {$rec->name}<BR> " . tr("е добавен в") ." \"{$folderRec->name}\" </center></div>");
    }
    
    
    /**
     * Показва формата за създаване на празен файл
     */
    function renderFormEmpty_($handler)
    {
        
        return 'CREATE EMPTY ' . $handler;
    }
    
    
    /**
     * Показва формата за избор на файл от папка
     */
    function renderFormFolders_($handler)
    {
        
        return 'SELECT FROM FOLDER ' . $handler;
    }
    
    
    /**
     *  Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm(&$mvc, $data)
    {
        $data->form->setHidden(array( 'hnd' => str::getRand()));
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function create($ownerId, $folderId = NULL )
    {
        $rec->folder = $folderId;
        
        $rec->name = $this->getName($name, $folderId);
        $rec->origin = $origin;
        $rec->state = 'draft';
        
        return $this->save($rec);
    }
    
    
    /**
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->allowedExtensions = str_replace(",", ", ", $row->allowedExtensions);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function setData($fHnd, $dataId)
    {
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function setFile($fHnd, $dataId)
    {
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function setName($fHnd, $name)
    {
    }
    
    
    /**
     * Промяна на състоянието в 'active'
     */
    function activation($fHnd)
    {
    }
    
    
    /**
     * Промяна на състоянието в 'rejected'
     */
    function rejected($fHnd)
    {
    }
}