<?php



/**
 * Дефиниции на партиди за категориите, всички артикули в категорията
 * ако са складируеми ще им се форсира след създаването дефиниция за партида
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_CategoryDefinitions extends embed_Manager {
    
	
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'batch_BatchTypeIntf';
	
	
    /**
     * Заглавие
     */
    public $title = 'Партиди на категории';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Партидност към категория';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2,cat_Wrapper';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Категории';
    
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'categoryId, classId';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'batch, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'batch,ceo';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'batch,ceo';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('categoryId', 'key(mvc=cat_Categories, select=name)', 'caption=Категория,silent,mandatory,input=hidden');
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	$data->form->title = core_Detail::getEditTitle('cat_Categories', $rec->categoryId, $mvc->singleTitle, $rec->id, ' ');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec->categoryId)){
    		if($mvc->fetch("#categoryId = '{$rec->categoryId}'")){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Подготовка на наличните партиди за един артикул
     *
     * @param stdClass $data
     * @return void
     */
    public function prepareDefinitions(&$data)
    {
    	// Име на таба
    	$data->TabCaption = 'Партидност';
    	$data->Order = 20;
    	
    	$query = $this->getQuery();
    	$query->where("#categoryId = '{$data->masterId}'");
    	if($rec = $query->fetch()){
    		$data->rec = $rec;
    		$data->row = $this->recToVerbal($rec);
    	}
    	
    	if($this->haveRightFor('add', (object)array('categoryId' => $data->masterId))){
    		$data->addUrl = array($this, 'add', 'categoryId' => $data->masterId, 'ret_url' => TRUE);
    	}
    }
    
    
    /**
     * Рендиране на дефинициите за партида
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderDefinitions($data)
    {
    	$tpl = getTplFromFile('batch/tpl/CategoryDefinitionDetail.shtml');
        $title = tr('Партидност на артикулите');
    	$table = cls::get('core_TableView', array('mvc' => $this));
    	
    	if(is_object($data->row)){
    		$tpl->placeObject($data->row);
    	} else {
    		$tpl->replace(tr("Няма запис"), 'CONTENT');
    	}
    	
    	$tpl->append($title, 'title');
    	if(isset($data->addUrl)){
    		$addBtn = ht::createLink('', $data->addUrl, FALSE, 'ef_icon=img/16/add.png,select=Добавяне на нова дефиниция');
    		$tpl->append($addBtn, 'title');
    	}
    	$tpl->removeBlocks();
    	
    	return $tpl;
    }
}