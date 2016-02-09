<?php



/**
 * Документ за наследяване от банковите документи
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class bank_Document extends core_Master
{

	
	/**
	 * Флаг, който указва, че документа е партньорски
	 */
	public $visibleForPartners = TRUE;
	
	
	/**
	 * Дали сумата е във валута (различна от основната)
	 *
	 * @see acc_plg_DocumentSummary
	 */
	public $amountIsInNotInBaseCurrency = TRUE;
	
	
	/**
	 * Неща, подлежащи на начално зареждане
	 */
	public $loadList = 'plg_RowTools, bank_Wrapper, acc_plg_RejectContoDocuments, acc_plg_Contable,
         plg_Sorting, doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary,doc_plg_HidePrices,
         plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank, doc_EmailCreatePlg';
	
	
	/**
	 * Полета свързани с цени
	 */
	public $priceFields = 'amount';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = "tools=Пулт, valior, title=Документ, reason, folderId, currencyId, amount, state, createdOn, createdBy";
	
	
	/**
	 * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
	 */
	public $rowToolsField = 'tools';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'title';
	
	
	/**
	 * Кой има право да чете?
	 */
	public $canRead = 'bank, ceo';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'bank, ceo';
	
	
	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'bank, ceo';
	
	
	/**
	 * Кой може да пише?
	 */
	public $canWrite = 'bank, ceo';
	
	
	/**
	 * Кой може да го контира?
	 */
	public $canConto = 'bank, ceo';
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'reason, contragentName, amount, id';
	
	
	/**
	 * Основна сч. сметка
	 */
	public static $baseAccountSysId = '503';
	
	
	/**
	 * Добавяне на дефолтни полета
	 *
	 * @param core_Mvc $mvc
	 * @return void
	 */
	protected function getFields(core_Mvc &$mvc)
	{
		$mvc->FLD('operationSysId', 'varchar', 'caption=Операция,mandatory');
		$mvc->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,mandatory');
		$mvc->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,summary=amount');
		$mvc->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута');
		$mvc->FLD('rate', 'double(decimals=5)', 'caption=Курс');
		$mvc->FLD('reason', 'richtext(bucket=Notes,rows=6)', 'caption=Основание,mandatory');
		$mvc->FLD('contragentName', 'varchar(255)', 'caption=От->Контрагент,mandatory');
		$mvc->FLD('contragentIban', 'iban_Type(64)', 'caption=От->Сметка');
		$mvc->FLD('ownAccount', 'key(mvc=bank_OwnAccounts,select=title)', 'caption=В->Сметка,mandatory');
		$mvc->FLD('contragentId', 'int', 'input=hidden,notNull');
		$mvc->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
		$mvc->FLD('debitAccId', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'caption=debit,input=none');
		$mvc->FLD('creditAccId', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'caption=Кредит,input=none');
		$mvc->FLD('state',
				'enum(draft=Чернова, active=Активиран, rejected=Сторниран, closed=Контиран)',
				'caption=Статус, input=none'
		);
		$mvc->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
	}


	/**
	 * Проверка след изпращането на формата
	 */
	protected static function on_AfterInputEditForm($mvc, $form)
	{
		if ($form->isSubmitted()){
	
			$rec = &$form->rec;
	
			$origin = $mvc->getOrigin($form->rec);
			$dealInfo = $origin->getAggregateDealInfo();
	
			// Коя е дебитната и кредитната сметка
			$opperations = $dealInfo->get('allowedPaymentOperations');
			$operation = $opperations[$rec->operationSysId];
			$debitAcc = empty($operation['reverse']) ? $operation['debit'] : $operation['credit'];
			$creditAcc = empty($operation['reverse']) ? $operation['credit'] : $operation['debit'];
			$rec->debitAccId = $debitAcc;
			$rec->creditAccId = $creditAcc;
			$rec->isReverse = empty($operation['reverse']) ? 'no' : 'yes';
	
			// Проверяваме дали банковата сметка е в същата валута
			$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->ownAccount);
	
			if($ownAcc->currencyId != $rec->currencyId) {
				$form->setError('currencyId', 'Банковата сметка е в друга валута');
			}
			$currencyCode = currency_Currencies::getCodeById($rec->currencyId);
	
			// Ако няма валутен курс, взимаме този от системата
			if(!$rec->rate) {
				$rec->rate = currency_CurrencyRates::getRate($rec->valior, $currencyCode, acc_Periods::getBaseCurrencyCode($rec->valior));
				if(!$rec->rate){
					$form->setError('rate', "Не може да се изчисли курс");
				}
			} else {
				if($msg = currency_CurrencyRates::hasDeviation($rec->rate, $rec->valior, $currencyCode, NULL)){
					$form->setWarning('rate', $msg);
				}
			}
		}
	}
	
	
	/**
	 * Извиква се след успешен запис в модела
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
	{
		// Ако няма такава банкова сметка, тя автоматично се записва
		if($rec->contragentIban){
			bank_Accounts::add($rec->contragentIban, $rec->currencyId, $rec->contragentClassId, $rec->contragentId);
		}
	}
	

	/**
	 * Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, $data)
	{
		// Добавяме към формата за търсене търсене по Каса
		bank_OwnAccounts::prepareBankFilter($data, array('ownAccount'));
	}

	
	/**
	 * Извиква се след подготовката на toolbar-а за табличния изглед
	 */
	protected static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		if(!empty($data->toolbar->buttons['btnAdd'])){
			$data->toolbar->removeBtn('btnAdd');
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
		return FALSE;
	}
	

	/**
	 * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
	 */
	public function getDocumentRow($id)
	{
		$rec = $this->fetch($id);
		$row = new stdClass();
		$row->title = $this->singleTitle . " №{$id}";
		$row->authorId = $rec->createdBy;
		$row->author = $this->getVerbal($rec, 'createdBy');
		$row->state = $rec->state;
		$row->recTitle = $rec->reason;
	
		return $row;
	}
	

	/**
	 * Проверка дали нов документ може да бъде добавен в
	 * посочената нишка
	 *
	 * @param int $threadId key(mvc=doc_Threads)
	 * @return boolean
	 */
	public static function canAddToThread($threadId)
	{
		$firstDoc = doc_Threads::getFirstDocument($threadId);
		$docState = $firstDoc->fetchField('state');
	
		if(($firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState == 'active')){
	
			// Ако няма позволени операции за документа не може да се създава
			$operations = $firstDoc->getPaymentOperations();
			$options = static::getOperations($operations);
	
			return count($options) ? TRUE : FALSE;
		}
	
		return FALSE;
	}
	

	/**
	 * Имплементация на @link bgerp_DealIntf::getDealInfo()
	 *
	 * @param int|object $id
	 * @return bgerp_iface_DealAggregator
	 * @see bgerp_DealIntf::getDealInfo()
	 */
	public function pushDealInfo($id, &$aggregator)
	{
		$rec = static::fetchRec($id);
		$aggregator->setIfNot('bankAccountId', bank_OwnAccounts::fetchField($rec->ownAccount, 'bankAccountId'));
	}
	

	/**
	 * Интерфейсен метод на doc_ContragentDataIntf
	 * Връща тялото на имейл по подразбиране
	 */
	public static function getDefaultEmailBody($id)
	{
		$self = cls::get(get_called_class());
		$handle = static::getHandle($id);
		$singleTitle = mb_strtolower($self->singleTitle);
		$tpl = new ET(tr("Моля запознайте се с нашия {$singleTitle}") . ': #[#handle#]');
		$tpl->append($handle, 'handle');
	
		return $tpl->getContent();
	}
	
	
	/**
	 * Връща разбираемо за човека заглавие, отговарящо на записа
	 */
	public static function getRecTitle($rec, $escaped = TRUE)
	{
		$self = cls::get(get_called_class());
	
		return $self->singleTitle . " №$rec->id";
	}
	

	/**
	 * Вкарваме css файл за единичния изглед
	 */
	protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
	{
		$tpl->push('bank/tpl/css/styles.css', 'CSS');
	}
	

	/**
	 * Подготовка на бутоните на формата за добавяне/редактиране
	 */
	protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
	{
		// Документа не може да се създава  в нова нишка, ако е възоснова на друг
		if(!empty($data->form->toolbar->buttons['btnNewThread'])){
			$data->form->toolbar->removeBtn('btnNewThread');
		}
	}
	
	
	/**
	 * Обработки по вербалното представяне на данните
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->title = $mvc->getLink($rec->id, 0);
	
		if($fields['-single']) {
	
			$row->currencyId = currency_Currencies::getCodeById($rec->currencyId);
	
			if($rec->rate != '1') {
	
				$period = acc_Periods::fetchByDate($rec->valior);
				$row->baseCurrency = currency_Currencies::getCodeById($period->baseCurrencyId);
				$row->equals = $mvc->getFieldType('amount')->toVerbal($rec->amount * $rec->rate);
			} else {
	
				unset($row->rate);
			}
	
			$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->ownAccount);
			$row->accCurrency = currency_Currencies::getCodeById($ownAcc->currencyId);
	
			if($rec->contragentIban){
				$row->accCurrencyIban = $row->accCurrency;
			}
	
			$ownCompany = crm_Companies::fetchOwnCompany();
			$Companies = cls::get('crm_Companies');
			$row->companyName = cls::get('type_Varchar')->toVerbal($ownCompany->company);
			$row->companyAddress = $Companies->getFullAdress($ownCompany->companyId);
	
			$contragent = new core_ObjectReference($rec->contragentClassId, $rec->contragentId);
			$row->contragentAddress = $contragent->getFullAdress();
	
			$row->ownAccount = bank_OwnAccounts::getHyperlink($rec->ownAccount);
		}
	}
}