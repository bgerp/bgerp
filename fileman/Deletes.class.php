<?php


/**
 *
 *
 * @category  vendors
 * @package   fileman
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class fileman_Deletes extends core_Manager
{

    /**
     * Заглавие на таблицата
     */
    public $title = 'Изтривания';

    
    /**
     * Път към картинка 16x16
     */
    public $singleIcon = 'img/16/repository.png';
    
    
    public $canSingle = 'debug';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'debug';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';

    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'debug';

    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'fileman_Wrapper, plg_Sorting';

    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('dataId', 'key(mvc=fileman_Data)', 'caption=Данни');
        $this->FNC('fileHandlers', 'fileman_type_Files(bucket=eprodMontage,align=vertical)', 'caption=Файлове');
        $this->FLD('lastUse', 'datetime(format=smartTime)', 'caption=Последно');
        $this->FLD('isUsing', 'enum(,no=Не,yes=Да)', 'caption=Използва се, refreshForm');

        $this->setDbUnique('dataId');
    }


    /**
     * Добавя стойност на функционалното поле boxFrom
     *
     * @param bgerp_Portal $mvc
     * @param stdClass     $rec
     */
    public static function on_CalcFileHandlers($mvc, &$rec)
    {
        if ($rec->dataId) {
            $fQuery = fileman_Files::getQuery();
            $fQuery->where(array("#dataId = '[#1#]'", $rec->dataId));
            $fQuery->show('id');
            while ($fRec = $fQuery->fetch()) {
                $rec->fileHandlers = type_Keylist::addKey($rec->fileHandlers, $fRec->id);
            }
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if ($rec->dataId) {
            if (fileman_Data::haveRightFor('list')) {
                $row->dataId = ht::createLink($rec->dataId, array('fileman_Data', 'list', $rec->dataId));
            } else {
                $row->dataId = $rec->dataId;
            }
            $row->dataId .= ' | ' . fileman_Data::fetchField($rec->dataId, 'path');
        }
    }


    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        // Да се показва полето за търсене
        $data->listFilter->showFields = 'isUsing';

        $data->listFilter->view = 'horizontal';

        //Добавяме бутон "Филтрирай"
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');

        $data->listFilter->input();

        if ($data->listFilter->rec->isUsing) {
            $data->query->where(array("#isUsing = '[#1#]'", $data->listFilter->rec->isUsing));
        }

        $data->query->orderBy('lastUse', 'ASC');
        $data->query->orderBy('id', 'DESC');
    }


    /**
     * Изтриване на старите файлове
     *
     * @return void
     */
    function cron_DeleteOldFiles()
    {
        // @todo - дали не е добре, да има списък с изключения, които да не се трият - примерно по кофи??? или по разширения??? - dae?
        // @todo - дали за isUsing не трябва да се проверява и в други таблици, освен doc_Files??? - dae?

        $dQuery = fileman_Data::getQuery();
        $dQuery->where(array("#lastUse < '[#1#]'", dt::subtractSecs(fileman_Setup::get('DELETE_UNUSED_AFTER'))));
        $dQuery->show('id, lastUse');
        while ($dRec = $dQuery->fetch()) {
            $rec = new stdClass();
            if (doc_Files::fetch(array("#dataId = '[#1#]'", $dRec->id))) {
                $rec->isUsing = 'yes';
            } else {
                $rec->isUsing = 'no';
            }
            $rec->dataId = $dRec->id;
            $rec->lastUse = $dRec->lastUse;

            $this->save($rec, null, 'IGNORE');
        }
    }
}
