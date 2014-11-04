<?php



/**
 * Мениджър на отчети от различни източници
 *
 *
 * @category  bgerp
 * @package   frame
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class frame_Reports extends core_Embedder
{
    
    
    /**
     * Необходими плъгини
     */
    var $loadList = 'plg_RowTools, plg_State2, frame_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Search, plg_Printing';
                      
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Отчет';
    

    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Отчети от източници в системата";

    /**
     * Права за писане
     */
    var $canWrite = 'ceo, report, admin';
    
    
    /**
     * Права за запис
     */
    var $canRead = 'ceo, report, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo, report, admin';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo, report, admin';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Rep";
    
    
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/report.png';


    /**
     * Групиране на документите
     */
    var $newBtnGroup = "18.9|Други";


    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'frame/tpl/SingleLayoutReport.shtml';


    /**
     * Свойство, което указва интерфейса на вътрешните обекти
     */
    public $innerObjectInterface = 'frame_ReportSourceIntf';
    
    
    /**
     * Как се казва полето за избор на вътрешния клас
     */
    public $innerClassField = 'source';
    
    
    /**
     * Как се казва полето за данните от формата на драйвъра
     */
    public $innerFormField = 'filter';
    
    
    /**
     * Как се казва полето за записване на вътрешните данни
     */
    public $innerStateField = 'data';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        // Име на отчета
        $this->FLD('name', 'varchar(255)', 'caption=Наименование, width=100%, notFilter, mandatory');

        // Singleton клас - източник на данните
        $this->FLD('source', 'class(interface=frame_ReportSourceIntf, allowEmpty, select=title)', 'caption=Източник,silent,mandatory,notFilter', array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit()")));

        // Поле за настройките за филтриране на данните, които потребителят е посочил във формата
        $this->FLD('filter', 'blob(1000000, serialize, compress)', 'caption=Филтър,input=none,single=none,column=none');

        // Извлечените данни за отчета. "Снимка" на състоянието на източника.
        $this->FLD('data', 'blob(1000000, serialize, compress)', 'caption=Данни,input=none,single=none,column=none');
 
        $this->setDbUnique('name');
    }

    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
		if($fields['-single']) {
	    	
            // Показваме заглавието само ако не сме в режим принтиране
            if(!Mode::is('printing')){
                $row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " (" . $mvc->getVerbal($rec, 'state') . ")" ;
            }
            
            // Обновяваме данните, ако отчета е в състояние 'draft'
            if($rec->state == 'draft') {//bp($rec->data);
            	$Source = cls::getInterface('frame_ReportSourceIntf', $rec->source);
            	$filter = $rec->filter;
            	$rec->data = $Source->prepareInnerState($filter);
            }
            /*$Source = cls::getInterface('frame_ReportSourceIntf', $rec->source);
            
            // Обновяваме данните, ако отчета е в състояние 'draft'
            if($rec->state == 'draft') {
            	
            	// Източника подготвя данните
                $rec->data = $Source->prepareReportData($rec->filter);
            }
                
            // Източника рендира данните
            $row->data = $Source->renderReportData($rec->data);*/
        }
    }


    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    static function on_AfterPrepareEditToolbar($mvc, $data)
    {
    	if (!empty($data->form->toolbar->buttons['activate'])) {
    		$data->form->toolbar->removeBtn('activate');
    	}
    }
    
    
    /**
     * Дали да записва вътрешното състояние при инпут на формата
     */
    public function shouldSaveInnerState($id = NULL)
    {
    	return FALSE;
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterActivation($mvc, &$rec)
    {
    	$Driver = cls::get($rec->source);
    	
    	$Driver->invoke('AfterActivation', array(&$rec->data, $rec->filter, &$rec));
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterReject($mvc, &$res, &$rec)
    {
    	$Driver = cls::get($rec->source);
    	
    	$Driver->invoke('AfterReject', array(&$rec->data, $rec->filter, &$rec));
    }
    
    
    /**
     * Функция, която се извиква след активирането на документа
     */
    public static function on_AfterRestore($mvc, &$res, &$rec)
    {
    	$Driver = cls::get($rec->source);
    
    	$Driver->invoke('AfterRestore', array(&$rec->data, $rec->filter, &$rec));
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	$folderCover = doc_Folders::getCover($folderId);
       
       return ($folderCover->haveInterface('frame_FolderCoverIntf')) ? TRUE : FALSE;
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     * 
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
	public static function canAddToThread($threadId)
    {
        $threadRec = doc_Threads::fetch($threadId);
        $folderCover = doc_Folders::getCover($threadRec->folderId);
        
    	return ($folderCover->haveInterface('frame_FolderCoverIntf')) ? TRUE : FALSE;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $this->singleTitle . " №{$id} {$rec->name}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = $rec->reason;
		
        return $row;
    }
}