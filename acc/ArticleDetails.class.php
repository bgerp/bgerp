<?php


/**
 * Мениджър на детайли на Мемориален ордер
 *
 *
 * @category  bgerp
 * @package   acc
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acc_ArticleDetails extends doc_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Мемориален ордер';
    
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = 'Счетоводна статия';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'articleId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, acc_Wrapper, plg_RowNumbering, plg_StyleNumbers, plg_AlignDecimals2, doc_plg_HidePrices,
        Accounts=acc_Accounts, Lists=acc_Lists, Items=acc_Items, plg_SaveAndNew, plg_PrevAndNext';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'debitAccId, debitQuantity=Дебит->К-во, debitPrice=Дебит->Цена, creditAccId, creditQuantity=Кредит->К-во, creditPrice=Кредит->Цена, amount=Сума, reason=Информация';
    
    
    /**
     * Активен таб
     */
    public $currentTab = 'Мемориални ордери';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,acc';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,acc';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,acc';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,acc';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'debitQuantity, debitPrice, creditQuantity, creditPrice, amount';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'reason';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('articleId', 'key(mvc=acc_Articles)', 'column=none,input=hidden,silent');
                
        $this->FLD(
            
            'debitAccId',
            
            'acc_type_Account(remember,allowEmpty)',
            'caption=Дебит->Сметка и пера,mandatory,removeAndRefreshForm=debitEnt1|debitEnt2|debitEnt3|debitQuantity|debitPrice|amount,tdClass=articleCell,silent'
        
        );
        $this->FLD('debitEnt1', 'acc_type_Item(select=titleNum,allowEmpty)', 'caption=Дебит->перо 1,remember,input=none');
        $this->FLD('debitEnt2', 'acc_type_Item(select=titleNum,allowEmpty)', 'caption=Дебит->перо 2,remember,input=none');
        $this->FLD('debitEnt3', 'acc_type_Item(select=titleNum,allowEmpty)', 'caption=Дебит->перо 3,remember,input=none');
        $this->FLD('debitQuantity', 'double', 'caption=Дебит->Количество');
        $this->FLD('debitPrice', 'double(minDecimals=2)', 'caption=Дебит->Цена');
        
        $this->FLD(
            
            'creditAccId',
            
            'acc_type_Account(remember,allowEmpty)',
            'caption=Кредит->Сметка и пера,mandatory,tdClass=articleCell,removeAndRefreshForm=creditEnt1|creditEnt2|creditEnt3|creditQuantity|creditPrice|amount,silent'
        
        );
        $this->FLD('creditEnt1', 'acc_type_Item(select=titleNum,allowEmpty)', 'caption=Кредит->перо 1,remember,input=none');
        $this->FLD('creditEnt2', 'acc_type_Item(select=titleNum,allowEmpty)', 'caption=Кредит->перо 2,remember,input=none');
        $this->FLD('creditEnt3', 'acc_type_Item(select=titleNum,allowEmpty)', 'caption=Кредит->перо 3,remember,input=none');
        $this->FLD('creditQuantity', 'double', 'caption=Кредит->Количество');
        $this->FLD('creditPrice', 'double(minDecimals=2)', 'caption=Кредит->Цена');
        
        $this->FLD('amount', 'double(decimals=2)', 'caption=Оборот->Сума,remember=info');
	
	$this->FLD('reason', 'varchar', 'caption=Допълнителна информация->Забележка');
    }
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'amount';
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$res)
    {
        $rows = &$res->rows;
        $recs = &$res->recs;
        
        if (countR($recs)) {
            foreach ($recs as $id => $rec) {
                $row = &$rows[$id];
                
                foreach (array('debit', 'credit') as $type) {
                    $ents = '';
                    
                    foreach (range(1, 3) as $i) {
                        $ent = "{$type}Ent{$i}";
                        
                        if ($rec->{$ent}) {
                            $ents .= "<tr><td> <span style='margin-left:10px; font-size: 11px; color: #747474;'>{$i}.</span> <span>{$row->{$ent}}</span></td</tr>";
                        }
                    }
                    
                    if (!empty($ents)) {
                        $row->{"{$type}AccId"} .=
                        "<table class='acc-article-entries'>" .
                        $ents .
                        '</table>';
                    }
                }
            }
        }
    }
    
	    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        // Подсигуряване че запис и нов го има дори и при редакция
        if (isset($data->form->rec->id)) {
            $data->form->toolbar->addSbBtn('Запис и Нов', 'save_n_new', null, array('id' => 'saveAndNew', 'order' => '1', 'ef_icon' => 'img/16/save_and_new.png', 'title' => 'Запиши документа и създай нов'));
        }
    }
	
	
	    
    /**
     * Логика за определяне къде да се пренасочва потребителския интерфейс.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareRetUrl($mvc, $data)
    {
        if (!isset($data->form) || !$data->form->isSubmitted()) {
            return;
        }
        
        if ($data->form->cmd == 'save_n_new') {
            $rec = $data->form->rec;


            unset($data->retUrl['id']);
            unset($data->retUrl['packagingId']);
            unset($data->retUrl['editSummary']);
            unset($data->retUrl['editBatch']);
            unset($data->retUrl['editQuantity']);
                                    
      }
    }
    
    
    /**
     * След подготовка на формата за добавяне/редакция
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $form->setField('debitAccId', 'caption=Дебит->Сметка');
        $form->setField('creditAccId', 'caption=Кредит->Сметка');
        
        if (isset($rec->id)) {
            $form->setReadOnly('debitAccId');
            $form->setReadOnly('creditAccId');
        }
        
        if (isset($rec->debitAccId)) {
            $debitAcc = acc_Accounts::getAccountInfo($rec->debitAccId);
        } else {
            $form->setField('debitQuantity', 'input=none');
            $form->setField('debitPrice', 'input=none');
            $form->setField('amount', 'input=none');
        }
        
        if (isset($rec->creditAccId)) {
            $creditAcc = acc_Accounts::getAccountInfo($rec->creditAccId);
        } else {
            $form->setField('creditQuantity', 'input=none');
            $form->setField('creditPrice', 'input=none');
            $form->setField('amount', 'input=none');
        }
        
        $dimensional = $debitAcc->isDimensional || $creditAcc->isDimensional;
        
        $quantityOnly = ($debitAcc->rec->type == 'passive' && $debitAcc->rec->strategy) ||
        ($creditAcc->rec->type == 'active' && $creditAcc->rec->strategy);
        
        $masterRec = $mvc->Master->fetch($form->rec->articleId);
        
        // Кои са корицата на папката на ордера, и първия докумен в нишката му
        $cover = doc_Folders::getCover($masterRec->folderId);
        $firstDoc = doc_Threads::getFirstDocument($masterRec->threadId);
        
        foreach (array('debit' => 'Дебит', 'credit' => 'Кредит') as $type => $caption) {
            $acc = ${"{$type}Acc"};
            if (!isset($acc)) {
                continue;
            }

            foreach ($acc->groups as $i => $list) {
                if (!$list->rec->itemsCnt) {
                    redirect(array('acc_Items', 'list', 'listId' => $list->rec->id), false, '|Липсва избор за|* "' . acc_Lists::getVerbal($list->rec, 'name') . '"');
                }

                $form->getField("{$type}Ent{$i}")->type->params['lists'] = $list->rec->num;
                $form->setField("{$type}Ent{$i}", "silent,mandatory,input,caption={$caption}->" . $list->rec->name);
                if($list->rec->regInterfaceId == core_Interfaces::fetchByName('currency_CurrenciesAccRegIntf')){
                    $form->setField("{$type}Ent{$i}", "removeAndRefreshForm");
                }

                // Ако може да се избират приключени пера, сетваме параметър в типа на перата
                if ($masterRec->useCloseItems == 'yes') {
                    $form->getField("{$type}Ent{$i}")->type->params['showAll'] = true;
                }
                
                // Ако номенклатурата има интерфейс
                if (!empty($list->rec->regInterfaceId)) {
                    
                    // Ако корицата има итнерфейса на номенклатурата и е перо, слагаме я по дефолт
                    if ($cover->haveInterface($list->rec->regInterfaceId)) {
                        if ($coverClassId = $cover->getInstance()->getClassId()) {
                            if ($itemId = acc_Items::fetchItem($coverClassId, $cover->that)->id) {
                                $form->setDefault("{$type}Ent{$i}", $itemId);
                            }
                        }
                    }
                    
                    // Ако първия документ има итнерфейса на номенклатурата и е перо, слагаме го по дефолт
                    if ($firstDoc->haveInterface($list->rec->regInterfaceId)) {
                        if ($docClassId = $firstDoc->getInstance()->getClassId()) {
                            if ($itemId = acc_Items::fetchItem($docClassId, $firstDoc->that)->id) {
                                $form->setDefault("{$type}Ent{$i}", $itemId);
                            }
                        }
                    }
                }
                
                // Ако номенклатурата е размерна и ще може да се въвеждат цени
                if ($list->rec->isDimensional == 'yes' && !$quantityOnly) {
                    
                    // Инпутване на размерното перо ако е в рекуеста
                    $item = Request::get("{$type}Ent{$i}", 'acc_type_Item');
                    $form->setDefault("{$type}Ent{$i}", $item);
                    
                    // И перото е попълнено и е от номенклатура валута
                    if (isset($rec->{"{$type}Ent{$i}"})) {
                        $itemRec = acc_Items::fetch($rec->{"{$type}Ent{$i}"});
                        
                        // Ако перото е на валута
                        if ($itemRec->classId == currency_Currencies::getClassId()) {
                            $form->setField("{$type}Ent{$i}", "removeAndRefreshForm={{$type}Price}");
                            
                            // Задаваме курса към основната валута за дефолт цена
                            $currencyCode = currency_Currencies::getCodeById($itemRec->objectId);
                            $rate = currency_CurrencyRates::getRate($masterRec->valior, $currencyCode, null);
                            $form->setDefault("{$type}Price", $rate);
                            $form->setField("{$type}Ent{$i}", "removeAndRefreshForm={$type}Price");
                            if($currencyCode == acc_Periods::getBaseCurrencyCode($masterRec->valior)){
                                $form->setReadOnly("{$type}Price");
                                $form->setField("{$type}Price", array('hint' => 'Цената на основната валута за периода, трябва да е винаги фиксирана|*!'));
                            }
                        }
                    }
                }
            }
            
            if (!$acc->isDimensional) {
                if(!haveRole('debug')){
                    $form->setField("{$type}Quantity", 'input=none');
                    $form->setField("{$type}Price", 'input=none');
                }
            }
            
            if ($quantityOnly) {
                if(!haveRole('debug')){
                    $form->setField("{$type}Price", 'input=none');
                    $form->setField("{$type}Quantity", 'mandatory');
                }
            }
        }
        
        if ($quantityOnly) {
            if(!haveRole('debug')){
                $form->setField('amount', 'input=none');
            }
        }
        
        if (!$dimensional && !$quantityOnly) {
            $form->setField('amount', 'mandatory');
        }
        
        // Добавя списък с предложения за счетоводната операция
        $reasonSuggestions = array('' => '');
        $oQuery = acc_Operations::getQuery();
        while ($oRec = $oQuery->fetch()) {
            $reasonSuggestions[$oRec->title] = $oRec->title;
        }
        $form->setSuggestions('reason', $reasonSuggestions);
    }
    
    
    /**
     * След изпращане на формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        if (!$form->isSubmitted()) {
            
            return;
        }
        
        $rec = $form->rec;
        
        $accs = array(
            'debit' => acc_Accounts::getAccountInfo($rec->debitAccId),
            'credit' => acc_Accounts::getAccountInfo($rec->creditAccId),
        );
        
        $quantityOnly = ($accs['debit']->rec->type == 'passive' && $accs['debit']->rec->strategy) ||
        ($accs['credit']->rec->type == 'active' && $accs['credit']->rec->strategy);
        
        if ($quantityOnly) {
            
            /**
             * @TODO да се провери, че debitQuantity == creditQuantity в случай, че размерните
             * аналитичности на дебит и кредит сметките са едни и същи.
             */
        } else {
            foreach ($accs as $type => $acc) {
                if ($acc->isDimensional && !isset($rec->amount)) {
                    if (isset($rec->{"{$type}Price"}, $rec->{"{$type}Quantity"})) {
                        $rec->amount = $rec->{"{$type}Price"} * (!empty($rec->{"{$type}Quantity"}) ? $rec->{"{$type}Quantity"} : 1);
                    }
                }
            }
            
            foreach ($accs as $type => $acc) {
                if ($acc->isDimensional) {
                    
                    /**
                     * @TODO За размерни сметки: проверка дали са въведени поне два от трите оборота.
                     * Изчисление на (евентуално) липсващия оборот.
                     */
                    $nEmpty = (int) !isset($rec->{"{$type}Quantity"}) +
                    (int) !isset($rec->{"{$type}Price"}) +
                    (int) !isset($rec->amount);
                    
                    if ($nEmpty > 1) {
                        $form->setError("{$type}Quantity, {$type}Price, amount", 'Поне два от оборотите трябва да бъдат попълнени');
                    } else {
                        
                        /**
                         * Изчисление на {$type}Amount:
                         *
                         * За размерни сметки: {$type}Amount = {$type}Quantity & {$type}Price
                         * За безразмерни сметки: {$type}Amount = amount
                         *
                         */
                        switch (true) {
                            case !isset($rec->{"{$type}Quantity"}):
                            $rec->{"{$type}Quantity"} = (!empty($rec->{"{$type}Price"})) ? $rec->amount / $rec->{"{$type}Price"} : 0;
                            break;
                            case !isset($rec->{"{$type}Price"}):
                            $rec->{"{$type}Price"} = (!empty($rec->{"{$type}Quantity"})) ? $rec->amount / $rec->{"{$type}Quantity"} : 0;
                            break;
                            case !isset($rec->amount):
                            $rec->amount = $rec->{"{$type}Price"} * (!empty($rec->{"{$type}Quantity"}) ? $rec->{"{$type}Quantity"} : 1);
                            break;
                        }
                        
                        $rec->{"{$type}Amount"} = $rec->amount;
                    }
                    
                    if (!empty($rec->{"{$type}Price"}) && !empty($rec->{"{$type}Quantity"}) && trim($rec->amount) != trim($rec->{"{$type}Price"} * $rec->{"{$type}Quantity"})) {
                        $form->setError("{$type}Quantity, {$type}Price, amount", 'Невъзможни стойности на оборотите');
                    }
                } else {
                    $rec->{"{$type}Amount"} = $rec->amount;
                }
            }
            
            
            /**
             * Проверка дали debitAmount == debitAmount
             */
            if ($rec->debitAmount != $rec->creditAmount) {
                $form->setError('debitQuantity, debitPrice, creditQuantity, creditPrice, amount', 'Дебит и кредит страните са различни');
            }
        }
    }
    
    
    /**
     * Преди изтриване на запис
     */
    protected static function on_BeforeDelete($mvc, &$res, &$query, $cond)
    {
        $_query = clone($query);
        $query->notifyMasterIds = array();
        
        while ($rec = $_query->fetch($cond)) {
            $query->notifyMasterIds[$rec->{$mvc->masterKey}] = true;
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if (($action == 'edit' || $action == 'delete' || $action == 'add') && isset($rec)) {
            $articleState = acc_Articles::fetchField($rec->articleId, 'state');
            
            if ($articleState != 'draft') {
                $res = 'no_one';
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        foreach (array('debitEnt1', 'debitEnt2', 'debitEnt3', 'creditEnt1', 'creditEnt2', 'creditEnt3') as $fld) {
            if (isset($rec->{$fld})) {
                $row->{$fld} = acc_Items::getVerbal($rec->{$fld}, 'titleLink');
            }
        }
        
        // В кой баланс е влязал записа
        $valior = $mvc->Master->fetchField($rec->articleId, 'valior');
        $balanceValior = acc_Balances::fetch("#fromDate <= '{$valior}' AND '{$valior}' <= #toDate");
        
        // Кешираме линковете към сметките
        foreach (array('debitAccId', 'creditAccId') as $accId) {
            if (!isset(static::$cache['accs'][$rec->{$accId}])) {
                static::$cache['accs'][$rec->{$accId}] = acc_Balances::getAccountLink($rec->{$accId}, $balanceValior);
            }
        }
        
        // Линкове към сметките в баланса
        $row->debitAccId = static::$cache['accs'][$rec->debitAccId];
        $row->creditAccId = static::$cache['accs'][$rec->creditAccId];
        
        if ($rec->reason) {
            $row->reason = "<span style='color:#444;font-size:0.9em;margin-left:5px'>{$row->reason}<span>";
        }
    }
}
