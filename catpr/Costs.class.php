<?php
/**
 * 
 * Себестойности на продуктите от каталога
 * 
 * @category   BGERP
 * @package    catpr
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @title      Себестойност
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class catpr_Costs extends core_Manager
{
	var $title = 'Себестойност';
	
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools,
                     catpr_Wrapper, plg_AlignDecimals, plg_SaveAndNew';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'productId, priceGroupId, xValiorDate, xValiorTime, cost, baseDiscount, publicPrice, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,catpr';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,catpr,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,catpr,broker';
    
    var $canList = 'admin,catpr,broker';
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,catpr';
	
    
    function description()
	{
		$this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'mandatory,input,caption=Продукт,remember=info');
		$this->FLD('priceGroupId', 'key(mvc=catpr_Pricegroups,select=name,allowEmpty)', 'mandatory,input,caption=Група');
		$this->FLD('valior', 'datetime', 'input,caption=Вальор');
		$this->FLD('cost', 'double(minDecimals=2)', 'mandatory,input,caption=Себестойност');
		
		$this->EXT('baseDiscount', 'catpr_Pricegroups', 'externalKey=priceGroupId,input=none,caption=Базова отстъпка');
		
		$this->FNC('publicPrice', 'double(decimals=2,minDecimals=2)', 'caption=Публична цена');
		
		// Полета, използвани за форматиране на вальора
		$this->XPR('xValiorDate', 'varchar', 'DATE(#valior)', 'caption=Вальор->Дата');
		$this->XPR('xValiorTime', 'varchar', 'TIME(#valior)', 'caption=Вальор->Час');
		
		// Кода в този модел гарантира, че ако вальора е бъдеща дата, то часа му е нула. Предвид 
		// това, този уникален индекс гарантира, че не могат да се въведат две себестойности за 
		// един продукт към една и съща *бъдеща* дата.
		$this->setDbUnique('productId, valior');
	}
	
	
	function on_CalcPublicPrice($mvc, &$rec)
	{
		$rec->publicPrice = self::getPublicPrice($rec);
	}
	
	
	function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		switch ($action) {
			case 'edit':
				// Не могат да се променят записи за себестойност
				$requiredRoles = 'no_one';
				break;
			case 'delete':
				// Могат да се изтриват само себестойности към бъдещи дати
				if ($rec && $rec->xValiorDate <= dt::today()) {
					$requiredRoles = 'no_one';
				}
				break;
		}
	}
	
	
	function on_AfterPrepareEditForm($mvc, $data)
	{
		$form = $data->form;
		$rec  = $form->rec;
		
		// Скриваме истинското поле за вальор от формата и добавяме фиктивно поле от тип `date`
		// (а не `datetime`). Целта е потребителя да може да въвежда само дати (без час), а 
		// системата автоматично да изчислява и записва часа, на базата на правила:
		//  * за бъдещи дати   - часа е нула (00:00:00)
		//  * за текущата дата - часа е текущия час
		//  * за минали дати   - не могат да се въвеждат, забранено е.
		$form->setField('valior', 'input=none');
		$form->FNC('fValior', 'date', 'mandatory,input,caption=Вальор,remember');
		$form->FNC('fIsChange', 'int', 'input=hidden');
	}  

	
	function on_AfterInputEditForm($mvc, $form)
	{
		if (!$form->isSubmitted()) {
			if ($baseId = Request::get('baseId', 'key(mvc=' . $this->className . ')')) {
				$form->rec = $mvc->fetch($baseId);
				$form->setDefault('fIsChange', 1);
				unset($form->rec->id);
			}
			$form->setDefault('fValior', dt::today());
			return;
		}
		
		$today = dt::today();
		
		switch (true) {
			case ($today > $form->rec->fValior):
				// Себестойност към дата в миналото - недопустимо!
				$form->setError('fValior', 
					'Не се допуска промяна на себестойност със задна дата.');
				break;
			case ($today < $form->rec->fValior):
				// Себестойност към дата в бъдещето - "забиваме" часа на 00:00:00
				$form->rec->valior = $form->rec->fValior . ' ' . '00:00:00';
				break;
			case ($today == $form->rec->fValior):
			default:
				// Себестойност към днешна дата - "забиваме" часа на текущия час
				$form->rec->valior = $form->rec->fValior . ' ' . date('H:i:s');
				if ($form->rec->fIsChange) {
					$form->setWarning('fValior', 'Внимание, променяте себестойността с днешна дата!');
				}
		}
	}
	
	
	/**
	 * Преди извличане на записите от БД
	 *
	 * @param core_Mvc $mvc
	 * @param StdClass $res
	 * @param StdClass $data
	 */
	function on_BeforePrepareListRecs($mvc, &$res, $data)
	{
		$data->query->orderBy('productId');
		$data->query->orderBy('valior', 'desc');
	}
	
	
	function on_AfterPrepareListRecs($mvc, $data)
	{
		$rows = &$data->rows;
		$recs = &$data->recs;
		
		$prevProductId = NULL;
		$prevGroupId   = NULL;
		
        if(count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
            	// Скриване на продукта и групата, ако са същите като в предходния ред.
                $rec = $recs[$i];
                
                if ($rec->productId == $prevProductId) {
                    $row->productId = '';
                    if ($rec->priceGroupId == $prevGroupId) {
                        $row->priceGroupId = '';
                    } else {
                    	$row->CSS_CLASS[] = 'pricegroup';
                    }
                    $row->CSS_CLASS[] = 'quiet';
                } else {
                    $row->productId = "<strong>{$row->productId}</strong>";
                }
                
                if ($rec->xValiorDate <= dt::today()) {
	                $prevProductId = $rec->productId;
	                $prevGroupId   = $rec->priceGroupId;
                } else {
                	$row->CSS_CLASS[] = 'quiet';
                	$row->CSS_CLASS[] = 'future';
                	$row->CSS_CLASS[] = 'pricegroup';
                }
                
                if (empty($row->CSS_CLASS) || !in_array('quiet', $row->CSS_CLASS)) {
                	$row->CSS_CLASS[] = 'current';
                	$prevGroupId   = NULL;
                	
                	// Линк за "редактиране" на текущата себестойност. Тъй като себестойностите
                	// не могат да се променят в буквален смисъл, линкът е към екшъна за добавяне
                	// на нова себестойност, която да отмени текущата.
					$editImg = "<img src=" . sbf('img/16/edit.png') . ">";
		            
		            $editUrl = toUrl(array(
		                $mvc,
		                'add',
		                'baseId' => $rec->id,
		                'ret_url' => TRUE
		            ));
		            
		            if (!is_a($row->tools, 'core_ET')) {
		            	$row->tools = new ET($row->tools);
		            }
		                            
	                $row->tools->append(''
	                	. '<div class="rowtools">'
	                		. ht::createLink($editImg, $editUrl)
	                	. '</div>' 
	                );
                }
                
                // Форматиране на вальора - не показва часа, ако той е '00:00:00'
                if ($rec->xValiorTime == '00:00:00') {
                	$row->xValiorTime = '';
                }
            }
        }
	}
	
	static function getPublicPrice($rec)
	{
		return (double)$rec->cost / (1 - (double)$rec->baseDiscount);
	}
	
	
	/**
	 * Себестойността на продукт към дата или историята на себестойностите на продукта.
	 *
	 * @param int $id key(mvc=cat_Product)
	 * @param string $date дата, към която да се изчисли себестойността или NULL за историята на 
	 * 						себестойностите.
	 * @return array масив от записи на този модел - catpr_Costs
	 */
	static function getProductCosts($id, $date = NULL)
	{
		$query = self::getQuery();
		
		$query->orderBy('valior', 'desc');
		$query->where("#productId = {$id}");
		if (isset($date)) {
			// Търсим себестойност към фиксирана дата. Това е най-новата себестойност с вальор 
			// преди тази дата.
			// В случай, че в датата има зададен час, търси се себестойността точно към този 
			// час. Иначе се търси себестойността към края на деня.
			$query->where(
				"DATE(#valior) < DATE('{$date}')"
					. ' OR '
					. '('
						. "DATE(#valior) = DATE('{$date}')"
							. ' AND '
						. "TIME(#valior) <= IF( TIME(TIMESTAMP('{$date}')), TIME(TIMESTAMP('{$date}')), '23:59:25' )"
					. ')'
			);
			$query->limit(1);
		}
		
		$result = array();
		
		while ($rec = $query->fetch()) {
			$result[] = $rec;
		}
		
		return $result;
	}
}
