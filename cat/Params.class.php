<?php



/**
 * Мениджира динамичните параметри на категориите
 *
 * Всяка категория (@see cat_Categories) има нула или повече динамични параметри. Те са всъщност
 * параметрите на продуктите (@see cat_Products), които принадлежат на категорията.
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продуктови параметри
 */
class cat_Params extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = "Параметри";
    
    
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
    var $listFields = 'id,typeExt';
    
    
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
     * Кой има право да го изтрие?
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('type', 'enum(double=Число, int=Цяло число, varchar=Текст, color=Цвят, date=Дата)', 'caption=Тип');
        $this->FLD('suffix', 'varchar(64)', 'caption=Суфикс');
        
        $this->FNC('typeExt', 'varchar', 'caption=Име');
        
        $this->setDbUnique('name, suffix');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_CalcTypeExt($mvc, $rec)
    {
        $rec->typeExt = $rec->name;
        
        if (!empty($rec->suffix)) {
            $rec->typeExt .= ' [' . $rec->suffix . ']';
        }
    }
    
    
    /**
     * Създава input поле или комбо-бокс
     */
    static function createInput($rec, $form)
    {
        $name = "value_{$rec->id}";
        $caption = static::getVerbal($rec, 'name');
        
        $type = $rec->type;
        
        switch ($type) {
            case 'color' :
                $type = 'varchar';
                break;
        }
        
        $suffix = static::getVerbal($rec, 'suffix');
        
        $caption = str_replace('=', '&#61;', $caption);
        $suffix = str_replace('=', '&#61;', $suffix);
        
        $form->FLD($name, $type, "input,caption={$caption},unit={$suffix}");
    }
    
    
    /**
     * Зареждане на първоначални данни
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $initData = array(
            array(
                'name' => 'Дължина',
                'type' => 'double',
                'suffix' => 'см',
            ),
            array(
                'name' => 'Височина',
                'type' => 'double',
                'suffix' => 'см',
            ),
            array(
                'name' => 'Тегло',
                'type' => 'double',
                'suffix' => 'гр',
            ),
            array(
                'name' => 'Тегло',
                'type' => 'double',
                'suffix' => 'кг',
            ),
            array(
                'name' => 'Цвят',
                'type' => 'varchar',
                'suffix' => '',
            ),
        );
        
        foreach ($initData as $rec) {
            $rec = (object)$rec;
            $rec->id = $mvc->fetchField("#name = '{$rec->name}' AND #suffix = '{$rec->suffix}'", 'id');
            $isUpdate = !empty($rec->id);
            
            if ($mvc->save($rec)) {
                $res .= "<li>" . ($isUpdate ? 'Обновен' : 'Добавен') . " параметър {$rec->name} [{$rec->suffix}]</li>";
            } else {
                $res .= "<li class=\"error\">Проблем при" . ($isUpdate ? 'обновяване' : 'добавяне') . " на параметър {$rec->name} [{$rec->suffix}]</li>";
            }
        }
    }
}