<?php


/**
 * Клас 'cond_TariffCodes' - Митнически тарифни кодове
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_TariffCodes extends core_Manager
{
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin,ceo';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'ceo,admin';


    /**
     * Кой може да добавя
     */
    public $canAdd = 'ceo,admin';


    /**
     * Кой може да редактира
     */
    public $canEdit = 'ceo,admin';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2,cond_Wrapper,plg_Modified';


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tariffCode,descriptionBg,descriptionEn,modifiedOn,modifiedBy';


    /**
     * Заглавие
     */
    public $title = 'Митнически тарифни кодове';


    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'Митнически тарифен код';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('tariffCode', 'varchar(16)', 'caption=МТК,mandatory');
        $this->FLD('descriptionBg', 'varchar(48)', 'caption=Описание,mandatory');
        $this->FLD('descriptionEn', 'varchar(48)', 'caption=Описание (межд.),mandatory');

        $this->setDbUnique('tariffCode');
    }


    /**
     * Намира описанието на кода на посочения език
     *
     * @param string $code
     * @param string|null $lg
     * @return string|null
     */
    public static function getDescriptionByCode($code, $lg = null)
    {
        $lg = $lg ?? core_Lg::getCurrent();

        // Връщане на описанието според езика
        $rec = static::fetch(array("#tariffCode = '[#1#]'", $code));
        if(is_object($rec)) return $lg == 'bg' ? $rec->descriptionBg : $rec->descriptionEn;

        return null;
    }
}
