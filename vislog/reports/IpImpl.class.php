<?php



/**
 * Мениджър на отчети от посещения по IP
 *
 *
 * @category  bgerp
 * @package   vislog
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class vislog_reports_IpImpl extends frame_BaseDriver
{                  
    
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'vislog_IpReports';
	
	
    /**
     * Заглавие
     */
    public $title = 'Сайт » Посещения по IP';

    
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
    public function addEmbeddedFields(core_FieldSet &$form)
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
        $data->ipCnt = array();
        $fRec = $data->fRec = $this->innerForm;
        
        $query = vislog_History::getQuery();

        if($fRec->from) {  
            $query->where("#createdOn >= '{$fRec->from} 00:00:00'");
        }

        if($fRec->to) {
            $query->where("#createdOn <= '{$fRec->to} 23:59:59'");
        }

        while($rec = $query->fetch()) {
        	
        	$data->ipCnt[$rec->ip]++;

        }

        // Сортиране на данните
        arsort($data->ipCnt);
     
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
    public function renderEmbeddedData(&$embedderTpl, $data)
    {
    	$tpl = new ET("
            <h1>Отчет за посещенията по IP</h1>
            [#FORM#]
    		[#PAGER#]
            [#VISITS#]
    		[#PAGER#]
        "
    	);
    
    	$form = cls::get('core_Form');
    
    	$this->addEmbeddedFields($form);
    
    	$form->rec = $data->fRec;
    	$form->class = 'simpleForm';
    
    	$tpl->prepend($form->renderStaticHtml(), 'FORM');
    
    	$tpl->placeObject($data->rec);

    	$pager = cls::get('core_Pager',  array('itemsPerPage' => $this->listItemsPerPage));
        $pager->setPageVar($this->EmbedderRec->className, $this->EmbedderRec->that);
        $pager->addToUrl = array('#' => $this->EmbedderRec->instance->getHandle($this->EmbedderRec->that));

    	$pager->itemsCount = count($data->ipCnt);

    	$f = cls::get('core_FieldSet');

    	$f->FLD('ip', 'ip(15)', 'caption=Посещения->Ip');
    	$f->FLD('cnt', 'int', 'caption=Посещения->Брой');
    	$f->FLD('createdBy', 'key(mvc=core_Users,select=names)', 'caption=Потребител');
    	
    	$rows = array();

    	$ft = $f->fields;
    	$ipType = cls::get('type_Ip');
        $cntType = $ft['cnt']->type;
      
        
    	foreach ($data->ipCnt as $ip => $createdCnt) { 
	    	if(!$pager->isOnPage()) continue;
	    		
	    	$row = new stdClass();
	   
	    	if ($data->fRec->to) {
	    		$row->ip = $ipType->decorateIp($ip, $data->fRec->to, TRUE, TRUE);
	    	} else {
	    		$row->ip = $ipType->decorateIp($ip, $data->fRec->createdOn, TRUE, TRUE);
	    	}
	    	
	    	$row->cnt = $cntType->toVerbal($createdCnt);

	    	$rows[] = $row;
    	}

    	$table = cls::get('core_TableView', array('mvc' => $f));
    	$html = $table->get($rows, 'ip=Посещения->Ip,cnt=Посещения->Брой');
    
    	$tpl->append($html, 'VISITS');
        $tpl->append($pager->getHtml(), 'PAGER');
    
    	$embedderTpl->append($tpl, 'data');
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