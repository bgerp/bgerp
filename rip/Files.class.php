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
    var $loadList = //, , plg_Rejected,, plg_RefreshRows, plg_SaveAndNew
					'plg_Created, rip_Wrapper, plg_State, plg_RowTools';
  
    
    
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('file', 'fileman_FileType(bucket=Rip)', 'caption=Файл, mandatory');
		$this->FLD('directoryId', 'key(mvc=rip_Directory,select=folder)', 'caption=Папка', 
			array('attr'=>array('disabled'=>'disabled;')));
		$this->FLD('comment', 'text', 'caption=Коментар');
		$this->FLD('clicheSize', 'varchar', 'caption=Размер');
		$this->FLD('clicheCopies', 'int', 'caption=Копия');
		$this->FLD('type', 'enum(source=Изходен,cliche=За клише,ready=Готов)', 'caption=Вид,notNul');
		
		
		//$this->FLD('directoryId', 'key(mvc=rip_Jobs,select=name)', 'input=hidden,silent, caption=Директория');
		
		//$this->FLD("name", "varchar", 'notNull, caption=Име на файла');
		
		//$this->FLD("size", "int", 'notNull, caption=Размер');
		
	}
	
	
	/**
	 * 
	 * Преди подготвянето на данните, проверява дали има избрана директория,
	 * и показва само файловете, които са в избраната директория
	 */
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		$currentDir = rip_Directory::getCurrent();
		
		if (!isset($currentDir) || !$folder = rip_Directory::fetch("#id = '$currentDir'")) {
			
			$redirect = redirect(array('rip_Directory', 'default'), FALSE, tr("Моля изберете директория."));
			
			$res = new Redirect($redirect);
	
	        return FALSE;
		}
		
		$data->title = "Съдържание на директорията: {$folder->folder}";
		
        $data->query->where("#directoryId = $currentDir");
        $data->query->orderBy('#createdOn', 'DESC');	
	}
	
	
	/**
	 * 
	 * Преди рендирането не листовия изгледа поледо "папка"
	 */
	function on_BeforeRenderListTable($mvc, &$res, $data)
	{
		unset($data->listFields['directoryId']);
	}
	
	
	/**
     * Извиква се след подготовка на формата за редактиране
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {  	
        // Директория, в която ще се добавят файловете
        $data->form->setField('directoryId', array('value' => rip_Directory::getCurrent()));
        
    	if (!empty($data->form->rec->id)) {
    		//$data->form->setReadOnly('number');
    		//$data->form->setReadOnly('directory');
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
	function on_BeforeSave($mvc, $id, $rec)
	{
		$filemanFiles = cls::get('fileman_Files');
    	$filePath = $filemanFiles->fetchByFh($rec->file, 'path');
    	$fileName = $filemanFiles->fetchByFh($rec->file, 'name');
    	
    	$folder = rip_Directory::fetch("#id = '$rec->directoryId'");
    	
    	$outDest = EF_RIP_DIRECTORY_PATH . $folder->folder . '/' . $fileName;
    	
    	//Проверява дали файла със същото име съществува в директорията
    	if (is_file($outDest)) {
    		
    		$redirect = redirect(array($mvc, 'default'), FALSE, tr("В директория съществува файк със същото име."));
			
			$res = new Redirect($redirect);
	
	        return FALSE;
    	}
    	
    	//Копира файла в избраната директория
    	if (!copy($filePath, $outDest)) {
    		$redirect = redirect(array($mvc, 'default'), FALSE, tr("Файлът не може да бъде копиран."));
			
			$res = new Redirect($redirect);
	
	        return FALSE;
    	}
    	    	
	}
	
	
	
	
	
	
	
}