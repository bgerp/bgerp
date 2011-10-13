<?php


/**
 * Задава пътя до директорията, където се намират файловете
 */
defIfNot('EF_RIP_DIRECTORY_PATH', '/home/developer/Desktop/rip/');


/**
 * Задава пътя до временната директория
 */
defIfNot('EF_RIP_TEMP_PATH', EF_TEMP_PATH . "/riptemp/");


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
    var $canEdit = 'no_one';
    
    
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
	 * Преди да вкараме записите в таблицата създаваме директорията
	 */
	function on_BeforeSave($mvc, $id, &$rec)
	{
		if (isset($rec->id)) {
			return ;
		}
		rip_Directory::log("Jobs->update(): idtss{$rec->id}");
		$folder = $this->makeDir($rec->number, $rec->directory);
		$rec->folder = $folder;
	}
	
	
	/**
	 * Създава директория
	 */
	function makeDir($number, $title) {
		$folderName = $number . " - " . $title;
		$folderPath = EF_RIP_DIRECTORY_PATH . $folderName;
		
		if(!is_dir($folderPath))  {
			if(!mkdir($folderPath, 0777, TRUE)) {
				rip_Directory::log("Jobs->update(): $folderPath - unable to make dir");
				bp("Jobs->AddRemote(): $folderPath - unable to make dir", $info);
			}
		}
		
		return $folderName;
	}
	
	
	/**
     * Изпълнява се след създаването на таблицата
     */
	function on_AfterSetupMVC($mvc, $res)
    {
        if(!is_dir(EF_RIP_DIRECTORY_PATH)) {
            if( !mkdir(EF_RIP_DIRECTORY_PATH, 0777, TRUE) ) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') . ' "' . EF_RIP_DIRECTORY_PATH . '</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' . EF_RIP_DIRECTORY_PATH . '"</font>';
            }
        } else {
        	$res .= '<li>' . tr('Директорията съществува: ') . ' <font color=black>"' . EF_RIP_DIRECTORY_PATH . '"</font>';
        }
        
    	if(!is_dir(EF_RIP_TEMP_PATH)) {
            if( !mkdir(EF_RIP_TEMP_PATH, 0777, TRUE) ) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') . ' "' . EF_RIP_TEMP_PATH . '</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' . EF_RIP_TEMP_PATH . '"</font>';
            }
        } else {
        	$res .= '<li>' . tr('Директорията съществува: ') . ' <font color=black>"' . EF_RIP_TEMP_PATH . '"</font>';
        }
        
        return $res;
    }
	
	
}