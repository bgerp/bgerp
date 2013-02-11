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
    var $singleTitle = 'Правило';
    
    
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
        $this->FLD('type', 'enum(value,discount,groupDiscount)', 'caption=Тип,input=hidden,silent');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'caption=Продукт,mandatory');
        $this->FLD('packagingId', 'key(mvc=cat_Packagings,select=name,allowEmpty)', 'caption=Опаковка');
        $this->FLD('groupId', 'key(mvc=price_Groups,select=title,allowEmpty)', 'caption=Група,mandatory');
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена');
        $this->FLD('discount', 'percent(decimals=2)', 'caption=Отстъпка');
        $this->FLD('validFrom', 'datetime', 'caption=В сила->От,mandatory');
        $this->FLD('validUntil', 'datetime', 'caption=В сила->До');
    }


    /**
     * Връща цената за посочения продукт
     */
    static function getPrice($listId, $productId, $packagingId = NULL, $datetime = NULL)
    {
        if(!$datetime) {
            $datetime = dt::verbal2mysql();
        }

        $productGroup = price_GroupOfProducts::getGroup($productId);

        $query = self::getQuery();
        
        // Общи ограничения
        $query->where("#listId = {$listId} AND #validFrom <= '{$datetime}' AND (#valudUntil IS NULL OR #validUntil > '{$datetime}')");

        // Конкретни ограничения
        if($packagingId) {
            $query->where("(#productId = $productId AND (#packagingId = $packagingId OR #packagingId IS NULL)) OR (#groupId = $productGroup)");
        } else {
            $query->where("(#productId = $productId AND #packagingId IS NULL) OR (#groupId = $productGroup)");
        }
        
        // Вземаме последното правило
        $query->orderBy("#validFrom", "DESC");
        $query->limit(1);

        $rec = self::fetch();

        if($rec) {
            if($rec->type = 'value') {
                $value = $rec->value; // TODO конвертиране
            } else {
                $parent    = price_Lists::fetchField($listId, 'parent');
                $value = self::getPrice($parent, $productId, $packagingId, $datetime);
                $value = $value / (1 + $rec->discount);
            }
        }

        return $value;
    }



    /**
     *
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
       
        $form = $data->form;

        $rec = $form->rec;

        $type = $rec->type;

        switch($type) {
            case 'groupDiscount' :
                $form->setField('productId,packagingId,price', 'input=none');
                break;
            case 'discount' :
                $form->setField('groupId,price', 'input=none');
                break;
            case 'value' :
                $form->setField('groupId,discount', 'input=none');
                break;
        }
    }


    /**
     *
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->removeBtn('*'); 
        $data->toolbar->addBtn('Стойност', array($mvc, 'add', 'type' => 'value', 'listId' => $data->masterData->rec->id, 'ret_url' => TRUE));
        if($data->masterData->rec->parent) {
            $data->toolbar->addBtn('Отстъпка', array($mvc, 'add', 'type' => 'discount', 'listId' => $data->masterData->rec->id, 'ret_url' => TRUE));
            $data->toolbar->addBtn('Групова отстъпка', array($mvc, 'add', 'type' => 'groupDiscount', 'listId' => $data->masterData->rec->id, 'ret_url' => TRUE));
        }

    }
    
}