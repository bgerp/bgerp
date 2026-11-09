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
        $data->listFilter->setField('isUsing', 'placeholderType=all');
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
     * Обхожда данните на порции по id и продължава от там, където е спрял предишния път,
     * защото при милиони записи един пълен обход не се побира във време-лимита
     *
     * @return string|null
     */
    function cron_DeleteOldFiles()
    {
        // @todo - дали не е добре, да има списък с изключения, които да не се трият - примерно по кофи??? или по разширения??? - dae?
        // @todo - дали за isUsing не трябва да се проверява и в други таблици, освен doc_Files??? - dae?

        // Спира преди време-лимита на процеса, за да не бъде прекъснат по средата
        $cronRec = core_Cron::getRecForSystemId('DeleteOldFiles');
        $deadline = time() + max(30, (int) (($cronRec->timeLimit ?? 0) * 0.8));

        $before = dt::subtractSecs(fileman_Setup::get('DELETE_UNUSED_AFTER'));
        $lastIdKey = 'fileman_Deletes::lastDataId';
        $lastId = (int) core_Permanent::get($lastIdKey);
        $addCnt = 0;

        do {
            $dQuery = fileman_Data::getQuery();
            $dQuery->where(array("#lastUse < '[#1#]' AND #id > '[#2#]'", $before, $lastId));
            $dQuery->orderBy('id', 'ASC');
            $dQuery->limit(1000);
            $dQuery->show('id, lastUse');

            $dataArr = array();
            while ($dRec = $dQuery->fetch()) {
                $dataArr[$dRec->id] = $dRec->lastUse;
                $lastId = $dRec->id;
            }

            // Обходът е завършен - следващия път започва отначало, за да хване новоостарелите
            if (empty($dataArr)) {
                $lastId = 0;
                break;
            }

            // Вече добавените не се обновяват
            $exQuery = self::getQuery();
            $exQuery->in('dataId', array_keys($dataArr));
            $exQuery->show('dataId');
            while ($exRec = $exQuery->fetch()) {
                unset($dataArr[$exRec->dataId]);
            }

            if (empty($dataArr)) {
                continue;
            }

            $usingArr = array();
            $fQuery = doc_Files::getQuery();
            $fQuery->in('dataId', array_keys($dataArr));
            $fQuery->groupBy('dataId');
            $fQuery->show('dataId');
            while ($fRec = $fQuery->fetch()) {
                $usingArr[$fRec->dataId] = true;
            }

            $recs = array();
            foreach ($dataArr as $dataId => $lastUse) {
                $recs[] = (object) array(
                    'dataId' => $dataId,
                    'lastUse' => $lastUse,
                    'isUsing' => isset($usingArr[$dataId]) ? 'yes' : 'no',
                );
            }

            $this->saveArray($recs, 'dataId, lastUse, isUsing');
            $addCnt += countR($recs);
        } while (time() < $deadline);

        core_Permanent::set($lastIdKey, $lastId, core_Permanent::FOREVER_VALUE);

        return "Добавени записи: {$addCnt}" . ($lastId ? ", продължава от id={$lastId}" : ', обходът е завършен');
    }
}
