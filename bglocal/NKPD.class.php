<?php 

/**
 * Типове договори
 *
 *
 * @category  bgerp
 * @package   bglocal
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bglocal_NKPD extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Национална класификация на професиите и длъжностите';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'НКПД';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, bglocal_Wrapper, plg_Printing,
                       plg_SaveAndNew';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin,hr';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'admin,hr';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('key', 'varchar', 'caption=Код');
        $this->FLD('number', 'varchar', 'caption=Номер');
        $this->FLD('title', 'text', 'caption=Наименование');
    }
    
    
    /**
     * Изпълнява се преид импортирването на запис
     */
    public static function on_BeforeImportRec($mvc, $rec)
    {
        $rec->key = $rec->key . $rec->number;
        $rec->title = $rec->key . ' ' . $rec->title;
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = 'bglocal/data/nkpd.csv';
        $fields = array(0 => 'key', 1 => 'number', 2 => 'title');
        $cntObj = csv_Lib::largeImportOnceFromZero($mvc, $file, $fields);
        $res .= $cntObj->html;
    }
    
    
    /**
     * Подготовка на опции за key2
     */
    public static function getSelectArr($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $query = self::getQuery();
        $query->orderBy('key', 'ASC');
        if (is_array($onlyIds)) {
            if (!countR($onlyIds)) {
                
                return array();
            }
            
            $ids = implode(',', $onlyIds);
            expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
            
            $query->where("#id IN (${ids})");
        } elseif (ctype_digit("{$onlyIds}")) {
            $query->where("#id = ${onlyIds}");
        }
        
        $titleFld = $params['titleFld'];
        
        $xpr = "CONCAT(' ', #{$titleFld}, ' ', #number)";
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
        
        $query->show('id,searchFieldXpr');
        
        $res = array();
        while ($rec = $query->fetch()) {
            $res[$rec->id] = trim($rec->searchFieldXpr);
        }
        
        return $res;
    }
}
