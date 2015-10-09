<?php



/**
 * Мениджър на отчети от продажбени артикули
 *
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_reports_SalesArticle extends frame_BaseDriver
{                  
    
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'cat_SalesArticleReport';
	
	
    /**
     * Заглавие
     */
    public $title = 'Артикули » Продажбени артикули';

    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';


    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    //public $oldClassName = 'doc_SalesArticleReport';
    


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
    public $canSelectSource = 'cat,ceo,sales,purchase';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'cat,ceo,sales,purchase';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'cat,ceo,sales,purchase';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'cat,ceo,sales,purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'cat,ceo,sales,purchase';

    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
    	$form->FLD('from', 'date', 'caption=Начало');
    	$form->FLD('to', 'date', 'caption=Край');
    	$form->FLD('user', 'users(rolesForAll = officer|manager|ceo, rolesForTeams = officer|manager|ceo|executive)', 'caption=Потребител');
    }
      

    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
    	$cu = core_Users::getCurrent();
    	
    	if (core_Users::haveRole('ceo', $cu)) {
    		$form->setDefault('user', 'all_users');
    	} elseif (core_Users::haveRole('manager', $cu)) {
    		$teamCu = type_Users::getUserWithFirstTeam($cu);
    		$team = strstr($teamCu, '_', TRUE);
    		$form->setDefault('user', "{$team} team");
    		
    	} else {
    	    $userFromTeamsArr = type_Users::getUserFromTeams($cu);
    	    
    		$form->setDefault('user', key($userFromTeamsArr));
    	}
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
        $data->articleCnt = array();
        $fRec = $data->fRec = $this->innerForm;
      
        $querySales = sales_SalesDetails::getQuery();
        $queryShipment = store_ShipmentOrderDetails::getQuery();
        $queryServices = sales_ServicesDetails::getQuery();


        if ($fRec->from) {
            $querySales->where("#createdOn >= '{$fRec->from} 00:00:00'");
            $queryShipment->where("#createdOn >= '{$fRec->from} 00:00:00'");
            $queryServices->where("#createdOn >= '{$fRec->from} 00:00:00'");
        }

        if ($fRec->to) {
            $querySales->where("#createdOn <= '{$fRec->to} 23:59:59'");
            $queryShipment->where("#createdOn <= '{$fRec->to} 23:59:59'");
            $queryServices->where("#createdOn <= '{$fRec->to} 23:59:59'");
        }

        if(($fRec->user != 'all_users') && (strpos($fRec->user, '|-1|') === FALSE)) {
            $querySales->where("'{$fRec->user}' LIKE CONCAT('%|', #createdBy, '|%')");
            $queryShipment->where("'{$fRec->user}' LIKE CONCAT('%|', #createdBy, '|%')");
            $queryServices->where("'{$fRec->user}' LIKE CONCAT('%|', #createdBy, '|%')");
        }
       

        while($rec = $querySales->fetch()) {
        	
        	$data->articleCnt['sales'][$rec->classId][$rec->productId]++;

        }

        while($recShipment = $queryShipment->fetch()) {

            $data->articleCnt['shipment'][$recShipment->classId][$recShipment->productId]++;

        }

        while($recServices = $queryServices->fetch()) {

            $data->articleCnt['services'][$recServices->classId][$recServices->productId]++;

        }
 
        // Сортиране на данните
        arsort($data->articleCnt);

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
    	$tpl = new ET(tr("
            |*<h1>|Продажбени артикули|*</h1>
            [#FORM#]
    		[#PAGER#]
            [#ARTICLE#]
    		[#PAGER#]
        "
    	));

    	$form = cls::get('core_Form');
    
    	$this->addEmbeddedFields($form);
    
    	$form->rec = $data->fRec;
    	$form->class = 'simpleForm';
    	
    	$tpl->prepend($form->renderStaticHtml(), 'FORM');
    
    	$tpl->placeObject($data->rec);
    
    	$pager = cls::get('core_Pager',  array('itemsPerPage' => $this->listItemsPerPage));
        $pager->setPageVar($this->EmbedderRec->className, $this->EmbedderRec->that);
        $pager->addToUrl = array('#' => $this->EmbedderRec->instance->getHandle($this->EmbedderRec->that));

    	$pager->itemsCount = count($data->articleCnt, COUNT_RECURSIVE);

    	$f = cls::get('core_FieldSet');
    
    	$f->FLD('article', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Продукт->Тип');
    	$f->FLD('salesCnt', 'int', 'caption=Брой срещания->Продажба');
    	$f->FLD('shipmentCnt', 'int', 'caption=Брой срещания->Доставка');

    	$rows = array();

    	$ft = $f->fields;

        $userType = $ft['createdBy']->type;
        $cntType = $ft['salesCnt']->type;

    	foreach ($data->articleCnt as $doc => $artCnt) {
    		foreach ($artCnt as $artClassId => $productCnt) {
                foreach ($productCnt as $product => $cnt) {
                        if (!$pager->isOnPage()) continue;

                        $row = new stdClass();
                        $row->article = cat_Products::getTitleById($product);

                        if ($doc == 'sales') {
                            $row->salesCnt = $cntType->toVerbal($cnt);
                        }
                        if ($doc == 'shipment' || $doc == 'services') {
                            $row->shipmentCnt = $cntType->toVerbal($cnt);
                        }


                        if (!$user) {
                            $row->createdBy = "Анонимен";
                        } elseif ($user == -1) {
                            $row->createdBy = "Система";
                        } else {
                            $row->createdBy = $userType->toVerbal($user) . ' ' . crm_Profiles::createLink($user);
                        }

                        $rows[] = $row;
                }
    		}
    	}

    	$table = cls::get('core_TableView', array('mvc' => $f));
    	$html = $table->get($rows, 'article=Продукт->Тип,salesCnt=Брой срещания->Продажба,shipmentCnt=Брой срещания->Доставка');
    
    	$tpl->append($html, 'ARTICLE');
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