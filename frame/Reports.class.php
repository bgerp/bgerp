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
class frame_Reports extends core_Master
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
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf';
   
    
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
     * Описание на модела
     */
    function description()
    {
        // Име на отчета
        $this->FLD('name', 'varchar(255)', 'caption=Наименование, width=100%, notFilter, mandatory');

        // Singleton клас - източник на данните
        $this->FLD('source', 'class(interface=frame_ReportSourceIntf, allowEmpty, select=title)', 'caption=Източник,silent,mandatory,notFilter', array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit()")));

        // Поле за настройките за филтриране на данните, които потребителят е посочил във формата
        $this->FLD('filter', 'blob(serialize, compress)', 'caption=Филтър,input=none,column=none');

        // Извлечените данни за отчета. "Снимка" на състоянието на източника.
        $this->FLD('data', 'blob(serialize, compress)', 'caption=Данни,input=none,column=none');
 
        $this->setDbUnique('name');
    }
    

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareEditform($mvc, &$data)
    {
        $form = &$data->form;
        $rec =  &$form->rec;
 
        // Извличаме класовете с посочения интерфейс
        $interfaces = core_Classes::getOptionsByInterface('frame_ReportSourceIntf', 'title');
        if(count($interfaces)){
        	foreach ($interfaces as $id => $int){
        		$Driver = cls::get($id);
        		
        		// Ако потребителя не може да го избира, махаме го от масива
        		if(!$Driver->canSelectSource()){
        			unset($interfaces[$id]);
        		}
        	}
        }
        
        // Ако няма достъпни драйвери полето е readOnly иначе оставяме за избор само достъпните такива
        if(!count($interfaces)) {
        	$form->setReadOnly('source');
        } else {
        	$form->setOptions('source', $interfaces);
        }
        
        // Ако има запис, не може да се сменя източника и попълваме данните на формата с тези, които са записани
        if($rec->id) {
            $form->setReadOnly('source');
            $filter = (array) self::fetch($rec->id)->filter;
            if(is_array($filter)) {  
                foreach($filter as $key => $value) {
                    $rec->{$key} = $value;
                }
            }
        }

        // Ако има източник инстанцираме го
        if($rec->source) {
            $Source = cls::get($rec->source);
            
            // Източника модифицира формата при нужда
            $Source->prepareReportForm($form);
        }
    }
 

    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        if($form->isSubmitted() && $form->rec->source) {
        	
        	// Инстанцираме източника
            $Source = cls::get($form->rec->source);
            if(!$Source->canSelectSource()){
            	$form->setError('source', 'Нямате права за избрания източник');
            }
            
            // Източника проверява подадената форма
            $Source->checkReportForm($form);
            
            // Ако няма грешки
            if(!$form->gotErrors()) {
                
                $filterFields = array_keys($form->selectFields("(#input == 'input' || #input == '') && !#notFilter"));
                
                if(!$form->rec->filter) {
                    $form->rec->filter = new stdClass();
                }

                // Записва данните от формата в полето 'filter'
                if(is_array($filterFields)) {
                    foreach($filterFields as $field) {
                        $form->rec->filter->{$field} = $form->rec->{$field};
                    }
                }
            }
         }
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
                
            $Source = cls::getInterface('frame_ReportSourceIntf', $rec->source);
            
            // Обновяваме данните, ако отчета е в състояние 'draft'
            if($rec->state == 'draft') {
            	
            	// Източника подготвя данните
                $rec->data = $Source->prepareReportData($rec->filter);
            }
           
            $mvc = cls::get('core_Mvc');
            $Source->prepareReportForm($mvc);
            $filterRow = $mvc->recToverbal($rec->filter);
                
            // Източника рендира данните
            $row->data = $Source->renderReportData($filterRow , $rec->data);
        }
    }


    /**
     * Преди запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Manager $mvc, $res, $rec)
    {
    	// Ако оттегляме / активираме документа
    	if($rec->state != 'draft'){
    		
    		// Ако няма $data я извличаме и записваме
    		if(empty($rec->data)){
    			$source = $mvc->fetchField($rec->id, 'source');
    			$filter = $mvc->fetchField($rec->id, 'filter');
    			$Source = cls::getInterface('frame_ReportSourceIntf', $source);
    			$rec->data = $Source->prepareReportData($filter);
    			
    			$mvc->save($rec, 'data');
    		}
    	} else {
    		
    		// Ако документа е чернова и има $data, ънсетваме я (след възстановяване на оттеглена чернова)
    		if(!empty($rec->data)){
    			unset($rec->data);
    			$mvc->save($rec, 'data');
    		}
    	}
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
       // Може да създаваме документ-а само в дефолт папката му
       if (doc_Folders::fetchCoverClassName($folderId) == 'doc_UnsortedFolders') {
        	return TRUE;
       } 
        
       return FALSE;
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
    	if (doc_Folders::fetchCoverClassName($threadRec->folderId) == 'doc_UnsortedFolders') {
        	
    		return TRUE;
       } 
        
       return FALSE;
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