<?php 



/**
 * Детайл за безналични методи на плащане
 *
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cash_NonCashPaymentDetails extends core_Manager
{
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'no_one';
	
	
	/**
	 * Кой може да създава?
	 */
	public $canAdd = 'no_one';
	
	
	/**
	 * Кой може да редактира?
	 */
	public $canEdit = 'no_one';
	
	
	/**
	 * Кой може да изтрива?
	 */
	public $canDelete = 'no_one';
	
	
	/**
	 * Кой може да изтрива?
	 */
	public $canModify = 'cash, ceo, purchase, sales';
	
	
	/**
	 * Неща, подлежащи на начално зареждане
	 */
	public $loadList = 'cash_Wrapper';
			

	/**
	 * Заглавие
	 */
	public $title = 'Начин на плащане';
	 
	 
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('documentId', 'int', 'input=hidden,mandatory,silent');
		$this->FLD('documentClassId', 'class', 'input=hidden,mandatory,silent');
		$this->FLD('paymentId', 'key(mvc=cond_Payments, select=title)', 'caption=Метод');
		$this->FLD('amount', 'double(decimals=2)', 'caption=Сума,mandatory');
		
		$this->setDbIndex('documentId,documentClassId');
		$this->setDbUnique('documentId,documentClassId,paymentId');
	}
	
	
	/**
	 * Подготовка на детайла
	 * 
	 * @param stdClass $data
	 */
	public function prepareDetail_($data)
	{
		$classId = $data->masterMvc->getClassId();
		$query = $this->getQuery();
		$query->where("#documentId = {$data->masterId} AND #documentClassId = {$classId}");
		$restAmount = $data->masterData->rec->amount;
		
		// Извличане на записите
		$data->recs = $data->rows = array();
		while ($rec = $query->fetch()){
			$data->recs[$rec->id] = $rec;
			$data->rows[$rec->id] = $this->recToVerbal($rec);
			$restAmount -= $rec->amount;
		}
	    
		if($restAmount > 0 && count($data->recs)){
			$r = (object)array('documentId' => $data->masterId, 'documentClassId' => $classId, 'amount' => $restAmount, 'paymentId' => -1);
			$data->recs[] = $r;
			$data->rows[] = $this->recToVerbal($r);
		}
		
		return $data;
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if($rec->paymentId == -1){
			$row->paymentId = tr('В брой');
		}
	}
	
	
	/**
	 * Рендиране на детайла
	 * 
	 * @param stdClass $data
	 * @return core_ET $tpl
	 */
	public function renderDetail_($data)
	{
		$tpl = new core_ET("");
		$block = getTplFromFile('cash/tpl/NonCashPayments.shtml');
		
		if(count($data->rows)){
			foreach ($data->rows as $row){
				$clone = clone $block;
				$clone->placeObject($row);
				$tpl->append($clone);
			}
		}
		
		return $tpl;
	}
	
	
	/**
	 * Eкшън за промяна на методите на плащане
	 * 
	 * @return core_ET $tpl
	 */
	function act_Modify()
	{
		// Проверки
		core_Request::setProtected('documentId,documentClassId');
		$this->requireRightFor('modify');
		expect($documentId = core_Request::get('documentId', 'int'));
		expect($documentClassId = core_Request::get('documentClassId', 'int'));
		$this->requireRightFor('modify', (object)array('documentId' => $documentId, 'documentClassId' => $documentClassId));
		
		// Подготовка на заглавието на формата
		$document = new core_ObjectReference($documentClassId, $documentId);
		$this->currentTab = ($document->isInstanceOf('cash_Pko')) ? 'ПКО' : 'РКО';
		$form = cls::get('core_Form');
		$form->title = core_Detail::getEditTitle($document->getInstance(), $document->that, 'начин на плащане', $document->that);
		
		// Подготовка на описанието на формата
		$amount = $document->fetchField('amount');
		$amountV = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($amount);
		$currency = currency_Currencies::getCodeById($document->fetchField('currencyId'));
		$form->info = tr('Сума за разпределяне') . ": <b>{$amount}</b> <span class='cCode'>{$currency}</span>";
		
		// Динамично добавяне на полета
		$paymentArr = $this->getPaymentsArr($document);
		foreach ($paymentArr as $key => $obj){
			$caption = cond_Payments::getTitleById($obj->paymentId);
			$form->FLD($key, 'double(Min=0)', "caption={$caption}");
			$form->setDefault($key, $obj->amount);
		}
		$form->input();
		
		// Ако формата е инпутната
		if($form->isSubmitted()){
			
			$rec = $form->rec;
			$arr = (array)$rec;
			$total = NULL;
			
			// Подготовка на записите за добавяне/редактиране/изтриване
			$update = $delete = array();
			foreach ($arr as $fld => $quantity){
				if(!empty($quantity)){
					$total += $quantity;
					$update[$fld] = (object)array('documentClassId' => $documentClassId, 'documentId' => $documentId, 'paymentId' => $paymentArr[$fld]->paymentId, 'amount' => $quantity, 'id' => $paymentArr[$fld]->id);
				} else {
					if(isset($paymentArr[$fld]->id)){
						$delete[] = $paymentArr[$fld]->id;
					}
				}
			}
			
			// Проверка на сумата
			if($total > $amount){
				$form->setError(implode(',', array_keys($update)), 'Количеството е над допустимото');
			} else {
				
				// Ъпдейт на нужните записи
				if(count($update)){
					$this->saveArray_($update);
				}
				
				// Изтриване на старите записи
				if(count($delete)){
					foreach ($delete as $id){
						self::delete($id);
					}
				}
				
				$documentRec = cls::get($documentClassId)->fetch($documentId);
				cls::get($documentClassId)->save($documentRec);
				
 				// Редирект
				followRetUrl();
			}
		}
		
		// Подготовка на туулбара на формата
		$form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Запис на промените');
		$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
			
		// Рендиране на формата
		$tpl = $this->renderWrapping($form->renderHtml());
		
		return $tpl;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'modify' && isset($rec)){
			if(empty($rec->documentClassId) || empty($rec->documentId)){
				$requiredRoles = 'no_one';
			} else {
				$document = new core_ObjectReference($rec->documentClassId, $rec->documentId);
				if(!$document->isInstanceOf('cash_Document')){
					$requiredRoles = 'no_one';
				} else {
					if($document->fetchField('state') != 'draft'){
						$requiredRoles = 'no_one';
					} elseif($document->fetchField('isReverse') == 'yes'){
						$requiredRoles = 'no_one';
					}
				}
			}
		}
	}
	
	
	/**
	 * Връща разрешените методи за плащане
	 * 
	 * @param core_ObjectReference $document
	 * @return array $res
	 */
	private function getPaymentsArr(core_ObjectReference $document)
	{
		$res = $exRecs = array();
		
		// Намиране на всички методи за плащане
		$pQuery = cond_Payments::getQuery();
		$pQuery->where("#state = 'active'");
		while($pRec = $pQuery->fetch()){
			$res["payment{$pRec->id}"] = (object)array('paymentId' => $pRec->id, 'amount' => NULL, 'id' => NULL);
		}
		
		// Взимане на методите за плащане към самия документ
		$query = $this->getQuery();
		$query->where("#documentId = {$document->that} AND #documentClassId = {$document->getClassId()}");
		while($rec = $query->fetch()){
			$res["payment{$rec->paymentId}"] = (object)array('paymentId' => $rec->paymentId, 'amount' => $rec->amount, 'id' => $rec->id);
		}
		
		return $res;
	}
}