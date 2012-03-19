<?php



/**
 * Мениджира Категориите от продукти
 *
 * Всеки продукт (@see cat_Products) принадлежи на точно една категория. Категорията определя
 * атрибутите на продуктите, които са в нея.
 *
 *
 * @category  all
 * @package   cat
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продуктови категории
 */
class cat_Categories extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Категории";
    
    
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
    var $listFields = 'id,name,params,packagings,state';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'cat_Products';
    
    
    /**
     * Кой има право да чете?
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
     * Кой може да го разглежда?
     */
    var $canList = 'admin,acc,broker';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('info', 'text', 'caption=Информация');
        $this->FLD('params', 'keylist(mvc=cat_Params,select=name)', 'caption=Параметри');
        $this->FLD('packagings', 'keylist(mvc=cat_Packagings,select=name)', 'caption=Опаковки');
        $this->FLD('productCnt', 'int', 'input=none,caption=Продукти');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    function on_AfterPrepareListRows($mvc, $data)
    {
        if (count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
                $rec = $data->recs[$i];
                $row->productCnt = intval($rec->productCnt);
                $row->name = $mvc->getVerbal($rec, 'name');
                $row->name .= " ({$row->productCnt})";
                $row->name = ht::createLink($row->name, array('cat_Products', 'list', 'categoryId' => $rec->id));
                $row->name .= "<div><small>" . $mvc->getVerbal($rec, 'info') . "</small></div>";
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function &getParamsForm($id, &$form = NULL)
    {
        $rec = self::fetch($id);
        $paramIds = type_Keylist::toArray($rec->params);
        
        sort($paramIds);    // за да бъде подредбата предсказуема и новите парам. да са най-отдолу.
        if (!isset($form)) {
            $form = cls::get('core_Form');
        }
        
        foreach ($paramIds as $paramId) {
            $rec = cat_Params::fetch($paramId);
            cat_Params::createInput($rec, $form);
        }
        
        return $form;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function &getPackagingsForm($id, &$form = NULL)
    {
        $rec = self::fetch($id);
        $packIds = type_Keylist::toArray($rec->packagings);
        
        sort($packIds);
        
        if (!isset($form)) {
            $form = cls::get('core_Form');
        }
        
        foreach ($packIds as $packId) {
            $rec = cat_Packagings::fetch($packId);
            cat_products_Packagings::createInputs($rec, $form);
        }
        
        return $form;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function updateProductCnt($id)
    {
        $query = cat_Products::getQuery();
        $productCnt = $query->count("#categoryId = {$id}");
        
        return static::save((object)compact('id', 'productCnt'));
    }
}