<?php



/**
 * Мениджър на отчети от различни източници
 *
 *
 * @category  bgerp
 * @package   sens2
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
    var $loadList = 'plg_RowTools, plg_State2, frame_Wrapper, doc_DocumentPlg, plg_Search';
                      
    
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
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo, report, admin';
    
    
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
        $this->FLD('name', 'varchar(255)', 'caption=Наименование, width=100%, notFilter');

        // Singleton клас - източник на данните
        $this->FLD('source', 'class(interface=frame_ReportSourceIntf, allowEmpty)', 'caption=Източник,silent,mandatory,notFilter', array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit()")));

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
        $form = $data->form;
        $rec =  $form->rec;
 
        if($rec->id) {
            $form->setReadOnly('source');
            $filter = (array) self::fetch($rec->id)->data->filter;
            if(is_array($filter)) {  
                foreach($filter as $key => $value) {
                    $rec->{$key} = $value;
                }
            }
        }

        if($rec->source) {
            $source = cls::get($rec->source);
            $source->prepareReportForm($form);
        }
    }

 

    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        if($form->isSubmitted() && $form->rec->source) {
            $source = cls::get($form->rec->source);
            $source->checkReportForm($form);
            if(!$form->gotErrors()) {
                
                $filterFields = array_keys($form->selectFields("(#input == 'input' || #input == '') && !#notFilter"));
                
                if(!$form->rec->filter) {
                    $form->rec->filter = new stdClass();
                }

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
                
                $source = cls::getInterface('frame_ReportSourceIntf', $rec->source);
                
                // Обновяваме данните, ако отчета е в състояние 'draft'
                if($rec->state == 'draft') {
                    $rec->data = $source->prepareReportData($rec->filter);
                }

                $mvc = cls::get('core_Mvc');
                $source->prepareReportForm($mvc);
                $filterRow = $mvc->recToverbal($rec->filter);
                
                $row->data = $source->renderReportData($filterRow , $rec->data);
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
       return TRUE;
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
        return TRUE;
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
