<?php
class transsrv_TransportModes extends core_Manager
{
    /**
     * Заглавие
     */
    var $title = 'Видове транспорт';


    /**
     * Заглавие
     */
    var $singleTitle = 'Транспортен вид';


    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'transsrv_Wrapper,plg_RowTools,plg_Created,plg_Modified';
    
    
    /**
     * Кой може да редактира
     */
    var $canEdit = 'transsrv,ceo';


    /**
     * Кой може да добавя транспортни единици
     */
    var $canAdd = 'transsrv,ceo';
    
    
    /**
     * Кой може да изтрива транспортни единици
     */
    var $canDelete = 'transsrv,ceo';
    
    
    /**
     * Кой може да разглежда
     */
    var $canList = 'transsrv,ceo';

    
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
    static function on_AfterGetRequiredRoles($mvc, &$roles, $action, $rec = NULL, $userId = NULL)
    {
        if(isset($rec) && is_int($rec)) {
            $rec = $mvc->fetch($rec);
        } elseif(isset($rec->id)) {
            $rec = $mvc->fetch($rec->id);
        }

        if(($action == 'delete' || $action == 'edit') && $rec->id) {  
            if($rec->createdBy != core_Users::getCurrent()) { 
                $roles = 'ceo'; 
            }
        }

    }
    

}