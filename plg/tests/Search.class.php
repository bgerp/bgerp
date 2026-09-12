<?php


/**
 * Regression coverage for choosing a bounded document search scope.
 */
class plg_tests_Search extends unit_Class
{
    public static function test_ScopeThreshold()
    {
        foreach (array(0, 5000, 5001) as $count) {
            $query = self::query($count ? range(1, $count) : array());
            plg_Search::applySearch('hartia art311', $query);
            $before = $query->where;

            ut::expectEqual(plg_Search::restrictToScope($query), $count <= 5000);
            ut::expectEqual(count(plg_tests_SearchQuery::$probes), 1);
            $probe = plg_tests_SearchQuery::$probes[0];
            ut::expectEqual($probe->limit, 5001);
            ut::expectEqual(strpos($probe->buildQuery(), 'MATCH('), false);
            if ($count > 5000) {
                ut::expectEqual($query->where, $before);
                ut::expectEqual(substr_count(implode(' ', $query->where), 'MATCH('), 2);
            } else {
                ut::expectEqual(array_values(array_intersect($before, $query->where)), $before);
                ut::expectEqual(isset($query->indexes[$query->mvc->dbTableName]['PRIMARY']), true);
                if ($count) {
                    ut::expectEqual(strpos(implode(' ', $query->where), '5000') !== false, true);
                } else {
                    ut::expectEqual((bool) preg_match('/1\s*=\s*2|0\s*=\s*1/', implode(' ', $query->where)), true);
                }
            }
        }
    }


    public static function test_StructuralConditionsAndProjection()
    {
        $query = self::query(array(11, 12));
        $structural = array(
            '#folderId = 48226',
            '#docClass = 95',
            "((#createdOn >= '2026-05-01') AND (#modifiedOn >= '2026-05-01')) OR (#valior >= '2026-05-01')",
            "#state != 'rejected' OR #state IS NULL",
            "#createdBy = 2 OR LOCATE('|2|', #shared)",
        );
        foreach ($structural as $condition) {
            $query->where($condition);
        }
        $query->show('id, searchKeywords');
        $query->orderBy('modifiedOn', 'DESC');
        $query->useIndex('folder_id');
        plg_Search::applySearch('hartia', $query);
        $query->buildQuery();
        $order = $query->orderBy;

        ut::expectEqual(plg_Search::restrictToScope($query), true);
        $probe = plg_tests_SearchQuery::$probes[0];
        foreach ($structural as $condition) {
            ut::expectEqual(in_array($condition, $probe->where, true), true);
            ut::expectEqual(in_array($condition, $query->where, true), true);
        }
        ut::expectEqual($probe->orderBy, array());
        ut::expectEqual(isset($probe->show['searchKeywords']), false);
        ut::expectEqual($query->orderBy, $order);
        ut::expectEqual(isset($query->show['searchKeywords']), true);
        ut::expectEqual(isset($query->indexes[$query->mvc->dbTableName]['PRIMARY']), true);
    }


    public static function test_NegativesWildcardsAndShortWords()
    {
        foreach (array('hartia -cardboard', 'hartia -na', 'hartia a4', 'hartia *arton', 'hartia art*311') as $search) {
            $query = self::query(array(1, 2));
            plg_Search::applySearch($search, $query);
            $nonFullText = array_filter($query->where, function ($condition) {
                return strpos($condition, 'MATCH(') === false;
            });
            ut::expectEqual(plg_Search::restrictToScope($query), true);
            foreach ($nonFullText as $condition) {
                ut::expectEqual(in_array($condition, $query->where, true), true);
            }
            ut::expectEqual(strpos(implode(' ', $query->where), 'MATCH(') !== false, true);
        }
    }


    public static function test_QuotedPhrasesAndChangedCondition()
    {
        foreach (array('"hartia art311"', 'hartia "art311 na karton"') as $search) {
            $query = self::query(array(1));
            plg_Search::applySearch($search, $query);
            $before = $query->where;
            ut::expectEqual(plg_Search::restrictToScope($query), true);
            ut::expectEqual(array_values(array_intersect($before, $query->where)), $before);
            ut::expectEqual(count(plg_tests_SearchQuery::$probes), 1);
        }

        $query = self::query(array(1));
        plg_Search::applySearch('hartia', $query);
        $query->where[0] = '(' . $query->where[0] . ') OR #createdBy = 2';
        $before = $query->where;
        ut::expectEqual(plg_Search::restrictToScope($query), false);
        ut::expectEqual($query->where, $before);
        ut::expectEqual(count(plg_tests_SearchQuery::$probes), 0);
    }


    public static function test_GroupedThreadsCountContainers()
    {
        $query = self::query(range(1, 5001));
        $query->fields['searchContainerId'] = (object) array(
            'kind' => 'EXT', 'name' => 'searchContainerId', 'externalClass' => 'plg_tests_SearchContainers',
            'externalName' => 'id', 'type' => new plg_tests_SearchType(),
        );
        $query->fields['containerSearchKeywords'] = (object) array(
            'kind' => 'EXT', 'name' => 'containerSearchKeywords', 'externalClass' => 'plg_tests_SearchContainers',
            'externalName' => 'searchKeywords', 'type' => new plg_tests_SearchType(),
        );
        $query->where('`search_scope_containers`.`thread_id` = `search_scope_documents`.`id`');
        $query->groupBy('`search_scope_documents`.`id`');
        plg_Search::applySearch('hartia', $query, 'containerSearchKeywords');
        $before = $query->where;
        ut::expectEqual(plg_Search::restrictToScope($query, 'searchContainerId'), false);
        ut::expectEqual($query->where, $before);
        ut::expectEqual(plg_tests_SearchQuery::$probes[0]->groupBy, array());
        ut::expectEqual(isset(plg_tests_SearchQuery::$probes[0]->show['searchContainerId']), true);
    }


