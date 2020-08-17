<?php
/**
 * This class provides static utility functions
 */
class Util {
    
    /**
     * Sepeedy time zone
     * @var string
     */
    const SPEEDY_TIME_ZONE = "Europe/Sofia";

    /**
     * Return intersection of two services list
     * @param array $arrAvailableServices Array of ResultCourierServiceExt instances for available services
     * @param array $arrEnabledServices Array of integers for enabled client services
     * @return array Array if integers - intersection
     */
    public static function serviceIntersection($arrAvailableServices, $arrEnabledServices) {
        $arrResult = array();
        if (isset($arrEnabledServices) && is_array($arrEnabledServices) && isset($arrAvailableServices) && is_array($arrAvailableServices)) {
            $k = 0;
            for($i = 0; $i < count($arrEnabledServices); ++$i) {
                for($j = 0; $j < count($arrAvailableServices); ++$j) {
                    if ($arrEnabledServices[$i] == $arrAvailableServices[$j]->getTypeId()) {
                        $arrResult[$k] = $arrEnabledServices[$i];
                        $k++;
                        break;
                    }
                }
            }
        }
        return $arrResult;
    }

    /**
     * Returns filtered list of services which may process pickings with provided declared weight
     * @param array $arrSelectedServices Selected list service identofiers - integers
     * @param double $weightDeclared Declared weight of picking
     * @param EPSFacade $eps EPS client instance
     * @param integer $senderSiteId Sender site ID
     * @param integer $receiverSiteId Receiver site Id
     * @param date $date Date
     * @param boolean $documents Document flag
     * @return array of integers - List of service Ids
     */
    public static function filterServicesByWeightIntervals($arrSelectedServices, $weightDeclared, $eps, $senderSiteId, $receiverSiteId, $date, $documents) {
        $arrResult = array();
        if (isset($arrSelectedServices) && is_array($arrSelectedServices)) {
            $j = 0;
            for ($i = 0; $i < count($arrSelectedServices); ++$i) {
                $weightInterval = $eps->getWeightInterval($arrSelectedServices[$i], $senderSiteId, $receiverSiteId, $date, $documents);
                if (isset($weightInterval)) {
                    if ($weightInterval->getMinValue() <= $weightDeclared && $weightDeclared <= $weightInterval->getMaxValue()) {
                        $arrResult[$j] = $arrSelectedServices[$i];
                        $j++;
                    }
                }
            }
        }
        return $arrResult;
    }

    /**
     * Returns single value from list
     * @param Array $arrList List in array
     * @throws ClientException Thrown in case no elements are found in the list or more than 1 elements is contained in the list
     * @return First and the only value in the list
     */
    public static function getIfSingleValueFromList($arrList) {
        if (count($arrList) == 0) {
            throw new ClientException("No element is found in the list.");
        } else if (count($arrList) > 1) {
            throw new ClientException("More than one element is found in the list.");
        } else {
            return $arrList[0];
        }
    }
    
    /**
     * Get library version
     */
    public static function getLibVersion() {
        return "@@VERSION";
    }
}
?>