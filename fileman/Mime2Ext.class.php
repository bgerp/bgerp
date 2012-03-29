<?php



/**
 * Клас 'fileman_Mime2Ext' -
 *
 *
 * @category  all
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_Mime2Ext extends core_Manager {
    
    
    /**
     * Заглавие на модула
     */
    var $title = 'MIME типове';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD("mime", "varchar(128)", 'caption=MIME-type');
        $this->FLD("ext", "varchar(16)", 'caption=Разширение,mandatory');
        $this->FLD("priority", "enum(yes,no)", 'caption=Приоритетно');
        
        $this->setDbUnique('ext');
        $this->setDbIndex('mime');
        
        $this->load('plg_rowTools,fileman_Wrapper');
    }
    
    
    /**
     * Инсталация на MVC
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        if((!$mvc->fetch("1=1")) || Request::get('Full')) {
            
            // Изтриваме съдържанието на таблицата
            $mvc->db->query("TRUNCATE TABLE  `{$mvc->dbTableName}`");
            
            include(dirname(__FILE__) . '/data/mimes.inc.php');

            $rec = new stdClass();

            foreach($mime2exts as $rec->mime => $exts) {
                
                $exts = explode(' ', $exts);
                
                foreach($exts as $rec->ext) {
                    if(!$mvc->fetch("#ext = '{$rec->ext}'")) {
                        
                        unset($rec->id);
                        
                        $mvc->save($rec);
                        
                        $j++;;
                    }
                }
            }
            $res .= "<li> Добавени {$j} записа от източник (2)";
            
            foreach($mimetypes as $rec->ext => $rec->mime) {
                
                unset($rec->id);
                
                $mvc->save($rec, NULL, 'IGNORE');
                
                $i++;
            }
            
            $res .= "<li> Добавени {$i} записа от източник (1)";
        }
    }
    
    
    /**
     * Извиква се преди подготовката на масивите $data->recs и $data->rows
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#mime');
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_BeforeSave($mvc, &$res, $rec)
    {
        if($mvc->fetch("#mime = '{$rec->mime}' AND #ext = '{$rec->ext}'")) {
            
            return FALSE;
        }
        
        if($mvc->fetch("#mime = '{$rec->mime}'")) {
            $rec->priority = 'no';
        } else {
            $rec->priority = 'yes';
        }
    }
}