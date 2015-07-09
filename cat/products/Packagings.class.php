<?php

/**
 * Клас 'cat_products_Packagings'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_products_Packagings extends cat_products_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Опаковки';
    
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Опаковка';
 
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'code=EAN, packagingId, quantity=К-во, netWeight=, tareWeight=, weight=Тегло, 
        sizeWidth=, sizeHeight=, sizeDepth=, dimention=Габарити, 
        eanCode=,tools=Пулт';
    
    
    /**
     * Поле за редактиране
     */
    var $rowToolsField = 'tools';

    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'cat_Wrapper, plg_RowTools, plg_SaveAndNew';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canAdd = 'ceo,cat';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canDelete = 'ceo,cat';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden, silent');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings,select=name,allowEmpty)', 'input,caption=Опаковка,mandatory,width=7em');
        $this->FLD('quantity', 'double(Min=0)', 'input,caption=Количество,mandatory');
        $this->FLD('isBase', 'enum(yes=Да,no=Не)', 'caption=Основна,mandatory,maxRadio=2');
        $this->FLD('netWeight', 'cat_type_Weight', 'caption=Тегло->Нето');
        $this->FLD('tareWeight', 'cat_type_Weight', 'caption=Тегло->Тара');
        $this->FLD('sizeWidth', 'cat_type_Size', 'caption=Габарит->Ширина');
        $this->FLD('sizeHeight', 'cat_type_Size', 'caption=Габарит->Височина');
        $this->FLD('sizeDepth', 'cat_type_Size', 'caption=Габарит->Дълбочина');
        $this->FLD('eanCode', 'gs1_TypeEan', 'caption=Код->EAN');
        
        $this->setDbUnique('productId,packagingId');
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
    	if ($form->isSubmitted()){
    		$rec = &$form->rec;
    		
    		if($rec->eanCode) {
    				
    			// Проверяваме дали има продукт с такъв код (като изключим текущия)
	    		$check = $mvc->Master->getByCode($rec->eanCode);
	    		if($check && ($check->productId != $rec->productId)
	    			|| ($check->productId == $rec->productId && $check->packagingId != $rec->packagingId)) {
	    			$form->setError('eanCode', 'Има вече продукт с такъв код!');
			    }
    		}
    			
    		// Ако за този продукт има друга основна опаковка, тя става не основна
    		if($rec->isBase == 'yes' && $packRec = static::fetch("#productId = {$rec->productId} AND #isBase = 'yes'")){
    			$packRec->isBase = 'no';
    			static::save($packRec);
    		}
    		
    		$roundQuantity = cat_UoM::round($rec->quantity, $rec->productId);
    		if($roundQuantity != $rec->quantity){
    			$form->setWarning('quantity', 'Количеството ще бъде закръглено до указаното в |*<b>|Артикули » Каталог » Мерки|*</b>|');
    			$rec->quantity = $roundQuantity;
    		}
    		
    		// Закръгляме к-то така че да е в границите на допустимото от мярката
    		$rec->quantity = cat_UoM::round($rec->quantity, $rec->productId);
    	}
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
    	if($requiredRoles == 'no_one') return;
    	
        if($action == 'add' && isset($rec->productId)) {
        	if (!count($mvc::getRemainingOptions($rec->productId))) {
                $requiredRoles = 'no_one';
            } else {
            	$productInfo = $mvc->Master->getProductInfo($rec->productId);
            	if(empty($productInfo->meta['canStore'])){
            		$requiredRoles = 'no_one';
            	}
            } 
        }
        
        if(($action == 'add' ||  $action == 'delete') && isset($rec->productId)) {
        	$masterState = $mvc->Master->fetchField($rec->productId, 'state');
        	if($masterState != 'active' && $masterState != 'draft'){
        		$requiredRoles = 'no_one';
        	}
        }
        
        // Ако потрбителя няма достъп до сингъла на артикула, не може да модифицира опаковките
        if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec) && $requiredRoles != 'no_one'){
        	if(!cat_Products::haveRightFor('single', $rec->productId)){
        		$requiredRoles = 'no_one';
        	}
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, $data)
    {
        $data->toolbar->removeBtn('*');
        
        if ($mvc->haveRightFor('add', (object)array('productId' => $data->masterId)) && count($mvc::getRemainingOptions($data->masterId) > 0)) {
        	$data->addUrl = array(
                $mvc,
                'add',
                'productId' => $data->masterId,
                'ret_url' => getCurrentUrl() + array('#'=>get_class($mvc))
            );
        }
    }


    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        $data->query->orderBy('#id');
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        if(!(count($mvc::getRemainingOptions($data->form->rec->productId)) - 1)){
    		$data->form->toolbar->removeBtn('saveAndNew');
    	}
    }
    
    
    /**
     * Връща не-използваните параметри за конкретния продукт, като опции
     *
     * @param $productId int ид на продукта
     * @param $id int ид от текущия модел, което не трябва да бъде изключено
     */
    static function getRemainingOptions($productId, $id = NULL)
    {
        $options = cat_Packagings::makeArray4Select('name');
       
        if(count($options)) {
            $query = self::getQuery();
            
            if($id) {
                $query->where("#id != {$id}");
            }

            while($rec = $query->fetch("#productId = $productId")) {
               unset($options[$rec->packagingId]);
            }
        } else {
            $options = array();
        }

        return $options;
    }

    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
    	$options = $mvc::getRemainingOptions($form->rec->productId, $form->rec->id);
        
        if (empty($options)) {
            // Няма повече недефинирани опаковки
            redirect(getRetUrl(), FALSE, tr('Няма повече недефинирани опаковки'));
        }
		
    	if(!$form->rec->id){
        	$options = array('' => '') + $options;
        }
        
        $form->setDefault('isBase', 'no');
        	
        $form->setOptions('packagingId', $options);
        
        $pInfo = cat_Products::getProductInfo($form->rec->productId);
        $unit = cat_UoM::getShortName($pInfo->productRec->measureId);
        $form->setField('quantity', "unit={$unit}");
        
        // Променяме заглавието в зависимост от действието
        if (!$form->rec->id) {
            
            // Ако добавяме нова опаковка
            $titleMsg = 'Добавяне на опаковка за';    
        } else {
            
            // Ако редактираме съществуваща
            $titleMsg = 'Редактиране на опаковка за';
        }
        
        // Добавяме заглавието
        $form->title = "{$titleMsg}|* <b>" . cat_Products::getVerbal($pInfo->productRec, 'name') . "</b>";
    }
    
   
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$varchar = cls::get("type_Varchar");
    	
    	$row->quantity = trim($rec->quantity);
    	$row->quantity = $varchar->toVerbal($rec->quantity);
    	if($rec->sizeWidth==0) {
    		$row->sizeWidth = '-';
    	}
    	if($rec->sizeHeight==0) {
    		$row->sizeHeight = '-';
    	}
    	if($rec->sizeDepth==0) {
    		$row->sizeDepth = '-';
    	}
    	$row->dimention = "{$row->sizeWidth} x {$row->sizeHeight} x {$row->sizeDepth}";
    	
    	if($rec->eanCode){
    		$row->code = $row->eanCode;
    	}
    	if($rec->netWeight){
    		$row->weight = tr("|Нето|*: ") . $row->netWeight . "<br />";
    	}
    	if($rec->tareWeight){
    		$row->weight .= tr("|Тара|*: {$row->tareWeight}");
    	}
    	
    	if($rec->isBase == 'yes'){
    		$row->packagingId = "<b>" . $row->packagingId . "</b>";
    	}
    }

    
    public static function on_AfterRenderDetail($mvc, &$tpl, $data)
    {
        $wrapTpl = getTplFromFile('cat/tpl/PackigingDetail.shtml');
        $wrapTpl->append($mvc->title, 'TITLE');
        
        if ($data->addUrl) {
        	$addBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addUrl, FALSE, 'title=Добавяне на нова опаковка');
        	$tpl->append($addBtn, 'TITLE');
        }
        
        $wrapTpl->append($tpl, 'CONTENT');
        
        $tpl = $wrapTpl;
    }
    
    
    /**
     * Подготвя опаковките на артикула
     * 
     * @param stdClass $data
     */
    public static function preparePackagings($data)
    {
    	// Ако мастъра не е складируем, няма смисъл да показваме опаковките му
    	$productInfo = $data->masterMvc->getProductInfo($data->masterId);
    	if(empty($productInfo->meta['canStore'])){
    		$data->hide = TRUE;
    		return;
    	}
    	
    	static::prepareDetail($data);
    }
    
    
    /**
     * Подготвя опаковките на артикула
     * 
     * @param stdClass $data
     */
    public function renderPackagings($data)
    {
    	if($data->hide === TRUE) return;
    	
        return static::renderDetail($data);
    }
}
