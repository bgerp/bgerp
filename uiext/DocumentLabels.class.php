<?php



/**
 * Клас 'uiext_DocumentLabels'
 *
 * Мениджър за тагове на документите
 *
 * @category  bgerp
 * @package   uiext
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class uiext_DocumentLabels extends core_Manager
{
	
	


	/**
	 * Заглавие
	 */
	public $title = 'Тагове на документи';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Таг на документ';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools2, plg_Created, uiext_Wrapper';
	
	
	/**
	 * Кой има право да гледа списъка?
	 */
	public $canList = 'debug';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'no_one';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'no_one';
	
	
	/**
	 * Кой може да го изтрие?
	 */
	public $canDelete = 'no_one';
	
	
	/**
	 * Кой може да избира етикет?
	 */
	public $canSelectlabel = 'powerUser';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'containerId,hash,labels';
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	public function description()
	{
		$this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Документ,mandatory');
		$this->FLD('hash', 'varchar(32)', 'caption=Хеш,mandatory');
		$this->FLD('labels', 'keylist(mvc=uiext_Labels,select=title)', 'caption=Тагове,mandatory');
		
		$this->setDbUnique('containerId,hash');
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'selectlabel' && isset($rec)){
			$document = doc_Containers::getDocument($rec->containerId);
			if(!$document->haveRightFor('single')){
				$requiredRoles = 'no_one';
			}
		}
	}
	
	
	function act_saveLabels()
	{
		//core_Request::setProtected('containerId,hash');
		$containerId = Request::get('containerId', 'int');
		$hash = Request::get('hash', 'varchar');
		$classId = Request::get('classId', 'int');
		
		if(!$containerId || !$hash || !$classId){
			core_Statuses::newStatus('|Невалиден ред|*!', 'error');
			return status_Messages::returnStatusesArray();
		}
		
		$delete = FALSE;
		$label = Request::get('label', 'int');
		if(!$label){
			$delete = TRUE;
		} else {
			if(!uiext_Labels::fetch($label)){
				core_Statuses::newStatus('|Няма такъв таг|*!', 'error');
				return status_Messages::returnStatusesArray();
			}
		}
		
		$rec = (object)array('containerId' => $containerId, 'hash' => $hash);
		$rec->labels = keylist::addKey('', $label);
		if($exRec = self::fetchByDoc($containerId, $hash)){
			$rec->id = $exRec->id;
		}
		
		if($delete === TRUE){
			self::delete($exRec->id);
			core_Statuses::newStatus('|Премахнат таг|*!', 'nitice');
		} else {
			$this->save($rec);
		}
		
		if(Request::get('ajax_mode')) {
				
			// Заместваме клетката по AJAX за да визуализираме промяната
			$resObj = new stdClass();
			$resObj->func = "html";
				
			$k = "{$containerId}|{$classId}|{$hash}";
			$resObj->arg = array('id' => "charge{$k}", 'html' => uiext_Labels::renderLabel($containerId, $classId, $hash), 'replace' => TRUE);
				
			$res = array_merge(array($resObj));
				
			return $res;
		}
		
		$document = doc_Containers::getDocument($containerId);
		
		redirect($document->getSingleUrlArray());
	}
	
	public static function fetchByDoc($containerId, $hash)
	{
		return self::fetch(array("#containerId = {$containerId} AND #hash = '[#1#]'", $hash));
	}
}