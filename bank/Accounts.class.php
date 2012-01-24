<?php



/**
 * Банкови сметки
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_Accounts extends core_Manager {
    
    
    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Банкови сметки';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, plg_Rejected';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    var $listFields = 'id, contragent=Контрагент, iban, currencyId, type, bank';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('contragentCls', 'class', 'caption=Контрагент->Клас,mandatory,input=hidden,silent');
        $this->FLD('contragentId', 'int', 'caption=Контрагент->Обект,mandatory,input=hidden,silent');
        $this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,mandatory');
        $this->FLD('type', 'enum(current=Разплащателна,
            deposit=Депозитна,
            loan=Кредитна,
            personal=Персонална,
            capital=Набирателна)', 'caption=Тип,mandatory');
        $this->FLD('iban', 'iban_Type', 'caption=IBAN / №,mandatory');  // Макс. IBAN дължина е 34 символа (http://www.nordea.dk/Erhverv/Betalinger%2bog%2bkort/Betalinger/IBAN/40532.html)
        $this->FLD('bic', 'varchar(16)', 'caption=BIC');
        $this->FNC('title', 'html', 'caption=Наименование');  // Да се смята на on_BeforeSave() ако е празно.
        $this->FLD('bank', 'varchar(64)', 'caption=Банка');
        $this->FLD('comment', 'richtext', 'caption=Информация,width=100%');
        
        // Задаваме индексите и уникалните полета за модела
                $this->setDbIndex('contragentCls,contragentId');
        $this->setDbUnique('iban');
    }
    
    
    /**
     * Изчислява полето 'title'
     */
    function on_CalcTitle($mvc, $rec)
    {
        $cCode = currency_Currencies::fetchField($rec->currencyId, 'code');
        $rec->title = "<span style='border:solid 1px #ccc;background-color:#eee; padding:2px;
        font-size:0.7em;vertical-align:middle;'>{$cCode}</span>&nbsp;";
        $rec->title .= iban_Type::toVerbal($rec->iban);
        
        $rec->title .= ", " . $mvc->getVerbal($rec, 'type');
        
        if($rec->bank) {
            $rec->title .= ", {$rec->bank}";
        }
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $rec = $data->form->rec;
        $Contragents = cls::get($rec->contragentCls);
        expect($Contragents instanceof core_Master);
        $details = arr::make($Contragents->details);
        expect($details['ContragentBankAccounts'] == 'bank_Accounts');
        
        // По подразбиране, валутата е тази, която е в обръщение в страната на контрагента
                $contragentRec = $Contragents->fetch($rec->contragentId);
        
        $countryRec = drdata_Countries::fetch($contragentRec->country);
        $cCode = $countryRec->currencyCode;
        
        $data->form->setDefault('currencyId',   currency_Currencies::fetchField("#code = '{$cCode}'", 'id'));
        
        $contragentTitle = $Contragents->getTitleById($contragentRec->id);
        
        if($rec->id) {
            $data->form->title = 'Редактиране на банкова с-ка на |*' . $contragentTitle;
        } else {
            $data->form->title = 'Нова банкова с-ка на |*' . $contragentTitle;
        }
    }
    
    
    /**
     * След зареждане на форма от заявката. (@see core_Form::input())
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    function on_AfterInputEditForm($mvc, &$form)
    {
        if (!$form->isSubmitted()) {
            return;
        }
        
        $rec = &$form->rec;
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $cMvc = cls::get($rec->contragentCls);
        $row->contragent = $cMvc->getTitleById($rec->contragentId);
        
        if($mvc->haveRightFor('single', $rec->contragentId)) {
            $row->contragent = ht::createLink($row->contragent, array($cMvc, 'single', $rec->contragentId, 'ret_url' => TRUE));
        }
    }
    
    
    /**
     * Подготвя данните необходими за рендиране на банковите сметки за даден контрагент
     */
    function prepareContragentBankAccounts($data)
    {
        expect($data->contragentCls = core_Classes::fetchIdByName($data->masterMvc));
        expect($data->masterId);
        $query = $this->getQuery();
        $query->where("#contragentCls = {$data->contragentCls} AND #contragentId = {$data->masterId}");
        
        while($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = $data->rows[$rec->id] = $this->recToVerbal($rec);
        }
    }
    
    
    /**
     * Рендира данните на банковите сметки за даден контрагент
     */
    function renderContragentBankAccounts($data)
    {
        $tpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        
        $tpl->append(tr('Банкови сметки'), 'title');
        
        if(count($data->rows)) {
            
            foreach($data->rows as $id => $row) {
                $tpl->append("<div style='padding:3px;white-space:nowrap;font-size:0.9em;'>", 'content');
                
                $tpl->append("{$row->title}", 'content');
                
                if(!Mode::is('printing')) {
                    if($this->haveRightFor('edit', $id)) {
                        // Добавяне на линк за редактиране
                                                $tpl->append("<span style='margin-left:5px;'>", 'content');
                        $url = array($this, 'edit', $id, 'ret_url' => TRUE);
                        $img = "<img src=" . sbf('img/16/edit-icon.png') . " width='16' height='16'>";
                        $tpl->append(ht::createLink($img, $url, FALSE, 'title=' . tr('Редактиране на банкова сметка')), 'content');
                        $tpl->append('</span>', 'content');
                    }
                    
                    if($this->haveRightFor('delete', $id)) {
                        // Добавяне на линк за изтриване
                                                $tpl->append("<span style='margin-left:5px;'>", 'content');
                        $url = array($this, 'delete', $id, 'ret_url' => TRUE);
                        $img = "<img src=" . sbf('img/16/delete-icon.png') . " width='16'  height='16'>";
                        $tpl->append(ht::createLink($img, $url, 'Наистина ли желаете да изтриете сметката?', 'title=' . tr('Изтриване на банкова сметка')), 'content');
                        $tpl->append('</span>', 'content');
                    }
                }
                
                $tpl->append("</div>", 'content');
            }
        } else {
            $tpl->append(tr("Все още няма банкови сметки"), 'content');
        }
        
        if(!Mode::is('printing')) {
            $url = array($this, 'add', 'contragentCls' => $data->contragentCls, 'contragentId' => $data->masterId, 'ret_url' => TRUE);
            $img = "<img src=" . sbf('img/16/add.png') . " width='16' valign=absmiddle  height='16'>";
            $tpl->append(ht::createLink($img, $url, FALSE, 'title=' . tr('Добавяне на нова банкова сметка')), 'title');
        }
        
        return $tpl;
    }
}