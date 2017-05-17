<?php
/**
 * Клас 'doc_plg_SelectFolder'
 *
 * Плъгин за избор на папка в която да се въздава документ.
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_plg_SelectFolder extends core_Plugin
{
    
    
	/**
	 * След инициализирането на модела
	 *
	 * @param core_Mvc $mvc
	 * @param core_Mvc $data
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->alwaysForceFolderIfEmpty, FALSE);
	}
	
	
    /**
     * Преди всеки екшън на мениджъра-домакин
     *
     * @param core_Manager $mvc
     * @param core_Et $tpl
     * @param core_Mvc $data
     */
    public static function on_BeforeAction(core_Mvc $mvc, &$tpl, $action)
    {
        if ($action != 'add') {
            // Плъгина действа само при добавяне на документ
            return;
        }
        
        // Ако нямаме сесия - да създадем 
        requireRole('user');
        
        if (!$mvc->haveRightFor($action)) {
            // Няма права за този екшън - не правим нищо - оставяме реакцията на мениджъра.
            return;
        }
        
    	if (Request::get('folderId', 'key(mvc=doc_Folders)') ||
            Request::get('threadId', 'key(mvc=doc_Threads)') ||
            Request::get('cloneId', 'key(mvc=doc_Containers)') ||
    		($mvc->alwaysForceFolderIfEmpty === FALSE && Request::get('originId', 'key(mvc=doc_Containers)'))) {
            // Има основание - не правим нищо
            
            return;
        }


        if($_companyId = Request::get('_companyId', 'key2(mvc=crm_Companies)')) {
            $cRec = crm_Companies::fetch($_companyId);
            if($cRec) {
                $folderId = crm_Companies::forceCoverAndFolder($cRec);
            }
        } elseif($_personId = Request::get('_personId', 'key2(mvc=crm_Persons)')) {
            $pRec = crm_Persons::fetch($_personId);
            if($pRec) {
                $folderId = crm_Persons::forceCoverAndFolder($pRec);
            }
        } elseif($_projectId = Request::get('_projectId', 'key2(mvc=doc_UnsortedFolders)')) {
            $pRec = doc_UnsortedFolders::fetch($_projectId);
            if($pRec) {
                $folderId = doc_UnsortedFolders::forceCoverAndFolder($pRec);
            }
        }

 
        // Генериране на форма за основание
        $form = self::prepareSelectForm($mvc);
        
        // Добавяме не-котнролните променливи
        $allParams = Request::getParams();
        if($allParams) {
            foreach($allParams as $name => $value) {
                if(strpos($name, '_') === FALSE && ucfirst($name{0}) != $name{0}) {
                    $form->setHidden($name, $value);
                }
            }
        }
        
        if($folderId) {
            $allParams['folderId'] = $folderId;
            $tpl = new Redirect(
                	// Редирект към създаването на документа в ясната папка
                    toUrl($allParams));
                
            return FALSE;
        }
 

        // Ако няма форма - не правим нищо
        if(!$form) return;
        

        $form = $form->renderHtml();
        $tpl = $mvc->renderWrapping($form);
        
        // ВАЖНО: спираме изпълнението на евентуални други плъгини
        return FALSE;
    }


    /**
     * Подготвя формата за избор на папка, за новия документ от клас $mvc
     */
    static function prepareSelectForm($mvc)
    {
    	// Подготовка на формата за избор на папка
    	$form = cls::get('core_Form');

        // Вземаме масив с възможноите корици, които могат да приемат документ от дадения $mvc
    	$coverArr = self::getAllowedCovers($mvc);
        
        $coverKeys = implode(',', array_keys($coverArr));

        $form->FLD('folderId', 'key2(mvc=doc_Folders, allowEmpty, restrictViewAccess=yes)', 'caption=Папка,class=w100 clearSelect');
        $form->setFieldTypeParams('folderId', array('where' => "#coverClass IN ({$coverKeys})"));
        $form->setField('folderId', array('attr' => array('onchange' => 'clearSelect(this, "clearSelect");')));
        
        $form->title = '|*' . tr('Избор на папка||Select a folder') . ' ' . tr('за създаване на||to create a new') . ' ' . mb_strtolower(tr($mvc->singleTitle));
        $form->toolbar->addSbBtn('Напред', 'refresh', array('class' => 'btn-next fright'), 'ef_icon = img/16/move.png, title=Продължи нататък');
        
        $retUrlOrg = Request::getParams();
        unset($retUrlOrg['virtual_url']);

        if(in_array('crm_Companies', $coverArr)) {
            $form->FLD('_companyId', 'key2(mvc=crm_Companies, allowEmpty, restrictViewAccess=yes)', 'caption=Фирма,class=w100 clearSelect');
            $form->setField('_companyId', array('attr' => array('onchange' => 'clearSelect(this, "clearSelect");')));

            $retUrl = $retUrlOrg;
            $retUrl['_companyId'] = crm_Companies::getUrlPlaceholder('id');
            $form->toolbar->addBtn('Нова фирма', array('crm_Companies', 'add', 'ret_url' => $retUrl), "ef_icon =img/16/office-building-add.png, title=В папка на нова фирма");
        }
        
        if(in_array('crm_Persons', $coverArr)) {
            $form->FLD('_personId', 'key2(mvc=crm_Persons, allowEmpty, restrictViewAccess=yes)', 'caption=Лице,class=w100 clearSelect');
            $form->setField('_personId', array('attr' => array('onchange' => 'clearSelect(this, "clearSelect");')));
            
            $retUrl = $retUrlOrg;
            $retUrl['_personId'] = crm_Persons::getUrlPlaceholder('id');
            $form->toolbar->addBtn('Ново лице', array('crm_Persons', 'add', 'ret_url' => $retUrl), "ef_icon =img/16/vcard-add.png, title=В папка на ново лице");
        }
        
        /*
        if(in_array('doc_UnsortedFolders', $coverArr)) {
            $retUrl = $retUrlOrg;
            $retUrl['_projectId'] = doc_UnsortedFolders::getUrlPlaceholder('id');
            $form->toolbar->addBtn('Нов проект', array('doc_UnsortedFolders', 'add', 'ret_url' => $retUrl), "ef_icon =img/16/vcard-add.png, title=В нов проект");
        } */

        $defaultFolderId = Request::get('defaultFolderId');
        
        if(!$defaultFolderId) {
            $defaultFolderId = $mvc->getDefaultFolder();
        }
        
        if($defaultFolderId && $mvc->canAddToFolder($defaultFolderId)) {
            $form->setDefault('folderId', $defaultFolderId);
        }
        
        $form->toolbar->addBtn('Отказ', self::getRetUrl($mvc), 'ef_icon=img/16/cancel.png, title=Отказ');


        return $form;
    }
    
    
	/**
     * Помощен метод за определяне на URL при успешен запис или отказ
     * 
     * @param core_Mvc $mvc
     * @return array
     */
    protected static function getRetUrl(core_Mvc $mvc)
    {
        if (!$retUrl = getRetUrl()) {
            
            // Ако има права за листване
            if ($mvc->haveRightFor('list')) {
                $retUrl = array($mvc, 'list');
            } else {
                $retUrl = FALSE;
            }
        }
        
        return $retUrl;
    }
    
    
    /**
     * Връща масив с допустимите корици, където може да се добави документа
     * @param core_Mvc $mvc
     * @return array
     */
    public static function getAllowedCovers(core_Mvc $mvc)
    {
    	// Между какви корици трябва да се избира
    	$interfaces = arr::make($mvc::getCoversAndInterfacesForNewDoc());
    	
    	// Ако няма корици се прескача плъгина
    	if(!count($interfaces)) return NULL;
    	 
    	// Ако има '*' се показват всички класове които могат да са корици
    	if(in_array('*', $interfaces)){
    		$interfaces = array('doc_FolderIntf');
    	}
    	
    	// Намират се всички класове отговарящи на тези интерфейси
    	$coversArr = array();
    	foreach ($interfaces as $index => $int){
    		
    		// Ако иднекса е число и името съдържа `Intf` приемаме, че е зададен интерфейс, 
            // иначе приемаме, че е име на клас
    		if(is_numeric($index) && stripos($int, 'Intf')){
    			$coversArr +=  core_Classes::getOptionsByInterface($int);
    		} else {
    			$clsRec = core_Classes::fetch("#name = '{$int}'", 'id,name');
    			$coversArr +=  array($clsRec->id => $clsRec->name);
    		}
    	}
    	
    	return $coversArr;
    }
    

    /**
     * Дефолтен метод, който разрешава документа да се слага в производлни папки
     */
    static function on_AfterGetCoversAndInterfacesForNewDoc($mvc, &$res)
    {
        if($res) return;

        if($mvc->coversAndInterfacesForNewDoc) {
            $res = $mvc->coversAndInterfacesForNewDoc;
        } else {
            $res = 'crm_Persons,crm_Companies,doc_UnsortedFolders';
        }
    }
    
     

    /**
     * Реализация по подразбиране на интерфейсния метод ::canAddToFolder()
     *
     */
    function on_AfterCanAddToFolder($mvc, &$res, $folderId)
    {
        if($res !== FALSE) {
            $allowedCovers = self::getAllowedCovers($mvc);
            $fRec = doc_Folders::fetch($folderId);
            if(!$allowedCovers[$fRec->coverClass]) {
                $res = FALSE;

                return FALSE;
            }
        }
    }

}
