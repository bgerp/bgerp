<?php



/**
 * Модел "Анкетни отговори"
 *
 *
 * @category  bgerp
 * @package   survey
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class survey_Alternatives extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Анкетни Въпроси';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, survey_Wrapper, plg_Sorting';
    
  
    /**
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'surveyId';
	
	
    /**
     * Кои полета да се показват в листовия изглед
     */
    var $listFields = 'id, tools=Пулт, surveyId, image, label';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Анкетни отговори";
    
    
    /**
     * Икона на единичния обект
     */
    //var $singleIcon = 'img/16/money.png';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    //var $rowToolsSingleField = 'iban';

    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'survey, ceo, admin';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'survey, ceo, admin';
    
    
    /**
	 * Файл за единичен изглед
	 */
	//var $singleLayoutFile = 'survey/tpl/SingleAccountLayout.shtml';
	
    
     /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('surveyId', 'key(mvc=survey_Surveys, select=title)', 'caption=Тема, input=hidden, silent');
		$this->FLD('label', 'varchar(64)', 'caption=Лейбъл, mandatory');
		$this->FLD('answers', 'text', 'caption=Отговори, mandatory');
		$this->FLD('image', 'fileman_FileType(bucket=survey_Images)', 'caption=Картинка');
    }
    
    
    /**
     * 
     */
    function renderDetail_($data)
    {
    	if($data->masterMvc) {
    		
    		//$tpl= $cls->renderQuestions($data);
    		$tpl = $this->renderAlternatives($data);
    		$tpl->append($this->renderListToolbar($data), 'ListToolbar');
    	} else {
    		
    		$tpl = $this->renderDetail($data);
    	}
    	
    	return $tpl;
    }
    
    
    /**
	 * Обработка на вербалното представяне на статиите
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if($fields['-list']) {
			$row->answers = $mvc->verbalAnswers($rec->answers);
			
			if(!$rec->image) {
				$imgLink = sbf('survey/img/question.png', '');
			}else {
				$attr = array('isAbsolute' => FALSE, 'qt' => '');
				$imgLink = thumbnail_Thumbnail::getLink($rec->image, array('36','36'), $attr);
			}
			
			$row->image = ht::createElement('img', array('src' => $imgLink));
		}
	}
	
	
	function verbalAnswers($text)
	{
		$tpl = new ET('');
		$txtArr = explode("\n", $text);
		$n = 1;
		foreach($txtArr as $an) {
			$tpl->append("<li><input type='radio' name='{}' value = '{$n}'/>&nbsp;&nbsp;&nbsp;{$an}</li>\n");
			$n++;
		}
		
		return $tpl;
	}
	/*
	function verbalAnswers($text)
	{
		$ansArray = array();
		//$tpl = new ET('');
		$txtArr = explode("\n", $text);
		$n = 1;
		foreach($txtArr as $an) {
			$ansArray[$n] = $an;
			$n++;
			//$tpl->append("<li><input type='radio' name='{}' value = '{$n}'/>{$an}</li>\n");
			//
		}
		
		return $ansArray;
	}*/
	
	/*
function renderQuestions($data)
	{
		$form = cls::get('core_Form');
		$form->method = 'POST';
        $form->action = array ('survey_Surveys', 'vote',);
		$form->layout = new ET(getFileContent('survey/tpl/SurveyForm.shtml'));
		$fieldLayout = new ET('');
		foreach($data->rows as $row) {
			$fieldLayout->append(new ET("<br>"));
			$answers = '';
			$name = $row->id;
			foreach($row->answers as $key=>$value) {
				$answers .= "{$key}={$value},";
			}
			$answers = trim($answers,", ");
			$form->FLD($name, "enum($answers)", "maxRadio=4,columns=1,notNull");
			$fieldLayout->append(new ET($row->label . "<br>"));
			$fieldLayout->append(new ET("[#{$name}#]"));
		}
		
		$form->fieldsLayout = $fieldLayout;
		$form->toolbar->addSbBtn('Изпрати');
		
		return $form->renderHtml();
	}*/
	
    /**
     * 
     */
    function renderAlternatives($data)
    {
    	$tpl = new ET(getFileContent('survey/tpl/SingleAlternative.shtml'));
    	$tplAlt = $tpl->getBlock('ROW');
    	foreach($data->rows as $row) {
    		$rowTpl = clone($tplAlt);
    		$rowTpl->placeObject($row);
    		$rowTpl->removeBlocks();
    		$tpl->append($rowTpl);
    	}
    	
    	$tpl->append(new ET('[#ListToolbar#]'));
    	
    	
    	return $tpl;
    }
}