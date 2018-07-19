<?php


/**
 * Клас 'cond_Colors' - Цветове
 *
 * Набор от стандартни цветове
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_Colors extends core_Manager
{
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да добавя
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'ceo,admin';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2,cond_Wrapper,plg_Created,plg_Sorting';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name,nameBG,hex,rgb,ral';
    
    
    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'no_one';
    
    
    /**
     * Заглавие
     */
    public $title = 'Цветове';
    
    
    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'Цвят';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar(32)', 'caption=Наименование->Международно,mandatory');
        $this->FLD('nameBG', 'varchar(32,nullIfEmpty)', 'caption=Наименование->Българско,mandatory');
        $this->FLD('hex', 'varchar(32)', 'caption=Кодове->Hex,mandatory');
        $this->FLD('rgb', 'varchar(32)', 'caption=Кодове->RGB');
        $this->FLD('ral', 'varchar(32)', 'caption=Кодове->RAL');
        
        $this->setDbUnique('name');
        $this->setDbUnique('nameBG');
    }
    
    
    /**
     * След като е готово вербалното представяне
     */
    public static function on_AfterGetVerbal($mvc, &$num, $rec, $part)
    {
        // Искаме състоянието на оттеглените чернови да се казва 'Анулиран'
        if ($part == 'name') {
            $num = '222';
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $file = 'cond/csv/Colors.csv';
        
        $fields = array(
            0 => 'ral',
            1 => 'rgb',
            2 => 'hex',
            3 => 'name',
            4 => 'csv_nameBG',
        );
        
        $cntObj = csv_Lib::importOnce($this, $file, $fields);
        $res = $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    protected static function on_BeforeImportRec($mvc, &$rec)
    {
        
        $rec->nameBG = !empty($rec->csv_nameBG) ? $rec->csv_nameBG : NULL;
    }
    
    
    /**
     * Подготовка на опции за key2
     */
    public static function getSelectArr($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        
        $query = self::getQuery();
        $query->orderBy('name', 'ASC');
        
        if (is_array($onlyIds)) {
            if (!count($onlyIds)) {
                
                return array();
            }
            
            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
            
            $query->where("#id IN (${ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $query->where("#id = ${onlyIds}");
        }
        
        $xpr = "CONCAT(' ', #name, ' ', #nameBG)";
        $query->XPR('searchFieldXpr', 'text', $xpr);
        $query->XPR('searchFieldXprLower', 'text', "LOWER({$xpr})");
       
        if ($q) {
            if ($q{0} == '"') {
                $strict = true;
            }
            $q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
            $q = mb_strtolower($q);
            
            if ($strict) {
                $qArr = array(str_replace(' ', '.*', $q));
            } else {
                $qArr = explode(' ', $q);
            }
            
            $pBegin = type_Key2::getRegexPatterForSQLBegin();
            foreach ($qArr as $w) {
                $query->where(array("#searchFieldXprLower REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
            }
        }
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $query->show('id,name,nameBG,rgb,hex,searchFieldXpr');
        $lg = core_Lg::getCurrent();
       
        $res = array();
        while ($rec = $query->fetch()) {
            $name = ($lg == 'bg' && !empty($rec->nameBG)) ? $rec->nameBG : $rec->name;
             
            $opt = new stdClass();
            $opt->attr = array('data-color' => $rec->hex);
            $opt->title = $name;
            
            $res[$rec->id] = $opt;
        }
        
        return $res;
    }
}