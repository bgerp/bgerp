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
    public $loadList = 'plg_Created, plg_RowTools2, plg_State2, plg_Rejected, floor_Wrapper, plg_StructureAndOrder, plg_Modified, plg_Clone, plg_Sorting';


    /**
     * Полета, които да не се клонират
     */
    public $fieldsNotToClone = 'createdOn, createdBy, modifiedOn, modifiedBy';


    /**
     * Детайла, на модела
     */
    public $details = 'floor_Objects';


    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'floor_Objects';


    /**
     * Кой може да клонира запис
     */
    public $canClonerec = 'floor,admin,ceo';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'floor,admin,ceo';

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
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Наименование, mandatory');
        $this->FLD('width', 'float(m=0)', 'caption=Широчина,unit=m');
        $this->FLD('height', 'float(m=0)', 'caption=Дълбочина,unit=m');
        $this->FLD('zoom', 'percent(min=0.1,max=10)', 'caption=Мащаб');
        $this->FLD('image', 'fileman_FileType(bucket=pictures)', 'caption=Фон->Изображение');
        $this->FLD('backgroundColor', 'color_Type', 'caption=Фон->Цвят');
        $this->FLD('decorator', 'class(interface=floor_ObjectDecoratorIntf,select=title,allowEmpty)', 'caption=Декоратор');

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
        $data->toolbar->addBtn('Покажи', $url, "ef_icon={$mvc->singleIcon}, target=_blank,title=Покажи плана");
        
        $url = array($mvc, 'Design', $data->rec->id);
        $data->toolbar->addBtn('Дизайн', $url, "ef_icon=img/16/shape_move_back.png, target=_blank,title=Дизайн на плана");
    }


    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('name');
    }


    /**
     * @return ET
     */
    public function act_Design()
    {
        return $this->act_View(null, true);
    }


    /**
     * @param $planId
     * @param $design
     * @param $tabs
     * @return ET
     * @throws core_exception_Expect
     */
    public function act_View($planId = null, $design = false, $tabs = '')
    {
        Mode::set('wrapper', 'page_Empty');
        RequireRole('admin,floor');
        
        if(!isset($planId)) {
            $planId = Request::get('id', 'int');
        }

        expect($pRec = floor_Plans::fetch($planId));

        $width = self::toPix($pRec->width, $pRec->zoom);
        $height = self::toPix($pRec->height, $pRec->zoom);
        if(!$pRec->backgroundColor) {
            $pRec->backgroundColor = 'white';
        }
        
        $style = array();
        if($pRec->image) {
            $style[] =  "background-image:url('" . trim(fileman_Download::getDownloadUrl($pRec->image)) . "')";
            $style[] = "background-size: {$width}px {$height}px";
        }

        $style[] = $design ? "outline:dotted 4px yellow" : "outline:solid 4px #666";
        
        if(countR($style)) {
            $styleStr = implode(';', $style);
        } else {
            $styleStr = '';
        }
        
        if($tabs) {
            $tpl = new ET("{$tabs}<div class='holder' data-id=\"{$planId}\" id=\"floor\"><div  class=\"floor\" style=\"width:{$width}px;height:{$height}px; background-color:{$pRec->backgroundColor};{$styleStr}\">[#OBJECTS#]</div></div>");
        } else {
            $tpl = new ET("<div class='holder' data-id=\"{$planId}\" id=\"floor\"> <div  class=\"floor\" style=\"width:{$width}px;height:{$height}px; background-color:{$pRec->backgroundColor};{$styleStr}\">[#OBJECTS#]</div></div>");
        }
        
        jqueryui_Ui::enable($tpl);
        $refreshTime = Mode::is('screenMode', 'narrow') ? 60000 : 3000;
        jquery_Jquery::run($tpl, $design ? 'editFloorplan();' : 'calculatePosition();setTimeout(refreshFloor, ' . $refreshTime . ');');
        $tpl->push('floor/css/floorplan.css', 'CSS');
        $tpl->push('floor/js/floorplan.js', 'JS');

        $Objects = cls::get('floor_Objects');
        
        $query = $Objects->getQuery();
        
        while($oRec = $query->fetch("#planId = {$planId}")) {
            
            $w = self::toPix($oRec->width, $pRec->zoom);
            $h = self::toPix($oRec->height, $pRec->zoom);
            $x = self::toPix($oRec->x, $pRec->zoom);
            $y = self::toPix($oRec->y, $pRec->zoom);
            $text = $Objects->getVerbal($oRec, 'text');
            if(!$text) {
                $text = $Objects->getVerbal($oRec, 'name');
            }
            $borderWidth = $oRec->borderWidth;

            $borderColor = $oRec->borderColor ? $oRec->borderColor : "#333";
            
            $style = array();

            if($oRec->backgroundColor) {
                $o = $oRec->opacity ? $oRec->opacity : 1;
                list($r, $g, $b) = color_Object::hexToRgbArr($oRec->backgroundColor);
                $style['background-color'] = "background-color:rgba($r, $g, $b, $o)";
            }
            
            if($oRec->image) {
                $style['background-image'] = "background-image:url('" . trim(fileman_Download::getDownloadUrl($oRec->image)) . "')";
                $style['background-size'] = "background-size: {$w}px";
            }

            $decorator = null;
            if($pRec->decorator) $decorator = $pRec->decorator;
            if($oRec->decorator) $decorator = $oRec->decorator;
        
            if($decorator && (!$design)) {
                $d = cls::get($decorator);
                $d->decorate($oRec->sysName ? $oRec->sysName : $oRec->name, $style, $text);
            }

            if($design) {
                $url = toUrl(array('floor_Objects', 'edit', $oRec->id, 'ret_url' => true));
                $dblClick = "ondblclick='document.location=\"{$url}\"'";
            } else {
                $dblClick = '';
            }

            $styleStr = implode(';', $style);

            $styleObj = array();
            $min = min($w, $h);
            if($min < 20) { ;
                $styleObj[] = "font-size: " . round($min/20, 2) . 'em';  
            }
            
            if(count($styleObj)) {
                $styleObj = 'style="' . implode(';', $styleObj) . '"';
            } else {
                $styleObj = '';
            }

            $r = round(min($w, $h) * $oRec->round);
            $tpl->append("<div id='{$oRec->id}' class='floor-object' {$dblClick} style=\"left:{$x}px;top:{$y}px;width:{$w}px;height:{$h}px;border-radius:{$r}px;border: {$borderWidth}px solid {$borderColor};{$styleStr};\">
                <div class='floor-obj' {$styleObj}>{$text}</div></div>", 'OBJECTS');
        }

        return $tpl;
    }


    /**
     * Екшън, който обновява позицията на даден елемент
     */
    public function act_UpdatePossition()
    { 
        $objId = Request::get('objId', 'int');
 
        if($rec = floor_Objects::fetch($objId)) {
            $this->requireRightfor('edit', $rec->planId);
            $pRec = self::fetch($rec->planId);
            $rec->x = self::fromPix(Request::get('x', 'int'), $pRec->zoom);
            $rec->y = self::fromPix(Request::get('y', 'int'), $pRec->zoom);
            floor_Objects::save($rec, 'x,y');
        }

        shutdown();
    }


    /**
     * Екшън, който обновява позицията на даден елемент
     */
    public function act_DeleteObject()
    { 
        $objId = Request::get('objId', 'int');
 
        if($rec = floor_Objects::fetch($objId)) {
            $this->requireRightfor('edit', $rec->planId);
            floor_Objects::delete($rec->id);
        }

        shutdown();
    }


    /**
     * Екшън, който обновява позицията на даден елемент
     */
    public function act_ChangeSize()
    { 
        $objId = Request::get('objId', 'int');
        
        if($rec = floor_Objects::fetch($objId)) {
            $this->requireRightfor('edit', $rec->planId);
            $pRec = self::fetch($rec->planId);

            $x = self::fromPix(Request::get('x', 'int'), $pRec->zoom);
            $y = self::fromPix(Request::get('y', 'int'), $pRec->zoom);
            $w = self::fromPix(Request::get('w', 'int'), $pRec->zoom);
            $h = self::fromPix(Request::get('h', 'int'), $pRec->zoom);
            
            list($x1, $y1, $w1, $h1) = self::getInRect($pRec->width, $pRec->height, $x, $y, $w, $h);
            
            $rec->x = $x1;
            $rec->y = $y1;
            $rec->width = $w1;
            $rec->height = $h1;
            floor_Objects::save($rec, 'width,height,x,y');

            $res = new stdClass();
            $res->x = self::toPix($x1, $pRec->zoom);
            $res->y = self::toPix($y1, $pRec->zoom);
            $res->w = self::toPix($w1, $pRec->zoom);
            $res->h = self::toPix($h1, $pRec->zoom);
            
            core_App::outputJson($res);
        }

        shutdown();
    }


    /**
     *
     */
    public function act_RefreshFloor()
    {
        $id = Request::get('floorId', 'int');
        
        if($rec = self::fetch($id)) {
            $this->requireRightFor('view', $rec);
            $res = array();
            $tpl = $this->act_View($id);
            $res['html'] = (string) $tpl;
            core_App::outputJson($res);
        }
    }


    /**
     * Конвертиране на метри към пиксели
     */
    private static function toPix($x, $zoom)
    {
        if(!$zoom) {
            $zoom = 1;
        }
        $y = round($x*40*$zoom);

        return $y;
    }

    /**
     * Конвертиране на пиксели към метри
     */
    private static function fromPix($x, $zoom)
    {
        if(!$zoom) {
            $zoom = 1; 
        }
        $y = round($x/(40*$zoom), 6);

        return $y;
    }


    /**
     * Връща координати x1, y1, w1, h1 които са възможни за правоъгилник, 
     * така, че той да се намира в правоъгълник с размери W и H
     */
    public static function getInRect($W, $H, $x, $y, $w, $h)
    {
        $w1 = min($w, $W);
        $h1 = min($h, $H);
        $x1 = min(max($x, 0), $W-$w);
        $y1 = min(max($y, 0), $H-$h);

        return array($x1, $y1, $w1, $h1);
    }

}