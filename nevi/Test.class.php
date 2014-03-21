<?php



/**
 * TEST не комитвай !!!
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заявки за покупки
 */
class nevi_Test extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Test';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    }
    
    function act_Scroll()
    {
    	//bp($_SESSION);
    }
    
    
    function act_hor(){
    	
    	$testArr = array('a'=>'a','b'=>'b','c'=>'c','d'=>'d','e'=>'e','f'=>'f','g'=>'g',);
    	
    	$formH = cls::get('core_Form');
    	$formH->title = 'Test horizontal';
    	$formH->view = 'horizontal';
    	
    	$formH->FNC('text1' , 'set(nevi=Nevii,gabi=Gabii)' , 'caption=Текст,input');
    	$formH->FNC('fld1', 'key(mvc=currency_Currencies,select=code)', 'input,caption=FLD 1');
    	$formH->FNC('fld2', 'key(mvc=pos_FavouritesCategories,select=name)', 'input, caption=FLD 2');
    	$formH->FNC('fld3', 'datetime', 'input,caption=FLD 3');
    	$formH->FNC('birthday', 'combodate(minYear=1850,maxYear=' . date('Y') . ')', 'caption=Рожден ден,input');
    	//$formH->FNC('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,remember,class=contactData,input');
    	$formH->FNC('fld4', 'varchar(32)', 'input,caption=FLD 4');
    	$formH->FNC('fld5', 'varchar(32)', 'input,caption=FLD 5');
    	$formH->FNC('fld6', 'richtext(rows=3)', 'input,caption=test');
    	$formH->FNC('info', 'text','rows=4');
    	$formH->FNC('groupList', 'keylist(mvc=crm_Groups,select=name)', 'input,caption=Групи,remember,silent');
    	$formH->setSuggestions('fld5', $testArr);
    	//$formH->FNC('gln', 'gs1_TypeEan(gln)', 'caption=GLN код,input');
    	//$formH->FNC('gpsCoords', 'location_Type', 'caption=Координати,input');
    	//$formH->FNC('image', 'fileman_FileType(bucket=survey_Images)', 'caption=Картинка,input');
    	$formH->FNC('commentsMode',
    			'enum(enabled=Разрешени,disabled=Забранени,stopped=Спрени)',
    			'caption=Режим,maxRadio=4,columns=4,input');
    	$formH->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	
    	
    	$formV = cls::get('core_Form');
    	$formV->title = 'Test vertical';
    	$formV->FNC('titleLink', 'html', 'column=none,input');
    	
    	$formV->FNC('fld1', 'key(mvc=currency_Currencies,select=code)', 'input,caption=FLD 1');
    	$formV->FNC('fld2', 'key(mvc=pos_FavouritesCategories,select=name)', 'input, caption=FLD 2');
    	$formV->FNC('fld3', 'datetime', 'input,caption=FLD 3');
    	$formV->FNC('text1' , 'set(nevi=Nevii,gabi=Gabii)' , 'caption=Текст->отговорник,input');
    	//$formV->FNC('country', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Държава,remember,input');
    	$formV->FNC('birthday', 'combodate(minYear=1850,maxYear=' . date('Y') . ')', 'caption=Рожден ден,input');
    	$formV->FNC('fld4', 'varchar(32)', 'input,caption=FLD 4');
    	$formV->FNC('fld6', 'richtext(rows=3)', 'input,caption=test');
    	$formV->FNC('fld5', 'varchar(32)', 'input,caption=FLD 5');
    	$formV->FNC('info', 'text','rows=4');
    	$formV->FNC('groupList', 'keylist(mvc=crm_Groups,select=name)', 'input,caption=Групи,remember,silent');
    	$formV->setSuggestions('fld5', $testArr);
    	//$formV->FNC('gln', 'gs1_TypeEan(gln)', 'caption=GLN код,input');
    	///$formV->FNC('gpsCoords', 'location_Type', 'caption=Координати,input');
    	//$formV->FNC('image', 'fileman_FileType(bucket=survey_Images)', 'caption=Картинка,input');
    	$formV->FNC('commentsMode',
    			'enum(enabled=Разрешени,disabled=Забранени,stopped=Спрени)',
    			'caption=Коментари->Режим,maxRadio=4,columns=4,input');
    	$formV->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	
    	$tpl1 = $formV->renderHtml();
    	$tpl2 = $formH->renderHtml();
    	
    	$tpl = new ET("[#1#] [#2#]", $tpl1->removeBlocks(), $tpl2->removeBlocks());
    	
    	$tpl->removeBlocks();

    	$orgData=array (
    			'1' => array (
    					'id' => 2,
    					'title' => "Ръководство",
    					'parent_id' => 10
    			),
    			'0' => array (
    					'id' => 1,
    					'title' => "Завод1",
    					'parent_id' => 2
    			),
    			'2' => array (
    					'id' => 3,
    					'title' => "Завод2",
    					'parent_id' => 2
    			),
    			'3' => array (
    					'id' => 4,
    					'title' => "Отдел HR",
    					'parent_id' => 1
    			),
    			'4' => array (
    					'id' => 5,
    					'title' => "Отдел ТРЗ",
    					'parent_id' => 3
    			),
    			'5' => array (
    					'id' => 6,
    					'title' => "Отдел Маркетинг",
    					'parent_id' => 3
    			),
    			'6' => array (
    					'id' => 7,
    					'title' => "Отдел Производство",
    					'parent_id' => 1
    			),
    			'7' => array (
    					'id' => 8,
    					'title' => "Отдел Продажби",
    					'parent_id' => 3
    			),
    			'8' => array (
    					'id' => 9,
    					'title' => "Цех Хартия",
    					'parent_id' => 7
    			),
    			'9' => array (
    					'id' => 10,
    					'title' => "Шивашки цех",
    					'parent_id' => 'NULL'
    			),
    			'10' => array (
    					'id' => 11,
    					'title' => "Смяна А",
    					'parent_id' => 9
    			),
    			'11' => array (
    					'id' => 12,
    					'title' => "Смяна Б",
    					'parent_id' => 9
    			),
    			'12' => array (
    					'id' => 13,
    					'title' => "Смяна А",
    					'parent_id' => 10
    			),
    			'13' => array (
    					'id' => 14,
    					'title' => "Смяна Б",
    					'parent_id' => 10
    			),
    			'14' => array (
    					'id' => 15,
    					'title' => "Връзки с обществеността",
    					'parent_id' => 6
    			),
    			'15' => array (
    					'id' => 16,
    					'title' => "Външни продажби",
    					'parent_id' => 8
    			),
    			'16' => array (
    					'id' => 17,
    					'title' => "Вътрешни продажби",
    					'parent_id' => 8
    			),
    			'17' => array (
    					'id' => 18,
    					'title' => "Рекламен",
    					'parent_id' => 6
    			),
    			'18' => array (
    					'id' => 19,
    					'title' => "Отдел Печат",
    					'parent_id' => 1
    			),
    			'19' => array (
    					'id' => 20,
    					'title' => "Предпечат",
    					'parent_id' => 19
    			),
    			'20' => array (
    					'id' => 21,
    					'title' => "Ситопечат",
    					'parent_id' => 19
    			),
    			'21' => array (
    					'id' => 22,
    					'title' => "Офсет",
    					'parent_id' => 19
    			),
    			'22' => array (
    					'id' => 23,
    					'title' => "Началник смяна",
    					'parent_id' => 13
    			),
    			'23' => array (
    					'id' => 24,
    					'title' => "Шивач",
    					'parent_id' => 13
    			),
    			'24' => array (
    					'id' => 25,
    					'title' => "Началник смяна",
    					'parent_id' => 14
    			),
    			'25' => array (
    					'id' => 26,
    					'title' => "Шивач",
    					'parent_id' => 14
    			),
    			'29' => array (
    					'id' => 27,
    					'title' => "Otdel",
    					'parent_id' => 18
    			),
    	);
    	$tpl->append(orgchart_Adapter::render_($orgData) );
    	$tpl->append(orgchart_Adapter::render_($orgData) );
    	$tpl->append(orgchart_Adapter::render_($orgData) );
    	$tpl->append(orgchart_Adapter::render_($orgData) );
    	return $tpl;
    }
}