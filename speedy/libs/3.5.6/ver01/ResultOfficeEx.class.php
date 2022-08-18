<?php

require_once 'ResultAddressEx.class.php';
require_once 'ResultWorkingTime.class.php';
require_once 'Size.class.php';


/**
 * Instances of this class are returned as a result of Speedy web service queries for offices
 */
class ResultOfficeEx {

    /**
     * Office ID
     * @var integer Signed 64-bit
     */
    private $_id;

    /**
     * Office name
     * @var string
     */
    private $_name;

    /**
     * Serving site ID
     * @var string
     */
    private $_siteId;

    /**
     * Office address
     * @var ResultAddressEx
     */
    private $_address;

    /**
     * Working time for FULL working days - FROM
     * @var integer Signed 16-bit integer ("HHmm" format, i.e., the number "1315" means "13:15", "830" means "8:30" etc.)
     */
    private $_workingTimeFrom;

    /**
     * Working time for FULL working days - TO
     * @var integer Signed 16-bit integer ("HHmm" format, i.e., the number "1315" means "13:15", "830" means "8:30" etc.)
     */
    private $_workingTimeTo;

    /**
     * Working time for HALF working days - FROM
     * @var integer Signed 16-bit integer ("HHmm" format, i.e., the number "1315" means "13:15", "830" means "8:30" etc.)
     */
    private $_workingTimeHalfFrom;

    /**
     *Working time for HALF working days - TO
     * @var integer Signed 16-bit integer ("HHmm" format, i.e., the number "1315" means "13:15", "830" means "8:30" etc.)
     */
    private $_workingTimeHalfTo;
    
    /**
     * Max parcel dimensions (size)
     * @var Size
     * @since 2.6.0
     */
    protected $_maxParcelDimensions;
    
    /**
     * Max parcel weight
     * @var double signed 64-bit
     * @since 2.6.0
     */
    protected $_maxParcelWeight;
    
    /**
     * Working time schedule
     * @var array of ResultWorkingTime
     * @since 2.6.0
     */
    protected $_workingTimeSchedule;

    /**
     * Office type
     * @var integer Signed 16-bit integer
     * @since 3.0.1
     */
    protected $_officeType;

    /**
     * Working time for DAY-OFF working days - FROM
     * @var signed 16-bit integer (nullable)
     * @since 3.2.6
     */
    protected $_workingTimeDayOffFrom;

    /**
     * Working time for DAY-OFF working days - TO
     * @var signed 16-bit integer (nullable)
     * @since 3.2.6
     */
    protected $_workingTimeDayOffTo;

     /**
     * Nearby office ID
     * @var integer signed 64-bit integer (nullable)
     * @since 3.3.2
     */
    protected $_nearbyOfficeId;

     /**
     * Specifies whether drop-off in office is allowed
     * @var boolean
     * @since 3.5.3
     */
    protected $_broughtToAllowed;

     /**
     * Specifies whether pickup from office is allowed
     * @var boolean
     * @since 3.5.3
     */
    protected $_toBeCalledAllowed;
    
      /**
     * Constructs new instance of ResultStreet
     * @param stdClass $stdClassResultStreet
     */
    function __construct($stdClassResultOffice) {
        $this->_id                  = isset($stdClassResultOffice->id)                  ? $stdClassResultOffice->id                            : null;
        $this->_name                = isset($stdClassResultOffice->name)                ? $stdClassResultOffice->name                          : null;
        $this->_siteId              = isset($stdClassResultOffice->siteId)              ? $stdClassResultOffice->siteId                        : null;
        $this->_address             = isset($stdClassResultOffice->address)             ? new ResultAddressEx($stdClassResultOffice->address)  : null;
        $this->_workingTimeFrom     = isset($stdClassResultOffice->workingTimeFrom)     ? $stdClassResultOffice->workingTimeFrom               : null;
        $this->_workingTimeTo       = isset($stdClassResultOffice->workingTimeTo)       ? $stdClassResultOffice->workingTimeTo                 : null;
        $this->_workingTimeHalfFrom = isset($stdClassResultOffice->workingTimeHalfFrom) ? $stdClassResultOffice->workingTimeHalfFrom           : null;
        $this->_workingTimeHalfTo   = isset($stdClassResultOffice->workingTimeHalfTo)   ? $stdClassResultOffice->workingTimeHalfTo             : null;
        $this->_maxParcelDimensions = isset($stdClassResultOffice->maxParcelDimensions) ? new Size($stdClassResultOffice->maxParcelDimensions) : null;
        $this->_maxParcelWeight     = isset($stdClassResultOffice->maxParcelWeight)     ? $stdClassResultOffice->maxParcelWeight               : null;
        $this->_officeType          = isset($stdClassResultOffice->officeType)          ? $stdClassResultOffice->officeType                    : null;
        $this->_workingTimeDayOffFrom = isset($stdClassResultOffice->workingTimeDayOffFrom) ? $stdClassResultOffice->workingTimeDayOffFrom : null;
        $this->_workingTimeDayOffTo = isset($stdClassResultOffice->workingTimeDayOffTo) ? $stdClassResultOffice->workingTimeDayOffTo : null;
        $this->_broughtToAllowed = isset($stdClassResultOffice->broughtToAllowed) ? $stdClassResultOffice->broughtToAllowed : null;
        $this->_toBeCalledAllowed = isset($stdClassResultOffice->toBeCalledAllowed) ? $stdClassResultOffice->toBeCalledAllowed : null;
        
        $arrWorkingTimeSchedule = array();
        if (isset($stdClassResultOffice->workingTimeSchedule)) {
            if (is_array($stdClassResultOffice->workingTimeSchedule)) {
                for($i = 0; $i < count($stdClassResultOffice->workingTimeSchedule); $i++) {
                    $arrWorkingTimeSchedule[$i] = new ResultWorkingTime($stdClassResultOffice->workingTimeSchedule[$i]);
                }
            } else {
                $arrWorkingTimeSchedule[0] = new ResultWorkingTime($stdClassResultOffice->workingTimeSchedule);
            }
        }
        $this->_workingTimeSchedule = $arrWorkingTimeSchedule;
        $this->_nearbyOfficeId = isset($stdClassResultOffice -> nearbyOfficeId) ? $stdClassResultOffice -> nearbyOfficeId : null;
   }

