<?php



/**
 * Мениджър на опаковки
 *
 * Всяка категория (@see cat_Categories) има нула или повече опаковки. Това са опаковките, в
 * които могат да бъдат пакетирани продуктите (@see cat_Products), принадлежащи на категорията.
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Опаковки
 */
class cat_Packagings extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Опаковки";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, cat_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,contentPlastic,contentPaper,contentGlass,contentMetals,contentWood';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
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
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(32)', 'caption=Име, mandatory');
        $this->FLD('contentPlastic', 'percent', 'caption=Полимер');
        $this->FLD('contentPaper', 'percent', 'caption=Хартия');
        $this->FLD('contentGlass', 'percent', 'caption=Стъкло');
        $this->FLD('contentMetals', 'percent', 'caption=Метали');
        $this->FLD('contentWood', 'percent', 'caption=Дървесина');
    }
    
    
    /**
     * Създава input поле или комбо-бокс
     */
    static function createInput($rec, $form)
    {
        $name = "packvalue_{$rec->id}";
        $caption = "Опаковки->{$rec->name}";
        $type = 'varchar(255)';
        
        $form->FLD($name, $type, "input,caption={$caption}");
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        $initData = array(
            array(
                'name' => 'Пакет',
            ),
            array(
                'name' => 'Ролка',
            ),
            array(
                'name' => 'Кашон',
            ),
            array(
                'name' => 'Чувал',
            ),
            array(
                'name' => 'Варел',
            ),
            array(
                'name' => 'Палет',
            ),
        );
        
        foreach ($initData as $rec) {
            $rec = (object)$rec;
            $rec->id = $mvc->fetchField("#name = '{$rec->name}'", 'id');
            $isUpdate = !empty($rec->id);
            
            if ($mvc->save($rec)) {
                $res .= "<li>" . ($isUpdate ? 'Обновена' : 'Добавена') . " опаковка {$rec->name}</li>";
            } else {
                $res .= "<li class=\"error\">Проблем при" . ($isUpdate ? 'обновяване' : 'добавяне') . " на опаковка {$rec->name}</li>";
            }
        }
    }
}