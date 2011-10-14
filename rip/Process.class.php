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
    var $loadList = //, , plg_Rejected,, plg_RefreshRows, plg_SaveAndNew
					'plg_Created, rip_Wrapper';
  
    
    
	/**
	 * Описание на модела
	 */
	function description()
	{
		//$this->FLD('fileId', 'key(mvc=rip_Files,select=file)', 'caption=Файл', array('attr'=>array('disabled'=>'disabled;')));
		//$this->FLD('fileId', 'key(mvc=rip_Files,select=file)', 'caption=Файл, input=none');
		$this->FLD('fileId', 'key(mvc=rip_Files,select=file)', 'caption=Файл');
		//$this->FLD('convert', 'enum(ProcessEmbossing=Ембосиране,ProcessEmbossingOld=СтароЕмбосиране,ProcessRip=Растеризиране,ProcessBlackFill=Запълване с черно)', 'caption=Обработка');
		//$this->FLD('classId', 'class(interface=acc_RegisterIntf,select=title,allowEmpty)', 
        //	'caption=Регистър,input=none');
		$this->FLD('process', 'class(interface=rip_FileProcessingIntf, select=title)', 'caption=Процеси');
		$this->FLD('params', 'text', 'caption=Параметри');
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
		$form->rec->ret_url = 'add';
				
	}
	
	
	function on_AfterSave($mvc, $id, $rec)
	{
		$recFile = new stdClass();
		$recFile->id = $rec->fileId;
		$recFile->state = 'waiting';
 		rip_Files::save($recFile);
 		
 		/////////////////////////////////
 		$instance = cls::getInterface('rip_FileProcessingIntf', $rec->process);
 		bp($instance->processFile($rec->fileId));
 		
 		
 		
 		
 		
 		
 		////////////////////////////		
 		
 		
 		$redirect = redirect(array('rip_Process', 'add'), FALSE, tr("Файлът успешно беше изпратен за обработка."));
			
		$res = new Redirect($redirect);

        return FALSE;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
