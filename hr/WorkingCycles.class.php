<?php 


/**
 * Работни цикли
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_WorkingCycles extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Работни цикли";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Работен цикъл";
    
    
    /**
     * @todo Чака за документация...
     */
    var $pageMenu = "Персонал";
    

    var $details = 'hr_WorkingCycleDetails';

    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, hr_Wrapper,  plg_Printing,
                       plg_SaveAndNew';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,dma';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'admin,dma';


    var $singleFields = 'id,name,cycleDuration,info';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, width=100%,mandatory');
        $this->FLD('cycleDuration', 'int(min=1)', 'caption=Брой дни, width=50px, mandatory');
        // $this->FLD('cycleMeasure', 'enum(days=Дни,weeks=Седмици)', 'caption=Цикъл->Мярка, maxRadio=4,mandatory');
        // $this->FLD('serial', 'text', "caption=Последователност,hint=На всеки ред запишете: \nчасове работа&#44; минути почивка&#44; неработни часове");
        
        $this->setDbUnique('name');
    }


 
    
    
    /**
     *
     */
    function on_AfterPrepareSingle($mvc, $res, $data)
    {
        $maxNight = 0;
        $rec = $data->rec;
        $tTime = core_Type::getByName("time(format=H:M)");

        for($i = 1; $i <= $rec->cycleDuration; $i++) {
            $night = 0;
            for($j = 0; $j < 7; $j++) {
                $day = (($i + $j) % $rec->cycleDuration) + 1;
                $dRec = hr_WorkingCycleDetails::fetch("#cycleId = {$rec->id} AND #day = {$day}"); 
                $night += hr_WorkingCycleDetails::getSection($dRec->start, $dRec->duration, 22*60*60, 7*60*60);
            } 
            echo "<li> $night";

            $maxNight = max($maxNight, $night);
        }

       
        $maxNight = $tTime->toVerbal($maxNight);

        $data->row->info = "Max night: $maxNight<br>";
    }

}