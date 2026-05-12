<?php


/**
 * Тип за параметър 'Артикул'
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Групи артикули
 */
class cond_type_Product extends cond_type_Varchar
{
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('productGroups', 'keylist(mvc=cat_Groups,select=name)', 'caption=Конкретизиране->Групи,mandatory');
        $fieldset->FLD('show', 'enum(name=Наименование,info=Описание)', 'caption=Конкретизиране->Показване,mandatory');
        $fieldset->FLD('showList', 'enum(name=Наименование,info=Описание)', 'caption=Конкретизиране->Показване лист,mandatory');
        $fieldset->FLD('display', 'enum(name=Наименование,info=Описание)', 'caption=Конкретизиране->Избор,mandatory');
        $fieldset->FLD('orderBy', 'enum(idAsc=По артикул [нарастващ ред],idDesc=По артикул [намаляващ ред],codeAsc=По код [нарастващ ред],codeDesc=По код [намаляващ ред])', 'caption=Конкретизиране->Подредба,mandatory');
        $fieldset->FLD('maxSuggestions', 'int(Min=0)', 'caption=Конкретизиране->Макс. предложения', "unit=при показване в комбобокс,placeholder=10");
        $fieldset->FLD('maxRadio', 'int(min=0,max=50)', 'caption=Конкретизиране->Радио бутони до,mandatory', "unit=|опции (при повече - падащо меню)|*");
        $fieldset->FLD('columns', 'int(Min=0)', 'caption=Конкретизиране->Радио бутон (колони),placeholder=2');
        $fieldset->FLD('meta', 'set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим,generic=Генеричен)', 'caption=Конкретизиране->Със свойства');
        $fieldset->FLD('exceptMeta', 'set(canSell=Продаваем,canBuy=Купуваем,canStore=Складируем,canConvert=Вложим,fixedAsset=Дълготраен актив,canManifacture=Производим,generic=Генеричен)', 'caption=Конкретизиране->Без свойства');
    }


    /**
     * Връща инстанция на типа
     *
     * @param stdClass    $rec         - запис на параметъра
     * @param mixed       $domainClass - клас на домейна
     * @param mixed       $domainId    - ид на домейна
     * @param NULL|string $value       - стойност
     *
     * @return core_Type - готовия тип
     */
    public function getType($rec, $domainClass = null, $domainId = null, $value = null)
    {
        $maxSuggestions = !empty($this->driverRec->maxSuggestions) ? $this->driverRec->maxSuggestions : 10;
        $CType = core_Type::getByName("key2(mvc=cat_ProductsProxy,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions={$maxSuggestions},forceAjax)");
        $CType->params['groups'] = $this->driverRec->productGroups;
        if(!empty($this->driverRec->meta)){
            $CType->params['hasProperties'] = $this->driverRec->meta;
        }
        if(!empty($this->driverRec->exceptMeta)){
            $CType->params['hasnotProperties'] = $this->driverRec->exceptMeta;
        }
        if(isset($this->driverRec->display) && $this->driverRec->display != 'name'){
            $CType->params['display'] = $this->driverRec->display;
        }

        $orderBy = $this->driverRec->orderBy ?? 'idAsc';
        $orderByField = ($orderBy == 'idAsc') ? 'id=ASC' : (($orderBy == 'idDesc') ? 'id=DESC' : (($orderBy == 'codeAsc') ? 'code=ASC' : 'code=DESC'));
        $CType->params['orderBy'] = $orderByField;

        // Ако няма зададени радио бутони - ще се показва като key2
        if(empty($this->driverRec->maxRadio)) return $CType;

        // Ако има зададен брой радио бутони, но опциите са над тях + 1, ще се рендира като key2
        $checkLimit = $this->driverRec->maxRadio + 1;
        $options = $CType->getOptions($checkLimit);
        $optionsCount = countR($options) - 1;

        if($optionsCount > $this->driverRec->maxRadio) return $CType;

        // Ако параметъра е към прототипен артикул - се рендира като кей2 за да може да се избере празната опция
        if(isset($domainClass) && isset($domainId)){
            $Domain = cls::get($domainClass);
            if($Domain instanceof cat_Products){
                $state = $Domain->fetchField($domainId, 'state');
                if($state == 'template') return $CType;
            }
        }

        // Ако има радио бутони ще се показва като селект
        $Type = core_Type::getByName('key(mvc=cat_Products,select=name)');
        $Type->params['maxRadio'] = $this->driverRec->maxRadio ?? 20;
        $columns = $this->driverRec->columns ?? 2;
        $Type->params['columns'] = $columns;

        $Type->options = $options;
        foreach ($Type->options as $k => $v){
            if(is_object($v)){
                unset($Type->options[$k]);
            }
        }
        $Type->params['select2MinItems'] = 1000000;

        return $Type;
    }


    /**
     * Вербално представяне на стойноста
     *
     * @param stdClass $rec
     * @param mixed    $domainClass - клас на домейна
     * @param mixed    $domainId    - ид на домейна
     * @param string   $value
     *
     * @return mixed
     */
    public function toVerbal($rec, $domainClass, $domainId, $value)
    {
        $showVal = Mode::is('taskListMode') ? $this->driverRec->showList : $this->driverRec->show;

        if($Driver = cat_Products::getDriver($value)){
            $title = $Driver->getProductParamValueDisplay($domainClass, $domainId, $value, $showVal);
        }

        if(empty($title)){
            $title = cat_Products::getTitleById($value);
        }

        if(!Mode::is('text', 'plain')){
            $singleUrlArray = cat_Products::getSingleUrlArray($value);
            if(countR($singleUrlArray)){
                $title = ht::createLink($title, $singleUrlArray);
            }
        }

        return $title;
    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param cond_type_abstract_Proto $Driver
     * @param embed_Manager     $Embedder
     * @param stdClass          $data
     */
    protected static function on_AfterPrepareEditForm(cond_type_abstract_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $data->form->setDefault('display', 'name');
        $data->form->setDefault('orderBy', 'idAsc');
    }


    /**
     * Параметри функция за вербализиране
     *
     * @param stdClass   $rec    - запис на параметър
     * @param mixed $domainClass - клас на домейна на параметъра
     * @param int   $domainId    - ид на домейна на параметъра
     * @param mixed $newValue    - нова стойност на параметъра
     * @param mixed $oldValue    - стара стойност
     *
     * @return array $res
     */
    public function onParamChanged($rec, $domainClass, $domainId, $newValue, $oldValue) : array
    {
        $res = array('msg' => null, 'error' => null);
        if (!(isset($domainClass) && isset($domainId))) return $res;

        $Domain = cls::get($domainClass);
        if (!($Domain instanceof cat_Products)) return $res;

        $matRec = cat_Products::fetch($newValue, 'canConvert,measureId');
        if ($matRec->canConvert != 'yes') return $res;

        $Boms = cls::get('cat_Boms');
        $BomDetails = cls::get('cat_BomDetails');

        $bQuery = $BomDetails->getQuery();
        $bQuery->EXT('productId', 'cat_Boms', 'externalName=productId,externalKey=bomId');
        $bQuery->EXT('state',     'cat_Boms', 'externalName=state,externalKey=bomId');
        $bQuery->where("#productId = {$domainId} AND #state != 'rejected'");

        $details = $resourceByStages = array();
        while ($bRec = $bQuery->fetch()) {
            $resourceByStages[$bRec->bomId][$bRec->parentId][$bRec->resourceId] = $bRec->resourceId;
            $details[$bRec->id] = $bRec;
        }

        $errors = $boms = array();
        foreach ($details as $bRec) {
            if ($bRec->paramId != $rec->id) continue;
            $err = cat_Boms::tryReplaceBomMaterial($bRec, $newValue, $resourceByStages, 'resourceId,modifiedOn,modifiedBy,packagingId,quantityInPack,params');

            if ($err === null) {
                $Boms->logWrite("Смяна на материал след променен параметър", $bRec->bomId);
                $boms[$bRec->bomId] = $bRec->bomId;
                store_StockPlanning::recalcByReff($Boms, $bRec->bomId);
            } else {
                $errors[] = "|*#Bom[{$bRec->bomId}] |{$err}|*";
            }
        }

        if (countR($details)) {
            $res['msg'] = "Рецепти с подменен материал|*: " . countR($boms);
        }
        if (countR($errors)) {
            $res['error'] = "Материалът не е подменен защото|*:" . implode(', ', $errors);
        }

        return $res;
    }
}
