<?php


/**
 * Модел за товарителници към CVC
 *
 * @category  bgerp
 * @package   cvc
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cvc_WayBills extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Товарителници към CVC';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';


    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Кой има право да го оттегля?
     */
    public $canReject = 'admin';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'drdata_Wrapper, plg_Sorting, plg_Created';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "id,containerId,number,pickupDate,deliveryDate,file,data,createdOn,createdBy,state";


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('containerId', 'key(mvc=doc_Containers,select=id)', 'caption=Документ');
        $this->FLD('number', 'varchar', 'caption=Товарителница');
        $this->FLD('pickupDate', 'datetime(format=smartTime)', 'caption=Взимане');
        $this->FLD('deliveryDate', 'datetime(format=smartTime)', 'caption=Доставка');
        $this->FLD('file', 'fileman_FileType(bucket=engView)', 'caption=Файл');
        $this->FLD('state', 'enum(pending=Чакаща,rejected=Оттеглена)', 'caption=Състояние,notNull,value=rejected');
        $this->FLD('data', 'blob(serialize,compress)', 'caption=Данни');

        $this->setDbIndex('containerId');
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->state = "<div class='state-{$rec->state} document-handler'>{$row->state}</div>";
        $row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);

        if($mvc->haveRightFor('reject', $rec)){
            $row->state .= ht::createLink('', array($mvc, 'reject', $rec->id, 'ret_url' => true), 'Наистина ли желаете да изпратите заявка за отказване на товарителницата към|* CVC?', 'ef_icon=img/16/delete.png,title=Отказване на товарителницата');
        }
    }


    /**
     * Екшън за оттегляне на товарителницата
     */
    public function act_Reject()
    {
        $this->requireRightFor('reject');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('reject', $rec);

        try{
            $res = cvc_Adapter::cancelWb($rec->number);
            if(is_numeric($res)){
                $msg = 'Заявката за отказване на товарителницата е приета успешно|*!';
            } else {
                $msg = 'Товарителницата вече е била оттеглена|*!';
            }
            $rec->state = 'rejected';
            $this->save($rec, 'state');
        } catch(core_exception_Expect $e){
            $msg = 'Имаше проблем при подаване на заявката за оттегляне на товарителницата|*!';
        }

        followRetUrl(null, $msg);
    }


    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('createdOn', "DESC");
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = null, $userId = null)
    {
        if ($action == 'reject' && isset($rec)) {
            if($rec->state == 'rejected'){
                $res = 'no_one';
            }
        }
    }
}