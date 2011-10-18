<?php


/**
 * Показва всички файлове
 */
class rip_Files extends core_Manager
{
	

    /**
     *  Заглавие на таблицата
     */
    var $title = "Файлове";
    
    
    /**
     * Права
     */
    var $canRead = 'admin, rip';
    
    
    /**
     *  
     */
    var $canEdit = 'admin, rip';
    
    
    /**
     *  
     */
    var $canAdd = 'admin, rip';
    
    
    /**
     *  
     */
    var $canView = 'admin, rip';
    
    
    /**
     *  
     */
    var $canList = 'admin, rip';
    
    
    /**
     *  
     */
    var $canDelete = 'admin, rip';
	
    
    
    /**
     * 
     */
    var $loadList = 'plg_Created, rip_Wrapper, plg_State, plg_RowTools, plg_SaveAndNew';
  
    
    
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('file', 'fileman_FileType(bucket=Rip)', 'caption=Файл, mandatory');
		$this->FLD('fileName', 'varchar', 'caption=Име, input=none');
		$this->FLD('directoryId', 'key(mvc=rip_Directory,select=folder)', 'caption=Папка', 
			array('attr'=>array('disabled'=>'disabled;')));
		$this->FLD('comment', 'text', 'caption=Коментар');
		$this->FLD('clicheSize', 'varchar', 'caption=Размер');
		$this->FLD('clicheCopies', 'int', 'caption=Копия');
		$this->FLD('type', 'enum(source=Изходен,cliche=За клише,ready=Готов)', 'caption=Вид,notNul');
				
	}
	
	
	/**
	 * 
	 * Преди подготвянето на данните, проверява дали има избрана директория,
	 * и показва само файловете, които са в избраната директория.
	 * Сканира текущия файл за нови файлове. Ако има такива ги добавя в системата.
	 */
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{	
		$directoryId = rip_Directory::getCurrent();
		//Проверява дали сме избрали директория
		if (!isset($directoryId) || !$folder = rip_Directory::fetch("#id = '$directoryId'")) {
			
			$redirect = redirect(array('rip_Directory', 'default'), FALSE, tr("Моля изберете директория."));
			
			$res = new Redirect($redirect);
	
	        return FALSE;
		}
		
		$query = $this->getQuery();
		$query->where("#directoryId = '$directoryId'");
		
		while ($rec = $query->fetch()) {
			$filesModel[] = $rec;
		}
		
    	$directory = EF_RIP_DIRECTORY_PATH . $folder->folder . '/';
		
		$files = scandir($directory);
				
		foreach ($files as $keyFiles => $file) {
			if ($file == '.' || $file == '..') {
				unset($files[$keyFiles]);
			}
		}
		if (count($filesModel)) {
			foreach ($filesModel as $rec) {
				
				$key = array_search($rec->fileName, $files);
				
				if ($key !== FALSE) {
					unset($files[$key]); 
				}
			}	
		}
		
		if (count($files)) {
			foreach ($files as $file) {
				$filePath = $directory.$file;
				
				$Fileman = cls::get('fileman_Files');
				$fh = $Fileman->addNewFile($filePath, 'Rip');	
				
				rip_Directory::log("Jobs->update(): FileMan: {$fh} from Directory");
				
				@unlink($filePath);
				
				$recFile = new stdClass();
				$recFile->file = $fh;
				$recFile->directoryId = $directoryId;
				$recFile->type = 'source';
				$fileId = self::save($recFile);
				
				$this->log("File saved from directory: " . $fileId);
			}
			
			$redirect = redirect(array($mvc), FALSE, tr("Добавени са нови файлове в модела, от директорията."));
			
			$res = new Redirect($redirect);
			
			return FALSE;
		}
		
		$data->title = "Съдържание на директорията: {$folder->folder}";
		
        $data->query->where("#directoryId = $directoryId");
        $data->query->orderBy('#type', 'ASC');
        $data->query->orderBy('#createdOn', 'DESC');
        	
	}
	
	
	/**
	 * 
	 * Преди рендирането не листовия изгледа поледо "папка"
	 */
	function on_BeforeRenderListTable($mvc, &$res, $data)
	{
		unset($data->listFields['directoryId']);
		unset($data->listFields['fileName']);
		
	}
	
		
	/**
     * Извиква се след подготовка на формата за редактиране
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {  	
        $data->form->setField('directoryId', array('value' => rip_Directory::getCurrent()));
        
        if (!empty($data->form->rec->id)) {
        	$fileName = $data->form->rec->fileName;
        	$data->form->title = "Редактиране на запис: {$fileName}";
        	$data->form->setField('file', array('input' => 'none'));
        }
    }
	
	
	/**
     * Изпълнява се след вкарване на данни в полетата
     */
	function on_AfterInputEditForm($mvc, &$form)
    {
        if (!$form->isSubmitted()) {
        	
        	return ;
        }
		
		$form->rec->directoryId = rip_Directory::getCurrent();
    }
	
	
    /**
     * Изпълнява се след вкараване на запис в модела
     */
	function on_BeforeSave($mvc, $id, &$rec)
	{
		if (!isset($rec->id)) {
			$fh = $rec->file;
			
			$filemanFiles = cls::get('fileman_Files');
	    	$filePath = $filemanFiles->fetchByFh($fh, 'path');
	    	$fileName = $filemanFiles->fetchByFh($fh, 'name');
	    	rip_Directory::log("Rip->Save: NewFileHandler: {$fh} - $fileName");
	    	$ext = mb_strtolower(mb_substr($fileName, mb_strrpos($fileName, '.') + 1));
	    		
			if (($ext == 'tiff' || $ext == 'tif') && ($rec->type == 'source')) {
				$rec->type = 'cliche';
			}
	    	
	    	$folder = rip_Directory::fetch("#id = '$rec->directoryId'");
	    	
	    	$outDest = EF_RIP_DIRECTORY_PATH . $folder->folder . '/' . $fileName;
	    	
	    	$rec->fileName = $fileName;
	    	rip_Directory::log("{$fh} - {$fileName} - {$filePath}");
	    	//Проверява дали файла със същото име съществува в директорията
	    	if (is_file($outDest)) {
	    		
	    		$md5Out = md5_file($outDest);
	    		$md5Fh = $filemanFiles->fetchByFh($fh, 'md5');
	    		
	    		if ($md5Out == $md5Fh) {
	    			rip_Directory::log("Rip->Save: Файлът съществуваше в директорията.");
	    			
	    			return FALSE;
	    		} else {
	    			$newName = $this->getUniqName($outDest);
	    			
	    			rename($outDest, $newName);
	    			
	    			rip_Directory::log("Rip->Save: Файлът в директорията със същото име беше преименуван на \"{$newName}\".");
	    		}
	    	}
	    	//Копира файла в избраната директория
	    	if (!copy($filePath, $outDest)) {
	    		bp($filePath, $outDest);
	    		$redirect = redirect(array($mvc, 'default'), FALSE, tr("Файлът не може да бъде копиран."));
				rip_Directory::log("Rip->Save: Файлът не може да бъде копиран.");
				
				$res = new Redirect($redirect);
		
		        return FALSE;
	    	}
		}
		
		
	}
		
	
	/**
	 * 
	 * Добавя бутон на файловете, които са за клишета
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		if ($rec->type == 'cliche') {
			if ($rec->state == 'waiting') $attr['disabled'] = 'disabled';
			
			$row->type = HT::createBtn('Обработка', array('rip_Process', 'add/?fileId=' . $rec->id), FALSE, FALSE, $attr);
		}
	}
	
	
	/**
	 * 
	 * Проверява и генерира уникално име на файла
	 */
	function getUniqName($fileName)
	{
		$path_parts = pathinfo($fileName);
        $baseName = $path_parts['basename'];
        $directory = $path_parts['dirname'];
        
		$fn = $baseName;
        
        if (($dotPos = mb_strrpos($baseName, '.')) !== FALSE ) {
	    	$firstName = mb_substr($baseName, 0, $dotPos);
	        $ext = mb_substr($baseName, $dotPos);
	    } else {
            $firstName = $baseName;
            $ext = '';
        }
        
        $i = 0;
        $files = scandir($directory);
	    
        // Циклим докато генерирме име, което не се среща до сега
        while (in_array($fn, $files)) {
            $fn = $firstName . '_' . (++$i) . $ext;
        }
        
        return $directory . '/' . $fn;	
        
        
	}
	
	
}