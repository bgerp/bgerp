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
        $fieldset->FLD('display', 'enum(name=Наименование,info=Описание)', 'caption=Конкретизиране->Избор,mandatory');
        $fieldset->FLD('orderBy', 'enum(idAsc=По артикул [нарастващ ред],idDesc=По артикул [намаляващ ред],codeAsc=По код [нарастващ ред],codeDesc=По код [намаляващ ред])', 'caption=Конкретизиране->Подредба,mandatory');
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
        $Type = core_Type::getByName('key2(mvc=cat_Products,select=name,selectSourceArr=cat_Products::getProductOptions,allowEmpty,maxSuggestions=100,forceAjax)');
        $Type->params['groups'] = $this->driverRec->productGroups;
        if(isset($this->driverRec->display) && $this->driverRec->display != 'name'){
            $Type->params['display'] = $this->driverRec->display;
        }

        $orderBy = isset($this->driverRec->orderBy) ? $this->driverRec->orderBy : 'idAsc';
        $orderByField = ($orderBy == 'idAsc') ? 'id=ASC' : (($orderBy == 'idDesc') ? 'id=DESC' : (($orderBy == 'codeAsc') ? 'code=ASC' : 'code=DESC'));
        $Type->params['orderBy'] = $orderByField;

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
        if($this->driverRec->show == 'info'){
            Mode::push('text', 'plain');
            $title = cat_Products::getVerbal($value, 'info');
            Mode::pop('text');
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
}