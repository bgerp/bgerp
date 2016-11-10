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
            'set(' => 'set(',
            'move(' => 'move(',
            'lineTo(' => 'lineTo(',
            'polarLineTo(' => 'polarLineTo(',
            'getPen(' => 'getPen(',
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

            $varId = ltrim($params[0], '$');
            if(!preg_match("/^[a-z][a-z0-9_]{0,64}$/", $varId)) {
                $error = "Невалидно име на променлива: \"" . $params[0] . "\"";

                return FALSE;
            }
            $contex->{$varId} = $expr; 
        }
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


    public static function cmd_Move($params, &$svg, &$contex, &$error)
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
        
        $svg->lineTo($x, $y);
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

        $svg->arcTo($x, $y, $r);
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
        if(!$data->error) {
            $tpl->append($data->canvas->render(), 'DETAILS');
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

        while($rec = $cmdQuery->fetch("#designId = {$designId}")) {
            $name = '$' . ltrim($rec->varId, '$');
            $res[$name] = $name;
        }

        return $res;
    }

}
