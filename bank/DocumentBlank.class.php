<?php



/**
 * Документ за наследяване от банковите бланки (Платежно нареждане, Вносна бележка и Нареждане разписка)
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class bank_DocumentBlank extends core_Master
{

	
	/**
	 * Какви интерфейси поддържа този мениджър
	 */
	public $interfaces = 'doc_DocumentIntf, email_DocumentIntf';
	
	
	/**
	 * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
	 */
	public $rowToolsSingleField = 'reason';
	
	
	/**
	 * Кой има право да разглежда документа?
	 */
	public $canSingle = 'bank, ceo';
	
	
	/**
	 * Кой може да пише?
	 */
	public $canWrite = 'bank, ceo';
	
	
	/**
	 * Кой може да създава
	 */
	public $canAdd = 'bank, ceo';
	
	
	/**
	 * Кой има право да редактира?
	 */
	public $canEdit = 'bank, ceo';
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'add' && isset($rec)){
			if(empty($rec->originId)){
				$requiredRoles = 'no_one';
			} else {
				$origin = doc_Containers::getDocument($rec->originId);
				if(!$origin->isInstanceOf('bank_IncomeDocuments') && !$origin->isInstanceOf('bank_SpendingDocuments')){
					$requiredRoles = 'no_one';
				} else {
					$originRec = $origin->fetch();
					if($originRec->state != 'draft'){
						$requiredRoles = 'no_one';
					}
				}
			}
		}
	}
	
	
	/**
	 * След рендиране на единичния изглед
	 */
	protected static function on_AfterRenderSingleLayout($mvc, $tpl, $data)
	{
		if(Mode::is('printing') || Mode::is('text', 'xhtml')){
			$tpl->removeBlock('header');
		}
	}
	

	/**
	 * Извиква се след подготовката на toolbar-а за табличния изглед
	 */
	protected static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		$data->toolbar->removeBtn('btnAdd');
	}
	
	
	/**
	 * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
	 */
	public function getDocumentRow($id)
	{
		$rec = $this->fetch($id);
		$row = new stdClass();
		$row->title = $rec->reason;
		$row->authorId = $rec->createdBy;
		$row->author = $this->getVerbal($rec, 'createdBy');
		$row->state = $rec->state;
		$row->recTitle = $rec->reason;
	
		return $row;
	}
	
	
	/**
	 * Вкарваме css файл за единичния изглед
	 */
	protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
	{
		$tpl->push('bank/tpl/css/belejka.css', 'CSS');
	}
}