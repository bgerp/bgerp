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
    public $interfaces = 'acc_TransactionSourceIntf=acc_transaction_RateDifferences, doc_DocumentIntf,acc_Wrapper';


    /**
     * Заглавие на мениджъра
     */
    public $title = 'Курсови разлики';


    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_Sorting, acc_plg_Contable, acc_Wrapper, doc_DocumentPlg, plg_Select, acc_plg_DocumentSummary, deals_plg_SaveValiorOnActivation';

    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'acc_RatesDifferencesDetails';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior,title= Документ, dealOriginId=Сделка, rate, total=Корекция, lastRecalced=Последно, currencyId';


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
    public $canList = 'ceo,acc';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'powerUser';


    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'acc/tpl/SingleLayoutRateDifferences.shtml';


    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'valior,createdOn,lastRecalced';


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
        $this->FLD('lastRecalced', 'datetime(format=smartTime)', 'caption=Последно преизчисляване,mandatory');
        $this->FLD('oldTotal', 'double(decimals=2)', 'caption=Общо (Преди),mandatory');
        $this->FLD('oldData', 'blob(serialize, compress)', 'caption=Допълнително->Стари условия (Кеширани),mandatory');

        $this->setDbIndex('dealOriginId');
        $this->setDbIndex('lastRecalced');
    }


    /**
     * Създаване на нов документ за курсова разлика в нишката (ако ще коригира суми) или реконтиране на вече наличния
     *
     * @param int $threadId          - ид на нишка
     * @param string $currencyCode   - код на валута
     * @param double $rate           - валутен курс
     * @param string|null $reason    - основание
     * @param bool $updateDealItem   - дали да се маркира перото на сделката че е обновено
     * @return int                   - ид-то на създадения/реконтирания документ
     * @throws core_exception_Expect
     */
    public static function force($threadId, $currencyCode, $rate, $reason = null, $updateDealItem = true)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        expect($firstDoc->isInstanceOf('sales_Sales') || $firstDoc->isInstanceOf('purchase_Purchases'));

        $isCreated = true;
        $rec = (object)array('reason' => $reason, 'threadId' => $threadId, 'currencyId' => $currencyCode, 'rate' => $rate, 'dealOriginId' => $firstDoc->fetchField('containerId'), 'lastRecalced' => dt::now());
        $exId = static::fetchField("#threadId = {$threadId} AND #state = 'active'");
        if(empty($exId)){
            $tData = acc_transaction_RateDifferences::getTransactionData($rate, dt::today(), $threadId);
            if(!countR($tData->entries)) return;
        } else {
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

            // Ако не се иска да се обновява датата на последно използване на перото на сделката да не се
            if(!$updateDealItem){
                $itemRec = acc_Items::fetchItem($firstDoc->getInstance(), $firstDoc->that);
                if(is_object($itemRec)){
                    Mode::push('dontUpdateLastUsedOnItems', array($itemRec->id => $itemRec->id));
                }
            }

            acc_Journal::reconto($containerId);

            if(!$updateDealItem && is_object($itemRec)){
                Mode::pop('dontUpdateLastUsedOnItems');
            }

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

        $row->total = ht::styleNumber($row->total, $rec->total);
        if(isset($fields['-single'])){
            $row->total = "<b>{$row->total}</b>";
        }
        $row->total = "{$row->total} <span class='cCode'>{$row->baseCurrencyCode}</span>";

        if(!empty($rec->oldTotal) && $rec->total != $rec->oldTotal){
            $icon = ($rec->total > $rec->oldTotal) ? 'img/16/arrow_up.png' : 'img/16/arrow_down.png';
            $row->total = ht::createHint($row->total, "Преди|*: {$row->oldTotal}", $icon, false);
        }

        if(is_array($rec->data)){
            $displayRes = "<table style='width:300px'>";
            if(countR($rec->data)){
                foreach ($rec->data as $containerId => $amountCorrected){
                    $doc = doc_Containers::getDocument($containerId);
                    $docLink = $doc->getLink(0)->getContent();
                    $amountCorrectedVerbal = core_Type::getByName('double(decimals=2)')->toVerbal($amountCorrected);
                    $amountCorrectedVerbal = ht::styleIfNegative($amountCorrectedVerbal, $amountCorrected);

                    if(is_array($rec->oldData) && isset($rec->oldData[$containerId])){
                        if($rec->oldData[$containerId] != $amountCorrected){
                            $icon = ($amountCorrected > $rec->oldData[$containerId]) ? 'img/16/arrow_up.png' : 'img/16/arrow_down.png';
                            $amountCorrectedVerbal = ht::createHint($amountCorrectedVerbal, "Преди|*: {$rec->oldData[$containerId]}", $icon, false);
                        }
                    }
                    if(!isset($rec->oldData[$containerId])){
                        $amountCorrectedVerbal = ht::createHint($amountCorrectedVerbal, "Ново", 'img/16/add2-16.png', false);
                    }
                    $displayRes .= "<tr><td>{$docLink}</td> <td style='text-align:right'>{$amountCorrectedVerbal} <span class='cCode'>{$row->baseCurrencyCode}</span></td></tr>";

                }
                $displayRes .= "</table>";
                $row->data = $displayRes;
            } else {
                $row->data = "<b>" . tr("Няма") . "</b>";
            }
        }

        $row->title = $mvc->getLink($rec->id, 0);
    }


    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow_($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();

        $row->title = $this->getRecTitle($rec);
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->recTitle = $row->title;
        $row->state = $rec->state;

        return $row;
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


    /**
     * Реконтиране на валутните разлики по разписание
     */
    public function cron_RecontoActive()
    {
        $dealClasses = array('sales_Sales', 'purchase_Purchases');

        // Извличане на всички активни документи за к.разлики
        $exRecs = array();
        $cQuery = static::getQuery();
        $cQuery->where("#state = 'active'");
        while($cRec = $cQuery->fetch()){
            $exRecs[$cRec->dealOriginId] = $cRec;
        }

        $recontoItems = array();
        $today = dt::today();
        foreach ($dealClasses as $class) {

            // Обикаляне на валутните сделки
            $Class = cls::get($class);
            $dQuery = $Class->getQuery();
            $dQuery->where("#state = 'active' AND #currencyId != 'BGN'");
            $dealRecs = $dQuery->fetchAll();
            $count = countR($dealRecs);

            core_App::setTimeLimit($count * 0.4, false, 300);
            foreach ($dealRecs as $dRec) {

                // Подменя се курса му и се реконтира
                $itemRec = acc_Items::fetchItem($Class, $dRec->id);

                // Ако няма създаден документ за валутни разлики и има платено и НЯМА изчислени разлики няма да се създава
                if (!isset($exRecs[$dRec->containerId])) {
                    if (!empty($dRec->amountPaid)) {
                        $tData = acc_transaction_RateDifferences::getTransactionData($dRec->currencyRate, $today, $dRec->threadId);
                        if (!countR($tData->entries)) continue;
                    }
                } else {
                    // Ако има вече КР в нишката и ще има промяна в общата ѝ сума - само тогава се реконтира
                    $tData = acc_transaction_RateDifferences::getTransactionData($dRec->currencyRate, $today, $dRec->threadId);
                    if(round($tData->amount, 2) == round($exRecs[$dRec->containerId]->total, 2)){
                        continue;
                    }
                }

                try {
                    Mode::push('preventNotifications', true);
                    Mode::push('dontUpdateThread', true);
                    acc_RatesDifferences::force($dRec->threadId, $dRec->currencyId, $dRec->currencyRate, 'Автоматична корекция на курсови разлики', false);
                    Mode::pop('dontUpdateThread');
                    Mode::pop('preventNotifications');
                    if (is_object($itemRec)) {
                        $recontoItems[$itemRec->id] = $itemRec;
                    }
                } catch (acc_journal_Exception $e) {
                    wp($e);
                    $Class->logErr('Грешка при реконтиране', $dRec->id);
                }
            }
        }

        // Форсиране на рекалкулиране на балансите
        cls::get('acc_Balances')->recalc();

        // Нотифициране на сделките да им се опресни статистиката
        foreach ($recontoItems as $itemRec){
            acc_Items::notifyObject($itemRec);
        }
    }


    /**
     * Реализация  на интерфейсния метод ::getThreadState()
     *
     * @param int $id
     * @return NULL|string
     */
    public static function getThreadState($id)
    {
        return null;
    }


    /**
     * След всеки запис в журнала
     */
    protected static function on_AfterSaveJournalTransaction($mvc, $res, $rec)
    {
        $mvc->save_($rec, 'data,total,lastRecalced,valior,oldTotal,oldData');
    }
}
