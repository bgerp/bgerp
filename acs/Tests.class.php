<?php


/**
 *
 *
 * @category  bgerp
 * @package   acs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acs_Tests extends core_Manager
{
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Тестване';
    
    
    /**
     * Титлата на обекта в единичен изглед
     */
    public $singleTitle = 'Тестване';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Sorting, acs_Wrapper, plg_Created, plg_Sorting, plg_RowTools';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'debug';
    
    
    /**
     * Кой има достъп до сингъла
     */
    public $canSingle = 'debug';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('permId', 'key(mvc=acs_Permissions, select=cardId)', 'caption=Карта, mandatory');
        $this->FLD('zoneId', 'key(mvc=acs_Zones, select=name)', 'caption=Зона, mandatory');

        $this->setDbUnique('permId, zoneId');
    }


    /**
     * Извиква се след подготовката на $data->recs и $data->rows за табличния изглед
     */
    public function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        foreach ($data->rows as $id => $row) {
            $cardId = acs_Permissions::fetchField($data->recs[$id]->permId, 'cardId');
            if (acs_Permissions::isCardHaveAccessToZone($cardId, $data->recs[$id]->zoneId)) {
                $row->ROW_ATTR['style'] .= ' color: green';
            } else {
                $row->ROW_ATTR['style'] .= ' color: red';
            }
        }
    }


    /**
     * Сортиране
     */
    public function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('createdOn', 'DESC');
    }


    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if ($mvc->haveRightFor('sync')) {
            $data->toolbar->addBtn('Тест', array($mvc, 'test'), null, 'ef_icon = img/16/arrow_refresh.png,title=Показване на всички тестове, target=_blank');
        }
    }


    /**
     * Временен тестов екшън
     *
     * @todo - премахване
     */
    function act_Test()
    {
        requireRole('debug');
        requireRole('admin');

        $res = "";

        $query = acs_Permissions::getQuery();
        $query->groupBy('cardId');
        $query->orderBy('createdOn', 'DESC');
        $zones = '';
        $cArr = array();
        while ($rec = $query->fetch()) {
            $rec->cardId = trim($rec->cardId);
            if (!$rec->cardId) {

                continue;
            }

            $cArr[$rec->cardId] = $rec->cardId;
            $zones = type_Keylist::merge($zones, $rec->zones);
        }

        $zArr = type_Keylist::toArray($zones);
        foreach ($cArr as $cId) {
            $res .= "<li style='color: black;'>cardId: {$cId}</li>";
            foreach ($zArr as $zId) {
                $styleColor = 'red';

                if (acs_Permissions::isCardHaveAccessToZone($cId, $zId)) {
                    $styleColor = 'green';
                }

                $zoneName = acs_Zones::getVerbal($zId, 'name');

                $res .= "<li style='color: {$styleColor};'>zoneId: {$zId}|{$zoneName} </li>";
            }
            $res .= "<hr>";
        }

        echo $res;

        echo "<pre>";

        $cardsResArr = acs_Permissions::getRelationsMap('card');
        $zonesResArr = acs_Permissions::getRelationsMap('zone');

        foreach ($cardsResArr as $cardId => $cArr) {
            foreach ($cArr as $zId => $tArr) {
                if ($tArr['activeFrom']) {
                    $cardsResArr[$cardId][$zId]['activeFrom'] = dt::mysql2verbal(dt::timestamp2Mysql($tArr['activeFrom']), 'smartTime');
                }

                if ($tArr['activeUntil']) {
                    $cardsResArr[$cardId][$zId]['activeUntil'] = dt::mysql2verbal(dt::timestamp2Mysql($tArr['activeUntil']), 'smartTime');
                }
            }
        }

        foreach ($zonesResArr as $zId => $cArr) {
            foreach ($cArr as $cardId => $tArr) {
                if ($tArr['activeFrom']) {
                    $zonesResArr[$zId][$cardId]['activeFrom'] = dt::mysql2verbal(dt::timestamp2Mysql($tArr['activeFrom']), 'smartTime');
                }

                if ($tArr['activeUntil']) {
                    $zonesResArr[$zId][$cardId]['activeUntil'] = dt::mysql2verbal(dt::timestamp2Mysql($tArr['activeUntil']), 'smartTime');
                }
            }
        }

        var_dump($cardsResArr);
        echo "<hr>";
        var_dump($zonesResArr);

        shutdown();
    }
}
