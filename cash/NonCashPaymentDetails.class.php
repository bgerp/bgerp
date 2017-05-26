<?php 



/**
 * Детайл за безналични методи на плащане към ПКО
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
		$this->FLD('documentId', 'key(mvc=cash_Pko)', 'input=hidden,mandatory,silent');
		$this->FLD('paymentId', 'key(mvc=cond_Payments, select=title)', 'caption=Метод');
		$this->FLD('amount', 'double(decimals=2)', 'caption=Сума,mandatory');
		
		$this->setDbIndex('documentId');
		$this->setDbUnique('documentId,paymentId');
	}
	
	
	/**
	 * Подготовка на детайла
	 * 
	 * @param stdClass $data
	 */
	public function prepareDetail_($data)
	{
		$query = $this->getQuery();
		$query->where("#documentId = {$data->masterId}");
		$restAmount = $data->masterData->rec->amount;
		
		// Извличане на записите
		$data->recs = $data->rows = array();
		while ($rec = $query->fetch()){
			$data->recs[$rec->id] = $rec;
			$data->rows[$rec->id] = $this->recToVerbal($rec);
			$restAmount -= $rec->amount;
		}
	    
		if($restAmount > 0 && count($data->recs)){
			$r = (object)array('documentId' => $data->masterId, 'amount' => $restAmount, 'paymentId' => -1);
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
	 * Връща разрешените методи за плащане
	 * 
	 * @param core_ObjectReference $document
	 * @return array $res
	 */
	public static function getPaymentsArr($documentId, $documentClassId)
	{
		$res = $exRecs = array();
		
		// Намиране на всички методи за плащане
		$pQuery = cond_Payments::getQuery();
		$pQuery->where("#state = 'active'");
		while($pRec = $pQuery->fetch()){
			$res["_payment{$pRec->id}"] = (object)array('paymentId' => $pRec->id, 'amount' => NULL, 'id' => NULL);
		}
		
		// Взимане на методите за плащане към самия документ
		if(isset($documentId)){
			$query = self::getQuery();
			$query->where("#documentId = {$documentId}");
			while($rec = $query->fetch()){
				$res["_payment{$rec->paymentId}"] = (object)array('paymentId' => $rec->paymentId, 'amount' => $rec->amount, 'id' => $rec->id);
			}
		}
		
		return $res;
	}
}