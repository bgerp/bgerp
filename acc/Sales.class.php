<?php



define(LIST_STORES, 'СКЛАДОВЕ');
define(LIST_CUSTOMERS, 'КЛИЕНТИ');
define(LIST_CASES, 'КАСИ');



/**
 * Документални продажби
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class acc_Sales extends core_Master
{
    
    
    /**
     * Какви интерфайси поддържа този мениджър
     */
    var $interfaces;
    
    
    
    /**
     * Кой линк от главното меню на страницата да бъде засветен?
     */
    var $menuPage = 'Счетоводство';
    
    
    
    /**
     * Заглавие
     */
    var $title = 'Продажби';
    
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, 
        plg_SaveAndNew, acc_Wrapper, Lists=acc_Lists, Accounts=acc_Accounts';
    
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'acc_SaleDetails';
    
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Продажба';
    
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,acc,broker,designer';
    
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,acc';
    
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,acc,broker,designer';
    
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,acc,broker,designer';
    
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,acc';
    
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 300;
    
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,date,storeEntId,customerEntId,moneyEntId,paymentMethodId,
                        paymentTermId,state=Съст.,tools=Пулт';
    
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    
    /**
     * @var acc_Lists
     */
    var $Lists;
    
    
    
    /**
     * @var acc_Accounts
     */
    var $Accounts;
    
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        
        
        /**
         * Перо от първата аналитичност на сметката `customerAccId` - указва клиента по
         * продажбата.
         */
        $this->FLD('customerEntId', 'key(mvc=acc_Items,select=caption,allowEmpty,maxSuggestions=100)', 'caption=Клиент,mandatory');
        
        
        /**
         * Локация (на купувача) за където е предназначена стоката
         */
        $this->FLD('locationId', 'key(mvc=crm_Locations,select=title,allowEmpty,maxSuggestions=100)', 'caption=Клиент->Обект');
        
        
        /**
         * Аналитична счетоводна сметка, чиято първа аналитичност се интерпретира според
         * стойността на полето `paymentMethodId` от мениджъра @see bank_PaymentMethods.
         */
        $this->FLD('moneyAccId', 'key(mvc=acc_Accounts,select=title,allowEmpty,maxSuggestions=100)', 'caption=Приход->Сметка,mandatory,silent');
        
        
        /**
         * Перо от първата аналитичност на сметката `moneyAccId` - указва касата, банковата с/ка
         * или, в най-общия случай, мястото, където да се осчетоводи прихода от продажбата.
         */
        $this->FLD('moneyEntId', 'key(mvc=acc_Items,select=caption,allowEmpty,maxSuggestions=100)', 'caption=Приход,mandatory');
        
        
        /**
         * Аналитична счетоводна сметка, чиято първа аналитичност се интерпретира като
         * номенклатура със складове.
         */
        $this->FLD('storeAccId', 'key(mvc=acc_Accounts,select=title,allowEmpty,maxSuggestions=100)', 'caption=Склад->Сметка,mandatory,silent');
        
        
        /**
         * Перо от първата аналитичност на сметката `storeAccId`
         */
        $this->FLD('storeEntId', 'key(mvc=acc_Items,select=caption,allowEmpty,maxSuggestions=100)', 'caption=Склад,mandatory');
        
        
        /**
         * Дата на продажбата
         */
        $this->FLD('date', 'date', 'caption=Продажба->Дата,mandatory');
        
        
        /**
         * Състояние
         */
        $this->FLD('state', 'enum(draft=Чернова,active=Активна,rejected=Отменена)', 'input=none');
        
        
        /**
         * Продавач - представителя на фирмата, който е осъществил продажбата
         */
        $this->FLD('sellerId', 'key(mvc=core_Users,select=names)', 'caption=Продавач,input=none,mandatory');
        
        
        /**
         * Начин на плащане
         */
        $this->FLD('paymentMethodId', 'key(mvc=bank_PaymentMethods,select=name)',
        'caption=Плащане->Начин,mandatory');
        
        
        /**
         * Условия на плащане (срок?)
         */
        $this->FLD('paymentTermId', 'key(mvc=bank_PaymentMethods,select=name)',
        'caption=Плащане->Срок,mandatory');
        
        
        /**
         * Дата, към която да се извличат цените от ценовата листа
         */
        $this->FLD('pricelistDate', 'date', 'caption=Продажба->Цени към,mandatory');
        
        
        /**
         * Допълнителни забележки, уточнения и пр.
         */
        $this->FLD('notes', 'text', 'caption=Забележки->Забележки');
    }
    
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    function on_AfterPrepareEditToolbar($mvc, $data)
    {
        $rec = $data->form->rec;
        
        if (!$rec->customerAccId || !$rec->storeAccId || !$rec->moneyAccId) {
            // Заменяме бутона за запис с бутон за рефреш
            $data->form->toolbar->addSbBtn('Запис', 'refresh', array('class' => 'btn-refresh'));
        }
    }
    
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        if (!$rec->customerAccId) {
            $form->setField('customerEntId', 'input=none');
            $form->setField('locationId', 'input=none');
            $this->fields['customerAccId']->type->params['where'] = "#isSynthetic OR #groupId1 IS NOT NULL";
        } else {
            $form->setReadOnly('customerAccId');
            $listId = $this->Accounts->fetchField($rec->customerAccId, 'groupId1');
            $this->fields['customerEntId']->type->params['where'] = "#listId = {$listId}";
        }
        
        if (!$rec->storeAccId) {
            $form->setField('storeEntId', 'input=none');
            $this->fields['storeAccId']->type->params['where'] = "#isSynthetic OR #groupId1 IS NOT NULL";
        } else {
            $form->setReadOnly('storeAccId');
            $listId = $this->Accounts->fetchField($rec->storeAccId, 'groupId1');
            $this->fields['storeEntId']->type->params['where'] = "#listId = {$listId}";
        }
        
        if (!$rec->moneyAccId) {
            $form->setField('moneyEntId', 'input=none');
            $this->fields['moneyAccId']->type->params['where'] = "#isSynthetic OR #groupId1 IS NOT NULL";
        } else {
            $form->setReadOnly('moneyAccId');
            $listId = $this->Accounts->fetchField($rec->moneyAccId, 'groupId1');
            $this->fields['moneyEntId']->type->params['where'] = "#listId = {$listId}";
        }
    }
}