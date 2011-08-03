<?php

/**
 * Клас 'common_Mvr'
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    common
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class common_Mvr extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, common_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, city, account, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'МВР по страната';
    

    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'admin, common';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin, common';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin, common';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin, common';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('city',    'varchar', 'caption=Град, mandatory');
        $this->FLD('account', 'varchar', 'caption=Сметка, input=none');
        $this->setDbUnique('city');
    }
    
    
    /**
     * Сортиране по city
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#city');
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        $data = array(
            array('city' => 'Благоевград',    'account' => 'BG69 RZBB 9155 3320 0377 15'),
            array('city' => 'Бургас',         'account' => 'BG42 STSA 9300 3303 5761 55'),
            array('city' => 'Варна',          'account' => 'BG55 TTBB 9400 3315 0687 77'),
            array('city' => 'Велико Търново', 'account' => 'BG09 UNCR 7527 3340 0445 37'),
            array('city' => 'Видин',          'account' => 'BG07 UNCR 7630 3300 0000 60'),
            array('city' => 'Враца',          'account' => 'BG74 UBBS 8002 3300 1359 33'),
            array('city' => 'Габрово',        'account' => 'BG63 STSA 9300 3305 0169 06'),
            array('city' => 'Добрич',         'account' => 'BG04 BUIN 7082 3360 0006 33'),
            array('city' => 'Кърджали',       'account' => 'BG32 RZBB 9155 3320 0373 14'),
            array('city' => 'Кюстендил',      'account' => 'BG79 STSA 9300 3303 3016 58'),
            array('city' => 'Ловеч',          'account' => 'BG33 UNCR 9660 3385 2667 18'),
            array('city' => 'Монтана',        'account' => 'BG98 UBBS 8002 3300 1311 36'),
            array('city' => 'Пазарджик',      'account' => 'BG51 UNCR 7630 3300 0001 41'),
            array('city' => 'Перник',         'account' => 'BG22 UBBS 8002 3300 1284 30'),
            array('city' => 'Плевен',         'account' => 'BG92 UNCR 7630 3300 0003 73'),
            array('city' => 'Пловдив',        'account' => 'BG44 UNCR 9660 3359 8981 10'),
            array('city' => 'Разград',        'account' => 'BG66 UBBS 8002 3300 1213 33'),
            array('city' => 'Русе',           'account' => 'BG51 UBBS 8002 3300 1237 37'),
            array('city' => 'Силистра',       'account' => 'BG13 UBBS 8002 3300 1370 31'),
            array('city' => 'Сливен',         'account' => 'BG34 UBBS 8002 3300 1307 36'),
            array('city' => 'Смолян',         'account' => 'BG95 STSA 9300 3300 6718 94'),
            array('city' => 'София-област',   'account' => 'BG40 UNCR 9660 3318 0869 14'),
            array('city' => 'Стара Загора',   'account' => 'BG47 UNCR 7630 3300 0003 10'),
            array('city' => 'Търговище',      'account' => 'BG33 UBBS 8002 3300 1215 39'),
            array('city' => 'Хасково',        'account' => 'BG86 RZBB 9155 3320 0405 13'),
            array('city' => 'Шумен',          'account' => 'BG93 UBBS 8002 3300 1246 30'),
            array('city' => 'Ямбол',          'account' => 'BG20 STSA 9300 3305 1437 80')                        
        );
        
        if(!$mvc->fetch("1=1")) {
            
            $nAffected = 0;
            
            foreach ($data as $rec) {
                $rec = (object)$rec;
                
                if (!$this->fetch("#city='{$rec->city}'")) {
                    if ($this->save($rec)) {
                        $nAffected++;
                    }
                }
            }
        }
        
        if ($nAffected) {
            $res .= "<li>Добавени са {$nAffected} записа.</li>";
        }
    }
    
}