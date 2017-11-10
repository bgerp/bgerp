<?php



/**
 * Мениджър на документ за прехвърляне на салда
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_BalanceTransfers extends core_Master
{
    
    
    /**
	 * Какви интерфейси поддържа този мениджър
	 */
	public $interfaces = 'acc_TransactionSourceIntf=acc_transaction_BalanceTransfer';
	
	
	/**
	 * Заглавие на мениджъра
	 */
	public $title = "Прехвърляне на салда";
	
	
	/**
	 * Неща, подлежащи на начално зареждане
	 */
	public $loadList = 'plg_RowTools2, acc_Wrapper, acc_plg_Contable, doc_DocumentPlg, doc_plg_SelectFolder';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = "valior=Вальор,fromAccount=От,toAccount=Към,createdOn,createdBy";

	
	/**
	 * Може ли да се контира въпреки, че има приключени пера в транзакцията
	 */
	public $canUseClosedItems = TRUE;
	
	
	/**
	 * Дали при възстановяване/контиране/оттегляне да се заключва баланса
	 *
	 * @var boolean TRUE/FALSE
	 */
	public $lockBalances = TRUE;
	
	
	/**
	 * Заглавие на единичен документ
	 */
	public $singleTitle = 'Прехвърляне на салдо';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = "Btr";
	
	
	/**
	 * Кой има право да чете?
	 */
	public $canRead = 'acc,ceo';
	
	
	/**
	 * Кой може да пише?
	 */
	public $canWrite = 'accMaster,ceo';
	
	
	/**
	 * Кой може да го контира?
	 */
	public $canConto = 'accMaster,ceo';
	
	
	/**
	 * Кой може да го отхвърли?
	 */
	public $canReject = 'accMaster,ceo';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,acc';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,acc';
	
	
	/**
	 * Дали може да бъде само в началото на нишка
	 */
	public $onlyFirstInThread = TRUE;
	
	
	/**
	 * Файл с шаблон за единичен изглед
	 */
	public $singleLayoutFile = 'acc/tpl/SingleLayoutBalanceTransfer.shtml';
	
	
	/**
	 * Групиране на документите
	 */
	public $newBtnGroup = "6.7|Счетоводни";
	
	
	/**
	 * Списък с корици и интерфейси, където може да се създава нов документ от този клас
	 */
	public $coversAndInterfacesForNewDoc = 'doc_UnsortedFolders';

	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('valior', 'date', 'caption=Вальор,mandatory');
		$this->FLD('fromAccount', 'acc_type_Account(allowEmpty)', 'mandatory,caption=От->Сметка,removeAndRefreshForm=ent1Id|ent2Id|ent3Id|toAccount|toEnt1Id|toEnt2Id|toEnt3Id,silent');
		$this->FLD('fromEnt1Id', 'acc_type_Item(allowEmpty,select=titleLink)', 'silent,caption=От->перо 1,input=none,removeAndRefreshForm=toEnt1Id');
        $this->FLD('fromEnt2Id', 'acc_type_Item(allowEmpty,select=titleLink)', 'silent,caption=От->перо 2,input=none,removeAndRefreshForm=toEnt2Id');
        $this->FLD('fromEnt3Id', 'acc_type_Item(allowEmpty,select=titleLink)', 'silent,caption=От->перо 3,input=none,removeAndRefreshForm=toEnt3Id');
		
		$this->FLD('toAccount', 'acc_type_Account(allowEmpty)', 'mandatory,caption=Към->Сметка,silent,removeAndRefreshForm=toEnt1Id|toEnt2Id|toEnt3Id');
		$this->FLD('toEnt1Id', 'acc_type_Item(allowEmpty,select=titleLink)', 'caption=Към->перо 1,input=none');
		$this->FLD('toEnt2Id', 'acc_type_Item(allowEmpty,select=titleLink)', 'caption=Към->перо 2,input=none');
		$this->FLD('toEnt3Id', 'acc_type_Item(allowEmpty,select=titleLink)', 'caption=Към->перо 3,input=none');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$rec = &$form->rec;
		$form->setDefault('valior', dt::today());
		
		// Ако е избрана начална сметка
		if(isset($rec->fromAccount)){
			$interfaces = array();
			$accInfo = acc_Accounts::getAccountInfo($rec->fromAccount);
			
			// Показваме аналитичностите и
			if(count($accInfo->groups)){
				foreach ($accInfo->groups as $i => $gr){
					if($gr->rec->regInterfaceId){
						$interfaces[] = core_Interfaces::fetchField($gr->rec->regInterfaceId,'name');
					}
					
					// Типа на полето му задаваме да показва само перата от номенклатурите
					$form->setField("fromEnt{$i}Id", "input,caption=От->{$gr->rec->name}");
					$form->setFieldTypeParams("fromEnt{$i}Id", array('lists' => $gr->rec->num));
				}
			}
			
			// Задаваме на другата сметка да използва същите аналитичности
			$interfaces = count($interfaces) ? implode('|', $interfaces) : 'none';
			$form->fromAccountInterfaces = $interfaces;
			$form->setFieldTypeParams('toAccount', array('regInterfaces' => $interfaces));
		}
		
		// Ако е избрана дестинационна сметка, показваме и аналитичностите
		if(isset($rec->toAccount)){
			$accInfo1 = acc_Accounts::getAccountInfo($rec->toAccount);
			if(count($accInfo1->groups)){
				foreach ($accInfo1->groups as $i => $gr){
					$form->setField("toEnt{$i}Id", "input,caption=Към->{$gr->rec->name}");
					$form->setFieldTypeParams("toEnt{$i}Id", array('lists' => $gr->rec->num));
				}
			}
		}
		
		// При избор на перо от първата група, задаваме го по дефолт и на втората
		foreach (range(1, 3) as $j){
			if($rec->{"fromEnt{$j}Id"}){
				$rec->{"toEnt{$j}Id"} = $rec->{"fromEnt{$j}Id"};
			}
		}
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	public static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = &$form->rec;
		
		if($form->isSubmitted()){

			// Подсигуряваме се, че аналитичностите на началната и крайната сметка, наистина съвпадат
			if($form->fromAccountInterfaces != $form->getFieldTypeParam("toAccount", 'regInterfaces')){
				$form->setError('fromAccount,toAccount', 'Трябва двете сметки да са със съответсващи аналитичности');
			}
			
			// Всички аналитичности
			foreach (range(1, 3) as $i){
				$from = $rec->{"fromEnt{$i}Id"};
				$to = $rec->{"toEnt{$i}Id"};
				
				// Или трябва да са зададени пера по текущата налитичност на двете сметки или да не са
				if((isset($from) && empty($to)) || (empty($from) && isset($to))){
					$form->setError("fromEnt{$i}Id,toEnt{$i}Id", 'Трябва или и двете да са избрани или да не са');
				} else {
					
					// Ако и двете са празни
					if(empty($from) && empty($to)){
						
						// Проверяваме дали следващото е празно, ако не е показваме грешка
						$entNext = "fromEnt" . ($i + 1) . "Id";
						if(empty($from) && !empty($rec->{$entNext})){
							$form->setError("fromEnt{$i}Id,toEnt{$i}Id", 'Ако има непопълнена аналитичност, то и следващата трябва да е празна');
						}
					}
					
					// Подсигуряваме се, че номенклатурите на съответните аналитичностите са еднакви
					$fromList = $form->getFieldTypeParam("fromEnt{$i}Id", 'lists');
					$toList = $form->getFieldTypeParam("toEnt{$i}Id", 'lists');
					if($fromList != $toList){
						$form->setError("fromEnt{$i}Id,toEnt{$i}Id", 'Номенклатурите на аналитичностите трябва да са еднакви');
					}
					
					// Ако номенклатурата е размерна и има избрани пера, то те трябва да са еднакви
					// не може да се прехвърлят например валути към артикули !
					if(acc_Lists::fetchField("#num = '{$fromList}'", 'isDimensional') == 'yes'){
						if($from !== $to){
							$form->setError("fromEnt{$i}Id,toEnt{$i}Id", 'Размерните номенклатури, трябва да са еднакви, или да не са попълнени');
						}
					}
				}
			}
			
			// Ако не са избрани пера а само сметки
			if(empty($rec->fromEnt1Id) && empty($rec->fromEnt2Id) && empty($rec->fromEnt3Id) && empty($rec->toEnt1Id) && empty($rec->toEnt1Id) && empty($rec->toEnt1Id)){
				
				// Сметките трябва да са различни
				if($rec->fromAccount == $rec->toAccount){
					$form->setError('fromAccount,toAccount', 'Ако се прехвърля цяла сметка, дестинацията трябва да е различна');
				}
			}
		}
	}
	
	
	/**
	 * Проверка дали нов документ може да бъде добавен в
	 * посочената папка като начало на нишка
	 *
	 * @param $folderId int ид на папката
	 */
	public static function canAddToFolder($folderId)
	{
		$me = cls::get(get_called_class());
		 
		// Може да създаваме документ-а само в дефолт папката му
		if ($folderId == doc_UnsortedFolders::forceCoverAndFolder((object)array('name' => $me->title))) {
	
			return TRUE;
		}
	
		return FALSE;
	}
	
	
	/**
	 * Интерфейсен метод на doc_DocumentInterface
	 */
	public function getDocumentRow($id)
	{
		$rec = $this->fetch($id);
	
		$row = new stdClass();
	
		$row->title    = $this->getRecTitle($rec);
		$row->authorId = $rec->createdBy;
		$row->author   = $this->getVerbal($rec, 'createdBy');
		$row->recTitle = $row->title;
		$row->state    = $rec->state;
	
		return $row;
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->fromAccount = acc_Balances::getAccountLink($rec->fromAccount);
		$row->toAccount = acc_Balances::getAccountLink($rec->toAccount);
	}
}