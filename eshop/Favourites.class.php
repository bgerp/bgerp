<?php


/**
 * Модел за Любими е-артикули
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eshop_Favourites extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Списък с любими e-артикули';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, eshop_Wrapper';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'userId,eshopProductId,createdOn,createdBy';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'debug';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'debug';

    /**
     * Кой има право да добавя/маха?
     */
    public $canAdd = 'no_one';


    /**
     * Кой има право да добавя/маха?
     */
    public $canToggle = 'user';


    /**
     * Кой има право да разглежда от външната част?
     */
    public $canShow = 'user';


    /**
     * Системно ид на страницата за любими артикули
     */
    const FAVOURITE_SYSTEM_GROUP_ID = -1;


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('userId', 'key(mvc=core_Users, select=nick)', 'caption=Потребител,input=none');
        $this->FLD('eshopProductId', 'key(mvc=eshop_Products,select=name)', 'caption=Ешоп артикул,mandatory,silent');

        $this->setDbIndex('userId');
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
        $row->eshopProductId = eshop_Products::getHyperlink($rec->eshopProductId, true);
    }


    /**
     * Екшън за добавяне/премахване на артикул от любими
     */
    public function act_Toggle()
    {
        $this->requireRightFor('toggle');
        expect($eshopProductId = Request::get('eshopProductId', 'int'));
        expect($cu = core_Users::getCurrent());
        $this->requireRightFor('toggle', (object)array('eshopProductId' => $eshopProductId));

        if($exRecId = static::fetchField("#userId = {$cu} AND #eshopProductId = {$eshopProductId}")){
            core_Statuses::newStatus('Артикулът е премахнат от "Любими"');
            static::delete($exRecId);
        } else {
            core_Statuses::newStatus('Артикулът е добавен в "Любими"');
            $rec = (object)array('userId' => $cu, 'eshopProductId' => $eshopProductId);
            static::save($rec);
        }

        if (Request::get('ajax_mode')) {
            $lang = cms_Domains::getPublicDomain('lang');
            core_Lg::push($lang);

            // Заместваме клетката по AJAX за да визуализираме промяната
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => "addToFavouritesBtn", 'html' => static::renderToggleBtn($eshopProductId)->getContent(), 'replace' => true);

            $resObj1 = new stdClass();
            $resObj1->func = 'html';
            $resObj1->arg = array('id' => "faveNavBtn", 'html' => static::renderFavouritesBtnInNavigation()->getContent(), 'replace' => true);

            $resObj2 = new stdClass();
            $resObj2->func = 'clearStatuses';
            $resObj2->arg = array('type' => 'notice');

            $hitTime = Request::get('hitTime', 'int');
            $idleTime = Request::get('idleTime', 'int');
            $statusData = status_Messages::getStatusesData($hitTime, $idleTime);

            $res = array_merge(array($resObj, $resObj1, $resObj2), (array) $statusData);
            core_Lg::pop();

            return $res;
        }

        return new Redirect(eshop_Products::getUrl($eshopProductId));
    }


    /**
     * Дали е-артикулът е вече добавен
     *
     * @param $eshopProductId - е-артикул
     * @param null|int $cu    - потребител (null за текущия)
     * @return bool
     */
    public static function isIn($eshopProductId, $cu = null)
    {
        $cu = isset($cu) ? $cu : core_Users::getCurrent();
        $exId = static::fetchField("#userId = '{$cu}' AND #eshopProductId = {$eshopProductId}");

        return !empty($exId);
    }


    /**
     * Рендиране на бутона за добавяне/премахване на любим артикул
     *
     * @param $eshopProductId - ид на артикул
     * @return core_ET        - бутона за добавяне/премахване
     */
    public static function renderToggleBtn($eshopProductId)
    {
        $attr = array('class' => 'favouritesBtn productBtn');
        $attr['data-url'] = toUrl(array('eshop_Favourites', 'toggle', 'eshopProductId' => $eshopProductId), 'local');

        $isIn = static::isIn($eshopProductId);
        $attr['ef_icon'] = $isIn ? 'img/16/heart-red.png' : 'img/16/heart_empty.png';
        $attr['title'] = $isIn ? tr('Добавяне на артикула в любими') : tr('Премахване на артикула от любими');
        $text = $isIn ? tr('Добавено в любими') : tr('Добави в любими');

        $tpl = ht::createLink($text, null, null, $attr);

        return $tpl;
    }


    /**
     * Колко любими артикула има потребителя
     *
     * @param null|int $cu
     * @return int
     */
    public static function getProducts($cu = null, $domainId = null)
    {
        $array = array();
        $cu = isset($cu) ? $cu : core_Users::getCurrent();

        if(isset($cu)){
            $domainId = isset($domainId) ? $domainId : cms_Domains::getPublicDomain()->id;

            $query = static::getQuery();
            $query->EXT('domainId', 'eshop_Products', 'externalName=domainId,externalKey=eshopProductId');
            $query->EXT('state', 'eshop_Products', 'externalName=state,externalKey=eshopProductId');
            $query->where("#domainId = {$domainId} AND #userId = {$cu}");// AND #state = 'active'

            return arr::extractValuesFromArray($query->fetchAll(), 'eshopProductId');
        }

        return $array;
    }


    /**
     * Екшън за единичен изглед на групата във витрината
     */
    public function act_Show()
    {
        self::requireRightFor('show');

        return Request::forward(array('Ctr' => 'eshop_Groups', 'Act' => 'Show', 'id' => static::FAVOURITE_SYSTEM_GROUP_ID));
    }


    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'show'){
            if(!$userId){
                $requiredRoles = 'no_one';
            } else {
                // Потребителят трябва да има любими артикули
                $products = static::getProducts($userId);
                if(!countR($products)){
                    $requiredRoles = 'no_one';
                }
            }
        }
    }


    /**
     * Връща каноничното URL на статията за външния изглед
     */
    public static function getUrl()
    {
        $lang = cms_Domains::getPublicDomain('lang');
        $vId = ($lang == 'bg') ? 'Любими-артикули' : 'Favourite-products';

        $url = array('A', 'F',$vId);

        return $url;
    }


    /**
     * Рендиране на бутона в навигацията
     *
     * @return core_ET
     */
    public static function renderFavouritesBtnInNavigation()
    {
        $products = eshop_Favourites::getProducts();
        if(countR($products)){
            $favouritesUrl = eshop_Favourites::getUrl();
            $cId = Request::get('id') == static::FAVOURITE_SYSTEM_GROUP_ID;
            $selClass = $cId ? 'sel_page' : '';

            $tpl = new core_ET("<div class='{$selClass} favouriteNavigationLink nav_item level-1'>" . ht::createLink(tr('Любими артикули||Favourite Products'), $favouritesUrl)  . '</div>');

            return $tpl;
        }

        return new core_ET(" ");
    }



}