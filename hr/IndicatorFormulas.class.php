<?php


/**
 * Мениджър на Формули на индикаторите
 *
 *
 * @category  bgerp
 * @package   hr
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Формули на индикаторите
 */
class hr_IndicatorFormulas extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Формули на индикатори';


    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Формули на индикатори';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,hrMaster';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,hrMaster';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,hrMaster';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,hrMaster';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'hr_Wrapper,plg_Created,plg_RowTools2,plg_Modified';


    /**
     * Служебна стойност на променливата
     */
    const VALUE_VARIABLE = '$x';


    /**
     * Кеширани формули
     */
    public static $cachedFormulas = false;


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'indicatorNameId,formula,createdOn,createdBy,modifiedOn=Промяна,modifiedBy=Променил';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('indicatorNameId', 'key(mvc=hr_IndicatorNames,select=name,allowEmpty)', 'caption=Вид индикатор,mandatory');
        $this->FLD('formula', 'text(rows=2)', 'caption=Формула,mandatory');

        $this->setDbUnique('indicatorNameId');
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;

        $valueVerbal = static::VALUE_VARIABLE . " (" . tr('Стойност') . ")";
        $formulaContext = array();
        $formulaContext[static::VALUE_VARIABLE] =  (object) array('val' => static::VALUE_VARIABLE, 'search' => $valueVerbal, 'template' => $valueVerbal);
        $form->setSuggestions('formula', $formulaContext);
        $form->setDefault('formula', static::VALUE_VARIABLE);
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        if ($form->isSubmitted()) {
            if(strpos($rec->formula, static::VALUE_VARIABLE) === false){
                $form->setError('formula', 'Формулата трябва да съдържа|*: <b>' . static::VALUE_VARIABLE . '</b>');
            }

            // Проверка все пак формулата дали е валидна
            $readyFormula = strtr($rec->formula, array(hr_IndicatorFormulas::VALUE_VARIABLE => 1));
            if (str::prepareMathExpr($readyFormula) === false) {
                $form->setError('formula', 'Формулата е не може да се изчисли|*!');
            } else {
                $success = null;
                str::calcMathExpr($readyFormula, $success);
                if ($success === false) {
                    $form->setError('formula', 'Формулата е не може да се изчисли|*!');
                }
            }
        }
    }


    /**
     * Кешира всички формули за индикаторите
     *
     * @return void
     */
    public static function cacheAll()
    {
        $query = static::getQuery();
        while($rec = $query->fetch()){
            static::$cachedFormulas[$rec->indicatorNameId] = $rec->formula;
        }
    }
}