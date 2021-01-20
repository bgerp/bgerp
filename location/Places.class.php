<?php

/**
 * Мениджър на вербализация на позиция
 *
 *
 * @category  bgerp
 * @package   location
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Вербализация на позиция
 */
class location_Places extends core_Master
{
    public $title = 'Вербализация на позиция';

    public $loadList = 'plg_RowTools2,plg_Created,plg_State2,location_Wrapper';

    /**
     * Кой има право да чете?
     *
     * @var string|array
     */
    public $canRead;


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,tracking';


    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'ceo,admin,tracking';


    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'ceo,admin,tracking';


    /**
     * Кой може да го види?
     *
     * @var string|array
     */
    public $canView = 'ceo,admin,tracking';


    /**
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = 'ceo,admin,tracking';

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

        $this->FLD('place', 'varchar', 'caption=Място');
        $this->FLD('location', 'location_Type', 'caption=Локация, tdClass=large-field');
        $this->FLD('diameter', 'varchar', 'caption=Вътрешен диаметър');
        $this->FLD('state', 'enum(active=Активиран,closed=Затворено)', 'caption=Статус, input=none');

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

        $data->toolbar->addBtn('Изход', array('location_Places', 'ret_url' => true));
    }


    /**
     *Вербализира позицията на обекта спрямо базата
     *
     * @param string $coordinates във вида: 'latitude, longitude'
     *
     * @return string
     */
    public static function toVerbal($coordinates)
    {
        // Координати на обекта чиято позиция вербализираме
        list($latitudeTo, $longitudeTo) = explode(',', $coordinates);

        //Определяне координатите на най–близката база
        $closestBase = self::getClosestBase($latitudeTo, $longitudeTo);

        $closestBaseName = $closestBase->name;

        $latitudeFrom = $closestBase->lat;

        $longitudeFrom = $closestBase->lng;
        //Определяне на разстоянието от най–близката база до обекта
        $distance = self::vincentyGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo);

        //Определяне на азимута от най–близката база към обекта
        $angle = self::angleFromCoordinate($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo);

        //Вербализиране на посоката
        $direction = self::getDirection($angle);
        $oldDeistance = $distance;

        if ($distance >= 30) {

            switch ($distance) {

                case $distance>=30 && $distance<100:$distance = round($distance/10,0)*10; break;
                case $distance>=100 && $distance<1000:$distance = round($distance/100,0)*100; break;
                case $distance>=1000 && $distance<5000:$distance = round($distance/1000,1); break;
                case $distance>5000:$distance = round($distance/1000,0); break;

            }

            $measure = ($distance < 1000 && $oldDeistance < 1000) ? 'м.' : 'км.';

        } else {
            $measure = '';
            $distance = 'в ' . $closestBaseName;
            $direction = '';
        }

        $position = is_numeric($distance) ? $distance . $measure . ' / ' . $direction . ' от ' . $closestBaseName : $distance;

        return $position;
    }

    /**
     * Calculates the great-circle distance between two points, with
     * the Vincenty formula.
     *
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param float $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    protected static function vincentyGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);

        return $angle * $earthRadius;
    }


    /**
     *Вербализира азимута на точка спрямо базата
     *
     * @param float $angle
     * @return string
     */
    protected static function getDirection($angle)
    {
        switch ($angle) {

            case (($angle > 0 && $angle <= 22.5) || ($angle > 337.5 && $angle <= 360)):
                $direction = 'северно';
                break;
            case ($angle > 22.5 && $angle <= 67.5):
                $direction = 'СИ';
                break;
            case ($angle > 67.5 && $angle <= 112.5):
                $direction = 'източно';
                break;
            case ($angle > 112.5 && $angle <= 157.5):
                $direction = 'ЮИ';
                break;
            case ($angle > 157.5 && $angle <= 202.5):
                $direction = 'южно';
                break;
            case ($angle > 202.5 && $angle <= 247.5):
                $direction = 'ЮЗ';
                break;
            case ($angle > 247.5 && $angle <= 292.5):
                $direction = 'западно';
                break;
            case ($angle > 292.5 && $angle <= 337.5):
                $direction = 'СЗ';
                break;


        }

        return $direction;
    }

    /**
     * Намира азимута между две точки lat, lаng
     * @param float $latitudeFrom
     * @param float $latitudeTo
     * @param float $longitudeFrom
     * @param float $longitudeTo
     * @return integer
     */

    protected static function angleFromCoordinate($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo)
    {

        $lat1 = deg2rad($latitudeFrom);
        $lat2 = deg2rad($latitudeTo);
        $long1 = deg2rad($longitudeFrom);
        $long2 = deg2rad($longitudeTo);

        $dLon = $long2 - $long1;

        $y = sin($dLon) * cos($lat2);
        $x = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($dLon);

        $brng = atan2($y, $x);

        $brng = $brng * 180 / pi();
        $brng = fmod($brng + 360, 360);

        return $brng;
    }

    /**
     *Вербализира азимута на точка спрямо базата
     *
     * @param float $latitudeTo
     * @param float $longitudeTo
     * @return int
     */
    protected static function getClosestBase($latitudeTo, $longitudeTo)
    {
        $closestBase = array();

        foreach (array('location_LocationsCoords','drdata_bg_Places') as $cls) {

            $dis= ( $cls == 'drdata_bg_Places') ? 0.05 :0.002;

            $countC = 0;
            while ($countC <= 0) {
                $query = $cls::getQuery();
                $query->where("#lat < ($latitudeTo+$dis) AND #lat > ($latitudeTo-$dis) AND
                               #lng < ($longitudeTo+$dis) AND #lng > ($longitudeTo-$dis)");
                $countC = $query->count();

                $dis += $dis;

            }

            while ($base = $query->fetch()) {

                $latitudeFrom = $longitudeFrom = $distance = 0;

                $latitudeFrom = $base->lat;

                $longitudeFrom = $base->lng;

                $distance = self::vincentyGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo);

                if ($closestBase->distance && $closestBase->distance < $distance) continue;

                $name = ($cls == 'location_LocationsCoords') ? $base->title : $base->city;

                $closestBase = (object)array('lat' => $latitudeFrom,
                    'lng' => $longitudeFrom,
                    'distance' => $distance,
                    'name' => $name,
                );


            }
        }

        return $closestBase;
    }


}