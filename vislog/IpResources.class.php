<?php



/**
 * Мениджър на отчети от посещения по ресурс
 *
 *
 * @category  bgerp
 * @package   vislog
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class vislog_IpResources extends frame_BaseDriver
{                  
    
	
    /**
     * Заглавие
     */
    public $title = 'Отчет на посещенията по ресурс';

    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, admin, cms';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'ceo, admin, cms';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo, admin, cms';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, admin, cms';

    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_Form &$form)
    {
    	$form->FLD('from', 'date', 'caption=Начало');
    	$form->FLD('to', 'date', 'caption=Край');
    }
      

    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
    
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
    	    	 
    	// Размяна, ако периодите са объркани
    	if(isset($form->rec->from) && isset($form->rec->to) && ($form->rec->from > $form->rec->to)) {
    		$mid = $form->rec->from;
    		$form->rec->from = $form->rec->to;
    		$form->rec->to = $mid;
    	}
    }  
    
    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     *
     * @param core_Form $innerForm
     */
    public function prepareInnerState()
    {
    	$data = new stdClass();
        $data->resourceCnt = array();
        $fRec = $data->fRec = $this->innerForm;
        
        $query = vislog_History::getQuery();

        if($fRec->from) {  
            $query->where("#createdOn >= '{$fRec->from} 00:00:00'");
        }

        if($fRec->to) {
            $query->where("#createdOn <= '{$fRec->to} 23:59:59'");
        }


        while($rec = $query->fetch()) {
        	
        	$data->resourceCnt[$rec->HistoryResourceId]++;

        }
        
        return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public function on_AfterPrepareEmbeddedData($mvc, &$res)
    {

    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData($data)
    {
    	$tpl = new ET("
            <h1>Отчет за посещенията по ресурс</h1>
            [#FORM#]
            [#PAGER#]
            [#RESOURCES#]
        "
    	);
    
    	$form = cls::get('core_Form');
    
    	$this->addEmbeddedFields($form);
    
    	$form->rec = $data->fRec;
    	$form->class = 'simpleForm';
    
    	Mode::push('staticFormView', TRUE);
    	$tpl->prepend($form->renderHtml(), 'FORM');
    	Mode::pop();
    
    	$tpl->placeObject($data->rec);
    
    	$html = "<h3>Посещения по ресурс</h3>";
        
        $pager = cls::get('core_Pager', array('pageVar' => 'P_' .  $this->EmbedderRec->that));
        $pager->itemsCount = count($data->resourceCnt);

    	$key = cls::get('type_Key');
    	$int = cls::get('type_Int');
    	
    	$f = cls::get('core_FieldSet');
    	$f->FLD('from', 'date', 'caption=Дата->Начало');
    	$f->FLD('to', 'date', 'caption=Дата->Край');
    	$f->FLD('resource', 'key(mvc=vislog_HistoryResources,select=query)', 'caption=Посещения->Ресурс');
    	$f->FLD('cnt', 'int', 'caption=Посещения->Брой');
    	
    	$rows = array();

    	$ft = $f->fields;
        $resourceType = $ft['resource']->type;
        $cntType = $ft['cnt']->type;
        $i = 0;
   
    	foreach($data->resourceCnt as $resource => $cnt) {
 
            if(!$pager->isOnPage()) continue;
            
    		$row = new stdClass();
    		$row->resource = $resourceType->toVerbal($resource);
    		$row->cnt = $cntType->toVerbal($cnt);
    		
    		$rows[] = $row;
    	}

    	$table = cls::get('core_TableView', array('mvc' => $f));
    	$html = $table->get($rows, 'resource=Посещения->Ресусрс,cnt=Посещения->Брой');
    
    	$tpl->append($html, 'RESOURCES');
        $tpl->append($pager->getHtml(), 'RESOURCES');

    	return  $tpl;
    }
    
    
     
    
    /**
     * Скрива полетата, които потребител с ниски права не може да вижда
     *
     * @param stdClass $data
     */
    public function hidePriceFields()
    {
    }
    
    
    /**
     * Коя е най-ранната дата на която може да се активира документа
     */
    public function getEarlyActivation()
    {
    	return $this->innerForm->to;
    }
}