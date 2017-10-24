<?php



/**
 * Клас 'core_Updates' - Мениджър за обновления на системата
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Updates extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = 'Нови версии на системата';


    /**
     * Заглавие на мениджъра
     */
    public $singleTitle = 'Нова версия на системата';


    /**
     * Кои полета ще бъдат показани?
     */
    public $listFields = 'update=Обновяване,version,ghPublishedAt,repo,branch,description';


    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'admin';


    /**
     * Кой може да листва и разглежда?
     */
    public $canRead = 'admin';


    /**
     * Кой може да добавя?
     */
    var $canAdd = 'no_one';


    /**
     * Кой може да редактира?
     */
    var $canEdit   = 'no_one';


    /**
     * Кой може да изтрива?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Кой може да оттегля?
     */
    var $canReject = 'no_one';
    
    
    /**
     * Плъгини и MVC класове за предварително зареждане
     */
    var $loadList = 'plg_SystemWrapper, plg_State,plg_RowTools2';


    /**
     * Масив с $objectId на всички заключени обекти от текущия хит
     */
    var $locks = array();


    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('version', 'varchar(64)', 'caption=Версия,tdClass=centered');
        $this->FLD('ghPublishedAt', 'datetime(format=smartTime)', 'caption=Публикуване');
        $this->FLD('repo', 'varchar(64)', 'caption=Репозитори,tdClass=centered');
        $this->FLD('branch', 'varchar(64)', 'caption=Бранч,tdClass=centered');
        $this->FLD('tag', 'varchar(64)', 'caption=Бранч,column=none');
        $this->FLD('description', 'text', 'caption=@Описание');
        $this->FLD('ghCreatedAt', 'datetime', 'caption=Създаване,column=none');


        $this->setDbUnique('repo,branch,tag');
    }


    /**
     * Взема от gitHub какви releases има bgERP. Записва данните в модел
     * Ако има релийз с по-нова дата, от колкото е кода в локалното репозитори и на гитхъб последния комит е
     * с по-нова дата от колкото на локалното репозитори, но бие нотификация на всички админи, с линк към листовия изглед
     * на този модел, че има по-нова версия
     *
     */
    public static function checkForUpdates()
    {

        $releases = self::getReleases('bgerp', 'bgerp');

        if(!is_array($releases)) return;

        foreach($releases as $rel) {

            if($rel->target_commitish != BGERP_GIT_BRANCH) continue;

            $rec = new stdClass();
            $rec->repo = 'bgerp';
            $rec->branch = $rel->target_commitish;
            $rec->tag = $rel->tag_name;
            $rec->version = $rel->name;
            $rec->description = $rel->body;
            $rec->ghCreatedAt = $rel->created_at;
            $rec->ghPublishedAt = $rel->published_at;

            self::save($rec, NULL, 'IGNORE');
        }

        $ghBgerpLastCommitLastDate = self::getLastCommitOnGitHub('bgerp', 'bgerp', BGERP_GIT_BRANCH);

        $localCommitObj = git_Lib::getLastCommit(EF_APP_PATH);
        $localBgerpCommitLastDate = $localCommitObj->date;

        if(defined('EF_PRIVATE_PATH')) {
           $rUrl = git_Lib::getRemoteUrl(EF_PRIVATE_PATH, $log);

           if($rUrl) {
               list($protocol, $ownerRepo) = explode('@github.com:', $rUrl);
               if($ownerRepo) {
                    list($privateOwner, $privateRepo) = explode('/', $ownerRepo);
                    $privateRepo = str_replace('.git', '', $privateRepo);

                    $releases = self::getReleases($privateOwner, $privateRepo);

                    if(is_array($releases)) {

                        foreach($releases as $rel) {

                            if($rel->target_commitish != PRIVATE_GIT_BRANCH) continue;

                            $rec = new stdClass();
                            $rec->repo = $privateRepo;
                            $rec->branch = $rel->target_commitish;
                            $rec->tag = $rel->tag_name;
                            $rec->version = $rel->name;
                            $rec->description = $rel->body;
                            $rec->ghCreatedAt = $rel->created_at;
                            $rec->ghPublishedAt = $rel->published_at;

                            self::save($rec, NULL, 'IGNORE');
                        }
                    }

                    $ghPrivateLastCommitLastDate = self::getLastCommitOnGitHub($privateOwner, $privateRepo, PRIVATE_GIT_BRANCH);

                    $localPrivateCommitObj = git_Lib::getLastCommit(EF_PRIVATE_PATH);
                    $localPrivateCommitLastDate = $localPrivateCommitObj->date;
               }
            }
        }

        $query = self::getQuery();
        $cQuery = clone $query;

        while($rec = $query->fetch()) {

            $lastState = $rec->state;

            if($rec->repo == 'bgerp' && $rec->branch == BGERP_GIT_BRANCH && $ghBgerpLastCommitLastDate && $localBgerpCommitLastDate) {
                if($rec->ghPublishedAt > $localBgerpCommitLastDate && $ghBgerpLastCommitLastDate > $localBgerpCommitLastDate) {
                    $rec->state = 'opened';
                } else {
                    $rec->state = 'closed';
                }
            }

            if($rec->repo == $privateRepo && $rec->branch == EF_PRIVATE_PATH && $ghPrivateLastCommitLastDate && $localPrivateCommitLastDate) {
                if($rec->ghPublishedAt > $localPrivateCommitLastDate && $ghPrivateLastCommitLastDate > $localPrivateCommitLastDate) {
                    $rec->state = 'opened';
                } else {
                    $rec->state = 'closed';
                }
            }

            if($lastState != $rec->state) {
                self::save($rec, 'state');
                if($rec->state == 'opened') {
                    $flagNew = TRUE;
                }
            }
        }

        if($flagNew) {
            $roleId = core_Roles::fetchByName('admin');
            $adminsArr = core_Users::getByRole($roleId);

            while($rec = $cQuery->fetch()) {
                $msg = '|Има налични обновления за системата';
                $urlArr = array('core_Updates', 'list');

                foreach ($adminsArr as $userId) {
                    bgerp_Notifications::add($msg, $urlArr, $userId, 'warning');
                }
            }

        }

    }


    /**
     * Намира датата на последния комит в gitHub
     */
    public static function getLastCommitOnGitHub($owner = 'bgerp', $repo = 'bgerp', $branch = 'master')
    {
        $headJson = git_Lib::gitHubApiCall("https://api.github.com/repos/{$owner}/{$repo}/git/refs/heads/{$branch}");

        $head = json_decode($headJson);

        $lastJson = git_Lib::gitHubApiCall($head->object->url);

        $last = json_decode($lastJson);

        $date = date("Y-m-d H:i:s", strtotime($last->author->date));

        return $date;
    }


    /**
     *
     */
    public static function getReleases($owner = 'bgerp', $repo = 'bgerp')
    {
        $url = "https://api.github.com/repos/{$owner}/{$repo}/releases";

        $releasesJson = git_Lib::gitHubApiCall($url);

        $releases = json_decode($releasesJson);

        return $releases;

    }


    /**
     * След порготвяне на формата за филтриране
     *
     * @param blast_Emails $mvc
     * @param object $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Сортиране на записите по състояние и по времето им на започване
        $data->query->orderBy('ghPublishedAt', 'DESC');
    }


    /**
     *
     * @param core_Updates $mvc
     * @param core_ET $tpl
     * @param stdObject $data
     */
    static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        bgerp_Notifications::clear(array('core_Updates', 'list'));
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if($rec->state == 'opened') {
            $row->update = ht::createBtn('Обнови', array("core_Packs", "systemUpdate"), NULL, FALSE,
                                               'ef_icon = img/16/download.png, title=Сваляне на най-новия код и инициализиране на системата, class=system-update-btn');
        }
    }


    /**
     * Изпълнява се по крон всеки ден между 8 и 12 ч
     */
    function cron_checkForUpdates()
    {
        return self::checkForUpdates();
    }

   
}