<?php


/**
 * Мениджър на логически блокове за управление на контролери
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class draw_Designs extends core_Master
{
    const CALC_ERROR = 'Грешка при изчисляване';
    
    
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_State2, plg_Rejected, draw_Wrapper, change_Plugin, plg_Search';
    
    
    /**
     * Поле за търсене
     */
    public $searchFields = 'name';
    
    
    /**
     * Полетата, които могат да се променят с change_Plugin
     */
    public $changableFields = 'name,script';
    
    
    /**
     * Кой може да променя записа
     */
    public $canChangerec = 'drawMaster,ceo,admin';
    
    
    /**
     * Кой може да променя записа
     */
    public $canChangestate = 'draw,ceo,admin';
    
    
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да оттегля документа
     */
    public $canReject = 'drawMaster, ceo,admin';
    
    
    /**
     * Заглавие
     */
    public $title = 'Скриптове';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'drawMaster, ceo,admin';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo, draw, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'debug, drawMaster';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,draw';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,draw';
    
    
    /**
     * Полето "Наименование" да е хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Заглавие в единичния изглед
     */
    public $singleTitle = 'Дизайн';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/script.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'draw/tpl/SingleLayoutDesign.shtml';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'order,name,state';
    
    public $rowToolsField = 'order';
    
    
    /**
     * Описание на модела
     */
    public function description()
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
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditform($mvc, &$data)
    {
        $pens = array();
        $query = draw_Pens::getQuery();
        while ($pRec = $query->fetch()) {
            $pens[$pRec->name] = '#' . $pRec->name;
        }
        $suggestions = array(
            'ArcTo(' => 'ArcTo(',
            'Call(' => 'Call(',
            'CallPHP(' => 'CallPHP(',
            'CloseGroup(' => 'CloseGroup(',
            'CloseLayer(' => 'CloseLayer(',
            'ClosePath(' => 'ClosePath(',
            'CurveTo(' => 'CurveTo(',
            'DrawEllipse(' => 'DrawEllipse(',
            'DrawPartOfEllipse(' => 'DrawPartOfEllipse(',
            'GetPen(' => 'GetPen(',
            'Else(' => 'Else(',
            'EndIf(' => 'EndIf(',
            'If' => 'If(',
            'Input(' => 'Input(',
            'LineTo(' => 'LineTo(',
            'MeasureAngle(' => 'MeasureAngle(',
            'MeasureLine(' => 'MeasureLine(',
            'MoveTo(' => 'MoveTo(',
            'OpenGroup(' => 'OpenGroup(',
            'OpenLayer(' => 'OpenLayer(',
            'PolarLineTo(' => 'PolarLineTo(',
            'SavePoint(' => 'SavePoint(',
            'Set(' => 'Set(',
            'WriteText(' => 'WriteText(',
        );
        $id = Request::get('id', 'int');
        
        if ($id) {
            $rec = self::fetch($id);
        }
        
        if ($script = $rec->script) {
            $script = ' ' . str_replace(array('-', '+', '*', '/', '(', ')', ',', "\n", "\r", "\t"), ' ', $script) . ' ';
            preg_match_all('/ (\$[a-z0-9_]{1,64}) /i', $script, $matches);
            foreach ($matches[1] as $varName) {
                $suggestions[$varName] = $varName;
            }
        }
        
        $suggestions = $pens + $suggestions;
        
        $data->form->setSuggestions('script', $suggestions);
    }
    
    
    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    public function on_AfterInputEditForm($mvc, $form)
    {
        if (!$form->rec->order) {
            $query = $mvc->getQuery();
            $query->orderBy('#order', 'DESC');
            $query->limit(1);
            $maxOrder = (int) $query->fetch()->order;
            $form->setDefault('order', round(($maxOrder + 1) / 10) * 10 + 10);
        }
    }
    
    
    /**
     * Парсира текста на скрипта до масив с масиви с два елемента
     * 0 => команда
     * 1 => параметри (като текст)
     */
    public static function parseScript($script)
    {
        $script = str_replace("\r\n", "\n", $script);
        $script = str_replace("\n\r", "\n", $script);
        $script = str_replace("\r", "\n", $script);
        
        $lines = explode("\n", $script);
        $res = array();
        
        foreach ($lines as $l) {
            $l = trim($l);
            
            // Коментар
            if (empty($l) || substr($l, 0, 2) == '//' || substr($l, 0, 1) == '#') {
                continue;
            }
            
            list($cmd, $params) = explode('(', $l, 2);
            
            $params = trim($params, '; ');
            
            while (substr($params, -1) != ')' && strlen($params) > 1) {
                $params = substr($params, 0, strlen($params) - 1);
            }
            $params = substr($params, 0, strlen($params) - 1);
            
            $res[] = array(
                0 => trim(mb_strtolower($cmd)),
                1 => $params,
                2 => $l,
            );
        }
        
        return $res;
    }
    
    
    public static function runScript($script, $svg, &$contex, &$error)
    {
        $sArr = self::parseScript($script);
        
        
        foreach ($sArr as $parsedLine) {
            list($cmd, $params, $l) = $parsedLine;
            
            if (is_array($contex->_if) && countR($contex->_if)) {
                $lastIf = array_pop($contex->_if);
                $contex->_if[] = $lastIf;
                
                if (!$lastIf && (strtolower($cmd) != 'else' && strtolower($cmd) != 'endif')) {
                    continue;
                }
            }
            
            $method = 'cmd_' . $cmd;
            
            if (!cls::existsMethod('draw_Designs', $method)) {
                $error = 'Липсваща команда: "' . $cmd . '"';
                
                return false;
            }
            
            $pArr = self::parseParams($params);
            if ($pArr === false) {
                $error = 'Грешка в параметрите: "' . $l . '"';
                
                return false;
            }
            
            $res = call_user_func_array(array('draw_Designs', $method), array($pArr, &$svg, &$contex, &$error));
            
            
            if ($res === false) {
                
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
        foreach (str_split($params) as $c) {
            if ($c == '(') {
                $level++;
            }
            if ($c == ')') {
                $level--;
            }
            
            if ($c == ',' && $level == 0) {
                $i++;
                $c = '';
            }
            
            $res[$i] .= $c;
        }
        
        foreach ($res as $i => &$expr) {
            $expr = trim($expr);
        }
        
        return $res;
    }
    
    
    public static function cmd_Set($params, &$svg, &$contex, &$error)
    {
        if (isset($params[2])) {
            $cond = self::calcExpr($params[2], $contex);
            if ($cond === self::CALC_ERROR) {
                $error = 'Грешка при изчисляване на: "' . $params[2] . '"';
                
                return false;
            }
        } else {
            $cond = true;
        }
        
        if ($cond) {
            $expr = self::calcExpr($params[1], $contex);
            if ($expr === self::CALC_ERROR) {
                $error = 'Грешка при изчисляване на: "' . $params[1] . '"';
                
                return false;
            }
            
            $varId = ltrim($params[0], '$ ');
            if (!preg_match('/^[a-z][a-z0-9_]{0,64}$/i', $varId)) {
                $error = 'Невалидно име на променлива: "' . $params[0] . '"';
                
                return false;
            }
            $contex->{$varId} = $expr;
        }
    }
    
    
    public static function cmd_If($params, &$svg, &$contex, &$error)
    {
        if (isset($params[0])) {
            $cond = self::calcExpr($params[0], $contex);
            if ($cond === self::CALC_ERROR) {
                $error = 'Грешка при изчисляване на: "' . $params[0] . '"';
                
                return false;
            }
        } else {
            $cond = true;
        }
        $contex->_if[] = $cond;
    }
    
    
    public static function cmd_Bp($params, &$svg, &$contex, &$error)
    {
        expect(false, $contex);
    }
    
    
    public static function cmd_Else($params, &$svg, &$contex, &$error)
    {
        if (!is_array($contex->_if) || !countR($contex->_if)) {
            $error = 'Грешка при ELSE';
            
            return false;
        }
        $cond = array_pop($contex->_if);
        $contex->_if[] = !($cond);
    }
    
    
    public static function cmd_EndIf($params, &$svg, &$contex, &$error)
    {
        if (!is_array($contex->_if) || !countR($contex->_if)) {
            $error = 'Грешка при затваряне на IF';
            
            return false;
        }
        array_pop($contex->_if);
    }
    
    
    /**
     * Извъкване на скрипт-модул
     */
    public static function cmd_Call($params, &$svg, &$contex, &$error)
    {
        $contexNew = new stdClass();
        $scriptName = $params[0];
        unset($p[0]);
        
        foreach ($params as $p) {
            if (stripos($p, '=')) {
                list($varName, $exVarName) = explode('=', $p);
            } else {
                $exVarName = $varName = $p;
            }
            $varName = ltrim(trim($varName), '$');
            $exVarName = ltrim(trim($exVarName), '$');
            
            $contexNew->{$varName} = $contex->{$exVarName};
        }
        
        $rec = self::fetch(array("#name = '[#1#]'", $scriptName));
        
        if (!$rec) {
            $error = 'Невалидно име на скрипт: "' . $scriptName . '"';
            
            return false;
        }
        
        self::runScript($rec->script, $svg, $contexNew, $error);
    }
    
    
    /**
     * Извъкване на външна функция
     */
    public static function cmd_CallPHP($params, &$svg, &$contex, &$error)
    {
        $contexNew = new stdClass();
        
        list($class, $method) = explode('::', $params[0]);
        
        if (!$class) {
            $error = 'Липсващо име на клас';
            
            return false;
        }
        
        
        if (!($cls = cls::get($class))) {
            $error = 'Невалидно име на клас: "' . $class . '"';
            
            return false;
        }
        
        if (!$method) {
            $error = 'Липсващо име на метод';
            
            return false;
        }
        
        $method = 'draw_' . $method;
        
        if (!cls::existsMethod($cls, $method)) {
            $className = cls::getClassName($cls);
            $error = "Липсващ метод в клас: \"{$className}::{$method}\"";
            
            return false;
        }
        
        call_user_func_array(array($cls, $method), array(&$contex));
    }
    
    
    public static function cmd_Input($params, &$svg, &$contex, &$error)
    {
        $varId = ltrim($params[0], '$ ');
        if (!preg_match('/^[a-z][a-z0-9_]{0,64}$/i', $varId)) {
            $error = 'Невалидно име на променлива: "' . $params[0] . '"';
            
            return false;
        }
        
        $d = cls::get('type_Double');
        
        $val = $d->fromVerbal(Request::get($varId));
        
        if ($val === null || $val === false) {
            $val = (float) $params[1];
        }
        
        if (!isset($contex->{$varId})) {
            $contex->{$varId} = $val;
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
        $x1 = self::calcExpr($params[0], $contex);
        if ($x1 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[0] . '"';
            
            return false;
        }
        
        $y1 = self::calcExpr($params[1], $contex);
        if ($y1 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[1] . '"';
            
            return false;
        }
        
        $x2 = self::calcExpr($params[2], $contex);
        if ($x2 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[2] . '"';
            
            return false;
        }
        $y2 = self::calcExpr($params[3], $contex);
        if ($y2 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[3] . '"';
            
            return false;
        }
        
        if (isset($params[4])) {
            $d = self::calcExpr($params[4], $contex);
            if ($d === self::CALC_ERROR) {
                $error = 'Грешка при изчисляване на: "' . $params[4] . '"';
                
                return false;
            }
        } else {
            $d = 1;
        }
        
        $text = trim($params[5]);
        
        
        self::drawMeasureLine($svg, $x1, $y1, $x2, $y2, $d, $text);
    }
    
    
    public static function cmd_MeasureAngle($params, &$svg, &$contex, &$error)
    {
        $x1 = self::calcExpr($params[0], $contex);
        if ($x1 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[0] . '"';
            
            return false;
        }
        
        $y1 = self::calcExpr($params[1], $contex);
        if ($y1 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[1] . '"';
            
            return false;
        }
        
        $x2 = self::calcExpr($params[2], $contex);
        if ($x2 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[2] . '"';
            
            return false;
        }
        
        $y2 = self::calcExpr($params[3], $contex);
        if ($y2 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[3] . '"';
            
            return false;
        }
        
        $x3 = self::calcExpr($params[4], $contex);
        if ($x3 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[4] . '"';
            
            return false;
        }
        
        $y3 = self::calcExpr($params[5], $contex);
        if ($y3 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[5] . '"';
            
            return false;
        }
        
        if (isset($params[6])) {
            $d = self::calcExpr($params[6], $contex);
            if ($d === self::CALC_ERROR) {
                $error = 'Грешка при изчисляване на: "' . $params[4] . '"';
                
                return false;
            }
        }
        
        self::drawMeasureAngle($svg, $x1, $y1, $x2, $y2, $x3, $y3);
    }
    
    // $caption, $val
    public static function cmd_Info($params, &$svg, &$contex, &$error)
    {
        $y = self::calcExpr($params[1], $contex);
        
        if ($y === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[1] . '"';
            
            return false;
        }
        
        $svg->info[$params[0]] = $y;
    }


    // $caption, $val
    public static function cmd_ErrorMsg($params, &$svg, &$contex, &$error)
    {
        $y = self::calcExpr($params[1], $contex);

        if ($y === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[1] . '"';

            return false;
        }

        $svg->errorMsg[$params[0]] = $y;
    }
    
    
    public static function cmd_WriteSizeText($params, &$svg, &$contex, &$error)
    {
        $x = self::calcExpr($params[0], $contex);
        if ($x === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[0] . '"';
            
            return false;
        }
        
        $y = self::calcExpr($params[1], $contex);
        if ($y === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[1] . '"';
            
            return false;
        }
        
        $num1 = self::calcExpr($params[2], $contex);
        if ($y === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[2] . '"';
            
            return false;
        }
        
        $num2 = self::calcExpr($params[3], $contex);
        if ($y === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[3] . '"';
            
            return false;
        }
        
        $text = "{$num1}x{$num2}";
        
        $rotation = self::calcExpr($params[4], $contex);
        if ($rotation === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[4] . '"';
            
            return false;
        }
        
        $textSize = self::calcExpr($params[5], $contex);
        if ($textSize === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[5] . '"';
            
            return false;
        }
        
        $svg->setAttr('font-size', $textSize);
        $svg->setAttr('font-weight', 'bold');
        $svg->setAttr('font-family', 'Verdana');
        $svg->writeText($x, $y, $text, $rotation);
    }
    
    
    public static function cmd_CurveTo($params, &$svg, &$contex, &$error)
    {
        $x1 = self::calcExpr($params[0], $contex);
        if ($x1 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[0] . '"';
            
            return false;
        }
        
        $y1 = self::calcExpr($params[1], $contex);
        if ($y1 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[1] . '"';
            
            return false;
        }
        
        $x2 = self::calcExpr($params[2], $contex);
        if ($x2 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[2] . '"';
            
            return false;
        }
        $y2 = self::calcExpr($params[3], $contex);
        if ($y2 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[3] . '"';
            
            return false;
        }
        
        $x3 = self::calcExpr($params[4], $contex);
        if ($x3 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[4] . '"';
            
            return false;
        }
        
        $y3 = self::calcExpr($params[5], $contex);
        if ($y3 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[5] . '"';
            
            return false;
        }
        
        $abs = trim(strtolower($params[6]));
        
        $svg->curveTo($x1, $y1, $x2, $y2, $x3, $y3, $abs === 'abs');
    }
    
    
    public static function cmd_GetPen($params, &$svg, &$contex, &$error)
    {
        if (isset($params[0])) {
            $pen = draw_Pens::fetch(array("#name = '[#1#]'", ltrim($params[0], '#')));
            
            if (!$pen) {
                $error = 'Липсващ молив: "' . $params[1] . '"';
                
                return false;
            }
            
            if ($pen->color) {
                $svg->setAttr('stroke', $pen->color);
            } else {
                $svg->setAttr('stroke', '#000');
            }
            
            if ($pen->background) {
                $svg->setAttr('fill', $pen->background);
            } else {
                $svg->setAttr('fill', 'none');
            }
            
            if ($pen->thickness) {
                $svg->setAttr('stroke-width', $pen->thickness);
            } else {
                $svg->setAttr('stroke-width', 0);
            }
            
            if ($pen->dasharray) {
                $svg->setAttr('stroke-dasharray', $pen->dasharray);
            } else {
                $svg->setAttr('stroke-dasharray', '');
            }
        } else {
            $error = 'Липсващ параметър за молив';
            
            return false;
        }
    }
    
    
    public static function cmd_MoveTo($params, &$svg, &$contex, &$error)
    {
        $x = self::calcExpr($params[0], $contex);
        if ($x === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[0] . '"';
            
            return false;
        }
        
        
        $y = self::calcExpr($params[1], $contex);
        if ($y === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[1] . '"';
            
            return false;
        }
        
        $svg->closePath(false);
        $svg->startPath();
        $svg->moveTo($x, $y, true);
    }
    
    
    /**
     * Изчертаване на линия
     */
    public static function cmd_LineTo($params, &$svg, &$contex, &$error)
    {
        $x = self::calcExpr($params[0], $contex);
        if ($x === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[0] . '"';
            
            return false;
        }
        
        
        $y = self::calcExpr($params[1], $contex);
        if ($y === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[1] . '"';
            
            return false;
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
        if (!preg_match('/^[a-z][a-z0-9_]{0,64}$/i', $varX)) {
            $error = 'Невалидно име на променлива: "' . $params[0] . '"';
            
            return false;
        }
        $contex->{$varX} = $x;
        
        $varY = ltrim($params[1], '$ ');
        if (!preg_match('/^[a-z][a-zA-Z0-9_]{0,64}$/', $varY)) {
            $error = 'Невалидно име на променлива: "' . $params[1] . '"';
            
            return false;
        }
        $contex->{$varY} = $y;
    }


    /**
     * Изчертаване на елипса
     */
    public static function cmd_DrawEllipse($params, &$svg, &$contex, &$error)
    {
        $x = self::calcExpr($params[0], $contex);

        if ($x === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[0] . '"';

            return false;
        }


        $y = self::calcExpr($params[1], $contex);
        if ($y === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[1] . '"';

            return false;
        }

        $rX = self::calcExpr($params[2], $contex);
        if ($rX === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[2] . '"';

            return false;
        }

        $rY = self::calcExpr($params[3], $contex);
        if ($rY === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[3] . '"';

            return false;
        }

        cad2_Ellipse::draw($svg, $x, $y, $rX, $rY);
    }


    /**
     * Изчертаване на част от елипса
     */
    public static function cmd_DrawPartOfEllipse($params, &$svg, &$contex, &$error)
    {
        $x = self::calcExpr($params[0], $contex);

        if ($x === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[0] . '"';

            return false;
        }


        $y = self::calcExpr($params[1], $contex);
        if ($y === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[1] . '"';

            return false;
        }

        $rX = self::calcExpr($params[2], $contex);
        if ($rX === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[2] . '"';

            return false;
        }

        $rY = self::calcExpr($params[3], $contex);
        if ($rY === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[3] . '"';

            return false;
        }

        $angle1 = self::calcExpr($params[4], $contex);
        if ($angle1 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[4] . '"';

            return false;
        }

        $angle2 = self::calcExpr($params[5], $contex);
        if ($angle2 === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[5] . '"';

            return false;
        }

        cad2_Ellipse::drawArcOfEllipse($svg, $x, $y, $rX, $rY, deg2rad($angle1), deg2rad($angle2));
    }


    /**
     * Изчертаване на дъга
     */
    public static function cmd_ArcTo($params, &$svg, &$contex, &$error)
    {
        $x = self::calcExpr($params[0], $contex);
        
        if ($x === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[0] . '"';
            
            return false;
        }
        
        
        $y = self::calcExpr($params[1], $contex);
        if ($y === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[1] . '"';
            
            return false;
        }
        
        $r = self::calcExpr($params[2], $contex);
        if ($r === self::CALC_ERROR) {
            $error = 'Грешка при изчисляване на: "' . $params[2] . '"';
            
            return false;
        }
        
        $abs = trim(strtolower($params[2]));
        
        $svg->arcTo($x, $y, $r, $abs === 'abs');
    }
    
    
    public static function on_AfterPrepareSingle($mvc, $res, $data)
    {
        // Инстанция на класа
        $inst = cls::get('core_TableView');
        
        // Вземаме таблицата с попълнени данни
        $fields = 'createdOn=Дата, createdBy=От, Version=Версия';
        $data->row->CHANGE_LOG = $inst->get(change_Log::prepareLogRow($mvc->className, $data->rec->id), $fields);
        
        // скрипта да се скрит с бутон за показване, ако потребителя е с по-малко права
        if (!haveRole('drawMaster, ceo, admin')) {
            $data->row->hiddenScript = "<a href=\"javascript:toggleDisplay('script-{$data->row->id}')\"  style=\"display: block; margin-bottom: 10px; background-repeat: no-repeat; font-weight:bold; background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn">Покажи скрипт</a>';
            $data->row->hiddenScript .= "<div style='margin:10px 0; display:none' id='script-{$data->row->id}'>";
            $data->row->hiddenEndScript = '</div>';
        }
        
        // скрит блок с метаинформация
        $data->row->hiddenMeta = "<a href=\"javascript:toggleDisplay('meta-{$data->row->id}')\"  style=\"display: block; margin: 10px 0; background-repeat: no-repeat; font-weight:bold; background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn">Покажи версии</a>';
        $data->row->hiddenMeta .= "<div style='margin:10px 0; display:none' id='meta-{$data->row->id}'>";
        $data->row->hiddenEndMeta = '</div>';
        
        $error = '';
        $contex = new stdClass();
        
        $cmd = Request::get('Cmd');
        
        if (is_array($cmd) && $cmd['pdf']) {
            $canvas = cls::get('cad2_PdfCanvas');
        } else {
            $canvas = cls::get('cad2_SvgCanvas');
        }
        
        $canvas->setPaper(210, 297, 0, 0, 0, 0);
        
        $res = self::runScript($data->rec->script, $canvas, $contex, $error);
        
        
        if (is_array($cmd) && $cmd['pdf']) {
            $fileContent = $canvas->render();
            $fileName = trim(fileman_Files::normalizeFileName($data->rec->name), '_');
            header('Content-type: application/pdf');
            header("Content-Disposition: attachment; filename={$fileName}.pdf");
            header('Pragma: no-cache');
            header('Expires: 0');
            echo $fileContent;
            
            shutdown();
        }
        
        if (is_array($cmd) && $cmd['svg']) {
            $fileContent = $canvas->render();
            $fileName = trim(fileman_Files::normalizeFileName($data->rec->name), '_');
            header('Content-type: application/svg');
            header("Content-Disposition: attachment; filename={$fileName}.svg");
            header('Pragma: no-cache');
            header('Expires: 0');
            echo $fileContent;
            
            shutdown();
        }
        
        
        if ($res === false) {
            $data->error = $error;
        }
        
        $data->contex = $contex;
        $data->canvas = $canvas;
        $data->form = self::prepareForm($data->rec->script, $error);
        if ($data->form === false) {
            $data->error .= "\n" . $error;
        }
    }
    
    
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        $tempScript = str_replace('<br>', "\n", $data->row->script);
        
        // Обвиваме съдъжанието на файла в код
        $code = "<div class='richtext'><pre class='rich-text code php'><code>{$tempScript}</code></pre></div>";
        
        
        $tpl2 = hljs_Adapter::enable('github');
        $tpl2->append($code, 'CODE');
        
        $tpl->append($tpl2);
        $tpl->append("state-{$data->rec->state}", 'STATE_CLASS');
        if ($data->form) {
            $tpl->append($data->form->renderHtml(), 'DETAILS');
        }
        
        if (!$data->error && !$data->canvas->errorMsg) {
            $tpl->append($data->canvas->render(), 'DETAILS');
        } else {
            $tpl->append("<h3 style='color:red;'>" . $data->error . '</h3>', 'DETAILS');
        }
        
        if ($data->canvas->info) {
            foreach ($data->canvas->info as $c => $v) {
                $tpl->append("<div>${c} = <b>${v}</b></div>", 'INFO_BLOCK');
            }
        }

        if ($data->canvas->errorMsg) {
            foreach ($data->canvas->errorMsg as $c => $v) {
                $tpl->append("<div class='errorMsg'>${c} = <b>${v}</b></div>", 'ERROR_BLOCK');
            }
        }
    }
    
    
    /**
     * За да не могат да се изтриват активните скриптове
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete') {
            if ($rec && $rec->state != 'closed') {
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
        foreach ((array) $contex as $varId => $value) {
            if ($value < 0) {
                $value = "({$value})";
            }
            $ctx['$' . ltrim($varId, '$')] = $value;
        }
        
        uksort($ctx, 'str::sortByLengthReverse');
        
        // Заместваме променливите и индикаторите
        $expr = strtr($expr, $ctx);
        
        if (str::prepareMathExpr($expr) === false) {
            $res = self::CALC_ERROR;
        } else {
            $res = str::calcMathExpr($expr, $success);
            
            if ($success === false) {
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
        while ($rec = $cmdQuery->fetch("#designId = {$designId}")) {
            $name = '$' . ltrim($rec->varId, '$');
            $res[$name] = $name;
        }
        
        return $res;
    }
    
    
    /**
     * Изчертава оразмерителна линия
     */
    public static function drawMeasureLine($svg, $Ax, $Ay, $Bx, $By, $dist = 1, $measureText = null)
    {
        $svg->setAttr('stroke', '#0000fe');
        $svg->setAttr('stroke-width', '0.1');
        $svg->setAttr('stroke-dasharray', '');
        $svg->setAttr('stroke-opacity', '1');
        $svg->setAttr('font-size', 66);
        $svg->setAttr('font-weight', 'bold');
        $svg->setAttr('font-family', 'Arial, Sans-serif');
        cad2_MeasureLine::draw($svg, $Ax, $Ay, $Bx, $By, $dist * 6, $measureText);
    }
    
    
    /**
     * Пресмята ъгъла ABC
     */
    public static function drawMeasureAngle($svg, $Ax, $Ay, $Bx, $By, $Cx, $Cy)
    {
        cad2_MeasureAngle::draw($svg, $Ax, $Ay, $Bx, $By, $Cx, $Cy);
    }
    
    
    /**
     * Задава стила на молива за оразмерителните линии
     */
    public function setMeasureAttr($svg)
    {
        if ($svg->p['view'] != 'preview') {
            $svg->setAttr('stroke', '#0000fe');
            $svg->setAttr('stroke-width', '0.1');
            $svg->setAttr('font-size', 40);
            $svg->setAttr('stroke-dasharray', '');
            $svg->setAttr('stroke-opacity', '1');
        }
    }
    
    
    public static function prepareForm($script, &$error)
    {
        $sArr = self::parseScript($script);
        $form = cls::get('core_Form');
        
        foreach ($sArr as $parsedLine) {
            list($cmd, $params, $l) = $parsedLine;
            if ($cmd === 'input') {
                $params = self::parseParams($params);
                if (countR($params) != 3 && countR($params) != 2) {
                    $error = "Очакват се два или три аргумента: {$l}";
                    
                    return false;
                }
                $caption = $params[2] ? $params[2] : $params[0];
                $varId = ltrim($params[0], '$');
                
                // Проверка за валидно име
                if (!preg_match('/^[a-z][a-zA-Z0-9_]{0,64}$/', $varId)) {
                    $error = "Невалидно име на входен параметър: \${$varId}";
                    
                    return false;
                }
                
                // Проверка за дублиране
                if ($form->fields[$varId]) {
                    $error = "Повторение на входен параметър: \${$varId}";
                    
                    return false;
                }
                
                $form->FLD($varId, 'float', 'silent,caption=' . trim($caption));
                $form->setDefault($varId, trim($params[1]));
            }
        }
        
        $form->method = 'GET';
        
        $form->input(null, 'silent');
        
        $form->toolbar->addSbBtn('Обнови', 'default', false, 'ef_icon=img/16/arrow_refresh.png');
        $form->toolbar->addSbBtn('SVG', 'svg', false, 'ef_icon=fileman/icons/16/svg.png');
        $form->toolbar->addSbBtn('PDF', 'pdf', false, 'ef_icon=fileman/icons/16/pdf.png');
        
        $form->title = 'Параметри на чертежа';
        
        return $form;
    }
    
    
    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Сортиране на записите по num
        $data->query->orderBy('name');
    }
}
