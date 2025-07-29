<?php


/**
 * Допълнителни филтри
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_Filters extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cond_Wrapper,plg_State2,plg_Created,plg_RowTools2';


    /**
     * Заглавие
     */
    public $title = 'Допълнителни филтри';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';


    /**
     * Кой може да пише?
     */
    public $canWrite = 'admin';


    /**
     * Кой може да пише?
     */
    public $canAdd = 'admin';


    /**
     * Кой може да пише?
     */
    public $canEdit = 'admin';


    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'admin';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име');
        $this->FLD('title', 'varchar(64)', 'caption=Заглавие');
        $this->FLD('group', 'varchar(64)', 'caption=Група,input=hidden');
        $this->FLD('classes', 'classes', 'caption=Класове');
        $this->FLD('packName', 'varchar(64)', 'caption=Зависим пакет');

        $this->FLD('regex', 'text(rows=3)', 'caption=Настройки за филтриране->Рег. израз');
        $this->FLD('regexField', 'varchar', 'caption=Настройки за филтриране->Поле');
        $this->FLD('orderBy', 'enum(ASC=Възходящ,DESC=Низходящ)', 'caption=Настройки за филтриране->Сортиране');

        $this->setDbUnique('name');
    }


    /**
     * Изпълнява се преди импортирването на данните
     */
    protected static function on_BeforeImportRec($mvc, &$rec)
    {
        if(!empty($rec->csv_classes)){
            // Ако има посочени класове - обръщат се в кейлист
            $classesArr =  array();
            $classes = explode(',', $rec->csv_classes);
            foreach ($classes as $csvClass){
                core_Classes::add($csvClass);
                $classId = cls::get($csvClass)->getClassId();
                $classesArr[$classId] = $classId;
            }
            $rec->classes = keylist::fromArray($classesArr);
        } else {
            $rec->classes = null;
        }
    }


    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    protected static function on_AfterSetupMvc($mvc, &$res)
    {
        // Подготвяме пътя до файла с данните
        $file = 'bgerp/data/csv/Filters.csv';

        // Кои колонки ще вкарваме
        $fields = array(
            0 => 'name',
            1 => 'title',
            2 => 'group',
            3 => 'csv_classes',
            4 => 'packName',
            5 => 'regex',
            6 => 'regexField',
            7 => 'orderBy',
        );

        // Импортираме данните от CSV файла.
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields, null, null);

        // Записваме в лога вербалното представяне на резултата от импортирането
        $res = $cntObj->html;

        return $res;
    }


    /**
     * Връща наличните за избор филтри в масив от опции за подадените класове
     *
     * @param mixed $classes - масив от класове, null за само тези без клас
     * @return array|mixed|object[]
     */
    public static function getArrOptions($classes = null)
    {
        $query = static::getQuery();
        $query->where("#state = 'active'");
        $query->where(bgerp_type_CustomFilter::getClassesWhereClause($classes));

        // Извличане на записите по-групи
        $grouped = $noGrouped = $options = array();
        while($rec = $query->fetch()){
            if(!empty($rec->packName)){
                if(!core_Packs::isInstalled($rec->packName)) continue;
            }
            if(!empty($rec->group)){
                $grouped[$rec->group][$rec->id] = tr(static::getRecTitle($rec, false));
            } else {
                $noGrouped[$rec->group][$rec->id] = tr(static::getRecTitle($rec, false));
            }
        }
        $grouped = $noGrouped + $grouped;
        foreach ($grouped as $groupName => $arr){
            if(!empty($groupName)){
                $options += array("_{$groupName}" => (object) array('group' => true, 'title' => tr($groupName)));
            }
            $options += $arr;
        }

        return $options;
    }
}