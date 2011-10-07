<?php


/**
 * Задава пътя до директорията, където се намират файловете
 */
defIfNot('EF_RIP_DIRECTORY_PATH', '/home/developer/Desktop/rip/');


/**
 * Показва всички файлове
 */
class rip_Directory extends core_Manager
{
	

    /**
     *  Заглавие на таблицата
     */
    var $title = "Задания за клишета";
    
    
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
	var $canJobs = 'admin, rip';
	
    
    /**
     * 
     */
    var $loadList = //, , plg_Rejected,, plg_RefreshRows, plg_SaveAndNew
					'plg_RowTools, plg_Created, plg_Selected, rip_Wrapper, plg_State';
    
    
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD("number", "int", "caption=Задание->Номер, mandatory");
		$this->FLD("directory", "varchar(128)", 'caption=Задание->Директория, mandatory');
		$this->FLD('folder', 'varchar(128)', 'caption=Папка,input=none');
		
		$this->setDbUnique('number');
		
	}
	
	
	/**
     * Преди извличане на записите подрежда ги по дата на създаване, в обратен ред
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, &$data)
    {
    	$data->listFilter->FNC('filter', 'varchar', 'caption=Филтър,input');
    	
    	$data->listFilter->showFields = 'filter';
        
        $data->listFilter->layout = new ET(
			"<form style='margin:0px;'  method=\"[#FORM_METHOD#]\" action=\"[#FORM_ACTION#]\" 
			<!--ET_BEGIN ON_SUBMIT-->onSubmit=\"[#ON_SUBMIT#]\"<!--ET_END ON_SUBMIT-->>\n" .
			"<table cellspacing=0 >\n".
			"<tr>[#FORM_FIELDS#]<td>[#FORM_TOOLBAR#]</td></tr>".
			"</table></form>\n"
		);
		
		$data->listFilter->fieldsLayout = "<td>[#filter#]</td>";	
		
		$data->listFilter->toolbar->addSbBtn('Филтрирай');
		
		$filterInput = trim($data->listFilter->input()->filter);
		
    	if($filterInput) {
 			$data->query->where(array("#id LIKE '%[#1#]%' OR #folder LIKE '%[#1#]%'", $filterInput));
		}
		
    	unset($data->listFields['number']);
    	unset($data->listFields['directory']);
    	
        $data->query->orderBy('#createdOn', 'DESC');
        
        return ;
    }  
    
    
    /**
     * Добавя линк на текущата директория
     * 
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {        
        if ($rec->id == $mvc->getCurrent()) {
        	$row->folder = ht::createLink(ht::createElement("img", array('src' => sbf('img/16/folder-y-check.png', ''), 'width' => 16, 'height' => 16, 'valign' =>'abs_middle')) . ' ' . $row->folder,  array('rip_Files'));
        } else {
        	$row->folder = ht::createElement("img", array('src' => sbf('img/16/folder-y.png', ''), 'width' => 16, 'height' => 16, 'valign' =>'abs_middle')) . ' ' . $row->folder;
        }
    	
    }
	
	
    /**
     * 
     * Enter description here ...
     * @param unknown_type $mvc
     * @param unknown_type $form
     */
	function on_AfterInputEditForm($mvc, &$form)
    {
        if (!$form->isSubmitted()) {
        	
        	return ;
        }
            
		$download = cls::get('php_Ripdownload');
		//$mvc->getVerbal($form->rec, 'directory')
		$folder = $download->makeDir($form->rec->number, $form->rec->directory);
		
		$form->rec->folder = $folder;
    }
	
	
	
	/**
	 *Изпълнява се след рендинаето на едит формата
	 */
	function on_AfterPrepareEditForm($mvc, $data)
    {
    	if (!empty($data->form->rec->id)) {
    		$data->form->setReadOnly('number');
    		$data->form->setReadOnly('directory');
        }
    }
	
	
	
	
	

    
	function on_AfterSave($mvc, &$id, $rec){
		//bp($rec, &$id, $mvc);
	
	}
	/**
	 * Сканира директорията за файлове
	 */
	function showFiles($dirName)
	{
		if (!isset($dirName)) {
			
			return FALSE;
		}
		
		if (is_array($dirName)) {
			foreach ($dirName as $file) {
				$files[$file] = scandir($file);
			}	
		} else {
			$files[$dirName] = scandir($dirName);
		}
		
		foreach ($files as $directory => $file) {
			
			$i = 0;
			foreach ($file as $name) {
				if ($name == '.' || $name == '..') {
					continue;
				};
				
				$fileNames[][$directory] = STR::utf2ascii($name);
			}
			
		}
		
		return $fileNames;
	}
	
	
	
	
	
	
	
	
	
	
	
	
}