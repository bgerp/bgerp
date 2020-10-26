<?php

/**
 * class school_ClassDriver
 *
 * Продуктов драйвер за обучителен клас
 *
 * @category  bgerp
 * @package   edu
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Обучителен клас
 */
class school_ClassDriver extends cat_GeneralProductDriver
{

    /**
     * Дефолт мета данни за всички продукти
     */
    protected $defaultMetaData = 'canSell,canBuy';
    
    
    /**
     * Стандартна мярка за ЕП продуктите
     */
    public $uom = 'pcs';


   /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        parent::addFields($fieldset);
        $fieldset->FLD('courseId', 'key(mvc=school_Courses,select=title)', 'caption=Курс||Course,autohide,after=name');
        $fieldset->FLD('courseType', 'enum(fullTime=Редовно,partTime=Вечерно,extramural=Задочно,online=Дистанционно)', 'caption=Форма,autohide,after=courseId');
        $fieldset->FLD('coursePlace', 'varchar(32)', 'caption=Място,autohide,after=type');
        $fieldset->FLD('courseStart', 'combodate(minYear=2020)', 'caption=Начало,autohide,after=place');
        $fieldset->FLD('courseEnd', 'combodate(minYear=2020)', 'caption=Край,autohide,after=start');
        $fieldset->FLD('courseReqDocuments', 'keylist(mvc=school_ReqDocuments,select=name)', 'caption=Документи за записване->Изискуеми');
        $fieldset->FLD('courseOptDocuments', 'keylist(mvc=school_ReqDocuments,select=name)', 'caption=Документи за записване->Опционални');

  //      $fieldset->FLD('courseOptDocuments', 'keylist(mvc=school_ReqDocuments,select=name)', 'caption=Документи за записване->Опционални');



        $fieldset->FLD('courseClassId', 'key(mvc=school_Classes,select=id)', 'caption=Клас,input=hidden');
 
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
        $form = $data->form;

        if ($form->getField('meta', false)) {
            $form->setField('meta', 'input=hidden');
        }
        
        if ($form->getField('measureId', false)) {
            $form->setField('measureId', 'input=hidden');
        }
        
        if ($form->getField('info', false)) {
            $form->setField('info', 'input=hidden');
        }
        
        if ($form->getField('packQuantity', false)) {
            $form->setField('packQuantity', 'input=hidden');
        }
        if ($form->getField('nameEn', false)) {
            $form->setField('nameEn', 'input=hidden');
        }

        if ($form->getField('notes', false)) {
            $form->setField('notes', 'input=hidden');
        }
        if ($form->getField('infoInt', false)) {
            $form->setField('infoInt', 'input=hidden');
        }

        if(is_a($Embedder, 'marketing_Inquiries2')) {
            $form->setReadOnly('courseId,courseType,courseStart,courseEnd,coursePlace');
        }
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
     * Връща заглавието на продукта
     */
    public function getProductTitle($rec)
    {
        $cDate = core_Type::getByName('combodate');
        $title = school_Courses::fetchField($rec->courseId, 'title') . '/' . $cDate->toVerbal($rec->start);
         
        return $title;
    }


    /**
     * Подготвя групите, в които да бъде вкаран продукта
     */
    public static function on_BeforeSave($Driver, embed_Manager &$Embedder, &$id, &$rec, $fields = null)
    {
        if(!isset($rec->id)) {
            unset($rec->classId);
        }
    }


    /**
     * Извиква се след успешен запис в модела
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param int               $id
     * @param stdClass          $rec
     */
    public static function on_AfterSave(cat_ProductDriver $Driver, embed_Manager $Embedder, &$id, $rec)
    {
        $cRec = new stdClass();
        if(is_numeric($rec->classId)) { 
            $cRec->id = $rec->classId;
        }
        $cRec->courseId = $rec->courseId;
        $cRec->start     = $rec->start;
        $cRec->end       = $rec->end;
        $cRec->state     = $rec->state;

        $cRec->productId = $rec->id;
        school_Classes::save($cRec);
        if(!is_numeric($rec->classId)) {
            $rec->courseClassId = $cRec->id;
            cat_Products::save($rec, 'courseClassId'); // bp(cat_Products::fetch($rec->id));
        }
    }
    

   
}
