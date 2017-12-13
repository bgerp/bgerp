<?php



/**
 * Мениджър на папките, които може да ползват ресурси
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_FoldersWithResources extends core_Manager
{
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Допълнителни папки с ресурси';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'ceo, planningMaster';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'ceo, planningMaster';
	
	
	/**
	 * Кой може да го изтрие?
	 */
	public $canDelete = 'ceo, planningMaster';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, planning';
	
	
	/**
	 * Заглавие в единствено число
	 */
	public $singleTitle = 'Папка';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'plg_RowTools2, plg_Modified, planning_Wrapper';
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'folderId,type,modifiedOn,modifiedBy';
	
	
	/**
	 * Описание на модела (таблицата)
	 */
	public function description()
	{
		$this->FLD('folderId', 'key2(mvc=doc_Folders,select=title,coverClasses=doc_UnsortedFolders,allowEmpty)', 'caption=Папка, mandatory');
		$this->FLD('type', 'set(assets=Оборудване,hr=Служители)', 'caption=Ресурси, mandatory,');
		
		$this->setDbUnique('folderId');
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид
	 */
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->ROW_ATTR['class'] = "state-active";
		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'delete' && isset($rec->folderId)){
			
			// Ако има навързано оборудване и служители не се изтрива
			if(planning_AssetResources::fetchField("LOCATE('|{$rec->folderId}|', #folders)") || planning_Hr::fetchField("LOCATE('|{$rec->folderId}|', #folders)")){
				$res = 'no_one';
			}
		}
	}
}