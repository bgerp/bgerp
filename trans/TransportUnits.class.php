<?php



/**
 * Клас 'trans_TransportUnits'
 *
 * Документ за Логистични единици
 *
 *
 * @category  bgerp
 * @package   trans
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class trans_TransportUnits extends core_Manager
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'transsrv_TransportUnits';
	
	
    /**
     * Заглавие
     */
    public $title = 'Логистични единици';


    /**
     * Заглавие
     */
    public $singleTitle = 'Логистична единица';


    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'trans_Wrapper,plg_RowTools2,plg_Created,plg_Modified';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'trans,ceo';


    /**
     * Никой не може да добавя директно през модела нови фирми
     */
    public $canAdd = 'trans,ceo';
    

    /**
     * Кой може да разглежда
     */
    public $canList = 'trans,ceo';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(24)', 'caption=Наименование->Единично,mandatory');
        $this->FLD('pluralName', 'varchar(24)', 'caption=Наименование->Множествено,mandatory');
        $this->FLD('abbr', 'varchar(10)', 'caption=Наименование->Съкращение,mandatory');
        $this->FLD('maxWeight', 'cat_type_Uom(unit=t,Min=0)', 'caption=Възможности->Макс. тегло');
        $this->FLD('maxVolume', 'cat_type_Uom(unit=cub.m,Min=0)', 'caption=Възможности->Макс. обем');
        
        // Видове транспорт
        $this->FLD('transModes', 'keylist(mvc=trans_TransportModes,select=name)', 'caption=Използване в транспорт->Вид');

        $this->setDbUnique('name');
    }
    
    
    /**
     * Динамично изчисляване на необходимите роли за дадения потребител, за извършване на определено действие към даден запис
     */
    public static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec = NULL, $userId = NULL)
    {
        if(isset($rec) && is_int($rec)) {
            $rec = $mvc->fetch($rec);
        }

        if(($action == 'delete' || $action == 'edit') && $rec->createdBy) {
            if($rec->createdBy != core_Users::getCurrent()) {
                $roles = 'ceo';
            }
        }
    }
}