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
class price_ListToCustomers extends core_Detail
{
    
    /**
     * Заглавие
     */
    var $title = 'Ценоразписи';
    
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Ценоразпис';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, price_Wrapper';
                    
    
    /**
     * Интерфейс за ценова политика
     */
    var $interfaces = 'price_PolicyIntf';


    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, listId, cClass, cId, validFrom';
    
    
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
    var $masterKey = 'cId';
    

    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Ценоразпис');
        $this->FLD('cClass', 'class(select=title)', 'caption=Клиент->Клас,input=hidden,silent');
        $this->FLD('cId', 'int', 'caption=Клиент->Обект');
        $this->FLD('validFrom', 'datetime', 'caption=В сила от,mandatory');
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
                $form->setError('validFrom', 'Ценоразписа не може да се задава с минала дата');
            }
        }
    }


    public static function on_AfterPrepareDetailQuery($mvc, $data)
    {
        $cClassId = core_Classes::getId($mvc->Master);
        
        $data->query->where("#cClass = {$cClassId}");

        $data->query->orderBy("#validFrom,#id", "DESC");
    }

    
    public function getMasterMvc_($rec)
    {   
        $masterMvc = cls::get($rec->cClass);
 
        return $masterMvc;      
    }
    
    
    public function getMasterKey_($rec)
    {
        return 'cId';      
    }
    
    
    public static function on_AfterGetMasters($mvc, &$masters, $rec)
    {
        if (empty($masters)) {
            $masters = array();
        }
        
        $masters['cId']    = cls::get($rec->cClass);
        $masters['listId'] = cls::get('price_Lists');
    }
    

    /**
     * След подготовка на лентата с инструменти за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, $data)
    {
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            $masterClassId = core_Classes::getId($this->Master);
            $data->toolbar->buttons['btnAdd']->url += array('cClass'=>$masterClassId);
        }
    }


    public static function on_AfterRenderDetail($mvc, &$tpl, $data)
    {
        $wrapTpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        $wrapTpl->append($mvc->title, 'title');
        $wrapTpl->append($tpl, 'content');
        $wrapTpl->replace(get_class($mvc), 'DetailName');
    
        $tpl = $wrapTpl;
    }
    
    
    public static function preparePricelists($data)
    { 
        static::prepareDetail($data);

        $now = dt::verbal2mysql();
 
        $query = self::getQuery();
        $cClassId = core_Classes::getId($data->masterMvc );
        $query->where("#cClass = {$cClassId} AND #cId = {$data->masterId}");
        $query->where("#validFrom <= '{$now}'");
        $query->limit(1);
        $query->orderBy("#validFrom,#id", 'DESC');
        $aRec = $query->fetch();

        foreach($data->rows as $id => &$row) {
            $rec = $data->recs[$id];
            if($rec->validFrom > $now) {
                $state = 'draft';
            } elseif($aRec->id == $rec->id) {
                $state = 'active';
            } else {
                $state = 'closed';
            }
            $data->rows[$id]->ROW_ATTR['class'] = "state-{$state}";

            if(price_Lists::haveRightFor('single', $rec)) {
                $row->listId = ht::createLink($row->listId, array('price_Lists', 'single', $rec->id));
            }
        }

    }


    /**
     *
     */
    public function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec)
    {
        if($rec->validFrom && ($action == 'edit' || $action == 'delete')) {
            if($rec->validFrom <= dt::verbal2mysql()) {
                $requiredRoles = 'no_one';
            }
        }
    }


    
    /**
     *
     */
    public function renderPricelists($data)
    {
        // Премахваме контрагента - в случая той е фиксиран и вече е показан 
        unset($data->listFields[$this->masterKey]);
        unset($data->listFields['cClass']);
        
        return static::renderDetail($data);
    }

    
    /**
     * Премахва кеша за интервалите от време
     */
    public static function on_AfterSave($mvc, &$id, &$rec, $fields = NULL)
    {
        price_History::removeTimeline();
    }



    /****************************************************************************************************
     *                                                                                                  *
     *    И Н Т Е Р Ф Е Й С   `price_PolicyIntf`                                                        *
     *                                                                                                  *
     ***************************************************************************************************/
    
    /**
     * Връща продуктие, които могат да се продават на посочения клиент, 
     * съгласно имплементиращата този интерфейс ценова политика
     *
     * @return array() - масив с опции, подходящ за setOptions на форма
     */
    public function getProducts($customerClass, $customerId, $date = NULL)
    {
         
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     * 
     * @return object
     * $rec->price  - цена
     * $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $productId, $packagingId = NULL, $quantity = NULL, $date = NULL)
    {
         
    }
    


    /**
     * Заглавие на ценоразписа за конкретен клиент
     *
     * @see price_PolicyIntf
     * @param mixed $customerClass
     * @param int $customerId
     * @return string
     */
    public function getPolicyTitle($customerClass, $customerId)
    {
        /* @TODO: Реализация на метода */
        return 'ListToCustomer';
    }
}