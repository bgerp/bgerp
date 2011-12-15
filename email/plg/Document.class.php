<?php
class email_plg_Document extends core_Plugin
{
	public function on_AfterGetEmailHtml($mvc, $res, $id, $emailTo = NULL, $boxFrom = NULL)
	{
		$res = $this->getDocumentBody($mvc, $id, 'html');
	}
	
	public function on_AfterGetEmailText($mvc, $res, $id, $emailTo = NULL, $boxFrom = NULL)
	{
		$res = $this->getDocumentBody($mvc, $id, 'plain');
	}
	
	function getDocumentBody($mvc, $id, $mode)
	{
		$mode = 'plain';
		
		// Създаваме обекта $data
        $data = new stdClass();
         
        // Трябва да има $rec за това $id
        expect($data->rec = $mvc->fetch($id));
        
    	// Запомняме стойността на обкръжението 'printing'
    	$isPrinting = Mode::get('printing');
    	$textMode   = Mode::get('text');
    	
    	// Емулираме режим 'printing', за да махнем singleToolbar при рендирането на документа
    	Mode::set('printing', TRUE);
    	Mode::set('text', $mode);
    	
        // Подготвяме данните за единичния изглед
        $mvc->prepareSingle($data);
        
        // Рендираме изгледа
        $res = $mvc->renderSingle($data)->removePlaces();
        
    	// Връщаме старата стойност на 'printing'
    	Mode::set('printing', $isPrinting);
    	Mode::set('text', $textMode);
    	
    	return $res;
	}
}