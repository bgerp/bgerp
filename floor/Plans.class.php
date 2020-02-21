<?php


/**
 * Мениджър на планове на помещения
 *
 *
 * @category  bgerp
 * @package   floor
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class floor_Plans extends core_Master {

   /**
     * Необходими плъгини
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_State2, plg_Rejected, floor_Wrapper, plg_StructureAndOrder';
    

    /**
     * Детайла, на модела
     */
    public $details = 'floor_Objects';


    /**
     * Заглавие
     */
    public $title = 'Планове';
    

    /**
     * Заглавие в единичния изглед
     */
    public $singleTitle = 'План';
    

    /**
     * Права за писане
     */
    public $canWrite = 'floor,admin,ceo';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'floor,admin,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'floor,admin,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'floor,admin,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'floor,admin,ceo';
    
      
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/floor.png';
    
      
    /**
     * Полета, които ще се показват в листов изглед
     */
    // public $listFields = 'order,name,state';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Наименование, mandatory');
        $this->FLD('width', 'float(m=0)', 'caption=Широчина,unit=m');
        $this->FLD('height', 'float(m=0)', 'caption=Дълбочина,unit=m');

        $this->setDbUnique('name');
    }


    /**
     * Необходим метод за подреждането
     */
    public static function getSaoItems($rec)
    {
        $res = array();
        $query = self::getQuery();
        while ($rec = $query->fetch()) {
            $res[$rec->id] = $rec;
        }
        
        return $res;
    }

    /**
     *
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     *
     * @return bool|null
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $url = array($mvc, 'View', $data->rec->id);

        $data->toolbar->addBtn('Покажи', $url, "ef_icon={$mvc->singleIcon}, target=_blank,title =Покажи плана");
    }


    public function act_View()
    {
        Mode::set('wrapper', 'page_Empty');
        RequireRole('admin');

        $planId = Request::get('id', 'int');

        expect($pRec = floor_Plans::fetch($planId));

        $width = self::toPix($pRec->width);
        $height = self::toPix($pRec->height);
        $tpl = new ET("<div style=\"width:{$width}px;height:{$height}px;border:solid 4px #666;  position:relative; display:table;\">[#OBJECTS#]</div>");
        
        jqueryui_Ui::enable($tpl);
        jquery_Jquery::run($tpl, ' $( ".floor-container" ).draggable({\'stop\': function(event) {console.log(event)}, containment: "floor-container"})');
        $tpl->push('floor/css/floorplan.css', 'CSS');
        
        $obects = array();

        $query = floor_Objects::getQuery();
        while($oRec = $query->fetch("#planId = {$planId}")) {
            
            $w = self::toPix($oRec->width);
            $h = self::toPix($oRec->height);
            $x = self::toPix($oRec->x);
            $y = self::toPix($oRec->y);
            $name = type_Varchar::escape($oRec->name);

            $r = round(min($w, $h) * $oRec->round);
            
            $tpl->append("<div class='floor-container' style='left:{$x}px;top:{$y}px;width:{$w}px;height:{$h}px;border-radius:{$r}px;'><div class='floor-obj'>{$name}</div></div>", 'OBJECTS');
        }

        return $tpl;
    }


    /**
     * Конвертиране на метри към пиксели
     */
    private static function toPix($x)
    {
        $y = round($x*40);

        return $y;
    }

}