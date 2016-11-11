<?php


/**
 * Мениджър на логически блокове за управление на контролери
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class draw_Designs extends core_Master
{

    const CALC_ERROR = "Грешка при изчисляване";

     
    /**
     * Необходими плъгини
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, plg_State2, plg_Rejected, draw_Wrapper';
	
    
    
    /**
     * Заглавие
     */
    var $title = 'Скриптове';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'ceo,draw,admin';
    
    
    /**
     * Права за запис
     */
    var $canRead = 'ceo, draw, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'debug';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,draw';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,draw';
    


    /**
     * Полето "Наименование" да е хипервръзка към единичния изглед
     */
    var $rowToolsSingleField = 'name';


    /**
     * Заглавие в единичния изглед
     */
    var $singleTitle = 'Скрипт';


    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/script.png';


    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'draw/tpl/SingleLayoutDesign.shtml';


    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'order,name,state,lastRun';

    var $rowToolsField = 'order';

    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('order', 'int', 'caption=№');
        $this->FLD('name', 'varchar(255)', 'caption=Наименование, mandatory,notConfig');
        $this->FLD('state', 'enum(active=Активно,closed=Затворено)', 'caption=Състояние, input=none,notConfig');
        $this->FLD('script', 'text(rows=20)', 'caption=Скрипт');

        $this->setDbUnique('name');
    }
    

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareEditform($mvc, &$data)
    {
        $data->form->setSuggestions('script', array(
            'Set(' => 'Set(',
            'MoveTo(' => 'MoveTo(',
            'LineTo(' => 'LineTo(',
            'SavePoint(' => 'SavePoint(',
            'ArcTo(' => 'ArcTo(',
            'PolarLineTo(' => 'PolarLineTo(',
            'ClosePath(' => 'ClosePath(',
            'GetPen(' => 'GetPen(',
            'OpenGroup(' => 'OpenGroup(',
            'CloseGroup(' => 'CloseGroup(',
            'OpenLayer(' => 'OpenLayer(',
            'CloseLayer(' => 'CloseLayer(',
            'MeasureLine(' => 'MeasureLine(',

            ));
    }


    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        if(!$form->rec->order) {
            $query = $mvc->getQuery();
            $query->orderBy('#order', 'DESC');
            $query->limit(1);
            $maxOrder = (int) $query->fetch()->order;
            $form->setDefault('order', round(($maxOrder+1)/10)*10 + 10);
        }
    }

    
    public static function runScript($script, $svg, &$contex, &$error)
    {
        $script = str_replace("\r\n", "\n", $script);
        $script = str_replace("\n\r", "\n", $script);
        $script = str_replace("\r", "\n", $script);

        $lines = explode("\n", $script);

        foreach($lines as $l) {
            
            $l = trim($l);
            
            // Коментар
            if(empty($l) || substr($l, 0, 2) == '//' || substr($l, 0, 1) == '#') {
                continue;
            }

            list($cmd, $params) = explode('(', $l);
 
            $method = "cmd_" . $cmd;
            
            if(!cls::existsMethod('draw_Designs', $method)) {
                $error = "Липсваща команда: \"" . $cmd . "\"";

                return FALSE;
            }

            $pArr = self::parseParams(rtrim($params, ');'));
            if($pArr === FALSE) {
                $error = "Грешка в параметрите: \"" . $l . "\"";

                return FALSE;
            }

            $res = call_user_func_array(array('draw_Designs', $method), array($pArr, &$svg, &$contex, &$error));
       
 
            if($res === FALSE) {

                return $res;
            }
        }


    }


    /**
     * Парсира параметри на функции
     */
    private static function parseParams($params)
    {
        $i = 0;
        $level = 0;
        $res = array();
        foreach(str_split($params) as $c) {
            if($c == '(') {
                $level++;
            }
            if($c == ')') {
                $level--;
            }

            if($c == ',' && $level == 0) {
                $i++;
                $c = '';
            }

            $res[$i] .= $c;
        }

        return $res;
    }


    public static function cmd_Set($params, &$svg, &$contex, &$error)
    { 
        if(isset($params[2])) {
            $cond = self::calcExpr($params[2], $contex);
            if($cond === self::CALC_ERROR) {
                $error = "Грешка при изчисляване на: \"" . $params[2] . "\"";

                return FALSE;
            }
        } else {
            $cond = TRUE;
        }
 
        if($cond) {
            $expr = self::calcExpr($params[1], $contex);  
            if($expr === self::CALC_ERROR) {
                $error = "Грешка при изчисляване на: \"" . $params[1] . "\"";
 
                return FALSE;
            }

            $varId = ltrim($params[0], '$ ');
            if(!preg_match("/^[a-z][a-z0-9_]{0,64}$/", $varId)) {
                $error = "Невалидно име на променлива: \"" . $params[0] . "\"";

                return FALSE;
            }
            $contex->{$varId} = $expr; 
        }
    }


    public static function cmd_ClosePath($params, &$svg, &$contex, &$error)
    {
        $svg->closePath();
    }


    public static function cmd_OpenLayer($params, &$svg, &$contex, &$error)
    {
        $name = trim($params[0]);

        $svg->openLayer($name);
    }


    public static function cmd_CloseLayer($params, &$svg, &$contex, &$error)
    {
        $svg->closeLayer();
    }


    public static function cmd_OpenGroup($params, &$svg, &$contex, &$error)
    {
        $name = trim($params[0]);

        $svg->openGroup($name);
    }


    public static function cmd_CloseGroup($params, &$svg, &$contex, &$error)
    {
        $svg->closeGroup();
    }


    public static function cmd_MeasureLine($params, &$svg, &$contex, &$error)
    {
        $x1 =  self::calcExpr($params[0], $contex);  
        if($x1 === self::CALC_ERROR) {
                $error = "Грешка при изчисляване на: \"" . $params[0] . "\"";
 
                return FALSE;
        }

        $y1 =  self::calcExpr($params[1], $contex);  
        if($y1 === self::CALC_ERROR) {
                $error = "Грешка при изчисляване на: \"" . $params[1] . "\"";
 
                return FALSE;
        }
        
        $x2 =  self::calcExpr($params[2], $contex);  
        if($x2 === self::CALC_ERROR) {
                $error = "Грешка при изчисляване на: \"" . $params[2] . "\"";
 
                return FALSE;
        }
        $y2 =  self::calcExpr($params[3], $contex);  
        if($y2 === self::CALC_ERROR) {
                $error = "Грешка при изчисляване на: \"" . $params[3] . "\"";
 
                return FALSE;
        }
        
        if(isset($params[4])) {
            $d =  self::calcExpr($params[4], $contex);  
            if($d === self::CALC_ERROR) {
                    $error = "Грешка при изчисляване на: \"" . $params[4] . "\"";
     
                    return FALSE;
            }
        } else {
            $d = 1;
        }

        $text = trim($params[5]);


        self::drawMeasureLine($svg, $x1, $y1, $x2, $y2, $d, $text);
    }

    
    
    public static function cmd_GetPen($params, &$svg, &$contex, &$error)
    { 
        if(isset($params[0])) {

            $pen = draw_Pens::fetch(array("#name = '[#1#]'", trim($params[0])));

            if(!$pen) {
                $error = "Липсващ молив: \"" . $params[1] . "\"";
 
                return FALSE;
            }
            
            if($pen->color) {
                $svg->setAttr('stroke', $pen->color);
            }

            if($pen->background) {
                $svg->setAttr('fill', $pen->background);
            }
            
            if($pen->thickness) {
                $svg->setAttr('stroke-width', $pen->thickness);
            }
            
            if($pen->dasharray) {
                $svg->setAttr('stroke-dasharray', $pen->dasharray);
            }

        } else {
            $error = "Липсващ параметър за молив";
 
            return FALSE;
        }
    }


    public static function cmd_MoveTo($params, &$svg, &$contex, &$error)
    { 
        $x =  self::calcExpr($params[0], $contex);  
        if($x === self::CALC_ERROR) {
                $error = "Грешка при изчисляване на: \"" . $params[0] . "\"";
 
                return FALSE;
        }


        $y =  self::calcExpr($params[1], $contex);  
        if($y === self::CALC_ERROR) {
                $error = "Грешка при изчисляване на: \"" . $params[1] . "\"";
 
                return FALSE;
        }
        
        $svg->closePath(FALSE);
        $svg->startPath();
        $svg->moveTo($x, $y, TRUE);
    }
  
    

    /**
     * Изчертаване на линия
     */
    public static function cmd_LineTo($params, &$svg, &$contex, &$error)
    { 
        $x =  self::calcExpr($params[0], $contex);  
        if($x === self::CALC_ERROR) {
                $error = "Грешка при изчисляване на: \"" . $params[0] . "\"";
 
                return FALSE;
        }


        $y =  self::calcExpr($params[1], $contex);  
        if($y == self::CALC_ERROR) {
                $error = "Грешка при изчисляване на: \"" . $params[1] . "\"";
 
                return FALSE;
        }

        $abs = trim(strtolower($params[2]));

        $svg->lineTo($x, $y, $abs === 'abs');
    }
    
    
    /**
     * Зашазва текущата точка
     */
    public static function cmd_SavePoint($params, &$svg, &$contex, &$error)
    { 
        list($x, $y) = $svg->getCP();
        
        $varX = ltrim($params[0], '$ ');
        if(!preg_match("/^[a-z][a-z0-9_]{0,64}$/", $varX)) {
            $error = "Невалидно име на променлива: \"" . $params[0] . "\"";

            return FALSE;
        }
        $contex->{$varX} = $x; 

        $varY = ltrim($params[1], '$ ');
        if(!preg_match("/^[a-z][a-z0-9_]{0,64}$/", $varY)) {
            $error = "Невалидно име на променлива: \"" . $params[1] . "\"";

            return FALSE;
        }
        $contex->{$varY} = $y;
    }


    /**
     * Изчертаване на дъга
     */
    public static function cmd_ArcTo($params, &$svg, &$contex, &$error)
    { 
        $x =  self::calcExpr($params[0], $contex);  
        
        if($x === self::CALC_ERROR) {
                $error = "Грешка при изчисляване на: \"" . $params[0] . "\"";
 
                return FALSE;
        }


        $y =  self::calcExpr($params[1], $contex);  
        if($y === self::CALC_ERROR) {
                $error = "Грешка при изчисляване на: \"" . $params[1] . "\"";
 
                return FALSE;
        }
        
        $r =  self::calcExpr($params[2], $contex);  
        if($r == self::CALC_ERROR) {
                $error = "Грешка при изчисляване на: \"" . $params[2] . "\"";
 
                return FALSE;
        }
        
        $abs = trim(strtolower($params[2]));

        $svg->arcTo($x, $y, $r, $abs === 'abs');
    }




    public static function on_AfterPrepareSingle($mvc, $res, $data)
    {
        $error = '';
        $contex = new stdClass();
        $canvas = cls::get('cad2_SvgCanvas');
        $canvas->setPaper(210, 297, 10, 10, 10, 10);

        $res = self::runScript($data->rec->script, $canvas, $contex, $error);

        if($res === FALSE) $data->error = $error;

        $data->contex = $contex;
        $data->canvas = $canvas;
    }

    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        //bp($data);
        if(!$data->error) {
            // Обвиваме съдъжанието на файла в код
            $code = "<div class='richtext'><pre class='rich-text code'><code>{$data->rec->script}</code></pre></div>";

            $tpl2 = hljs_Adapter::enable('github');
            $tpl2->append($code, 'CODE');

            $tpl->append($tpl2);
            $tpl->append($data->canvas->render(), 'DETAILS');
            $tpl->append("state-{$data->rec->state}", 'STATE_CLASS');

        } else {
            $tpl->append("<h3 style='color:red;'>" . $data->error . "</h3>", 'DETAILS');
        }
    }


 

    /**
	 * За да не могат да се изтриват активните скриптове
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
   		if($action == 'delete') {
	    	if($rec->state != 'closed'){
	    		$res = 'no_one';
	    	}
   		}
   		
	}


    /**
     * Изчислкяване на числов израз. Могат да участват индикаторите и променливите от даден скрипт
     */
    public static function calcExpr($expr, $contex)
    {
        // Намираме и сортираме контекста
        $ctx = array();
        foreach((array) $contex as $varId => $value) {
            $ctx['$' . ltrim($varId, '$')] = $value;
        }

        uksort($ctx, "str::sortByLengthReverse");

        // Заместваме променливите и индикаторите
        $expr  = strtr($expr, $ctx);
      
        if(str::prepareMathExpr($expr) === FALSE) {
            $res = self::CALC_ERROR;
        } else {
            $res = str::calcMathExpr($expr, $success);

            if($success === FALSE) {
                $res = self::CALC_ERROR;
            }
        }

        return $res; 
    }


    /**
     * Връща всички дефинирани променливи
     */
    public static function getVars($designId)
    {
        $cmdQuery = draw_DesignCommands::getQuery();
        
        $res = array();
        while($rec = $cmdQuery->fetch("#designId = {$designId}")) {
            $name = '$' . ltrim($rec->varId, '$');
            $res[$name] = $name;
        }

        return $res;
    }



    /**
     * Изчертава оразмерителна линия
     */
    static function drawMeasureLine($svg, $Ax, $Ay, $Bx, $By, $dist = 1, $measureText = NULL)
    {
        $svg->setAttr('stroke', '#0000fe');
        $svg->setAttr('stroke-width', '0.1');
        $svg->setAttr('font-size', 40);
        $svg->setAttr('stroke-dasharray', '');
        $svg->setAttr('stroke-opacity', '1');
        $svg->setAttr('stroke-opacity', '1');
        $svg->setAttr('font-size', 40);
	    $svg->setAttr('font-weight', 'bold');
	    $svg->setAttr('font-family', 'Arial, Sans-serif');
        cad2_MeasureLine::draw($svg, $Ax, $Ay, $Bx, $By, $dist * 5, $measureText);
    }


    /**
     * Задава стила на молива за оразмерителните линии
     */
    function setMeasureAttr($svg)
    {
    	if($svg->p["view"] != "preview") {
	        $svg->setAttr('stroke', '#0000fe');
	        $svg->setAttr('stroke-width', '0.1');
	        $svg->setAttr('font-size', 40);
	        $svg->setAttr('stroke-dasharray', '');
	        $svg->setAttr('stroke-opacity', '1');
    	}
    }

}
