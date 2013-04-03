<?php



/**
 * Мениджър на групи с продукти.
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Groups extends groups_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Групи на продуктите";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cat_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,systemId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,acc,broker';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,acc,broker';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'admin,acc';


    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('sysId', 'varchar(32)', 'caption=System Id,oldFieldName=systemId,input=none,column=none');
        $this->FLD('info', 'text', 'caption=Информация');
        $this->FLD('productCnt', 'int', 'input=none');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterPrepareListRows($mvc, $data)
    {
        if (count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
                $rec = $data->recs[$i];
                $row->productCnt = intval($rec->productCnt);
                $row->name = $mvc->getVerbal($rec, 'name');
                $row->name .= " ({$row->productCnt})";
                $row->name .= "<div><small>" . $mvc->getVerbal($rec, 'info') . "</small></div>";
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {   
        // Ако групата е системна или в нея има нещо записано - не позволяваме да я изтриваме
        if(($rec->sysId || $rec->productCnt) && $action == 'delete') {
            $requiredRoles = 'no_one';
        }
    }

    
    /**
     * @todo Чака за документация...
     */
    static function updateProductCnt($id)
    {
        $query = cat_Products::getQuery();
        $productCnt = $query->count("#groups LIKE '%|{$id}|%'");
        
        return static::save((object)compact('id', 'productCnt'));
    }
}