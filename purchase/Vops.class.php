<?php



/**
 * Модел за протоколи за ВОП
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_Vops extends core_Manager
{
	
	
	/**
	 * Заглавие
	 */
	public $title = "Протоколи за вътреобщностно придобиване";
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = "purchase_Wrapper,plg_Created,plg_Search,plg_Printing, plg_RowTools2";
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'purchase,ceo';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'purchase,ceo';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'purchase,ceo';
	
	
	/**
	 * Кой може да го изтрие?
	 */
	public $canDelete = 'purchase,ceo';
	
	
	/**
	 * Полета, които се виждат
	 */
	public $listFields  = "invoiceId,vodNumber,vodDate,createdOn,createdBy,printBtn=|*&nbsp;";
	
	
	/**
	 * Дали в листовия изглед да се показва бутона за добавяне
	 */
	public $listAddBtn = FALSE;
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'vodNumber,invoiceId';
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	public function description()
	{
		$this->FLD('vodNumber', 'int', 'caption=Номер,mandatory');
		$this->FLD('vodDate', 'date', 'caption=Дата,mandatory');
		$this->FLD('invoiceId', 'key(mvc=purchase_Invoices,select=id)', 'caption=Вх. фактура,silent,input=hidden,tdClass=rightCol');
		
		$this->setDbUnique('invoiceId');
		$this->setDbUnique('vodNumber');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		
		$query = $mvc->getQuery();
		$query->XPR('max', 'int', 'MAX(#vodNumber)');
		$max = $query->fetch()->max + 1;
		$form->setDefault('vodNumber', $max);
	}
	
	
	/**
	 * След подготовката на заглавието на формата
	 */
	protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
	{
		// По-хубаво заглавие на формата
		$rec = $data->form->rec;
		if(isset($rec->invoiceId)){
			$data->form->title = core_Detail::getEditTitle('purchase_Invoices', $rec->invoiceId, 'протокол за ВОП', $rec->id);
		}
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->invoiceId = purchase_Invoices::getLink($rec->invoiceId, 0);
		$row->printBtn = ht::createBtn('Разпечатване', array($mvc, 'print', $rec->id, 'Printing' => 'yes'), FALSE, TRUE, 'ef_icon=img/16/printer.png,Разпечатване на протокол за ВОП');
		$row->vodNumber = str_pad($row->vodNumber, 10, 0, STR_PAD_LEFT);
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'add' && isset($rec)){
			if(empty($rec->invoiceId)){
				$requiredRoles = 'no_one';
			} elseif(self::fetch("#invoiceId = {$rec->invoiceId}")){
				$requiredRoles = 'no_one';
			} else {
				$iRec = purchase_Invoices::fetch($rec->invoiceId);
				if($iRec->state != 'active'){
					$requiredRoles = 'no_one';
				}
			}
		}
	}
	
	
	/**
	 * Преди рендиране на таблицата
	 */
	protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
	{
		$data->listTableMvc->FNC('printBtn', 'int', 'tdClass=rightCol');
	}
	
	
	/**
	 * Екшън за принтиране
	 */
	function act_Print()
	{
		// Проверка и извличане на данни
		$this->requireRightFor('print');
		expect($id = Request::get('id', 'int'));
		expect($rec = $this->fetch($id));
		$this->requireRightFor('print', $rec);
		$invoiceRec = purchase_Invoices::fetch($rec->invoiceId);
		$row = self::recToVerbal($rec);
		
		// Подготовка на данните на фактурата
		Mode::push('text', 'xhtml');
		Mode::push('inlineDocument', TRUE);
		$sudoUser = core_Users::sudo($invoiceRec->createdBy);
		$Invoices = cls::get('purchase_Invoices');
		$data = $Invoices->prepareDocument($invoiceRec->id);
		$data->singleLayout = getTplFromFile('purchase/tpl/VatProtocol.shtml');
		
		// Добавяне на допълнителните полета
		$data->row->vodDate = $row->vodDate;
		$data->row->vodNumber = $row->vodNumber;
		
		// Рендиране на вх. ф-ра в шаблона за ВОП-а
		Mode::push("singleLayout-purchase_Invoices{$invoiceRec->id}", getTplFromFile('purchase/tpl/VatProtocol.shtml'));
		$tpl = $Invoices->renderDocument($invoiceRec->id, $data);
		Mode::pop("singleLayout-purchase_Invoices{$invoiceRec->id}");
		
		core_Users::exitSudo($sudoUser);
		Mode::pop('inlineDocument');
		Mode::pop('text');
		
		// Връщане на шаблона
		return $tpl;
	}
	
	
	/**
	 * Изпълнява се след подготвянето на формата за филтриране
	 */
	protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
	{
		$data->listFilter->showFields = 'search';
		$data->listFilter->view = 'horizontal';
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
	}
	
	
	/**
	 * Пренасочва URL за връщане след запис към сингъл изгледа
	 */
	protected static function on_AfterPrepareRetUrl($mvc, $res, $data)
	{
		// Ако има форма, и тя е събмитната и действието е 'запис'
		if ($data->form->rec->id && $data->form->isSubmitted() && $data->form->cmd == 'save') {
			$data->retUrl = toUrl(array($mvc, 'print', $data->form->rec->id, 'Printing' => 'yes'));
		}
	}
}