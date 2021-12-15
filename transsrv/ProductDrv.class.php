<?php


/**
 * Драйвър за продукт на ЕП
 *
 *
 * @category  bgerp
 * @package   transsrv
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Транспортна услуга
 */
class transsrv_ProductDrv extends cat_ProductDriver
{
    /**
     * Интерфейси които имплементира
     */
    public $interfaces = 'cat_ProductDriverIntf';
    
    
    /**
     * Дефолт мета данни за всички продукти
     */
    protected $defaultMetaData = 'canSell,canBuy';
    
    
    /**
     * Стандартна мярка за ЕП продуктите
     */
    public $uom = 'pcs';
    
    
    /**
     * Кои полета да се добавят към ключовите думи на артикула
     */
    protected $searchFields = 'load,conditions,ourReff,auction';
    
    
    /**
     * Допълва дадената форма с параметрите на фигурата
     * Връща масив от имената на параметрите
     */
    public function addFields(core_Fieldset &$form)
    {
        // Локация за натоварване
        $form->FLD('fromCountry', 'key(mvc=drdata_Countries,select=commonNameBg,allowEmpty)', 'caption=Натоварване->Държава');
        $form->FLD('fromPCode', 'varchar(16)', 'caption=Натоварване->П. код');
        $form->FLD('fromPlace', 'varchar(32)', 'caption=Натоварване->Нас. място');
        $form->FLD('fromAddress', 'varchar', 'caption=Натоварване->Адрес');
        $form->FLD('fromCompany', 'varchar', 'caption=Натоварване->Фирма');
        $form->FLD('fromPerson', 'varchar', 'caption=Натоварване->Лице');
        $form->FLD('loadingTime', 'datetime(defaultTime=09:00:00)', 'caption=Натоварване->Най-късно на');
        
        // Локация за разтоварване
        $form->FLD('toCountry', 'key(mvc=drdata_Countries,select=commonNameBg,allowEmpty)', 'caption=Разтоварване->Държава');
        $form->FLD('toPCode', 'varchar(16)', 'caption=Разтоварване->П. код');
        $form->FLD('toPlace', 'varchar(32)', 'caption=Разтоварване->Нас. място');
        $form->FLD('toAddress', 'varchar', 'caption=Разтоварване->Адрес');
        $form->FLD('toCompany', 'varchar', 'caption=Разтоварване->Фирма');
        $form->FLD('toPerson', 'varchar', 'caption=Разтоварване->Лице');
        $form->FLD('deliveryTime', 'datetime(defaultTime=17:00:00)', 'caption=Разтоварване->Краен срок');
        
        // Описание на товара
        $form->FLD('transUnit', 'varchar', 'caption=Информация за товара->Трансп. ед.,suggestions=Европалета|Палета|Кашона|Скари|Сандъка|Чувала|Каси|Биг Бага|20\' контейнер|40\' контейнер|20\' контейнер upgraded|40\' High cube контейнер|20\' reefer хладилен|40\' reefer хладилен|Reefer 40\' High Cube хлд|Open Top 20\'|Open Top 40\'|Flat Rack 20\'|Flat Rack 40\'|FlatRack Collapsible 20\'|FlatRack Collapsible 40\'|Platform 20\'|Platform 40\'|Хенгер|Прицеп|Мега трейлър|Гондола|IBC контейнер|IBC контейнери|Цистерна|Палет с варели');
        $form->FLD('unitQty', 'int(Min=0)', 'caption=Информация за товара->Количество');
        $form->FLD('maxWeight', 'cat_type_Uom(unit=t,min=1,max=5000000)', 'caption=Информация за товара->Общо тегло');
        $form->FLD('maxVolume', 'cat_type_Uom(unit=cub.m,min=0.1,max=5000)', 'caption=Информация за товара->Общ обем');
        $form->FLD('maxHeight', 'cat_type_Uom(unit=m,min=0.1,max=10)', 'caption=Информация за товара->Макс. височина');
        $form->FLD('dangerous', 'enum( no = Безопасен товар,
                                       hl = Извънгабаритен товар,
                                        1 = Клас 1 - Взривни вещества и изделия,
                                        2 = Клас 2 - Газове,
                                        3 = Клас 3 - Запалими течности,
                                        4 = Клас 4 - Други запалими вещества,
                                        5 = Клас 5 - Окисляващи вещества и органични пероксиди,
                                        6 = Клас 6 - Отровни и заразни вещества,
                                        7 = Клас 7 - Радиоактивни материали,
                                        8 = Клас 8 - Корозионни вещества,
                                        9 = Клас 9 - Други опасни вещества)', 'caption=Информация за товара->Опасност');
        $form->FLD('load', 'varchar', 'caption=Информация за товара->Описание');
        
        // Обща информация
        $form->FLD('conditions', 'richtext(bucket=Notes,rows=3)', 'caption=Обща информация->Условия');
        $form->FLD('ourReffDomainUrl', 'varchar', 'caption=Обща информация->Наш реф.№,input=hidden');
        $form->FLD('ourReff', 'varchar', 'caption=Обща информация->Наш реф.№');
        $form->FLD('auction', 'varchar', 'caption=Обща информация->Търг');
        $form->FLD('auctionId', 'varchar', 'caption=Обща информация->Търг,input=hidden');

        $this->invoke('AfterTransportServiceFields', array(&$form));
    }
    
    
    /**
     * Връща дефолтното име на артикула
     *
     * @param stdClass $rec
     *
     * @return NULL|string
     */
    public function getProductTitle($rec)
    {
        $myCompany = crm_Companies::fetchOurCompany();
        
        if (!$rec->fromCountry) {
            $rec->fromCountry = $myCompany->country;
        }
        
        if (!$rec->toCountry) {
            $rec->toCountry = $myCompany->country;
        }
        
        $from2let = drdata_Countries::fetch($rec->fromCountry)->letterCode2;
        $to2let = drdata_Countries::fetch($rec->toCountry)->letterCode2;
        
        $title = $from2let . '»' . $to2let;
        
        if ($rec->unitQty && $rec->transUnit) {
            $title .= ', ' . $rec->unitQty . ' ' . type_Varchar::escape($rec->transUnit);
        } elseif ($rec->transUnit) {
            $title .= ', ' . type_Varchar::escape($rec->transUnit);
        }
        
        return $title . ' / ' . tr('Транспорт') . '';
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param stdClass          $data
     */
    public static function on_AfterInputEditForm(cat_ProductDriver $Driver, embed_Manager $Embedder, &$form)
    {
        $form->rec->name = $Driver->getProductTitle($form->rec);
        
        if ($form->isSubmitted()) {
            $fields = $form->selectFields("#input != 'none'");
            
            foreach ($fields as $name => $fld) {
                if ($form->rec->{$name} === '' && cls::getClassName($fld->type) == 'type_Varchar') {
                    $form->rec->{$name} = null;
                }
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param stdClass          $data
     */
    public static function on_AfterPrepareEditForm(cat_ProductDriver $Driver, embed_Manager $Embedder, &$data)
    {
        if ($data->form->getField('meta', false)) {
            $data->form->setField('meta', 'input=hidden');
        }
        
        if ($data->form->getField('measureId', false)) {
            $data->form->setField('measureId', 'input=hidden');
        }
        
        if ($data->form->getField('info', false)) {
            $data->form->setField('info', 'input=hidden');
        }
        
        if ($data->form->getField('packQuantity', false)) {
            $data->form->setField('packQuantity', 'input=hidden');
        }
        
        if ($data->form->getField('name', false)) {
            $data->form->setField('name', 'input=hidden');
        }
        if ($data->form->getField('notes', false)) {
            $data->form->setField('notes', 'input=hidden');
        }
    }
    
    
    /**
     * Връща стойността на параметъра с това име, или
     * всички параметри с техните стойностти
     *
     * @param int    $classId - ид на клас
     * @param string $id      - ид на записа
     * @param string $name    - име на параметъра, или NULL ако искаме всички
     * @param bool   $verbal  - дали да са вербални стойностите
     *
     * @return mixed $params - стойност или FALSE ако няма
     */
    public function getParams($classId, $id, $name = null, $verbal = false)
    {
        $rec = cls::get($classId)->fetchField($id, 'driverRec');
        
        $params = array();
        $toleranceId = cat_Params::force('tolerance', 'Толеранс', 'cond_type_Percent', null, '%');
        $params[$toleranceId] = 0;

        $this->invoke('AfterTransportGetParams', array(&$params, $rec));

        if (!is_numeric($name)) {
            $nameId = cat_Params::fetch(array("#sysId = '[#1#]'", $name))->id;
        } else {
            $nameId = $name;
        }
        
        if ($nameId && isset($params[$nameId])) {
            
            return $params[$nameId];
        }
        
        if ($name && isset($rec->_params[$name])) {
            
            return $rec->_params[$name];
        }
        
        if (isset($name) && !isset($params[$nameId])) {
            
            return;
        }
        
        return $params;
    }
    
    
    /**
     * Допълнителните условия за дадения продукт,
     * които автоматично се добавят към условията на договора
     *
     * @param stdClass    $rec     - ид/запис на артикул
     * @param string      $docType - тип на документа sale/purchase/quotation
     * @param string|NULL $lg      - език
     */
    public function getConditions($rec, $docType, $lg = null)
    {
        if ($condition = transsrv_Setup::get('SALE_DEFAULT_CONDITION')) {
            
            return array($condition);
        }
        
        return array();
    }
    
    
    /**
     * Връща хеша на артикула (стойност която показва дали е уникален)
     *
     * @param embed_Manager $Embedder - Ембедър
     * @param mixed         $rec      - Ид или запис на артикул
     *
     * @return NULL|string - Допълнителните условия за дадения продукт
     */
    public function getHash(embed_Manager $Embedder, $rec)
    {
        $objectToHash = new stdClass();
        $fields = $Embedder->getDriverFields($this);
        foreach ($fields as $name => $caption) {
            $objectToHash->{$name} = $rec->{$name};
        }
        
        $hash = md5(serialize($objectToHash));
        
        return $hash;
    }
    
    
    /**
     * Връща задължителната основна мярка
     *
     * @return int|NULL - ид на мярката, или NULL ако може да е всяка
     */
    public function getDefaultUomId()
    {
        return cat_UoM::fetchBySinonim($this->uom)->id;
    }
    
    
    /**
     * Връща броя на количествата, които ще се показват в запитването
     *
     * @return int|NULL - броя на количествата в запитването
     */
    public function getInquiryQuantities()
    {
        return 0;
    }
    
    
    /**
     * Може ли вградения обект да се избере
     */
    public function canSelectDriver($userId = null)
    {
        return haveRole('powerUser', $userId) || (transsrv_Setup::get('AVIABLE_FOR_PARTNERS') == 'yes' && haveRole('partner', $userId));
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal(cat_ProductDriver $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        $row->fromCountry = drdata_Countries::getTitleById($rec->fromCountry);
        $row->toCountry = drdata_Countries::getTitleById($rec->toCountry);
    }


    /**
     * Рендиране на описанието на драйвера
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public function renderProductDescription($data)
    {
        // Шаблон
        $tpl = getTplFromFile('transsrv/tpl/TransportProduct.shtml');
        $this->invoke('BeforeTransportRenderDescription', array(&$data));

        // ще се заместват само полетата от драйвера
        $fields = cat_Products::getDriverFields($this);
        $row = new stdClass();
        foreach ($fields as $name => $caption) {
            $row->{$name} = $data->row->{$name};
        }
        
        if (!Mode::isReadOnly()) {
            $systemId = remote_Authorizations::getSystemId(transsrv_Setup::get('BID_DOMAIN'));
            if (haveRole('officer')) {
                if (!empty($data->rec->auction) && haveRole('officer')) {
                    if ($systemId) {
                        $url = array("transbid_Auctions/single/{$data->rec->auctionId}");
                        $url = remote_Authorizations::getRemoteUrl($systemId, $url);
                        $row->auction = ht::createLink($row->auction, $url);
                    } else {
                        $row->auction = ht::createHint($row->auction, 'За да видите търга, ви е нужно оторизация за trans.bid', 'warning');
                    }
                }
            }
        }

        if (!empty($data->rec->ourReff)) {
            $ourRefDomainId = !empty($data->rec->ourReffDomainUrl) ? $data->rec->ourReffDomainUrl : '';

            $selfUrl = core_App::getSelfURL();
            $selfUrl = str_replace($_SERVER['REQUEST_URI'], '', $selfUrl);
            $reff = str_replace('#', '', $data->rec->ourReff);

            $url = array();
            if($ourRefDomainId == $selfUrl || empty($ourRefDomainId)){
                if(doc_Search::haveRightFor('list')){
                    $url = array('doc_Search', 'list', 'search' => "#{$reff}");
                }
            } elseif($systemId = remote_Authorizations::getSystemId($ourRefDomainId)) {
                $url = remote_Authorizations::getRemoteUrl($systemId, array('doc_Search', 'list', 'search' => "#{$reff}"));
            }

            $row->ourReff = ht::createLink($row->ourReff, $url);
        }
        
        $tpl->placeObject($row);
        
        return $tpl;
    }
}
