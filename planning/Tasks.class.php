<?php



/**
 * Мениджър на задачи за производство
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заявки за покупки
 */
class planning_Tasks extends core_Manager
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'mp_Tasks';
	
	
    /**
     * Заглавие
     */
    var $title = 'Производствени задачи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, planning_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,planning';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,planning';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,planning';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,planning';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,planning';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,planning';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    }
    
    
    /**
     * Екшън по подразбиране.
     * Извежда картинка, че страницата е в процес на разработка
     */
    function act_Default()
    {
        requireRole('planning, admin');
        
    	$text = tr('В процес на разработка');
    	$underConstructionImg = "<h2>$text</h2><img src=". sbf('img/under_construction.png') .">";

        return $this->renderWrapping($underConstructionImg);
    }
    
    
    /**
     * Подготвя задачие към заданията
     */
    public function prepareTasks($data)
    {
    	//@TODO
    }
    
    
    /**
     * Рендира задачите на заданията
     */
    public function renderTasks($data)
    {
    	//@TODO
    }
}