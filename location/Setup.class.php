<?php


defIfNot('LOCATION_DEFAULT_REGION', '');


defIfNot('LOCATION_GMAP3_VERSION', '6.0');


/**
 *
 *
 * @category  bgerp
 * @package   location
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2012 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class location_Setup extends core_ProtoSetup
{
    /**
     * Версията на пакета
     */
    public $version = '0.1';


    /**
     * Описание на модула
     */
    public $info = 'Локация';

    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'location_Places',
        'location_LocationsCoords',
        'migrate::fillGpsCoords',

    );


    /**
     * Пакет без инсталация
     */
    //  public $noInstall = true;


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(

        'LOCATION_DEFAULT_REGION' => array('varchar', 'mandatory, caption=Кой регион да се използва по подрабиране->Регион'),
        'LOCATION_GMAP3_VERSION' => array('enum(4.1, 6.0, 7.2)', 'mandatory, caption=Версията на програмата->Версия')
    );


    /*
     * Миграция за попълване на location_LocationsCoords
     */
    public function fillGpsCoords()
    {

        $query = crm_Locations::getQuery();
        $query->where("#state != 'rejected' AND #gpsCoords IS NOT NULL");

        $lQuery = location_LocationsCoords::getQuery();
        $lQuery->show('originId');

        $locationsArr = $cRec = array();
        if ($lQuery->count()) {

            $locationsArr = arr::extractValuesFromArray($lQuery->fetchAll(), 'originId');

        }

        while ($lRec = $query->fetch()) {

            if (in_array($lRec->id, $locationsArr)) continue;

            list($lat, $lng) = explode(',', $lRec->gpsCoords);

            $cRec[$lRec->id] = (object)array('lat' => $lat,
                'lng' => $lng,
                'title' => $lRec->title,
                'address' => $lRec->address,
                'originId' => $lRec->id

            );

        }
        if (!empty($cRec)) {
            foreach ($cRec as $v) {
                location_LocationsCoords::save($v);
            }
        }

    }

}
