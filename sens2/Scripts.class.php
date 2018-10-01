<?php


/**
 * Мениджър на логически блокове за управление на контролери
 *
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_Scripts extends core_Master
{
    const CALC_ERROR = 'Грешка при изчисляване';
    
    
    public $oldClassName = 'sens2_Logics';
    
    
    /**
     * Необходими плъгини
     */
    public $loadList = 'plg_Created, plg_Rejected, plg_RowTools2, plg_State2, plg_Rejected, sens2_Wrapper';
    
    
    /**
     * Заглавие
     */
    public $title = 'Скриптове';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'ceo,sens,admin';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo, sens, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,sens';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,sens';
    
    
    /**
     * Детайли на блока
     */
    public $details = 'sens2_script_DefinedVars,sens2_script_Actions';
    
    
    /**
     * Полето "Наименование" да е хипервръзка към единичния изглед
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Заглавие в единичния изглед
     */
    public $singleTitle = 'Скрипт';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/script.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'order,name,state,lastRun';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('order', 'int', 'caption=№');
        $this->FLD('name', 'varchar(255)', 'caption=Наименование, mandatory,notConfig');
        $this->FLD('lastRun', 'datetime(format=smartTime)', 'caption=Последно,input=none');
        $this->FLD('state', 'enum(active=Активно,closed=Затворено)', 'caption=Състояние, input=none,notConfig');
        
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
     * Стартира всички скриптове
     */
    public function cron_RunAll()
    {
        $query = self::getQuery();
        $query->orderBy('#order');
        while ($rec = $query->fetch("#state = 'active'")) {
            sens2_script_Actions::runScript($rec->id);
            $rec->lastRun = dt::verbal2mysql();
            self::save($rec);
        }
    }
    
    
    /**
     * Изчислкяване на числов израз. Могат да участват индикаторите и променливите от даден скрипт
     */
    public static function calcExpr($expr, $scriptId)
    {
        // Намираме и сортираме контекста
        $contex = sens2_Indicators::getContex();
        $contex += sens2_script_DefinedVars::getContex($scriptId);
        uksort($contex, 'str::sortByLengthReverse');
        
        // Заместваме променливите и индикаторите
        $expr = strtr($expr, $contex);
        
        if (str::prepareMathExpr($expr) === false) {
            $res = self::CALC_ERROR;
        } else {
            $res = str::calcMathExpr($expr, $success);
            
            if ($success === false) {
                $res = self::CALC_ERROR;
            }
        }
        
        // Конвертираме булевите стойности, към числа
        if ($value === false) {
            $value = 0;
        } elseif ($value === true) {
            $value = 1;
        }
        
        return $res;
    }
    
    
    /**
     * Проверява за коректност израз и го форматира.
     */
    public static function highliteExpr($expr, $scriptId)
    {
        static $opts = array();
        
        if (!$opts[$scriptId]) {
            $opts = array();
            $inds = sens2_Indicators::getContex();
            
            foreach ($inds as $name => $value) {
                $opts[$scriptId][$name] = "<span style='color:blue;'>{$name}</span>";
            }
            $vars = sens2_script_DefinedVars::getContex($scriptId);
            foreach ($vars as $name => $value) {
                $opts[$scriptId][$name] = "<span style='color:blue;'>{$name}</span>";
            }
        }
        
        $value = self::calcExpr($expr, $scriptId);
        
        if ($value === self::CALC_ERROR) {
            $style = 'border-bottom:dashed 1px red;';
        } else {
            $style = 'border-bottom:solid 1px transparent;';
        }
        
        $expr = strtr($expr, $opts[$scriptId]);
        
        $expr = "<span style='{$style}' title='{$value}'>{$expr}</span>";
        
        return $expr;
    }
    
    
    /**
     * За да не могат да се изтриват активните скриптове
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'delete') {
            if ($rec->state != 'closed') {
                $res = 'no_one';
            }
        }
    }
}
