<?php



/**
 * Драйвер за артикул тип услуга: поддръжка/ремонт
 *
 * Този клас управлява специфичните атрибути и поведение
 * на артикулите, свързани със сервизно обслужване и ремонтни дейности.
 *
 * @category  bgerp
 * @package   cat
 * @author    [Твоето ИМЕ] <[твоят@имейл.com]>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Поддръжка/Ремонт
 */
class cat_RepairProductDriver extends cat_ProductDriver
{

    /**
     * Интерфейси които имплементира
     */
    public $interfaces = 'cat_ProductDriverIntf';



    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('executionPlace', 'enum(,service=В сервиза, onsite=На адрес, remote=Отдалечено)', 'caption=Място');
        $fieldset->FLD('serviceLevel', 'enum(,standard=Стандартна, express=Експресна, emergency=Аварийна)', 'caption=Тип');
        $fieldset->FLD('resources', 'set(material=Материал, place=Място, transport=Транспорт)', 'caption=Ресурси');
        $fieldset->FLD('purchaseDate', 'date', 'caption=Закупен на');
        $fieldset->FLD('warrantyPeriod', 'time(uom=days, suggestions=30 дни|90 дни|180 дни|365 дни)', 'caption=Гаранция->Период');
        $fieldset->FLD('warrantyAfterRepair', 'time(uom=days, suggestions=1 ден|1 седмица|1 месец|1 година)', 'caption=Гаранция->След ремонта');
        $fieldset->FLD('estimatedLaborTime', 'time(uom=hours)', 'caption=Очаквано време за ремонт->Време');
        $fieldset->FLD('problemDescription', 'text(rows=2)', 'caption=Описание->Проблем');
        $fieldset->FLD('additionalInfo', 'text(rows=2)', 'caption=Описание->Допълнително');
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
        $form = &$data->form;
        $rec = &$form->rec;

        if (cls::haveInterface('marketing_InquiryEmbedderIntf', $Embedder)) {
            $form->setField('quantities', 'input=none');
            $form->setField('quantity1', 'input=none');
            $form->setField('quantity2', 'input=none');
            $form->setField('quantity3', 'input=none');
        }
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

        return null;
    }
}