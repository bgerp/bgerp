<?php


/**
 * Мениджър за документ обиращ корекциите от закръгляния
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_RatesDifferences extends core_Master
{
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'acc_TransactionSourceIntf=acc_transaction_RateDifferences, doc_DocumentIntf';


    /**
     * Заглавие на мениджъра
     */
    public $title = 'Курсови разлики';


    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'acc_plg_Contable, doc_DocumentPlg, acc_plg_DocumentSummary, deals_plg_SaveValiorOnActivation';

    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'acc_RatesDifferencesDetails';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title= Документ, reason, valior';


    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';


    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Курсови разлики';


    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/blog.png';


    /**
     * Абревиатура
     */
    public $abbr = 'Diff';


    /**
     * Кой може да редактира системните данни
     */
    public $canEditsysdata = 'no_one';


    /**
     * Кой може да пише?
     */
    public $canWrite = 'no_one';


    /**
     * Кой може да го контира?
     */
    public $canConto = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'no_one';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'powerUser';


    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'acc/tpl/RateDifferences.shtml';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Вальор,mandatory');
        $this->FLD('reason', 'varchar(128)', 'caption=Основание,mandatory');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута');
        $this->FLD('dealOriginId', 'key(mvc=doc_Containers,select=id)', 'caption=Документ,mandatory');
        $this->FLD('rate', 'double(decimals=5)', 'caption=Курс,mandatory');
        $this->FLD('data', 'blob(serialize, compress)', 'caption=Допълнително->Условия (Кеширани),input=none');
        $this->FLD('total', 'double(decimals=2)', 'caption=Общо,mandatory');
    }


    public static function create($threadId, $currencyCode, $rate, $reason = null)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        expect($firstDoc->isInstanceOf('sales_Sales') || $firstDoc->isInstanceOf('purchase_Purchases'));

        $isCreated = true;
        $rec = (object)array('reason' => $reason, 'threadId' => $threadId, 'currencyId' => $currencyCode, 'rate' => $rate, 'dealOriginId' => $firstDoc->fetchField('containerId'));
        if($exId = static::fetchField("#threadId = {$threadId} AND #state = 'active'")){
            $rec->id = $exId;
            $isCreated = false;
        }

        core_Users::forceSystemUser();
        $id = static::save($rec);
        core_Users::cancelSystemUser();

        if($isCreated){
            static::conto($id);
        } else {
            $containerId = static::fetchField($rec->id, 'containerId');
            acc_Journal::reconto($containerId);
        }

        return $id;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'reject' && isset($rec)){
            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
            $requiredRoles = $firstDoc->getInstance()->getRequiredRoles('reject', $firstDoc->fetch());
        }

        if($action == 'restore' && isset($rec)){
            $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
            $requiredRoles = $firstDoc->getInstance()->getRequiredRoles('restore', $firstDoc->fetch());
            if($mvc->fetchField("#threadId = {$rec->threadId} AND #state = 'active'")){
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * Документа винаги може да се активира, дори и да няма детайли
     */
    public static function canActivate($rec)
    {
        return true;
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = null)
    {
        $row->dealOriginId = doc_Containers::getDocument($rec->dealOriginId)->getLink(0);
        $row->baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->valior);

        $row->total = ht::styleIfNegative($row->total, $rec->total);
        if(is_array($rec->data)){
            $displayRes = "<table style='width:300px'>";
            if(countR($rec->data)){
                foreach ($rec->data as $containerId => $amountCorrected){
                    $doc = doc_Containers::getDocument($containerId);
                    $docLink = $doc->getLink(0)->getContent();
                    $amountCorrectedVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($amountCorrected);
                    $amountCorrectedVerbal = ht::styleIfNegative($amountCorrectedVerbal, $amountCorrected);
                    $displayRes .= "<tr><td>{$docLink}</td> <td style='text-align:right'>{$amountCorrectedVerbal} <span class='cCode'>{$row->baseCurrencyCode}</span></td></tr>";
                }
                $displayRes .= "</table>";
                $row->data = $displayRes;
            } else {
                $row->data = "<b>" . tr("Няма") . "</b>";
            }
        }
    }


    /**
     * Кой е дефолтния вальор на документа
     *
     * @param $rec
     * @return date|null $valior
     */
    public function getDefaultValior($rec)
    {
        return dt::today();
    }
}