    /**
     * Get quarter ID
     * @return integer Signed 64-bit quarter ID
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * Get quarter name
     * @return string Quarter name
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Get serving site ID
     * @return string Serving site ID
     */
    public function getSiteId() {
        return $this->_siteId;
    }

    /**
     * Get office address
     * @return ResultAddressEx Office address
     */
    public function getAddress() {
        return $this->_address;
    }

    /**
     * Get working time for FULL working days - FROM
     * @return integer Signed 16-bit integer ("HHmm" format, i.e., the number "1315" means "13:15", "830" means "8:30" etc.)
     */
    public function getWorkingTimeFrom() {
        return $this->_workingTimeFrom;
    }

    /**
     * Get working time for FULL working days - TO
     * @return integer Signed 16-bit integer ("HHmm" format, i.e., the number "1315" means "13:15", "830" means "8:30" etc.)
     */
    public function getWorkingTimeTo() {
        return $this->_workingTimeTo;
    }

    /**
     * Get working time for HALF working days - FROM
     * @return integer Signed 16-bit integer ("HHmm" format, i.e., the number "1315" means "13:15", "830" means "8:30" etc.)
     */
    public function getWorkingTimeHalfFrom() {
        return $this->_workingTimeHalfFrom;
    }

    /**
     * Get working time for HALF working days - TO
     * @return integer Signed 16-bit integer ("HHmm" format, i.e., the number "1315" means "13:15", "830" means "8:30" etc.)
     */
    public function getWorkingTimeHalfTo() {
        return $this->_workingTimeHalfTo;
    }
    
    /**
     * Get MAX parcel dimensions
     * @return Size Maximum parcel dimensions
     * @since 2.6.0
     */
    public function getMaxParcelDimensions() {
        return $this->_maxParcelDimensions;
    }
    
    /**
     * Get MAX parcel weight
     * @return Size Maximum parcel weight
     * @since 2.6.0
     */
    public function getMaxParcelWeight() {
        return $this->_maxParcelWeight;
    }
    
    /**
     * Get working time schedule
     * @return array list of ResultWorkingTime
     * @since 2.6.0
     */
    public function getWorkingTimeSchedule() {
        return $this->_workingTimeSchedule;
    }

    /**
     * Get office type
     * @return signed 16-bit integer (nullable)
     * @since 3.0.1 
     */
    public function getOfficeType() {
        return $this->_officeType; // @since 3.2.4 getOfficeType changed to officeType
    }


    /**
     * Get working time for DAY-OFF working days - FROM
     * @return signed 16-bit integer (nullable)
     * @since 3.2.6
     */
    public function getWorkingTimeDayOffFrom() {
        return $this->_workingTimeDayOffFrom;
    }

    /**
     * Get working time for DAY-OFF working days - TO
     * @return signed 16-bit integer (nullable)
     * @since 3.2.6 
     */
    public function getWorkingTimeDayOffTo() {
        return $this->_workingTimeDayOffTo; 
    }

    /**
     * Get Nearby office ID
     * @return signed 64-bit integer 
     * @since 3.3.2 
     */
    public function getNearbyOfficeId() {
        return $this->_nearbyOfficeId; 
    }

    /**
     * Specifies whether drop-off in office is allowed
     * @return boolean 
     * @since 3.5.3
     */
    public function getBroughtToAllowed() {
        return $this->_broughtToAllowed; 
    }
    
    /**
     * Specifies whether pickup from office is allowed
     * @return boolean 
     * @since 3.5.3
     */
    public function getToBeCalledAllowed() {
        return $this->_toBeCalledAllowed; 
    }
    
}
?>