<?php



/**
 * Правилата за ценоразписите за продуктите от каталога
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ценоразписи
 */
class price_ListRules extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Ценоразписи->Правила';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, price_Wrapper';
                    
 
     
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, groupId, productId, packagingId, price, discount, validFrom, validUntil, createdOn, createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'user';
    
        
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'user';
    
    
    /**
     * Поле - ключ към мастера
     */
    var $masterKey = 'listId';

    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Ценоразпис,input=hidden,silent');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Продукт,mandatory');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings,select=name,allowEmpty)', 'caption=Опаковка');
        $this->FLD('groupId', 'key(mvc=price_Groups,select=title,allowEmpty)', 'caption=Група,mandatory');
        $this->FLD('price', 'double', 'caption=Цена');
        $this->FLD('discount', 'percent', 'caption=Отстъпка');
        $this->FLD('validFrom', 'datetime', 'caption=В сила->От,mandatory');
        $this->FLD('validUntil', 'datetime', 'caption=В сила->До');
    }


    /**
     *
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
       
        $form = $data->form;

        $form->FNC('type', 'varchar', 'input=hidden,silent');

        $type = Request::get('type');
        if(!$type && $form->rec->id) {
            if($form->rec->groupId) {
                $type = 'groupDiscount';
            } elseif($rec->discount) {
                $type = 'discount';
            } else {
                $type = 'price';
            }
        }
        $form->setDefault('type', $type);

        switch($type) {
            case 'groupDiscount' :
                $form->setField('productId,packagingId,price', 'input=none');
                break;
        }
    }
    
}