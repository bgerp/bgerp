<?php


/**
 * Модел за товарителници към Transpress
 *
 * @category  bgerp
 * @package   transpress
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class transpress_WayBills extends core_Manager
{
    /**
     * Заглавие на модела
     */
    public $title = 'Товарителници към Transpress';


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
    public $canList = 'transpress,admin';


    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';


    /**
     * Кой може да отказва товарителница?
     */
    public $canReject = 'transpress,admin';


    /**
     * Кой може да сваля PDF-а наново?
     */
    public $canPrint = 'transpress,admin';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'drdata_Wrapper, plg_Sorting, plg_Created, plg_Search';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,containerId,number,barcodes,price,file,data,environment,account,state,createdOn,createdBy';


    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'number,barcodes';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('containerId', 'key(mvc=doc_Containers,select=id)', 'caption=Документ');
        $this->FLD('number', 'varchar(32)', 'caption=Товарителница');
        $this->FLD('barcodes', 'text', 'caption=Баркодове');
        $this->FLD('price', 'double(decimals=2)', 'caption=Цена с ДДС');
        $this->FLD('currencyId', 'varchar(3)', 'caption=Валута');
        $this->FLD('file', 'fileman_FileType(bucket=transpress)', 'caption=Файл');
        $this->FLD('environment', 'enum(production=Продукция,test=Тест)', 'caption=Среда,notNull,value=production');
        $this->FLD('account', 'varchar(128)', 'caption=Акаунт');
        $this->FLD('state', 'enum(active=Издадена,rejected=Отказана)', 'caption=Състояние,notNull,value=active');
        $this->FLD('data', 'blob(serialize,compress)', 'caption=Данни');

        $this->setDbIndex('containerId');
        $this->setDbIndex('number');
    }


    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
        $row->ROW_ATTR['class'] = "state-{$rec->state}";

        if(!empty($rec->price)){
            $row->price = currency_Currencies::decorate($row->price, $rec->currencyId);
        }

        $row->number = ht::createLink($row->number, transpress_Adapter::getTrackingUrl($rec->number, $rec->environment), false, 'target=_blank,title=Проследяване на пратката');

        if($mvc->haveRightFor('print', $rec)){
            $row->file = ht::createLink('', array($mvc, 'print', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/arrow_refresh.png,title=|Сваляне на PDF-а от|* Transpress');
        }

        if($mvc->haveRightFor('reject', $rec)){
            $row->state .= ' ' . ht::createLink('', array($mvc, 'reject', $rec->id, 'ret_url' => true), false, 'ef_icon=img/16/delete.png,title=Отказване на товарителницата');
        }
    }


    /**
     * Издадена ли е товарителницата от друг акаунт, а не от настроения на потребителя
     *
     * Паролата е само в настройките, затова отказ и печат минават само със същия акаунт.
     *
     * @param stdClass $rec
     * @return bool
     */
    public static function isOtherAccount($rec)
    {
        $login = transpress_Adapter::getLoginData();

        return ($login['userName'] ?? '') !== ($rec->account ?? '');
    }


    /**
     * Отказва товарителницата в Transpress и я отбелязва в нея и в документа
     *
     * @param stdClass    $rec       - товарителницата
     * @param string|null $reason    - причина
     * @param string      $docLogMsg - запис в историята на документа
     * @return stdClass {status, error}
     */
    public function cancel($rec, $reason, $docLogMsg)
    {
        if(self::isOtherAccount($rec)){

            return (object)array('status' => cond_CourierApiIntf::BOL_REJECTED, 'error' => "Издадена от друг акаунт в Transpress: {$rec->account}");
        }

        try{
            transpress_Adapter::cancel($rec->number, $reason, $rec->environment);
        } catch(core_exception_Expect $e){
            // Без отговор не е ясно дали е отказана
            $status = ($e->getType() == transpress_Adapter::NO_RESPONSE_TYPE) ? cond_CourierApiIntf::BOL_UNKNOWN : cond_CourierApiIntf::BOL_REJECTED;

            return (object)array('status' => $status, 'error' => $e->getMessage());
        }

        $rec->state = 'rejected';
        $this->save($rec, 'state');
        $this->logWrite('Отказване на товарителница' . (strlen($reason ?? '') ? ": {$reason}" : ''), $rec->id);

        // В историята на документа, от който е издадена
        $Document = doc_Containers::getDocument($rec->containerId);
        $Document->getInstance()->logWrite($docLogMsg, $Document->that);
        store_plg_CourierApiShipment::rejectWayBillLink($rec->containerId, $rec->file ?? null);

        return (object)array('status' => cond_CourierApiIntf::BOL_CANCELLED, 'error' => null);
    }


    /**
     * Отказване на товарителницата в Transpress
     */
    public function act_Reject()
    {
        $this->requireRightFor('reject');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('reject', $rec);

        $form = cls::get('core_Form');
        $form->setAction(getCurrentUrl());
        $form->title = 'Отказване на товарителница|* ' . type_Varchar::escape($rec->number);
        $form->FLD('comment', 'varchar(1024)', 'caption=Причина');
        $form->input();

        if($form->isSubmitted()){
            $res = $this->cancel($rec, $form->rec->comment, 'Ръчно отказана товарителница');
            if($res->status == cond_CourierApiIntf::BOL_CANCELLED){
                followRetUrl(null, 'Товарителницата е отказана|*!');
            }

            if($res->status == cond_CourierApiIntf::BOL_UNKNOWN){
                $form->setError('comment', '|*Transpress |не отговори - проверете в системата им дали товарителницата е отказана|*!');
            } else {
                $form->setError('comment', '|*Transpress |не прие отказа|*: ' . type_Varchar::escape($res->error));
            }
        }

        $form->toolbar->addSbBtn('Отказване', 'save', 'ef_icon=img/16/delete.png,title=|Отказване на товарителницата в|* Transpress');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon=img/16/close-red.png,title=Прекратяване на действията');
        $form = $form->renderHtml();

        return $this->renderWrapping($form);
    }


    /**
     * Сваля наново PDF-а на товарителницата и го закача към документа
     */
    public function act_Print()
    {
        $this->requireRightFor('print');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('print', $rec);

        if(self::isOtherAccount($rec)){
            followRetUrl(null, '|Товарителницата е издадена от друг акаунт в|* Transpress: ' . type_Varchar::escape($rec->account) . '!', 'error');
        }

        try{
            $data = is_array($rec->data) ? $rec->data : array();
            $fh = transpress_Adapter::printPdf($rec->number, $data['printFormat'] ?? 'pdf-label', $rec->environment);
        } catch(core_exception_Expect $e){
            followRetUrl(null, '|PDF-ът не можа да се изтегли|*: ' . type_Varchar::escape($e->getMessage()), 'error');
        }

        $rec->file = $fh;
        $this->save($rec, 'file');
        $this->logWrite('Сваляне на PDF на товарителница', $rec->id);

        $fileId = fileman::fetchByFh($fh, 'id');
        doc_Linked::add($rec->containerId, $fileId, 'doc', 'file', cls::get('transpress_interface_CourierImpl')->wayBillComment);

        followRetUrl(null, 'PDF-ът на товарителницата е изтеглен|*!');
    }


    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        $data->query->orderBy('createdOn', 'DESC');
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (in_array($action, array('reject', 'print')) && isset($rec)) {
            if($rec->state == 'rejected'){
                $requiredRoles = 'no_one';
            }
        }

        if ($action == 'print' && isset($rec) && !empty($rec->file)) {
            $requiredRoles = 'no_one';
        }
    }
}
