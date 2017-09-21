<?php



/**
 * Базов документ за наследяване на платежни документи
 * 
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class deals_PaymentDocument extends core_Master {
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Master &$mvc)
	{
		$mvc->FLD('fromContainerId', 'int', 'caption=От фактура,input=hidden,silent');
	}
	
	
	/**
	 * Функция, която се извиква след активирането на документа
	 */
	public static function on_AfterActivation($mvc, &$rec)
	{
		// Обновяваме автоматично изчисления метод на плащане на всички фактури в нишката на документа
		$threadId = ($rec->threadId) ? $rec->threadId : $mvc->fetchField($rec->id, 'threadId');
		sales_Invoices::updateAutoPaymentTypeInThread($threadId);
	}
	
	
	/**
	 * След оттегляне на документа
	 */
	public static function on_AfterReject(core_Mvc $mvc, &$res, $rec)
	{
		$id = (is_object($rec)) ? $rec->id : $rec;
		if($rec->brState == 'active'){
			
			// Обновяваме автоматично изчисления метод на плащане на всички фактури в нишката на документа
			$threadId = ($rec->threadId) ? $rec->threadId : $mvc->fetchField($id, 'threadId');
			sales_Invoices::updateAutoPaymentTypeInThread($threadId);
		}
	}
	
	
	/**
	 *  Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, $data)
	{
		if(!Request::get('Rejected', 'int')){
			$data->listFilter->FNC('dState', 'enum(all=Всички, pending=Заявка, draft=Чернова, active=Контиран)', 'caption=Състояние,input,silent');
			$data->listFilter->showFields .= ',dState';
			$data->listFilter->input();
			$data->listFilter->setDefault('dState', 'all');
		}
			
		if($rec = $data->listFilter->rec){
			if($rec->dState){
				if($rec->dState != 'all'){
					$data->query->where("#state = '{$rec->dState}'");
				}
			}
		}
	}
	
	
	/**
	 *  Обработки по вербалното представяне на данните
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->valior = (isset($rec->valior)) ? $row->valior : ht::createHint('', 'Вальора ще бъде датата на контиране');
		
		if($rec->fromContainerId){
			$Document = doc_Containers::getDocument($rec->fromContainerId);
			$number = str_pad($Document->fetchField('number'), '10', '0', STR_PAD_LEFT);
			$row->fromContainerId = "#{$Document->abbr}{$number}";
		}
		
		if(!Mode::isReadOnly()){
			if($mvc->haveRightFor('selectinvoice', $rec)){
				$row->fromContainerId = $row->fromContainerId . ht::createLink('', array($mvc, 'selectInvoice', $rec->id, 'ret_url' => TRUE), FALSE, 'title=Смяна на фактурата към която е документа,ef_icon=img/16/edit.png');
			}
		}
	}
	
	
	/**
	 * Екшън за избор на налични фактури
	 */
	function act_selectinvoice()
	{
		$this->requireRightFor('selectinvoice');
		expect($id = Request::get('id', 'int'));
		expect($rec = $this->fetch($id));
		$this->requireRightFor('selectinvoice', $rec);
		
		$form = cls::get('core_Form');
		$form->title = "Избор на фактура по която е|* <b>" . $this->getHyperlink($rec);
		$form->FLD('fromContainerId', 'int', 'caption=За фактура');
		
		$invoices = deals_Helper::getInvoicesInThread($rec->threadId);
		$form->setOptions('fromContainerId', array('' => '') + $invoices);
		$form->setDefault('fromContainerId', $rec->fromContainerId);
		
		$form->input();
		if($form->isSubmitted()){
			$rec->fromContainerId = $form->rec->fromContainerId;
			$rec->modifiedOn = dt::now();
			$rec->modifiedBy = core_Users::getCurrent();
			$this->save_($rec, 'fromContainerId,modifiedOn,modifiedBy');
			
			followRetUrl(NULL, 'Промяната е записана успешно');
		}
	
		// Добавяне на тулбар
    	$form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Импорт');
    	$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
    
    	// Рендиране на опаковката
    	$tpl = $this->renderWrapping($form->renderHtml());

		$formId = $form->formAttr['id'] ;
		jquery_Jquery::run($tpl, "preventDoubleSubmission('{$formId}');");

		
    	return $tpl;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
	 *
	 * @param core_Mvc $mvc
	 * @param string $requiredRoles
	 * @param string $action
	 * @param stdClass $rec
	 * @param int $userId
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'selectinvoice' && isset($rec)){
			if($rec->state == 'rejected' || !deals_Helper::getInvoicesInThread($rec->threadId, TRUE)){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	/**
	 * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
	 */
	public function getDocumentRow($id)
	{
		$rec = $this->fetch($id);
		$row = new stdClass();
		$row->title = $this->getRecTitle($rec);
		$row->authorId = $rec->createdBy;
		$row->author = $this->getVerbal($rec, 'createdBy');
		$row->state = $rec->state;
	
		$recTitle = $rec->amount . " " . currency_Currencies::getCodeById($rec->currencyId);
		$date = ($rec->valior) ? $rec->valior : (isset($rec->termDate) ? $rec->termDate : NULL);
		if(isset($date)){
			$recTitle .= " / " . dt::mysql2verbal($date, 'd.m.y');
		}
	
		$row->recTitle = $recTitle;
	
		return $row;
	}
}