<?php



/**
 * Мениджър на ресурсите свързани с обекти
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_AssetResources extends core_Master
{
    
	
	/**
     * Заглавие
     */
    public $title = 'Оборудване';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, planning_Wrapper, plg_State2, plg_Search, plg_SaveAndNew';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,planning';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,planning';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,planning';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,planning';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,debug';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code,name,protocolId,departments,quantity=К-во,createdOn,createdBy,state';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'code';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Оборудване';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'protocolId';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'name,code';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('name', 'varchar', 'caption=Име,mandatory');
    	$this->FLD('code', 'varchar(16)', 'caption=Код,mandatory');
    	$this->FLD('protocolId', 'key(mvc=accda_Da,select=id)', 'caption=Протокол за пускане в експлоатация,silent,input=hidden');
    	$this->FLD('departments', 'keylist(mvc=hr_Departments,select=name,makeLinks)', 'caption=Структура');
    	$this->FLD('quantity', 'int', 'caption=Kоличество,notNull,value=1');
    	
    	$this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
    	
    	$this->setDbUnique('code');
    	$this->setDbUnique('protocolId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	if(isset($form->rec->protocolId)){
    		$daTitle = accda_Da::fetchField($form->rec->protocolId, 'title');
    		$form->setDefault('name', $daTitle);
    		$form->info = tr('От') . " " . accda_Da::getHyperLink($form->rec->protocolId, TRUE);
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if($rec->protocolId){
    		$row->protocolId = accda_Da::getHyperlink($rec->protocolId, TRUE);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec)){
    		if(isset($rec->protocolId)){
    			$state = accda_Da::fetchField($rec->protocolId, 'state');
    			if($state != 'active'){
    				$requiredRoles = 'no_one';
    			} else {
    				if($mvc->fetch("#protocolId = {$rec->protocolId}")){
    					$requiredRoles = 'no_one';
    				}
    			}
    		}
    	}
    	
    	if($action == 'delete' && isset($rec)){
    		if(isset($rec->lastUsedOn)){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->showFields = 'search';
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }
}