<?php

//require_once __DIR__ . "/Api.class.php";

class vtotal_Checks extends core_Master
{

    public $loadList = "plg_Created";
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('firstCheck', 'double', 'caption=Последно от вирус тотал'); //
        $this->FLD('lastCheck', 'double', 'caption=Последно проверяване от системата');
        $this->FLD('filemanDataId', 'key(mvc=fileman_Files,select=id)', 'caption=Файл');
//        $this->EXT('md5', 'fileman_Files', 'externalName=md5,externalKey=filemanFilesId');

        $this->setDbUnique('filemanDataId');
    }


    public function act_CheckFiles()
    {
        return $this->cron_CheckFiles();
    }


    public function cron_CheckFiles()
    {
        $dangerExtensions = [
            //Executable
            'EXE', 'PIF', 'APPLICATION', 'GADGET',
            'MSI', 'MSP', 'COM', 'SCR', 'HTA', 'CPL',
            'MSC', 'JAR', 'BAT', 'CMD', 'VB', 'VBS',
            'JS', 'JSE', 'WS', 'WSH', 'WSC', 'WSF',
            "PS1", 'PS1XML', 'PS2', 'PS2XML', 'PSC1',
            'PSC2', 'SCF', 'LNK', 'INF',
            //Macro
            'REG', 'DOC', 'XLS', 'PPT', 'DOCM',
            'DOTM', 'XLSM', 'XLTM', 'XLAM',
            'PPTM', 'POTM', 'PPAM', 'PPSM' , 'SLDM',
        ];

        $query = fileman_Files::getQuery();
        $query->where("#dangerRate IS NULL");
        $query->orderBy("#createdOn", "DESC");
        $query->limit(4);

        while($rec = $query->fetch()) {
            $extension = pathinfo($rec->name, PATHINFO_EXTENSION);

            if (!in_array(strtoupper($extension), $dangerExtensions)) {
                $rec->dangerRate = 0;
                fileman_Files::save($rec, 'dangerRate');
            }
            elseif (!$this->fetch("#filemanDataId = {$rec->dataId}")) {
                    $checkFile = (object)array('filemanDataId' => $rec->dataId,
                        'firstCheck' => NULL, 'lastCheck' => NULL);
                    $this->save($checkFile);
            }

        }

    }



}