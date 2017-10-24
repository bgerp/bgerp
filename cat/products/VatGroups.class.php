<?php

/**
 * Клас 'cat_products_Vat' 
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_products_VatGroups extends core_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    public $title = 'ДДС групи';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'vatGroup,vatPercent=ДДС (%),validFrom';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_Created';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canAdd = 'ceo,cat';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canDelete = 'ceo,cat';
    
    
    /**
     * Заглавие в единствено число
     *
     * @var string
     */
    public $singleTitle = 'ДДС група';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent,mandatory');
        $this->FLD('vatGroup', 'key(mvc=acc_VatGroups,select=title,allowEmpty)', 'caption=Група,mandatory');
        $this->FLD('validFrom', 'datetime', 'caption=В сила oт');
    }

    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		$now = dt::verbal2mysql();
    
    		if(!$form->rec->validFrom) {
    			$form->rec->validFrom = $now;
    		}
    
    		if($form->rec->validFrom < $now) {
    			//$form->setError('validFrom', 'Групата не може да се сменя с минала дата');
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->vatGroup = acc_VatGroups::getTitleById($rec->vatGroup);
    	$row->vatPercent = acc_VatGroups::getVerbal($rec->vatGroup, 'vat');
    }
    
    
    /**
     * Извиква се след подготовка на заявката за детайла
     */
    public static function on_AfterPrepareDetailQuery(core_Detail $mvc, $data)
    {
    	// Историята на ценовите групи на продукта - в обратно хронологичен ред.
    	$data->query->orderBy("validFrom,id", 'DESC');
    }
    
    
    /**
     * След подготовка на записите във вербален вид
     */
    public static function on_AfterPrepareListRows1(core_Detail $mvc, $data)
    {
    	if (!$data->rows) return;
    	
    	$now  = dt::now(TRUE);
    	$currentGroup = NULL;
    	
    	foreach ($data->rows as $id => &$row) {
    		$rec = $data->recs[$id];
    		if($rec->validFrom > $now){
    			$data->rows[$id]->ROW_ATTR['class'] = 'state-draft';
    		}elseif($rec->validFrom <= $now && is_null($currentGroup)){
    			$currentGroup = $rec->validFrom;
    			$data->rows[$id]->ROW_ATTR['class'] = 'state-active';
    		} else {
    			$data->rows[$id]->ROW_ATTR['class'] = 'state-closed';
    		}
    	}
    }
    
    
    /**
     * Подготовка на файловете
     */
    public function prepareVatGroups($data)
    {   
    	$now  = dt::now(TRUE);
    	$currentGroup = NULL;
    	$data->recs = $data->rows = array();
    	
    	$query = $this->getQuery();
        $query->where("#productId = {$data->masterId}");
        $query->orderBy("#validFrom", 'DESC');
        while($rec = $query->fetch()){
        	$data->recs[$rec->id] = $rec;
        	$row = $this->recToVerbal($rec);
        	
        	$row->createdOn .= " " . tr('от') . " " . $row->createdBy;
        	$data->rows[$rec->id] = $row;
        }
        
        if(count($data->rows)) {
            foreach ($data->rows as $id => &$row) {
                $rec = $data->recs[$id];
                
                if($rec->validFrom > $now){
                    $data->rows[$id]->ROW_ATTR['class'] = 'state-draft';
                }elseif($rec->validFrom <= $now && is_null($currentGroup)){
                    $currentGroup = $rec->validFrom;
                    $data->rows[$id]->ROW_ATTR['class'] = 'state-active';
                } else {
                    $data->rows[$id]->ROW_ATTR['class'] = 'state-closed';
                }
            }
        }
        
        if(static::haveRightFor('add', (object)array('productId' => $data->masterId))){
        	$data->addUrl = array($this, 'add', 'productId' => $data->masterId, 'ret_url' => TRUE);
        }
    }
    
    
    /**
     * Рендиране на файловете
     */
    public function renderVatGroups($data)
    {
    	$wrapTpl = getTplFromFile('cat/tpl/ProductDetail.shtml');
    	$table = cls::get('core_TableView', array('mvc' => $this));
    	$data->listFields = array("vatGroup" => "Група", 'vatPercent' => 'ДДС|* (%)', 'validFrom' => 'В сила oт', 'createdOn' => 'Създаване');
        $tpl = $table->get($data->rows, $data->listFields);
    	
    	$title = 'ДДС';
    	if($data->addUrl && !Mode::isReadOnly()){
			$title .= ht::createLink("<img src=" . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addUrl, FALSE, 'title=Избор на ДДС група');
		}
    	
    	$wrapTpl->append($title, 'TITLE');
    	$wrapTpl->append($tpl, 'CONTENT');
    	
    	return $wrapTpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'edit' || $action == 'delete') && isset($rec)){
    		if($rec->validFrom <= dt::now()){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	if($action == 'add' && isset($rec->productId)){
    		if(cat_Products::fetchField($rec->productId, 'state') != 'active'){
    			$requiredRoles = 'no_one';
    		}  elseif(!cat_Products::haveRightFor('single', $rec->productId)) {
    			$requiredRoles = 'no_one';
    		} elseif(cat_Products::fetchField($rec->productId, 'createdBy') == core_Users::SYSTEM_USER) {
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Коя е активната данъчна група към дата
     * 
     * @param int $productId
     * @param datetime|NULL $date
     * @return double|FALSE $value
     */
    public static function getCurrentGroup($productId, $date = NULL)
    {
    	$date = (!empty($date)) ? $date : dt::now();
    	
    	// Кешираме активната данъчна група на артикула в текущия хит
    	$query = cat_products_VatGroups::getQuery();
    	$query->where("#productId = {$productId}");
    	$query->where("#validFrom <= '{$date}'");
    	$query->orderBy("#validFrom", "DESC");
    	$query->limit(1);
    		 
    	$value = FALSE;
    	if($rec = $query->fetch()){
    		$value = acc_VatGroups::fetch($rec->vatGroup);
    	}
    	
    	return $value;
    }
}
