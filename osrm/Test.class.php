<?php

class osrm_Test extends core_Manager
{
    public function act_Test()
    {
        requireRole('admin');
        requireRole('debug');

        $startCoord = "23.3219,42.6977";
        $endCoord = "27.9147,43.2141";

        $additionalCoord = array(
            "24.7453,42.1354",
            "25.6352,42.0001",
            "26.8732,42.1001"
        );
/*
        $resultGetMap = osrm_Api::getRoute($startCoord, $endCoord, $additionalCoord);
        bp($resultGetMap, $resultGetOpt);

        $result = osrm_Api::getRoute(
            "23.3219,42.6977",   // Sofia
            "27.9147,43.2141",   // Varna
            array("25.6172,43.0757", "24.7453,42.1354") // Veliko Tarnovo
        );

        bp($result, osrm_Api::getRoute(
            "23.3219,42.6977",   // Sofia
            "27.9147,43.2141",   // Varna
        ));

        $resultAdditionalCord = osrm_Api::getRoute(
            "23.3219,42.6977",   // Sofia
            "27.9147,43.2141",   // Varna
            array("24.7453,42.1354") // Plovdiv
        );

        bp($result);
*/

        $resultRoute = osrm_Api::getRoute($startCoord, $endCoord, $additionalCoord);

        $resultNearestRoute = osrm_Api::getNearest($startCoord);


        $resultTable = osrm_Api::getTable(array_merge(
            array($startCoord, $endCoord),
            $additionalCoord
        ));

       
        $track = array(
            "23.3219,42.6977",
            "23.3300,42.6980",
            "23.3400,42.7000",
            "23.3500,42.7050",
            "23.3600,42.7100",
            "23.3700,42.7200"
        );

        $resultMatch = osrm_Api::getMatch($track);

        $resultTrip = osrm_Api::getTrip(array_merge(
            array($startCoord, $endCoord),
            $additionalCoord
        ));

        bp(
            $resultRoute,
            $resultNearestRoute,
            $resultTable,
            $resultMatch,
            $resultTrip
        );
    }
}