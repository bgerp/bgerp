<?php

/**
 * Мениджър на продажби - мастър
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */

define(LIST_STORES, 'СКЛАДОВЕ');
define(LIST_CUSTOMERS, 'КЛИЕНТИ');
define(LIST_CASES, 'КАСИ');


/**
 * Клас 'acc_Sales' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    acc
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class acc_Sales extends core_Master implements intf_Settings
{
    /**
     *  @todo Чака за документация...
     */
    var $menuPage = 'Счетоводство';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Продажби';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, 
        plg_SaveAndNew, acc_Wrapper, Lists=acc_Lists, Accounts=acc_Accounts';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $details = 'Details=acc_SaleDetails';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $singleTitle = 'Продажба';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,acc,broker,designer';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,acc,broker,designer';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,acc,broker,designer';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 300;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,date,storeEntId,customerEntId,moneyEntId,paymentMethodId,
                        paymentTermId,state=Съст.,tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * @var acc_SaleDetails
     */
    var $Details;
    
    
    /**
     * @var acc_Lists
     */
    var $Lists;
    
    
    /**
     * @var acc_Accounts
     */
    var $Accounts;
    
    
    /**
     *  Описание на модела (таблицата)
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
        $this->FLD('locationId', 'key(mvc=common_Locations,select=title,allowEmpty,maxSuggestions=100)', 'caption=Клиент->Обект');
        
        
        /**
         * Аналитична счетоводна сметка, чиято първа аналитичност се интерпретира според
         * стойността на полето `paymentMethodId` от мениджъра @see common_PaymentMethods.
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
        $this->FLD('paymentMethodId', 'key(mvc=common_PaymentMethods,select=name)',
        'caption=Плащане->Начин,mandatory');
        
        
        /**
         * Условия на плащане (срок?)
         */
        $this->FLD('paymentTermId', 'key(mvc=common_PaymentTerms,select=name)',
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
     *  Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
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
     *  Извиква се след въвеждането на данните от Request във формата ($form->rec)
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
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_AfterPrepareSettingsForm($mvc, $data)
    {
        $form = $data->form;
        
        
        /**
         * Аналитична счетоводна сметка, чиято първа аналитичност се интерпретира като
         * номенклатура с клиенти.
         */
        $form->FLD('customerAccId', 'key(mvc=acc_Accounts,select=title,allowEmpty,maxSuggestions=100)', 'caption=Клиент->Сметка,mandatory,silent,column=none');
    }
}