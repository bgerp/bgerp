<?php
class transsrv_TransportUnits extends core_Manager
{
    /**
     * Заглавие
     */
    var $title = 'Транспортни единици';


    /**
     * Заглавие
     */
    var $singleTitle = 'Транспортна единица';


    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'transsrv_Wrapper,plg_RowTools,plg_Created,plg_Modified';
    
    
    /**
     * Кой може да редактира
     */
    var $canEdit = 'transsrv,ceo,admin';


    /**
     * Никой не може да добавя директно през модела нови фирми
     */
    var $canAdd = 'transsrv,ceo,admin';
    

    /**
     * Кой може да разглежда
     */
    var $canList = 'transsrv,ceo,admin';


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
        $this->FLD('transModes', 'keylist(mvc=transsrv_TransportModes,select=name)', 'caption=Използване в транспорт->Вид');

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