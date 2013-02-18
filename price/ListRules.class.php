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
    var $listFields = 'id, rule=Правило, validFrom, validUntil, createdOn, createdBy';
    
    
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
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена,mandatory');
        $this->FLD('discount', 'percent(decimals=2)', 'caption=Отстъпка,mandatory,placeholder=%');
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
 
        

        $price = price_History::getPrice($listId, $datetime, $productId, $packagingId);

        if($price) {
            return $price;
        }

        $datetime = price_History::canonizeTime($datetime);

        $productGroup = price_GroupOfProducts::getGroup($productId, $datetime);
        
        if(!$productGroup) {

            return NULL;
        }

        $query = self::getQuery();
        
        // Общи ограничения
        $query->where("#listId = {$listId} AND #validFrom <= '{$datetime}' AND (#validUntil IS NULL OR #validUntil > '{$datetime}')");

        // Конкретни ограничения
        if($packagingId) {
            $query->where("(#productId = $productId AND (#packagingId = $packagingId OR #packagingId IS NULL)) OR (#groupId = $productGroup)");
        } else {
            $query->where("(#productId = $productId AND #packagingId IS NULL) OR (#groupId = $productGroup)");
        }
        
        // Вземаме последното правило
        $query->orderBy("#validFrom,#id", "DESC");
        $query->limit(1);

        $rec = $query->fetch();
  
        if($rec) {
            if($rec->type == 'value') {
                $price = $rec->price; // TODO конвертиране
                $listRec = price_Lists::fetch($listId);
                list($date, $time) = explode(' ', $datetime);
                $price = currency_CurrencyRates::convertAmount($price, $date, $listRec->currency);
                if($listRec->vat == 'yes') {

                }
            } else {
                $parent = price_Lists::fetchField($listId, 'parent');
                $price  = self::getPrice($parent, $productId, $packagingId, $datetime);
                $price  = $value / (1 + $rec->discount);
            }
        }
        
        // Записваме току-що изчислената цена в историята;
        price_History::setPrice($price, $listId, $datetime, $productId, $packagingId);

        return $price;
    }


    function act_Test()
    {
        bp(self::getPrice(2, 1, NULL, '2013-02-13 01:00:00'));
    }



    /**
     * Подготвя формата за въвеждане на правила
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = $data->form;

        $rec = $form->rec;

        $type = $rec->type;

        $masterRec = price_Lists::fetch($rec->listId);

        $masterTitle = price_Lists::getVerbal($masterRec, 'title');

        switch($type) {
            case 'groupDiscount' :
                $form->setField('productId,packagingId,price', 'input=none');
                $title = "Групова отстъпка в ценоразпис \"$masterTitle\"";
                break;
            case 'discount' :
                $form->setField('groupId,price', 'input=none');
                $title = "Продуктова отстъпка в ценоразпис \"$masterTitle\"";
                break;
            case 'value' :
                $form->setField('groupId,discount', 'input=none');
                $title = "Продуктова цена в ценоразпис \"$masterTitle\"";
                break;
        }

        $form->title = $title;

        if(!$rec->validFrom) {
            $rec->validFrom = Mode::get('PRICE_VALID_FROM');
        }
    }


    /**
     * След създаване на ново правило, записва за дефолт на следващите правила
     * началото на валидността му
     */
    function on_AfterCreate($mvc, $rec)
    {
        Mode::setPermanent('PRICE_VALID_FROM', $rec->validFrom);
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


    /**
     * Премахва кеша за интервалите от време
     */
    public static function on_AfterSave($mvc, &$id, &$rec, $fields = NULL)
    {
        price_History::removeTimeline();
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($rec->validFrom && ($action == 'edit' || $action == 'delete')) {
            if($rec->validFrom <= dt::verbal2mysql()) {
                $requiredRoles = 'no_one';
            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {   
        $now = dt::verbal2mysql();

        if($rec->validFrom > $now) {
            $state = 'draft';
        } else {

            $query = $mvc->getQuery();
            $query->orderBy('#validFrom,#id', 'DESC');
            $query->limit(1);
            
            $query->where("#validFrom <= '{$now}' AND (#validUntil IS NULL OR #validUntil > '{$now}')");

            if($rec->groupId) {
                $query->where("#groupId = $rec->groupId");
            } else {
                $productGroup = price_GroupOfProducts::getGroup($rec->productId, $now);
                if($productGroup) {
                    $pgCond = "#groupId = $productGroup OR ";
                }
                expect($rec->productId);
                if($rec->productId && $rec->packagingId) {
                    $query->where("{$pgCond}(#productId = $rec->productId AND (#packagingId = $rec->packagingId OR #packagingId IS NULL))");
                } else {
                    $query->where("{$pgCond}(#productId = $rec->productId AND #packagingId IS NULL)");
                }
            }

            expect($actRec = $query->fetch());

            if($actRec->id == $rec->id) {
                $state = 'active';
            } else {
                $state = 'closed';
            }
        }


        // Вербален изказ на правилото
        $price = $mvc->getVerbal($rec, 'price');

        
        if($rec->discount < 0) {$discount = $mvc->getVerbal($rec, 'discount');
            $discount = "<font color='red'>Марж {$discount}</font>";
        } else {
            $discount = "Отстъпка {$discount}";
        }
        
        if($rec->productId) {
            $product = $mvc->getVerbal($rec, 'productId');
            $product = ht::createLink($product, array('cat_Products', 'single', $rec->productId));
        }

        if($rec->packagingId) {
            $packaging = $mvc->getVerbal($rec, 'packagingId');
            $product = "{$packaging} $product";
        }
        
        if($rec->groupId) {
            $group = 'група ' . $mvc->getVerbal($rec, 'groupId');
            $group = ht::createLink($group, array('price_Groups', 'single', $rec->groupId));
        }

        $currency = price_Lists::fetchField($rec->listId, 'currency');

        switch($rec->type) {
            case 'groupDiscount' :
                $row->rule = "{$discount} за {$group}";
                break;
            case 'discount' :
                $row->rule = "{$discount} за {$product}";
                break;
            case 'value' :
                $row->rule = "Цена {$price} {$currency} за {$product}";
                break;
        }        
        
        if($state == 'active') {
            $row->rule = "<b>{$row->rule}</b>";
        }

        // Линк към продукта
        if($rec->productId) {
            $row->productId = ht::createLink($row->productId, array('cat_Products', 'Single', $rec->productId));
        }

        $row->ROW_ATTR['class'] .= " state-{$state}";
    }


    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()) {
            
            $rec = $form->rec;

            $now = dt::verbal2mysql();

            if($rec->validFrom <= $now) {
                $form->setError('validFrom', 'Не могат да се задават правила за минал момент');
            }

             if($rec->validUntil && ($rec->validUntil <= $rec->validFrom)) {
                $form->setError('validUntil', 'Правилото трябва да е в сила до по-късен момент от началото му');
            }
        }
    }


    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#validFrom,#id', 'DESC');
    }

    
}