<?php

/**
 * GPS Координати на локациите
 *
 *
 * @category  bgerp
 * @package   location
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     GPS Координати на локациите
 */
class location_LocationsCoords extends core_Master
{
    public $title = 'GPS Координати на локациите';

    public $loadList = 'plg_RowTools2,plg_Created,plg_State2';

    /**
     * Кой има право да чете?
     *
     * @var string|array
     */
    public $canRead;


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,debug';


    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'ceo,admin,debug';


    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'ceo,admin,debug';


    /**
     * Кой може да го види?
     *
     * @var string|array
     */
    public $canView = 'ceo,admin,debug';


    /**
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = 'no_one';

    /**
     * Полета за показване
     *
     * var string|array
     */
    public $listFields;


    /**
     * Описание на модела (таблицата)
     */
    protected function description()
    {

        $this->FLD('lat', 'varchar', 'caption=Географска ширина');
        $this->FLD('lng', 'varchar', 'caption=Географска дължина');
        $this->FLD('title', 'varchar', 'caption=Наименование');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,class=contactData');
        $this->FLD('originId', 'int', 'caption=Локация,input = hidden ');

    }


    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;


    }


    /**
     * Добавя бутони  към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {

        $data->toolbar->addBtn('Изход', array('location_LocationsCoords', 'ret_url' => true));
    }

    }

