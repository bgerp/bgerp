<?php 

/**
 * Комбинирана номеклатура KH8 - 2025
 *
 *
 * @category  bgerp
 * @package   bglocal
 *
 * @author    Gabriela Petrova <gpetrova@experta.bg>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bglocal_HScode extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Комбинирана митническа номенклатура';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'HS';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, bglocal_Wrapper, plg_Printing,plg_Search,
                       plg_SaveAndNew';
    
    
    /**
     *  Полета по които ще се търси
     */
    public $searchFields = 'title,cnCode,headingName,chapterName,sectionName';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin,hr';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'admin,hr';
   
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin';
    
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('sectionId', 'varchar(4)', 'caption=Раздел->код');
        $this->FLD('sectionName', 'text', 'caption=Раздел->име');
        $this->FLD('chapterId', 'varchar(2)', 'caption=Глава->код');
        $this->FLD('chapterName', 'text', 'caption=Глава->име');
        $this->FLD('headingId', 'varchar(4)', 'caption=Подглава->код');
        $this->FLD('headingName', 'text', 'caption=Подглава->име');
        $this->FLD('level', 'int(4)', 'caption=Степен->Степен на подробност на съответния код на продукта');
        $this->FLD('cnCode', 'varchar(8)', 'caption=Стока->Код по КН');
        $this->FLD('title', 'text', 'caption=Стока->Описание на стоката');
        $this->FLD('name', 'text', 'input=none');
        
        $this->setDbIndex('cnCode');
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        $form->view = 'horizontal';
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $form->showFields = 'search';
        
        $form->input();
        
    }
    
    
    /**
     * Изпълнява се преид импортирването на запис
     */
    public static function on_AfterImportRec($mvc, $rec)
    {
        $rec->name = $rec->cnCode . ' ' . $rec->title;
    }
    

    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $file = 'bglocal/data/HScode.csv';
        $fields = array(0 => 'sectionId', 1 => 'sectionName',2 => 'chapterId', 3 => 'chapterName',
                        4 => 'headingId', 5 => 'headingName',6 => 'level', 7 => 'cnCode',8 => 'title');
        $cntObj = csv_Lib::largeImportOnceFromZero($mvc, $file, $fields);
        $res .= $cntObj->html;
    }
    
    
    /**
     * Подготовка на опции за key2
     */
    public static function getSelectArr($params, $limit = null, $q = '', $onlyIds = null, $includeHiddens = false)
    {
        $mvc = cls::get(get_called_class());
        $res = [];
        
        $query = $mvc->getQuery();
        $query->orderBy('id');
        
        // ===============================
        //  ТЪРСЕНЕ
        // ===============================
        if ($q) {
            $qEsc = str_replace("'", "''", $q);
            
            if (ctype_digit($qEsc)) {
                // търсене по код
                $query->where("#cnCode LIKE '{$qEsc}%'");
            } else {
                // търсене по текст
                $query->where("#title LIKE '%{$qEsc}%'");
            }
        }
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $rows = [];
        while ($rec = $query->fetch()) {
           
            $rows[] = $rec;
        }
        
        if (!$rows) {
            return $res;
        }
        
        // ===============================
        // Събиране на нужните Section / Chapter
        // ===============================
        $sections = [];
        $chapters = [];
        
        foreach ($rows as $rec) {
            $sections[$rec->sectionId] = $rec->sectionName;
            $chapters[$rec->sectionId . '_' . $rec->chapterId] = $rec->chapterName;
        }
        
        // ===============================
        // Рендер на hierarchy
        // ===============================
        foreach ($sections as $sectionId => $sectionName) {
            
            $res['s' . $sectionId] = (object)[
                'title' => $sectionName,
                'noSelect' => true
            ];
            
            foreach ($chapters as $ckey => $chapterName) {
                
                list($sId, $chId) = explode('_', $ckey);
                
                if ($sId != $sectionId) continue;
                
                $res['c' . $ckey] = (object)[
                    'title' => $chapterName,
                    'noSelect' => true
                ];
                
                foreach ($rows as $rec) {
                    
                    if ($rec->sectionId == $sId && $rec->chapterId == $chId) {
                        
                        $code = $rec->cnCode ?? '';
                       
                        $res[$rec->id] = $code
                        ? "{$code} {$rec->title}"
                        : $rec->title;
                    }
                }
            }
        }
        
      
        
        return $res;
    }
    


    /**
     * Връща HS кода, който най-точно съвпада с подадения префикс:
     * - кодът трябва да започва с $input
     * - избира се този с най-малко допълнителни цифри
     * - при равни дължини: най-близкият числово до $input, паднат с нули до дължината на кода
     *
     * @param string $input Подаден стринг/префикс (може да съдържа и други символи)
     * @param array  $codes Масив от HS кодове (стрингове)
     * @return string|null  Намереният код (почистен като оригиналния е trim-нат) или null ако няма съвпадение
     */
    public static function findBestHsCode(string $input, array $codes = array(), int $minLen = 8)
    {
        // Нормализираме входа до цифри
        $needle = preg_replace('/\D+/', '', $input);
        if ($needle === '') return null;

        // Ако не са подадени - зареждаме всички кодове
        if (empty($codes)) {
            $hsQuery = bglocal_HScode::getQuery();
            $hsQuery->show('id,cnCode');
            while ($hsRec = $hsQuery->fetch()) {
                $codes[$hsRec->id] = $hsRec->cnCode;
            }
        }

        // Нормализираме кодовете и правим индекс: normCode => оригинален код
        $map = array();
        foreach ($codes as $code) {
            $orig = trim((string)$code);
            $norm = preg_replace('/\D+/', '', $orig);
            if ($norm === '') continue;

            // Ако има дубликати след нормализация - пазим първия
            if (!isset($map[$norm])) {
                $map[$norm] = $orig;
            }
        }

        $len = strlen($needle);

        // Ако входът е >= 8 цифри: режем отдясно и търсим ТОЧНО съвпадение
        if ($len >= $minLen) {
            for ($tryLen = $len; $tryLen >= $minLen; $tryLen--) {
                $candidate = substr($needle, 0, $tryLen);
                if (isset($map[$candidate])) {
                    return $map[$candidate];
                }
            }

            return null;
        }

        // Ако входът е < 8 цифри: няма как да "режем до 8".
        // По избор: връщаме най-късия код, който започва с входа (практично за частично въведени кодове).
        $bestOrig = null;
        $bestExtra = null;
        $bestNorm = null;

        foreach ($map as $norm => $orig) {
            if (strpos($norm, $needle) !== 0) continue;

            $extra = strlen($norm) - $len; // колко цифри добавя кодът след входа
            if ($bestOrig === null || $extra < $bestExtra || ($extra === $bestExtra && strcmp($norm, $bestNorm) < 0)) {
                $bestOrig = $orig;
                $bestExtra = $extra;
                $bestNorm = $norm;
            }
        }

        return $bestOrig;
    }
}
