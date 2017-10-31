<?php
/**
 * Константи за брой дни след които статията се заключва за коментиране
 */
defIfNot('BLOGM_MAX_COMMENT_DAYS', 50*24*60*60);


/**
 *  Константа за тема по-подразбиране на блога
 */
defIfNot('BLOGM_DEFAULT_THEME', 'blogm_DefaultTheme');


/**
 *  Константа за продължителноста на живота на бисквитките създадени от блога
 */
defIfNot('BLOGM_COOKIE_LIFETIME', '2592000');


/**
 *  Броя на статии, които да се показват
 */
defIfNot('BLOGM_ARTICLES_PER_PAGE', '5');


/**
 * Думи, срещани в спам моментари
 */
defIfNot('BLOGM_SPAM_WORDS', 'sex, xxx, porn, cam, teen, adult, cheap, sale, xenical, pharmacy, pills, prescription, опционы');


/**
  * class blogm_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с Блога
 *
 *
 * @category  bgerp
 * @package   blogm
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blogm_Setup extends core_ProtoSetup
{


	/**
	 * Версия на пакета
	 */
	var $version = '0.1';


	/**
	 * Мениджър - входна точка в пакета
	 */
	var $startCtr = 'blogm_Articles';


	/**
	 * Екшън - входна точка в пакета
	 */
	var $startAct = 'list';


	/**
	 * Описание на модула
	 */
	var $info = "Поддръжка на блог: категории, статии, коментари ...";

	/**
	 * Описание на конфигурационните константи
	 */
	
	
	/**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'BLOGM_DEFAULT_THEME' => array ('class(interface=blogm_ThemeIntf,select=title)', 'caption=Тема по подразбиране в блога->Тема'),
			
			'BLOGM_MAX_COMMENT_DAYS' => array ('time(uom=days,suggestions=1 ден|2 дни|5 дни|1 седмица|2 седмици|30 дни|45 дни|50 дни)', 'caption=След колко време статията да се заключва за коментиране?->Време'),

            'BLOGM_ARTICLES_PER_PAGE' => array('int', 'caption=Колко статии да се показват на една страница->Брой'),

            'BLOGM_SPAM_WORDS' => array('text', 'caption=Определяне на SPAM рейтинг на коментар->Думи'),
	
	);
	
	
	/**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
				'blogm_Articles',
				'blogm_Categories',
				'blogm_Comments',
				'blogm_Links',
		);
	
		
		
    /**
     * Роли за достъп до модула
     */
    var $roles = 'blog';
    

    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.52, 'Сайт', 'Блог', 'blogm_Articles', 'list', "cms, blog, admin, ceo"),
        );
        
        
   /**
	* Инсталиране на пакета
	*/
	function install()
	{
		$html = parent::install();

        // Ако няма категории, създаваме някакви 
        if(!blogm_Categories::fetch('1=1')) {
            $cat = array('Новини' => 'Новостите за нашата фирма',
                         'Интересно' => 'Интересни неща за бизнеса и около него',
                         'Политика' => 'Позиция по въпроси касаещи България, Европа и света',
                         'Наука' => 'Достиженията на науката в нашия бизнес',
                         'Изкуство' => 'Творческото начало в бизнеса',
                         'Статистика' => 'Отчети и доклади, за любителите на цифрите',
                );
            foreach($cat as $title => $d) {
                $rec = (object) array('title' => $title, 'description' => $d);
                blogm_Categories::save($rec);
            }

        }
        
        $Bucket = cls::get('fileman_Buckets');
        $html  .= $Bucket->createBucket(blogm_Articles::FILE_BUCKET, 'Файлове към блог-статиите', '', '10MB', 'every_one', 'every_one');

		// Добавяме класа връщащ темата в core_Classes
        $html .= core_Classes::add('blogm_DefaultTheme');

        // Публикуване на чакащите блог статии по крон
        $rec = new stdClass();
        $rec->systemId = 'PublishPendingBlogArt';
        $rec->description = 'Публикуване на чакащите блог статии';
        $rec->controller = 'blogm_Articles';
        $rec->action = 'PublicPending';
        $rec->period = 5;
        $rec->offset = 3;
        $html .= core_Cron::addOnce($rec);
        
        
        // Изтриване на СПАМ коментари
        $rec = new stdClass();
        $rec->systemId = 'Delete SPAM comments';
        $rec->description = 'Изтриване на спам коментарите';
        $rec->controller = 'blogm_Comments';
        $rec->action = 'deleteSPAM';
        $rec->period = 24;
        $rec->offset = rand(1, 23);
        $rec->delay = 0;
        $rec->timeLimit = 50;
        $html .= core_Cron::addOnce($rec);

		return $html;
	}


	/**
	 * Де-инсталиране на пакета
	 */
	function deinstall()
	{
		// Изтриване на пакета от менюто
		$res = bgerp_Menu::remove($this);

		return $res;
	}
}