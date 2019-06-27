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
    
    public $loadList = 'plg_RowTools2,plg_Created,plg_State2';
    
    public $listFields;
    
    
    /**
     * Кой има право да чете?
     *
     * @var string|array
     */
    public $canRead;
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo';
    
    
    /**
     * Кой има право да променя?
     *
     * @var string|array
     */
    public $canEdit = 'ceo';
    
    
    /**
     * Кой има право да добавя?
     *
     * @var string|array
     */
    public $canAdd = 'ceo';
    
    
    /**
     * Кой може да го види?
     *
     * @var string|array
     */
    public $canView = 'ceo';
    
    
    /**
     * Кой може да го изтрие?
     *
     * @var string|array
     */
    public $canDelete = 'ceo';
    
    
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
     * @param stdClass      $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        
        
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        if ($form->isSubmitted()) {
            
        }
    }
    
    
    /**
     * Преди показване на листовия тулбар
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $data->toolbar->addBtn('Вербализирай', array($mvc, 'ToVerbal'));
    }
    public function act_ToVerbal()
    {
        
        $posFrom = self::fetch(1);
        $posTo = self::fetch(8);
        
        list($latitudeFrom,$longitudeFrom)=explode(',', $posFrom->location);
        list($latitudeTo,$longitudeTo)=explode(',', $posTo->location);
        
        $distace = self::vincentyGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo);
        
        $angle = self::angleFromCoordinate($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo);
        
        $direction = self::getDirection($angle);
        
        if($distace > $posFrom->diameter){
            
            if($distace < 1000){
                
                $measure = 'метра';
                $distace = round($distace,0);
            }else{
                
                $measure = 'км.';
                $distace = round($distace/1000,2);
                
            }
        }else{
            $measure = '';
            $distace = 'в '.$posFrom->place;
            $direction = '';
        }
        
        $position = is_numeric($distace) ? $distace.$measure.' / '.$direction :$distace;
        
        bp($position,$posFrom->place,$posTo->place,'azimut: '.$angle,'distance: '.$distace,'direction: '.$direction,$latitudeFrom,$longitudeFrom,$latitudeTo,$longitudeTo);
        
        
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
    public static function vincentyGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
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
    
    public static function getDirection($angle)
    {
        switch ($angle) {
            
            case (($angle > 0 && $angle <= 22.5) || ($angle > 337.5 && $angle <= 0)): $direction = 'Север'; break;
            case ($angle > 22.5 && $angle <= 67.5): $direction = 'СИ'; break;
            case ($angle > 67.5 && $angle <= 112.5): $direction = 'Изток'; break;
            case ($angle > 112.5 && $angle <= 157.5): $direction = 'ЮИ'; break;
            case ($angle > 157.5 && $angle <= 202.5): $direction = 'Юг'; break;
            case ($angle > 202.5 && $angle <= 247.5): $direction = 'ЮЗ'; break;
            case ($angle > 247.5 && $angle <= 292.5): $direction = 'Запад'; break;
            case ($angle > 292.5 && $angle <= 337.5): $direction = 'СЗ'; break;
            
            
            
        }
        
        return $direction;
    }
    
    /**
     * Calculate angle between 2 given latLng
     * @param  float $latitudeFrom
     * @param  float $latitudeTo
     * @param  float $longitudeFrom
     * @param  float $longitudeTo
     * @return integer
     */
    
    function angleFromCoordinate($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo) {
        
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
    
  
    
}