    public static function test_DuplicateCandidatesAndEmptySearch()
    {
        $query = self::query(array(11, 11, 12));
        plg_Search::applySearch('hartia', $query);
        ut::expectEqual(plg_Search::restrictToScope($query, 'id', 2), true);
        ut::expectEqual(strpos(plg_tests_SearchQuery::$probes[0]->buildQuery(), 'DISTINCT') !== false, true);

        $query = self::query(array(1));
        ut::expectEqual(plg_Search::restrictToScope($query), false);
        ut::expectEqual(count(plg_tests_SearchQuery::$probes), 0);
    }


    public static function test_UnsupportedGroupingAndUnionFallBack()
    {
        $query = self::query(array(1));
        plg_Search::applySearch('hartia', $query);
        $query->groupBy('folderId');
        $before = $query->where;
        ut::expectEqual(plg_Search::restrictToScope($query), false);
        ut::expectEqual($query->where, $before);
        ut::expectEqual(count(plg_tests_SearchQuery::$probes), 0);

        $query = self::query(array(1));
        plg_Search::applySearch('hartia', $query);
        $query->setUnion('#folderId = 1');
        $query->setUnion('#folderId = 2');
        $before = $query->where;
        ut::expectEqual(plg_Search::restrictToScope($query), false);
        ut::expectEqual($query->where, $before);
        ut::expectEqual(count(plg_tests_SearchQuery::$probes), 0);
    }


    public static function test_HavingFallsBack()
    {
        $query = self::query(array(1));
        $query->fields['total'] = (object) array('name' => 'total', 'kind' => 'XPR', 'expression' => 'COUNT(#id)', 'type' => new plg_tests_SearchType());
        $query->where('#total > 1');
        plg_Search::applySearch('hartia', $query);
        $before = $query->where;
        ut::expectEqual(plg_Search::restrictToScope($query), false);
        ut::expectEqual($query->where, $before);
        ut::expectEqual(count(plg_tests_SearchQuery::$probes), 0);
    }


    public static function test_CustomLeftJoinFallsBack()
    {
        $query = self::query(array(1));
        $query->fields['joinedContainerId'] = (object) array(
            'name' => 'joinedContainerId', 'kind' => 'EXT', 'externalClass' => 'plg_tests_SearchContainers',
            'externalName' => 'id', 'type' => new plg_tests_SearchType(),
            'onCond' => '#id = #joinedContainerId', 'join' => 'LEFT',
        );
        $query->show('id, joinedContainerId');
        plg_Search::applySearch('hartia', $query);
        $before = $query->where;
        ut::expectEqual(plg_Search::restrictToScope($query), false);
        ut::expectEqual($query->where, $before);
        ut::expectEqual(count(plg_tests_SearchQuery::$probes), 0);
    }


    public static function query($ids)
    {
        plg_tests_SearchQuery::$probes = array();
        $mvc = new plg_tests_SearchModel();
        $query = new plg_tests_SearchQuery();
        $query->mvc = $mvc;
        $query->fields = $mvc->fields;
        $query->candidateIds = $ids;

        return $query;
    }
}


/**
 * Lightweight model and candidate cursor keep these tests independent of application data.
 */
class plg_tests_SearchModel extends core_BaseClass
{
    public $dbTableName = 'search_scope_documents';
    public $className = 'plg_tests_SearchModel';
    public $dbEngine;
    public $db;
    public $fields = array();

    public function __construct()
    {
        $this->db = new plg_tests_SearchDb();
        foreach (array('id', 'folderId', 'docClass', 'createdOn', 'modifiedOn', 'valior', 'state', 'createdBy', 'shared', 'searchKeywords') as $name) {
            $this->fields[$name] = (object) array('kind' => 'FLD', 'name' => $name, 'type' => new plg_tests_SearchType());
        }
    }
}


class plg_tests_SearchContainers extends plg_tests_SearchModel
{
    public $dbTableName = 'search_scope_containers';
    public $className = 'plg_tests_SearchContainers';
}


class plg_tests_SearchType
{
    public function fromMysql($value)
    {
        return $value;
    }
}


class plg_tests_SearchDb
{
    public function escape($value)
    {
        return addslashes($value ?? '');
    }
}


class plg_tests_SearchQuery extends core_Query
{
    public static $probes = array();
    public $candidateIds = array();
    private $candidateOffset = 0;

    public function fetch($cond = null, $preventEvent = false)
    {
        if (!$this->candidateOffset) {
            self::$probes[] = clone $this;
        }
        if ($this->candidateOffset >= count($this->candidateIds) || ($this->limit !== null && $this->candidateOffset >= $this->limit)) {
            return false;
        }
        $id = $this->candidateIds[$this->candidateOffset++];

        return (object) array('id' => isset($this->fields['searchContainerId']) ? 7 : $id, 'searchContainerId' => $id);
    }
}
