<?php


/**
 * Абстрактен клас за наследяване на допълнителни документи към складовите документи
 *
 * @category  bgerp
 * @package   trans
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class trans_abstract_ShipmentDocument extends core_Master
{
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;


    /**
     * На кой ред в тулбара да се показва бутона за принтиране
     */
    public $printBtnToolbarRow = 1;


    /**
     * Може ли да се редактират активирани документи
     */
    public $canEditActivated = true;


    /**
     * Да се добавя ли документа като линк към ориджина си
     */
    public $addLinkedDocumentToOriginId = true;


    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'search';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    }


    /**
     * Изпълнява се преди възстановяването на документа
     */
    public static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        if($rec->brState == 'active'){
            $stopMsg = null;
            if($mvc->hasOtherActivated($rec, $stopMsg)){
                core_Statuses::newStatus($stopMsg, 'error');

                return false;
            }
        }
    }


    /**
     * Функция, която се извиква преди активирането на документа
     */
    protected static function on_BeforeActivation($mvc, $res)
    {
        $rec = $mvc->fetchRec($res);
        $stopMsg = null;
        if($mvc->hasOtherActivated($rec, $stopMsg)){
            core_Statuses::newStatus($stopMsg, 'error');

            return false;
        }
    }


    /**
     * Има ли друго активно ЧМР за въпросното експедиционно
     *
     * @param int $id
     * @param null|string $msg
     * @return bool
     */
    protected function hasOtherActivated($id, &$msg)
    {
        $rec = $this->fetchRec($id);
        $originId = isset($rec->originId) ? $rec->originId : $this->fetchField($rec->id, 'originId', '*');
        if($exCmrId = $this->fetchField("#originId = {$originId} AND #state != 'rejected' AND #id != '{$rec->id}'")){
            $msg = "Вече има друг създаден към документа|*: " . $this->getLink($exCmrId, 0);

            return true;
        }

        return false;
    }


    /**
     * Документа не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     */
    public static function canAddToFolder($folderId)
    {
        return false;
    }


    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow_($id)
    {
        expect($rec = $this->fetch($id));
        $title = $this->getRecTitle($rec);

        $row = (object) array('title' => $title,
                              'authorId' => $rec->createdBy,
                              'author' => $this->getVerbal($rec, 'createdBy'),
                              'state' => $rec->state,
                              'recTitle' => $title
        );

        return $row;
    }


    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' && isset($rec->originId)) {
            $origin = doc_Containers::getDocument($rec->originId);
            if (!$origin->isInstanceOf('store_ShipmentOrders')) {
                $requiredRoles = 'no_one';
            } else {
                $state = $origin->fetchField('state');
                if (!in_array($state, array('active', 'pending'))) {
                    $requiredRoles = 'no_one';
                }
            }

            if($requiredRoles != 'no_one'){

                // Ако има вече създаден неоттеглен документ към източника да не може да се създава нов
                if($mvc->fetchField("#originId = {$rec->originId} AND #state != 'rejected' AND #id != '{$rec->id}'", 'id')){
                    $requiredRoles = 'no_one';
                } elseif($mvc instanceof trans_IntraCommunitySupplyConfirmations) {

                    // Ако е потвърждение за ВОД да се гледа настройката
                    $addSettings = trans_Setup::get('SHOW_VOD_BTN');
                    if($addSettings == 'never'){
                        $requiredRoles = 'no_one';
                    } elseif($addSettings == 'auto'){

                        // Ако е автоматично гледа се условието на доставка от договора на ЕН-то
                        $firstDocument = doc_Threads::getFirstDocument($origin->fetchField('threadId'));
                        if($deliveryTermId = $firstDocument->fetchField('deliveryTermId')){

                            // Ако не е разрешено да се скрие
                            $deliveryProperties = type_Set::toArray(cond_DeliveryTerms::fetchField($deliveryTermId, 'properties'));
                            if(!isset($deliveryProperties['vodeu'])){
                                $requiredRoles = 'no_one';
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * Метод по подразбиране, за връщане на състоянието на документа в зависимот от класа/записа
     *
     * @param core_Master $mvc
     * @param NULL|string $res
     * @param NULL|int    $id
     * @param NULL|bool   $hStatus
     *
     * @see doc_HiddenContainers
     */
    public function getDocHiddenStatus($id, $hStatus)
    {
        $cid = $this->fetchField($id, 'containerId');
        if (doclog_Documents::fetchByCid($cid, doclog_Documents::ACTION_PRINT)) {

            return true;
        }
    }


    /**
     * Оттегляне на документ
     */
    public static function on_AfterReject(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        doc_DocumentCache::invalidateByOriginId($rec->containerId);
    }


    /**
     * Възстановяване на документ
     */
    public static function on_AfterRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        doc_DocumentCache::invalidateByOriginId($rec->containerId);
    }
}