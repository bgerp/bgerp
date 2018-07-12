<?php


/**
 * Клас 'trans_TransportModes'
 *
 * Документ за Видове транспорт
 *
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class trans_TransportModes extends core_Manager
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'transsrv_TransportModes';
    
    
    /**
     * Заглавие
     */
    public $title = 'Видове транспорт';
    
    
    /**
     * Заглавие
     */
    public $singleTitle = 'Транспортен вид';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'trans_Wrapper,plg_RowTools2,plg_Created,plg_Modified';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'trans,ceo';
    
    
    /**
     * Кой може да добавя транспортни единици
     */
    public $canAdd = 'trans,ceo';
    
    
    /**
     * Кой може да изтрива транспортни единици
     */
    public $canDelete = 'trans,ceo';
    
    
    /**
     * Кой може да разглежда
     */
    public $canList = 'trans,ceo';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(24)', 'caption=Наименование');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Динамично изчисляване на необходимите роли за дадения потребител, за извършване на определено действие към даден запис
     */
    public static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec = null, $userId = null)
    {
        if (isset($rec) && is_int($rec)) {
            $rec = $mvc->fetch($rec);
        } elseif (isset($rec->id)) {
            $rec = $mvc->fetch($rec->id);
        }
        
        if (($action == 'delete' || $action == 'edit') && $rec->id) {
            if ($rec->createdBy != core_Users::getCurrent()) {
                $roles = 'ceo';
            }
        }
    }
}
