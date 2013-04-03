<?php

/**
 * Клас 'cat_products_Packagings'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
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
    var $singleTitle = 'Опаковка';
 
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'code=Код, packagingId, quantity=К-во, netWeight=, tareWeight=, weight=Тегло, 
        sizeWidth=, sizeHeight=, sizeDepth=, dimention=Габарити, 
        eanCode=, customCode=,tools=Пулт';
    
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
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings,select=name)', 'input,silent,caption=Опаковка,mandatory');
        $this->FLD('quantity', 'double', 'input,caption=Количество,mandatory');
        $this->FLD('netWeight', 'double(decimals=3)', 'input,caption=Тегло->Нето');
        $this->FLD('tareWeight', 'double(decimals=3)', 'input,caption=Тегло->Тара');
        $this->FLD('sizeWidth', 'double(decimals=3)', 'input,caption=Габарит->Ширина');
        $this->FLD('sizeHeight', 'double(decimals=3)', 'input,caption=Габарит->Височина');
        $this->FLD('sizeDepth', 'double(decimals=3)', 'input,caption=Габарит->Дълбочина');
        $this->FLD('eanCode', 'gs1_TypeEan', 'input,caption=Код->EAN');
        $this->FLD('customCode', 'varchar(64)', 'input,caption=Код->Вътрешен');
        
        $this->setDbUnique('productId,packagingId');
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
    	if ($form->isSubmitted()){
    		$rec = &$form->rec;
    		
    		foreach(array('eanCode', 'customCode') as $code) {
    			if($rec->$code) {
    				
    				// Проверяваме дали има продукт с такъв код (като изключим текущия)
	    			$check = $mvc->Master->checkIfCodeExists($rec->$code);
	    			if($check && ($check->productId != $rec->productId)
	    				 || ($check->productId == $rec->productId && $check->packagingId != $rec->packagingId)) {
	    				$form->setError($code, 'Има вече продукт с такъв код!');
			        }
    			}
    		}
    	}
    }
    
    
    public static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
        if ($action == 'add') {
            if (isset($rec) && !count($mvc::getPackagingOptions($rec->productId))) {
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
        
        if ($mvc->haveRightFor('add', (object)array('productId'=>$data->masterId)) && count($mvc::getPackagingOptions($data->masterId) > 0)) {
        	$data->addUrl = array(
                $mvc,
                'add',
                'productId'=>$data->masterId,
                'ret_url'=>getCurrentUrl() + array('#'=>get_class($mvc))
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
        $data->form->toolbar->addBtn('Отказ', array($mvc->Master, 'single', $data->form->rec->productId), array('class'=>'btn-cancel'));
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        $options = $mvc::getPackagingOptions($data->form->rec->productId, $data->form->rec->id);
        
        if (empty($options)) {
            // Няма повече недефинирани опаковки
            redirect(getRetUrl(), FALSE, tr('Няма повече недефинирани опаковки'));
        }
        $data->form->setOptions('packagingId', $options);
        
        $productRec = cat_Products::fetch($data->form->rec->productId);
        
        // Променяме заглавието в зависимост от действието
        if (!$data->form->rec->id) {
            
            // Ако добавяме нова опаковка
            $titleMsg = 'Добавяне на опаковка за';    
        } else {
            
            // Ако редактираме съществуваща
            $titleMsg = 'Редактиране на опаковка за';
        }
        
        // Добавяме заглавието
        $data->form->title = "{$titleMsg} |*" . cat_Products::getVerbal($productRec, 'name');
    }
    
    
    /**
     * Опаковките, определени от категорията на продукта и все още не дефинирани за този него.
     *
     * @param int ид на продукт
     * @return array опциите, подходящи за @link core_Form::setOptions()
     */
    static function getPackagingOptions($productId, $id=NULL)
    {
        $options = cat_Packagings::makeArray4Select();
        
        return $options;
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
    		$row->codecode = "EAN: {$row->eanCode} <br />";
    	}
    	if($rec->customCode){
    		$row->codecode .= "Вътрешен: {$row->customCode}";
    	}
    	if($rec->netWeight){
    		$row->weight = "Нето: {$row->netWeight} <br />";
    	}
    	if($rec->tareWeight){
    		$row->weight .= "Тара: {$row->tareWeight}";
    	}
    }

    
    public static function on_AfterRenderDetail($mvc, &$tpl, $data)
    {
        if ($data->addUrl) {
            $addBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " valign=bottom style='margin-left:5px;'>", $data->addUrl);
            $tpl->append($addBtn, 'TITLE');
        }
    }
    
    
    public static function preparePackagings($data)
    {
        static::prepareDetail($data);
    }
    
    
    public function renderPackagings($data)
    {
        return static::renderDetail($data);
    }
}
