<?php



/**
 * Клас 'drdata_DistrictCourts' -
 *
 * Окръжни съдилища
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bglocal_DistrictCourts extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, bglocal_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, city, type, code';
    
    
    /**
     * Заглавие
     */
    var $title = 'Окръжни съдилища';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, common';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, common';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, common';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, common';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('city',  'varchar', 'caption=Град, mandatory');
        $this->FLD('type',  'enum(cityCourt=Градски съд, districtCourt=Окръжен съд )', 'caption=Обхват, mandatory');
        $this->FLD('code',  'varchar(3)', 'caption=Код, mandatory');
    }
    
    
    /**
     * Сортиране по name
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#city');
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $data = array(
            array('city' => 'Благоевград',    'type' => 'districtCourt', 'code' => '120'),
            array('city' => 'Бургас',         'type' => 'districtCourt', 'code' => '210'),
            array('city' => 'Варна',          'type' => 'districtCourt', 'code' => '310'),
            array('city' => 'Велико Търново', 'type' => 'districtCourt', 'code' => '410'),
            array('city' => 'Видин',          'type' => 'districtCourt', 'code' => '130'),
            array('city' => 'Враца',          'type' => 'districtCourt', 'code' => '140'),
            array('city' => 'Габрово',        'type' => 'districtCourt', 'code' => '420'),
            array('city' => 'Добрич',         'type' => 'districtCourt', 'code' => '320'),
            array('city' => 'Кюстендил',      'type' => 'districtCourt', 'code' => '150'),
            array('city' => 'Кърджали',       'type' => 'districtCourt', 'code' => '510'),
            array('city' => 'Ловеч',          'type' => 'districtCourt', 'code' => '430'),
            array('city' => 'Монтана',        'type' => 'districtCourt', 'code' => '160'),
            array('city' => 'Перник',         'type' => 'districtCourt', 'code' => '170'),
            array('city' => 'Плевен',         'type' => 'districtCourt', 'code' => '440'),
            array('city' => 'Пловдив',        'type' => 'districtCourt', 'code' => '530'),
            array('city' => 'Пазарджик',      'type' => 'districtCourt', 'code' => '520'),
            array('city' => 'Разград',        'type' => 'districtCourt', 'code' => '330'),
            array('city' => 'Русе',           'type' => 'districtCourt', 'code' => '450'),
            array('city' => 'София',          'type' => 'cityCourt',     'code' => '110'),
            array('city' => 'София-област',   'type' => 'districtCourt', 'code' => '180'),
            array('city' => 'Сливен',         'type' => 'districtCourt', 'code' => '220'),
            array('city' => 'Силистра',       'type' => 'districtCourt', 'code' => '340'),
            array('city' => 'Смолян',         'type' => 'districtCourt', 'code' => '540'),
            array('city' => 'Стара Загора',   'type' => 'districtCourt', 'code' => '550'),
            array('city' => 'Търговище',      'type' => 'districtCourt', 'code' => '350'),
            array('city' => 'Хасково',        'type' => 'districtCourt', 'code' => '560'),
            array('city' => 'Шумен',          'type' => 'districtCourt', 'code' => '360'),
            array('city' => 'Ямбол',          'type' => 'districtCourt', 'code' => '230')
        );
        
        if(!$mvc->fetch("1=1")) {
            
            $nAffected = 0;
            
            foreach ($data as $rec) {
                $rec = (object)$rec;
                
                if (!$mvc->fetch("#city='{$rec->city}'")) {
                    if ($mvc->save($rec)) {
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