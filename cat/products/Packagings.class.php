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
    
    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Опаковка';
 
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'code=Код, packagingId, quantity=К-во, netWeight=, tareWeight=, weight=Тегло, 
        sizeWidth=, sizeHeight=, sizeDepth=, dimention=Габарити, 
        eanCode=, customCode=,tools=Пулт';
    
    
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
        $this->FLD('packagingId', 'key(mvc=cat_Packagings,select=name)', 'input,caption=Опаковка,mandatory');
        $this->FLD('quantity', 'double', 'input,caption=Количество,mandatory');
        $this->FLD('netWeight', 'cat_type_Weight', 'input,caption=Тегло->Нето');
        $this->FLD('tareWeight', 'cat_type_Weight', 'input,caption=Тегло->Тара');
        $this->FLD('sizeWidth', 'cat_type_Dimension', 'input,caption=Габарит->Ширина');
        $this->FLD('sizeHeight', 'cat_type_Dimension', 'input,caption=Габарит->Височина');
        $this->FLD('sizeDepth', 'cat_type_Dimension', 'input,caption=Габарит->Дълбочина');
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
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles(core_Mvc $mvc, &$requiredRoles, $action, $rec)
    {
    	if($requiredRoles == 'no_one') return;
    	
        if ($action == 'add') {
        	if (!count($mvc::getRemainingOptions($rec->productId))) {
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
        $data->form->toolbar->addBtn('Отказ', array($mvc->Master, 'single', $data->form->rec->productId),  'ef_icon = img/16/close16.png');
    	if(!(count($mvc::getRemainingOptions($data->form->rec->productId)) - 1)){
    		$data->form->toolbar->removeBtn('Запис и Нов');
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
        $options = cat_Packagings::makeArray4Select();
        
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
        $options = $mvc::getRemainingOptions($data->form->rec->productId, $data->form->rec->id);
        
        if (empty($options)) {
            // Няма повече недефинирани опаковки
            redirect(getRetUrl(), FALSE, tr('Няма повече недефинирани опаковки'));
        }
		
    	if(!$data->form->rec->id){
        	$options = array('' => '') + $options;
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
    		$row->code = tr("|EAN|*:") . $row->eanCode . "<br />";
    	}
    	if($rec->customCode){
    		$row->code .= tr("Вътрешен") . ": " . $row->customCode;
    	}
    	if($rec->netWeight){
    		$row->weight = tr("|Нето|*: ") . $row->netWeight . "<br />";
    	}
    	if($rec->tareWeight){
    		$row->weight .= tr("|Тара|*: {$row->tareWeight}");
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
