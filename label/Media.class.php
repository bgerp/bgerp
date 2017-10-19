<?php 


/**
 * Медии за отпечатване
 * 
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_Media extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Медия';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Медия';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'labelMaster, admin, ceo';
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'labelMaster, admin, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'label_Wrapper, plg_RowTools2, plg_Created, plg_State';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие, mandatory, width=100%, silent');
        
        $this->FLD('width', 'double(Min=0, max=1000, decimals=1, smartRound)', 'caption=Размер->Широчина, unit=mm, notNull, mandatory');
        $this->FLD('height', 'double(Min=0, max=1000, decimals=1, smartRound)', 'caption=Размер->Височина, unit=mm, notNull, mandatory');
        
        $this->FLD('fieldUp', 'double(min=-1000, max=1000, decimals=1, smartRound)', 'caption=Отстъпи->Отгоре, value=0, title=Поле на листа отгоре, unit=mm, notNull');
        $this->FLD('fieldLeft', 'double(min=-1000, max=1000, decimals=1, smartRound)', 'caption=Отстъпи->Отляво, value=0, title=Поле на листа отляво, unit=mm, notNull');
        
        $this->FLD('columnsCnt', 'int(min=1, max=10)', 'caption=Колони->Брой, value=1, title=Брой колони в един лист, mandatory, notNull');
        $this->FLD('columnsDist', 'double(min=-20, max=200, decimals=1, smartRound)', 'caption=Колони->Междина, value=0, title=Разстояние на колоните в един лист, unit=mm, notNull');
        
        $this->FLD('linesCnt', 'int(min=1, max=50)', 'caption=Редове->Брой, value=1, title=Брой редове в един лист, mandatory, notNull');
        $this->FLD('linesDist', 'double(min=-20, max=200, decimals=1, smartRound)', 'caption=Редове->Междина, value=0, title=Разстояние на редовете в един лист, unit=mm, notNull');
        
        $this->setDbUnique('title');
    }
    
    
    /**
     * Сменяме състоянието на активно
     * 
     * @param integer $id
     */
    public static function markMediaAsUsed($id)
    {
        $rec = self::fetch($id);
        
        if ($rec->state == 'active') return ;
        
        $rec->state = 'active';
        
        self::save($rec, 'state', 'UPDATE');
    }
    
    
    /**
     * Връща броя на квадратчетата за попълване в една страница на медията
     * 
     * @param integer $id
     * 
     * @return integer
     */
    public static function getCountInPage($id)
    {
        $rec = self::fetch($id);
        $cnt = $rec->columnsCnt * $rec->linesCnt;
        
        return $cnt;
    }
    
    
    /**
     * Връща масив с всички възможни размери
     * 
     * @return array
     */
    public static function getAllSizes()
    {
        $resArr = array();
        $query = static::getQuery();
        
        while ($rec = $query->fetch()) {
            $size = self::getSize($rec->width, $rec->height);
            $resArr[$size] = $size; 
        }
        
        return $resArr;
    }
    
    
    /**
     * Връща размера от широчината и височината
     * 
     * @param integer $width
     * @param integer $heigh
     * 
     * @return string
     */
    public static function getSize($width, $heigh)
    {
        $size = $width . 'x' . $heigh . ' mm';
        
        return $size;
    }
    
    
    /**
     * Връща масив с ключове id-та и заглавие на всички медии които отговарят на размера
     * 
     * @param string $sizes
     * 
     * @return array
     */
    public static function getMediaArrFromSizes($sizes)
    {
        $resArr = array();
        $sizes = rtrim($sizes, ' m');
        $sizeArr = explode('x', $sizes);
        
        $query = self::getQuery();
        $query->where(array("#width = '[#1#]'", trim($sizeArr[0])));
        $query->where(array("#height = '[#1#]'", trim($sizeArr[1])));
        
        $query->orderBy('createdOn', 'DESC');
        
        while ($rec = $query->fetch()) {
            
            $resArr[$rec->id] = self::recToVerbal($rec)->title;
        }
        
        return $resArr;
    }
    
    
    /**
     * Подготвя данните за лейаулта на медията 
     * 
     * @param object $data
     */
    static function prepareMediaPageLayout(&$data)
    {
        $rec = $data->Media->rec;
        
        // Ако някоя от необходимите стойности не е сетната
        if (!$rec->columnsCnt || !$rec->linesCnt || !$data->cnt) return FALSE;
        
        // Ако не е сетнат
        if (!$data->pageLayout) {
        
            // Създаваме обекта
            $data->pageLayout = new stdClass();
        }
        
        // Колко етикети ще има на страница
        $data->pageLayout->itemsPerPage = self::getCountInPage($rec->id);
        
        // Брой страници
        $data->pageLayout->pageCnt = (int)ceil($data->cnt / $data->pageLayout->itemsPerPage);
        
        // Брой записи в поседната страница
        $data->pageLayout->lastPageCnt = (int)($data->cnt % $data->pageLayout->itemsPerPage);
        
        // Брой на колоните
        $data->pageLayout->columnsCnt = $rec->columnsCnt;
        
        // Брой на редовете
        $data->pageLayout->linesCnt = $rec->linesCnt;
        
        // Ако не са сетнати да са единици
        setIfNot($data->pageLayout->columnsCnt, 1);
        setIfNot($data->pageLayout->linesCnt, 1);
        
        // Отместване на цялата страница
        $data->pageLayout->up = $rec->fieldUp . 'mm';
        $data->pageLayout->left = $rec->fieldLeft . 'mm';

        // Отместване на колона
        $data->pageLayout->columnsDist = $rec->columnsDist . 'mm';
        
        // Отместване на ред 
        $data->pageLayout->linesDist = $rec->linesDist . 'mm';
    }

    
    /**
     * Рендираме лейаулта за съответната медия
     * 
     * @param object $data
     */
    static function renderMediaPageLayout(&$data)
    {
        // Брой колоени
        $columns = $data->pageLayout->columnsCnt;
        
        // Брой редове
        $lines = $data->pageLayout->linesCnt;
        
        // Брояч
        $cnt = 0;
        
        // Създаваме таблицата
        $t = "<table class='label-table printing-page-break' style='border-collapse: separate; margin-top: {$data->pageLayout->up}; margin-left: {$data->pageLayout->left};'>";
        
        // Броя на редовете
        for ($i = 0; $i < $lines; $i++) {
            
            // Ако е последен ред
            if ($i == ($lines - 1)) {
                
                // Да няма отместване отдолу
                $bottom = 0;
            }
            
            // Добавям ред
            $t .= '<tr>';
            
            // Броя на колоните
            for ($s = 0; $s < $columns; $s++) {
                
                // Добавяме колона
                $t .= "<td>[#$cnt#]</td>";
                
                // Увеличаваме брояча
                $cnt++;
            }
            
            // Добавяме край на ред
            $t .= "</tr>";
        }
        
        // Добавяме край на таблица
        $t .= '</table>';
        
        return new ET($t);
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
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Активните записи да не може да се редактират или изтриват
        if ($rec && ($action == 'edit' || $action == 'delete')) {
            if ($rec->state == 'active') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След подготовка на вербалното представяне
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->title = $row->title . " " . self::getSize($row->width, $row->height);
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
    	// Подготвяме пътя до файла с данните
    	$file = "label/csv/Media.csv";
    
    	// Кои колонки ще вкарваме
    	$fields = array(
    			0 => "title",
    			1 => "width",
    			2 => "height",
    			3 => "fieldUp",
    			4 => "fieldLeft",
    			5 => "columnsCnt",
    			6 => "columnsDist",
    			7 => "linesCnt",
    			8 => "linesDist",
    	);
    
    	// Импортираме данните от CSV файла.
    	// Ако той не е променян - няма да се импортират повторно
    	$cntObj = csv_Lib::importOnce($this, $file, $fields, NULL, NULL);
    	
    	// Записваме в лога вербалното представяне на резултата от импортирането
    	$res = $cntObj->html;
    
    	return $res;
    }
}
