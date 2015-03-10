<?php 


/**
 * Детайл на смените
 *
 *
 * @category  bgerp
 * @package   dec
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class dec_DeclarationDetails extends doc_Detail
{
    
    /**
     * Заглавие
     */
    var $title = "Декларации - детайли";
    
    
    var $singleTitle = "Условие";
    
    
    var $masterKey = 'declarationId';
    
    
    /**
     * @todo Чака за документация...
     */
    //var $pageMenu = "Персонал";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, sales_Wrapper,
    				 plg_SaveAndNew, plg_RowZebra, Declarations=dec_Declarations';

    
    var $listFields = 'id, statementId';
    
    
    var $rowToolsField = 'id';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        
        $this->FLD('declarationId', 'key(mvc=dec_Declarations)', 'caption=Декларация, column=none');
		$this->FLD('statementId', 'key(mvc=dec_Statements, select=title)', 'caption=Обстоятелства');
        
    }

    
	function on_AfterRenderListTable($mvc, &$res, $data)
    {
        if(!count($data->recs)) {
            return NULL;
        }
        
    	
		$res = new ET(' <ol>
							<!--ET_BEGIN COMMENT_LI-->
	
								<li></br>[#text#]</li>
	
							<!--ET_END COMMENT_LI-->
						</ol>
                ');
		
    	foreach($data->recs as $rec){
				
        	$statement = dec_Statements::fetch("#id = '{$rec->statementId}'");
				
			$cTpl = $res->getBlock("COMMENT_LI");
			$cTpl->placeObject($statement);
			$cTpl->removeBlocks();
			$cTpl->append2master();
		}

    }

}