<?php

require_once __DIR__ . "/Api.class.php";

class vtotal_Checks extends core_Master
{

    public $loadList = "plg_Created";
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('dangerRate', 'double', 'caption=Опасност');
        $this->FLD('firstCheck', 'double', 'caption=Последно от вирус тотал'); //
        $this->FLD('lastCheck', 'double', 'caption=Последно проверяване от системата');
        $this->FLD('filemanDataId', 'key(mvc=fileman_Data,select=id)', 'caption=Файл');
        $this->EXT('md5', 'varchar(32)', 'externalName=md5,externalKey=filemanDataId');
    }


    function cron_CheckFiles()
    {
//        $query = $this->query();
//        $query->orderBy("#createdOn", 'DESC');
//        $query->limit(1);
//        $query->fetch()->createdOn;

        /**
         * Вземане на максималното мое преглеждане от системата
         */
        $query = $this->query();
        $query->XPR('maxLastCheck', 'datetime', 'MAX(#lastCheck)');
        $latestVTCheck = $query->fetch()->maxLastCheck;

        /**
         * Филтриране от последното преглеждане
         */
        $fQuery = fileman_Data::getQuery();
        $fQuery->limit(4);


        $fQuery->where("#createdOn > '{$latestVTCheck}'");

        while($rec = $fQuery->fetch()){

            //Break
            bp($rec);

            $result = vtotal_Api::VTGetReport($rec->md5);

            $dangerRate = vtotal_Api::getDangerRateVTResponse($result);

            $vtResponseDate = vtotal_Api::getLastCheckVTResponse($result);

            $nRec = (object)array('filemanDataId' => $rec->id, 'dangerRate' => $dangerRate,
                'firstCheck' => $vtResponseDate, 'lastCheck' => $vtResponseDate);

            $this->save($nRec);
        }
    }
}