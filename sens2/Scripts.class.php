<?php


/**
 * Мениджър на логически блокове за управление на контролери
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens2_Scripts extends core_Master
{

    
    const CALC_ERROR = "Грешка при изчисляване";

    
    public $oldClassName = 'sens2_Logics';
    
    
    /**
     * Необходими плъгини
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools2, plg_State2, plg_Rejected, sens2_Wrapper';
                      
    
    /**
     * Заглавие
     */
    var $title = 'Скриптове';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'ceo,sens,admin';
    
    
    /**
     * Права за запис
     */
    var $canRead = 'ceo, sens, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'debug';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,sens';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,sens';
    

    /**
     * Детайли на блока
     */
    var $details = 'sens2_ScriptDefinedVars,sens2_ScriptActions';

    
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


    /**
     * Описание на модела
     */
    function description()
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
     * @param stdClass $data
     */
    static function on_AfterPrepareEditform($mvc, &$data)
    {
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


    /**
     * Подготвя конфигурационната форма на посочения драйвер
     */
    static function prepareConfigForm($form, $driver)
    {
        $drv = cls::get($driver);
        $drv->prepareConfigForm($form);

        $ports = $drv->getInputPorts();

        if(!$ports) {
            $ports = array();
        }

        expect(is_array($ports));

        foreach($ports as $port => $params) {
            
            $prefix = $params->caption . " ({$port})";

            $form->FLD($port . '_name', 'varchar(32)', "caption={$prefix}->Наименование");
            $form->FLD($port . '_scale', 'varchar(255,valid=sens2_Controllers::isValidExpr)', "caption={$prefix}->Скалиране,hint=Въведете функция на X с която да се скалира стойността на входа");
            $form->FLD($port . '_uom', 'varchar(16)', "caption={$prefix}->Единица");
            $form->FLD($port . '_update', 'time(suggestions=1 min|2 min|5 min|10 min|30 min,uom=minutes)', "caption={$prefix}->Четене през");
            $form->FLD($port . '_log', 'time(suggestions=1 min|2 min|5 min|10 min|30 min,uom=minutes)', "caption={$prefix}->Логване през");
            if(trim($params->uom)) {
                $form->setSuggestions($port . '_uom', arr::combine(array('' => ''), arr::make($params->uom, TRUE)));
            }
        }

        $ports = $drv->getOutputPorts();

        if(!$ports) {
            $ports = array();
        }
        
        foreach($ports as $port => $params) {

            $prefix = $params->caption . " ({$port})";

            $form->FLD($port . '_name', 'varchar(32)', "caption={$prefix}->Наименование");
            $form->FLD($port . '_uom', 'varchar(16)', "caption={$prefix}->Единица");
            if(trim($params->uom)) {
                $form->setSuggestions($port . '_uom', arr::combine(array('' => ''), arr::make($params->uom, TRUE)));
            }
        }
    }


    /**
     * Стартира всички скриптове
     */
    function cron_RunAll()
    {   
        $query = self::getQuery();
        $query->orderBy("#order");
        while($rec = $query->fetch("#state = 'active'")) {
            sens2_ScriptActions::runScript($rec->id);
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
        $contex += sens2_ScriptDefinedVars::getContex($scriptId);
        uksort($contex, "str::sortByLengthReverse");

        // Заместваме променливите и индикаторите
        $expr  = strtr($expr, $contex);
        
        if(str::prepareMathExpr($expr) === FALSE) {
            $res = self::CALC_ERROR;
        } else {
            $res = str::calcMathExpr($expr, $success);

            if($success === FALSE) {
                $res = self::CALC_ERROR;
            }
        }

        // Конвертираме булевите стойности, към числа
        if($value === FALSE) {
            $value = 0;
        } elseif($value === TRUE) {
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

        if(!$opts[$scriptId]) {
            $opts = array();
            $inds = sens2_Indicators::getContex();
            
            foreach($inds as $name => $value) {
                $opts[$scriptId][$name] = "<span style='color:blue;'>{$name}</span>";
            }
            $vars = sens2_ScriptDefinedVars::getContex($scriptId);
            foreach($vars as $name => $value) {
                $opts[$scriptId][$name] = "<span style='color:blue;'>{$name}</span>";
            }
        }
  
        $value = self::calcExpr($expr, $scriptId );

        if($value === self::CALC_ERROR) {
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
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{  
   		if($action == 'delete') {
	    	if($rec->state != 'closed'){
	    		$res = 'no_one';
	    	}
   		}
   		
	}


}
