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
    public $loadList = 'plg_Created, plg_Rejected, plg_RowTools2, plg_State2, plg_Rejected, sens2_Wrapper, plg_Search';
    
    
    /**
     * Заглавие
     */
    public $title = 'Скриптове';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'sensMaster';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo, sens, admin';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'sensMaster';
    
    
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
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'name, state';

    
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
     * Стартира ръчно скрипта
     */
    public function act_Run()
    {
        $this->requireRightFor('single');

        $id = Request::get('id', 'int');
        
        $rec = self::fetch($id);
        $this->requireRightFor('single', $rec);

        sens2_script_Actions::runScript($id);

        return new Redirect(array($this, 'Single', $id));
    }
    
    
    /**
     * След подготовка на тулбара за единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $data->toolbar->addBtn('Старт', array($mvc, 'Run', $data->rec->id), 'ef_icon=img/16/lightning.png, title=Ръчно изпълнение');
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
     * Връща контекста от променливи и индикатори за дадения скрипт
     */
    public static function getContext($scriptId)
    {
        // Намираме и сортираме контекста
        $context = sens2_Indicators::getContex();
        $context += sens2_script_DefinedVars::getContex($scriptId);
        uksort($context, 'str::sortByLengthReverse');
        
        return $context;
    }


    /**
     * Подредба по номера
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#order', 'ASC');
    }

    
    /**
     * Изчислкяване на числов израз. Могат да участват индикаторите и променливите от даден скрипт
     */
    public static function calcExpr($expr, $scriptId, &$error = null)
    {
        // Вземаме контекста
        $context = self::getContext($scriptId);
        
        if ((str::prepareMathExpr($expr, $context)) === false) {
            $res = self::CALC_ERROR;
        } else {
            $res = str::calcMathExpr($expr, $success, $error);
            
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
        
        $value = self::calcExpr($expr, $scriptId, $error);
        
        if ($value === self::CALC_ERROR) {
            $style = 'border-bottom:dashed 1px red;';
        } else {
            $style = 'border-bottom:solid 1px transparent;';
        }
        
        $expr = strtr($expr, $opts[$scriptId]);

        if($error) {
            $value .= ' ' . $error;
        }

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


    /**
     * Подготвя иконата за единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, $data)
    {
        if (!Mode::isReadOnly()) {
            
            // Изчистваме нотификацията за събуждане
            $url = array($mvc, 'single', $data->rec->id, 'order' => Request::get('order', 'int'));
 
            bgerp_Notifications::clear($url);
        }
    }


    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }


    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        $rec = $mvc->fetchRec($rec);
        if (!isset($res)) {
            $res = plg_Search::getKeywords($mvc, $rec);
        }

        foreach ($mvc->details as $det) {
            $detInst = cls::get($det);
            $dQuery = $detInst::getQuery();
            $dQuery->where("#{$detInst->masterKey} = '{$rec->id}'");
            while ($dRec = $dQuery->fetch()) {

                $res .= ' ' . $detInst->getSearchKeywords($dRec);
            }
        }
    }


    /**
     *
     *
     * @param $mvc
     * @param $res
     * @param $id
     * @return void
     * @throws core_exception_Break
     */
    public static function on_AfterUpdateMaster($mvc, &$res, $id)
    {
        if ($id) {
            $rec = $mvc->fetchRec($id);

            plg_Search::forceUpdateKeywords($mvc, $rec);
        }
    }
}
