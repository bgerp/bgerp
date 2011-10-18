<?php


/**
 * Показва всички файлове
 */
class rip_Process extends core_Manager
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
	var $canRip = 'admin, rip';
    
    
    /**
     * 
     */
    var $loadList = 'plg_Created, rip_Wrapper, plg_SaveAndNew, plg_State';
  
    
    
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('fileId', 'key(mvc=rip_Files,select=file)', 'caption=Файл');
		$this->FLD('process', 'class(interface=rip_FileProcessingIntf, select=title)', 'caption=Процеси');
		$this->FLD('params', 'text', 'caption=Параметри');
		$this->FLD('state', 'enum(waiting=Чакащо,closed=Приключено)', 'caption=Статус, input=none');
	}
	
	
	/**
	 * След подготвяне на формата за добавяне на запис
	 * 
	 */
	function on_AfterPrepareEditForm($mvc, &$data)
	{
		$directoryId = rip_Directory::getCurrent();
		
		//Проверява дали сме избрали директория
		if (!isset($directoryId) || !$folder = rip_Directory::fetch("#id = '$directoryId'")) {
			
			$redirect = redirect(array('rip_Directory', 'default'), FALSE, tr("Моля изберете директория."));
			
			$res = new Redirect($redirect);
	
	        return FALSE;
		}
		
		$query = rip_Files::getQuery();
		$query->where("#directoryId = '$directoryId'");
		
		while ($rec = $query->fetch()) {
			$fileName = $rec->fileName;
			
			if (($rec->type == 'cliche') && ($rec->state != 'waiting')) {
				$files[$rec->id] = $fileName;
			}
		}
		
		if (!$files) {
			$redirect = redirect(array('rip_Files', 'default'), FALSE, tr("В директорията няма повече файлове за обработки."));
			
			$res = new Redirect($redirect);
	
	        return FALSE;
		}
		
		$form = $data->form;
		
		$form->setOptions('fileId', $files);
		
		$fileId = Request::get('fileId');
		if (isset($fileId)) {
			$fileId = intval($fileId);
			
			if (!isset($files[$fileId])) {
				$redirect = redirect(array('rip_Files', 'default'), FALSE, tr("На избрания от Вас файл не могат да се приложат обработки."));
			
				$res = new Redirect($redirect);
	
	        	return FALSE;
			}
			
			$form->setDefault('fileId', $fileId); //Селектира избрания файл
		}
		
		$form->title = "Файлове за обработка в директорията: {$folder->folder}";
				
	}
	
	
	/**
	 * 
	 * Подрежда в низходящ ред
	 */
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		$data->query->orderBy('#state', 'ASC');
        $data->query->orderBy('#createdOn', 'DESC');
	}
	
	
	/**
	 * Прави състоянието на чакащо
	 */
	function on_BeforeSave($mvc, $id, &$rec)
	{
		if ($rec->state != 'closed') {
			$rec->state = 'waiting';
		}
	}
	
	
	/**
	 * След като вкараме записите в модела извиква интерфейса
	 */
	function on_AfterSave($mvc, $id, $rec)
	{
		if ($rec->state == 'waiting') {
			$recFile = new stdClass();
			$fileId = $rec->fileId;
			$recFile->id = $fileId;
			$recFile->state = 'waiting';
	 		rip_Files::save($recFile);
	 		
	 		$instance = cls::getInterface('rip_FileProcessingIntf', $rec->process);
	 		$instance->processFile($fileId, $id);
	 				
		}
		
		return TRUE;
		
	}
	
	
	/**
	 * Взема FileHandler' а на файла, който искаме да се обработва от модела rip_Files
	 */
	function getFh($id)
	{
		$fileHnd = rip_Files::fetchField("#id = '$id'", 'file');
		
		return $fileHnd;
	}
	
	
	/**
	 * Връща размера на файла, ако има такива
	 */
	function getSize($id)
	{
		$size = rip_Files::fetchField("#id = '$id'", 'clicheSize');
		
		return $size;
	}
	
	
	/**
	 * Връща новото име с нужната преставка
	 */
	function newName($fh, $midExt = 'CONV')
	{
		$fileName = $this->getFileName($fh);
		if (!$fileName) {
			$fileName = $fh;
		}
		
		if (($dotPos = mb_strrpos($fileName, '.')) !== FALSE ) {
	    	$firstName = mb_substr($fileName, 0, $dotPos);
	        $ext = mb_substr($fileName, $dotPos);
	        $newName = $firstName . '.' . $midExt . $ext;
	    } else {
            $firstName = $fileName;
            $newName = $firstName . '.' . $midExt;
        }
		
		return $newName;
	}
	
	
	/**
	 * Взема FileHandler' а на файла, който искаме да се обработва от модела rip_Files
	 */
	function getFileName($fh)
	{
		$Fileman = cls::get('fileman_Files');
		$fileName = $Fileman->fetchByFh($fh, 'name');
		
		return $fileName;
	}
	
	
	/**
	 * Приема управлението след обработка на файловете
	 */
	function copyFiles($script)
	{
		$outFilePath = $script->tempDir . $script->outFileName;
		$fh = $this->addToFileman($outFilePath);
				
		$recFile = new stdClass();
		$recFile->state = 'active';
		$recFile->type = 'source';
		$recFile->file = $fh;
		$recFile->fileName = $script->outFileName;
		$recFile->directoryId = $script->currentDir;
			
		if (isset($script->returnlog)) {
			$size = $this->getSizeFromFile($script->returnlog);
 			rip_Process::log('adadad'.$size .'.'. $script->returnlog);
 			$recFile->clicheSize = $size;
 			
 		}
 		
 		if (isset($script->clicheSize)) {
 			$recFile->clicheSize = $script->clicheSize;
 		}
 		$croppedFileId = rip_Files::save($recFile);
		
 		$updFile = new stdClass();
 		$updFile->id = $script->fileId;
 		$updFile->state = 'active';
 		rip_Files::save($updFile);
 		
 		if (isset($script->combined)) {
 			if ($script->combined == 'embossingOld') {
 				$Emb = cls::get('rip_EmbossingOld');
 			} else {
 				$Emb = cls::get('rip_Embossing');
 			}
 			$Emb->processFile($croppedFileId, $script->processId);
 			
 			return FALSE;	
 		}
 		
 		$updProcess = new stdClass();
 		$updProcess->id = $script->processId;
 		$updProcess->state = 'closed';
 		rip_Process::save($updProcess);
 		
 		return TRUE;
 		
		
	}
	
	
	/**
	 * Записва файловете във fileman
	 */
	function addToFileman($filePath) 
	{
		$Fileman = cls::get('fileman_Files');
		$fh = $Fileman->addNewFile($filePath, 'Rip');	
		
		return $fh;
	}
	
	
	/**
	 * Прочита съдържанието на файла
	 */
	function getSizeFromFile($file) 
	{
		$incToCm = 2.54;
		$dpi = 2400;
		
		$content = file_get_contents($file);
		$content = strtolower($content);	
		
		$pattern = '/\s*height\s*=\s*[0-9]+\s*/';
		preg_match($pattern, $content, $match);
		$patternMatch = '/[^0-9]/';
		$height = preg_replace($patternMatch, '', $match);
  		
  		$pattern = '/\s*width\s*=\s*[0-9]+\s*/';
  		preg_match($pattern, $content, $match);
  		$patternMatch = '/[^0-9]/';
  		$width = preg_replace($patternMatch, '', $match);
  		
  		$width = intval($width[0]);
  		$height = intval($height[0]);
  		
  		$widthInc = $width / 2400;
  		$heightInc = $height / 2400;
  		
  		$widthCm = $widthInc * $incToCm;
  		$heightCm = $heightInc * $incToCm;
  		
  		$size = $widthCm * $heightCm;
  		
  		return $size;
		
		
	}
	
	
	
	
}
