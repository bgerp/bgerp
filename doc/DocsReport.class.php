<?php



/**
 * Мениджър на отчети от документи
 * 
 * По посочен тип на документа със статус различен от 
 * чернова и оттеглено се брои за посочения период,
 * колко документа е създал конкретният потребител
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_DocsReport extends frame_BaseDriver
{                  
    
	
    /**
     * Заглавие
     */
    public $title = 'Документи » Създадени документи';

    
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
    public $canSelectSource = 'powerUser';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'powerUser';

    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_Form &$form)
    {
    	$form->FLD('from', 'date', 'caption=Начало');
    	$form->FLD('to', 'date', 'caption=Край');
    	$form->FLD('docClass', 'class(interface=doc_DocumentIntf,select=title)', 'caption=Документ,mandatory');
    	$form->FLD('user', 'users(rolesForAll = ceo|report, rolesForTeams = manager|ceo|report)', 'caption=Потребител');
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
        $data->docCnt = array();
        $fRec = $data->fRec = $this->innerForm;
      
        $query = doc_Containers::getQuery();
        
        if ($fRec->from) {  
            $query->where("#createdOn >= '{$fRec->from} 00:00:00'");
        }

        if ($fRec->to) {
            $query->where("#createdOn <= '{$fRec->to} 23:59:59'");
        }
        
        if ($fRec->docClass) {
        	$query->where("#docClass = '{$fRec->docClass}'");
        }
        
        if(($fRec->user != 'all_users') && (strpos($fRec->user, '|-1|') === FALSE)) {
        	$query->where("'{$fRec->user}' LIKE CONCAT('%|', #createdBy, '|%')");
        }
       

        while($rec = $query->fetch()) {
        	
        	$data->docCnt[$rec->docClass][$rec->createdBy]++;

        }
 
        // Сортиране на данните
        arsort($data->docCnt);

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
            <h1>Създадени документи тип \"[#DOCTYPE#]\"</h1>
            [#FORM#]
    		[#PAGER#]
            [#DOCS#]
    		[#PAGER#]
        "
    	);
    
    	$docClass = cls::get($data->fRec->docClass);
    	$tpl->replace($docClass->singleTitle,'DOCTYPE');
    	
    	$form = cls::get('core_Form');
    
    	$this->addEmbeddedFields($form);
    
    	$form->rec = $data->fRec;
    	$form->class = 'simpleForm';
    	
    	$tpl->prepend($form->renderStaticHtml(), 'FORM');
    
    	$tpl->placeObject($data->rec);
    
    	$pager = cls::get('core_Pager',  array('pageVar' => 'P_' .  $this->EmbedderRec->that,'itemsPerPage' => $this->listItemsPerPage));
    	$pager->itemsCount = count($data->docCnt, COUNT_RECURSIVE);
    	
    	$f = cls::get('core_FieldSet');
    
    	$f->FLD('docClass', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', 'caption=Създадени документи->Тип');
    	$f->FLD('createdBy', 'key(mvc=core_Users,select=names)', 'caption=Създадени документи->Автор');
    	$f->FLD('cnt', 'int', 'caption=Създадени документи->Брой');
    	
    	$rows = array();

    	$ft = $f->fields;
    	$docClassType = $ft['docClass']->type;
        $userType = $ft['createdBy']->type;
        $cntType = $ft['cnt']->type;
        
    	foreach ($data->docCnt as $docClass => $userCnt) {
    		foreach ($userCnt as $user => $cnt) {
	    		if(!$pager->isOnPage()) continue;
	    		
	    		$row = new stdClass();
	    		$row->docClass = $docClassType->toVerbal($docClass);
	    		$row->cnt = $cntType->toVerbal($cnt);
	    		
	    		if(!$user) {
	    			$row->createdBy = "Анонимен";
	    		} elseif($user == -1) {
	    			$row->createdBy = "Система";
	    		} else {
	    			$row->createdBy = $userType->toVerbal($user) . ' ' . crm_Profiles::createLink($user);
	    		}

	    		$rows[] = $row;
    		}
    	}

    	$table = cls::get('core_TableView', array('mvc' => $f));
    	$html = $table->get($rows, 'docClass=Създадени документи->Тип,createdBy=Създадени документи->Автор,cnt=Създадени документи->Брой');
    
    	$tpl->append($html, 'DOCS');
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