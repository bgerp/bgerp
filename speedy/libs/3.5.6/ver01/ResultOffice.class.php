<?php

require_once 'ValueAddress.class.php';

/**
 * Instances of this class are returned as a result of Speedy web service queries for offices
 */
class ResultOffice {

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
     * @var ValueAddress
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
     * Constructs new instance of ResultStreet
     * @param stdClass $stdClassResultStreet
     */
    function __construct($stdClassResultOffice) {
        $this->_id                  = isset($stdClassResultOffice->id)                  ? $stdClassResultOffice->id                        : null;
        $this->_name                = isset($stdClassResultOffice->name)                ? $stdClassResultOffice->name                      : null;
        $this->_siteId              = isset($stdClassResultOffice->siteId)              ? $stdClassResultOffice->siteId                    : null;
        $this->_address             = isset($stdClassResultOffice->address)             ? new ValueAddress($stdClassResultOffice->address) : null;
        $this->_workingTimeFrom     = isset($stdClassResultOffice->workingTimeFrom)     ? $stdClassResultOffice->workingTimeFrom           : null;
        $this->_workingTimeTo       = isset($stdClassResultOffice->workingTimeTo)       ? $stdClassResultOffice->workingTimeTo             : null;
        $this->_workingTimeHalfFrom = isset($stdClassResultOffice->workingTimeHalfFrom) ? $stdClassResultOffice->workingTimeHalfFrom       : null;
        $this->_workingTimeHalfTo   = isset($stdClassResultOffice->workingTimeHalfTo)   ? $stdClassResultOffice->workingTimeHalfTo         : null;
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
     * @return ValueAddress Office address
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
}
?>