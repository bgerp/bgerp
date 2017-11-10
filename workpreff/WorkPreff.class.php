<?php



/**
 * Избор на данни на Форма за CV
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Избор на данни на Форма за CV
 */


class workpreff_WorkPreff extends core_Manager
{


    public $title = "Избор";

    public $loadList = 'plg_RowTools2,plg_Sorting, hr_Wrapper';

    function description()
    {

        $this->FLD('name', 'varchar(255,ci)', 'caption=Предпочитания->Възможности,class=contactData,mandatory,remember=info,silent,export=Csv');
        $this->FLD('type', 'enum(set=Фиксиране, enum=Избор)', 'notNull,caption=Тип на избора,maxRadio=2,after=name');
        $this->FLD('choice', 'text', 'caption=Информация->Предложения за избор,class=contactData,mandatory,remember=info,silent,removeAndRefreshForm, export=Csv');

    }


    /**
     * Връща масив с опции за възможен избор
     * @return array
     */
    public static function getOptionsForChoice()
    {
       $query = self::getQuery();

        while ($rec = $query->fetch()){

            $partsTemp = '';

            $parts = explode("\n", $rec->choice);
            $count = count($parts);

            foreach ($parts as $part) {

               $partsTemp .= "$part".',';

            }

            $workPreffOptions[$rec->id] = (object)array(

                'id' => $rec->id,
                'type' => $rec->type,
                'parts' => trim($partsTemp,','),
                'name' => $rec->name,
                'count' => $count,

            );

        }


        if (!$workPreffOptions){

            $workPreffOptions = array();

            return $workPreffOptions;

        }else{return $workPreffOptions;}

    }

}