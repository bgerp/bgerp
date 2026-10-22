<?php


/**
 * Проверки на индексните подсказки и проекцията при преброяване
 *
 * @category  ef
 * @package   core
 * @copyright 2026 Experta EOOD
 */
class core_tests_Query extends unit_Class
{
    public static function test_FullTextIndexHints()
    {
        $query = self::makeThreadQuery();
        $query->mvc->dbIndexes = array('title_fts' => (object) array('type' => 'FULLTEXT', 'fields' => 'title'));
        $query->useIndex('PRIMARY');
        ut::expectEqual($query->getIndexes('doc_threads'), "\nUSE INDEX(PRIMARY)");
        ut::expectEqual($query->mvc->db->engineReads, 0);

        $query->where("MATCH (#title) AGAINST('+synthetic*' IN BOOLEAN MODE)");
        ut::expectEqual($query->getIndexes('doc_threads'), "\nUSE INDEX(PRIMARY,title_fts)");
        ut::expectEqual($query->indexes['doc_threads'], array('PRIMARY' => true));

        $query->where = array();
        ut::expectEqual($query->getIndexes('doc_threads'), "\nUSE INDEX(PRIMARY)");
        $query->fields['relevance'] = (object) array(
            'name' => 'relevance', 'kind' => 'XPR',
            'expression' => "match(#title) against('+synthetic*' IN BOOLEAN MODE)",
        );
        ut::expectEqual($query->getIndexes('doc_threads'), "\nUSE INDEX(PRIMARY,title_fts)");

        $query->useIndex('title_fts');
        ut::expectEqual($query->getIndexes('doc_threads'), "\nUSE INDEX(PRIMARY,title_fts)");
        $query->indexes = array();
        ut::expectEqual($query->getIndexes('doc_threads'), '');
        ut::expectEqual($query->mvc->db->engineReads, 1);
    }


    public static function test_ExternalFullTextIndexHints()
    {
        $query = self::makeThreadQuery();
        $query->useIndex('PRIMARY');
        $query->useIndex('folder_id', 'core_tests_QueryContainers');
        $query->where("MATCH(#containerSearchKeywords) AGAINST('+synthetic*' IN BOOLEAN MODE)");
        ut::expectEqual($query->getIndexes('doc_containers'), "\nUSE INDEX(folder_id,search_keywords)");
        ut::expectEqual($query->getIndexes('doc_threads'), "\nUSE INDEX(PRIMARY)");
        $sql = $query->buildQuery();
        ut::expectEqual(strpos($sql, 'USE INDEX(folder_id,search_keywords)') !== false, true);
        ut::expectEqual(strpos($sql, 'MATCH(`doc_containers`.`search_keywords`)') !== false, true);

        $query->where = array();
        ut::expectEqual($query->getIndexes('doc_containers'), "\nUSE INDEX(folder_id)");
    }


    public static function test_MyisamFullTextKeepsPrimaryPlan()
    {
        foreach (array('MyISAM', 'Aria') as $engine) {
            $query = self::makeThreadQuery();
            $query->mvc->db->engine = $engine;
            $query->useIndex('PRIMARY', 'core_tests_QueryContainers');
            $query->where("MATCH(#containerSearchKeywords) AGAINST('+synthetic*' IN BOOLEAN MODE)");
            ut::expectEqual($query->getIndexes('doc_containers'), "\nUSE INDEX(PRIMARY)");
            ut::expectEqual($query->getIndexes('doc_containers'), "\nUSE INDEX(PRIMARY)");
            ut::expectEqual($query->mvc->db->engineReads, 1);

            // Същият обект на БД може временно да бъде пренасочен към реплика с друг engine.
            $primaryDbName = $query->mvc->db->dbName;
            $query->mvc->db->dbName = $primaryDbName . '_replica';
            $query->mvc->db->engine = 'InnoDB';
            ut::expectEqual($query->getIndexes('doc_containers'), "\nUSE INDEX(PRIMARY,search_keywords)");
            ut::expectEqual($query->mvc->db->engineReads, 2);
            $query->mvc->db->dbName = $primaryDbName;
            ut::expectEqual($query->getIndexes('doc_containers'), "\nUSE INDEX(PRIMARY)");
            ut::expectEqual($query->mvc->db->engineReads, 2);
        }
    }


    public static function test_FullTextEngineCacheSharedByQueries()
    {
        foreach (array('MyISAM', 'Aria', 'InnoDB') as $engine) {
            $query = self::makeThreadQuery();
            $query->mvc->db->engine = $engine;
            $query->useIndex('PRIMARY', 'core_tests_QueryContainers');
            $query->where("MATCH(#containerSearchKeywords) AGAINST('+synthetic*' IN BOOLEAN MODE)");
            $copy = clone $query;

            $hash = $query->getHash(true);
            ut::expectEqual($query->getHash(true), $hash);
            $sql = $query->buildQuery();
            ut::expectEqual($copy->buildQuery(), $sql);
            ut::expectEqual($query->mvc->db->engineReads, 1);

            // Независима заявка със същата SQL връзка също ползва вече прочетения engine.
            $other = self::makeThreadQuery();
            $other->mvc->db = clone $query->mvc->db;
            $other->mvc->db->engineReads = 0;
            $other->useIndex('PRIMARY', 'core_tests_QueryContainers');
            $other->where("MATCH(#containerSearchKeywords) AGAINST('+synthetic*' IN BOOLEAN MODE)");
            ut::expectEqual($other->buildQuery(), $sql);
            ut::expectEqual($other->mvc->db->engineReads, 0);

            // Същото име на база на друг сървър не трябва да използва този кеш.
            $other->mvc->db->dbHost = 'replica.example.test';
            $other->mvc->db->engine = 'InnoDB';
            ut::expectEqual($other->getIndexes('doc_containers'), "\nUSE INDEX(PRIMARY,search_keywords)");
            ut::expectEqual($other->mvc->db->engineReads, 1);
        }
    }


    public static function test_PlainCountDropsProjectionButPreservesJoins()
    {
        $query = self::makeThreadQuery();
        $query->groupBy = array();
        $query->count();
        $originalSql = $query->mvc->db->lastQuery;
        $query->countById = true;
        $query->count();
        $sql = $query->mvc->db->lastQuery;

        ut::expectEqual(substr($sql, 0, strpos($sql, "\nFROM ")), "SELECT \n   count(*) AS `_count`");
        ut::expectEqual(substr($sql, strpos($sql, "\nFROM ")), substr($originalSql, strpos($originalSql, "\nFROM ")));
        ut::expectEqual($query->show, array());
    }


    public static function test_PlainCountPreservesHavingAndBoundedQueries()
    {
        $query = self::makeThreadQuery();
        $query->groupBy = array();
        $query->countById = true;
        $query->fields['matches'] = (object) array('name' => 'matches', 'kind' => 'XPR', 'expression' => 'COUNT(*)');
        $query->where('#matches > 1');
        $query->count();
        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'COUNT(*) AS `matches`') !== false, true);
        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'HAVING') !== false, true);

        foreach (array(array(2, null), array(null, 2), array(2, 2)) as $bounds) {
            $query = self::makeThreadQuery();
            $query->groupBy = array();
            $query->limit($bounds[0]);
            $query->startFrom($bounds[1]);
            $query->count();
            $originalSql = $query->mvc->db->lastQuery;
            $query->countById = true;
            $query->count();
            ut::expectEqual($query->mvc->db->lastQuery, $originalSql);
        }
    }


    public static function test_GroupedCountProjectsOnlyId()
    {
        $query = self::makeThreadQuery();
        $query->countById = true;
        $originalShow = $query->show;
        $originalWhere = $query->where;

        ut::expectEqual($query->count(), 3);
        $sql = $query->mvc->db->lastQuery;

        ut::expectEqual(strpos($sql, '1 AS `fix_val`,`doc_threads`.`id` AS `id`') !== false, true);
        ut::expectEqual(strpos($sql, 'AS `containerSearchKeywords`') === false, true);
        ut::expectEqual(strpos($sql, 'AS `title`') === false, true);
        ut::expectEqual(strpos($sql, 'GROUP BY `doc_threads`.`id`') !== false, true);
        ut::expectEqual(strpos($sql, '`doc_containers`.`thread_id` = `doc_threads`.`id`') !== false, true);
        ut::expectEqual(strpos($sql, "LOCATE(' hartia', `doc_containers`.`search_keywords`)") !== false, true);
        ut::expectEqual($query->show, $originalShow);
        ut::expectEqual($query->where, $originalWhere);
    }


    public static function test_GroupedCountPreservesProjectionOnlyJoin()
    {
        $query = self::makeThreadQuery();
        $query->countById = true;
        $query->where = array();
        $query->fields['containerSearchKeywords']->onCond = '#id = `doc_containers`.`thread_id`';
        $query->fields['containerSearchKeywords']->join = 'LEFT';

        $query->count();
        $sql = $query->mvc->db->lastQuery;

        ut::expectEqual(strpos($sql, '`doc_containers`') !== false, true);
        ut::expectEqual(strpos($sql, 'LEFT JOIN `doc_threads` ON') !== false, true);
        ut::expectEqual(strpos($sql, '`doc_threads`.`id` = `doc_containers`.`thread_id`') !== false, true);
        ut::expectEqual(strpos($sql, 'AS `containerSearchKeywords`') === false, true);
    }


    public static function test_GroupedCountPreservesExternalKeyJoin()
    {
        $query = self::makeThreadQuery();
        $query->countById = true;
        $query->where = array();
        $query->fields['containerSearchKeywords']->externalKey = 'id';
        $query->fields['containerSearchKeywords']->externalFieldName = 'threadId';

        $query->count();
        $sql = $query->mvc->db->lastQuery;

        ut::expectEqual(strpos($sql, '`doc_threads`.`id` = `doc_containers`.`thread_id`') !== false, true);
        ut::expectEqual(strpos($sql, 'AS `containerSearchKeywords`') === false, true);
    }


    public static function test_GroupedCountIsOptIn()
    {
        $query = self::makeThreadQuery();
        $query->count();

        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'AS `containerSearchKeywords`') !== false, true);
    }


    public static function test_GroupedCountPreservesHavingProjection()
    {
        $query = self::makeThreadQuery();
        $query->countById = true;
        $query->fields['matches'] = (object) array('name' => 'matches', 'kind' => 'XPR', 'expression' => 'COUNT(*)');
        $query->where('#matches > 1');
        $query->count();

        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'COUNT(*) AS `matches`') !== false, true);
        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'HAVING') !== false, true);
        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'AS `containerSearchKeywords`') !== false, true);
    }


    public static function test_GroupedCountDropsUnusedAggregateOnlyWithOptIn()
    {
        $query = self::makeThreadQuery();
        $query->fields['matches'] = (object) array('name' => 'matches', 'kind' => 'XPR', 'expression' => 'COUNT(*)');
        $query->count();
        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'COUNT(*) AS `matches`') !== false, true);

        $query->countById = true;
        $query->count();
        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'AS `matches`') === false, true);

        $query->groupBy = array('`doc_threads`.`title`' => true);
        $query->count();
        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'COUNT(*) AS `matches`') !== false, true);
    }


    public static function test_GroupedCountPreservesLimit()
    {
        $query = self::makeThreadQuery();
        $query->countById = true;
        $query->count(null, 2);

        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'LIMIT 2') !== false, true);
        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'AS `containerSearchKeywords`') === false, true);
        ut::expectEqual($query->limit, null);
    }


    public static function test_GroupedCountPreservesOffset()
    {
        $query = self::makeThreadQuery();
        $query->countById = true;
        $query->startFrom(2);
        $query->count();

        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'LIMIT 2,18446744073709551615') !== false, true);
        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'AS `containerSearchKeywords`') === false, true);
    }


    public static function test_GroupedCountChangesOnlyProjectionWithLimits()
    {
        foreach (array(array(null, null), array(0, null), array(2, null), array(0, 0), array(2, 0), array(2, 2), array(null, 2)) as $bounds) {
            $query = self::makeThreadQuery();
            $query->limit($bounds[0]);
            $query->startFrom($bounds[1]);
            $query->count();
            $originalSql = $query->mvc->db->lastQuery;

            $query->countById = true;
            $query->count();
            $optimizedSql = $query->mvc->db->lastQuery;

            ut::expectEqual(substr($optimizedSql, strpos($optimizedSql, "\nFROM ")), substr($originalSql, strpos($originalSql, "\nFROM ")));
            ut::expectEqual(strpos($optimizedSql, 'AS `containerSearchKeywords`') === false, true);
            ut::expectEqual($query->limit, $bounds[0]);
            ut::expectEqual($query->start, $bounds[1]);
        }
    }


    public static function test_GroupedCountPreservesOtherGroups()
    {
        $query = self::makeThreadQuery();
        $query->countById = true;
        $query->groupBy = array('`doc_threads`.`title`' => true);
        $query->count();

        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'AS `containerSearchKeywords`') !== false, true);

        $query->groupBy('`doc_threads`.`id`');
        $query->count();
        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'AS `containerSearchKeywords`') !== false, true);
    }


    public static function test_GroupedCountPreservesUnionProjection()
    {
        $query = self::makeThreadQuery();
        $query->countById = true;
        $query->setUnion('#id = 1');
        $query->setUnion('#id = 2');
        $query->count();

        ut::expectEqual(strpos($query->mvc->db->lastQuery, 'UNION') !== false, true);
        ut::expectEqual(substr_count($query->mvc->db->lastQuery, 'AS `containerSearchKeywords`'), 2);
    }


    public static function test_HasUnionIncludesSingleBranch()
    {
        $query = self::makeThreadQuery();
        ut::expectEqual($query->hasUnion(), false);

        $query->setUnion('#id = 1');
        ut::expectEqual($query->hasUnion(), true);

        $query->useUnionAll = true;
        $query->setUnion('#id = 2');
        ut::expectEqual($query->hasUnion(), true);
    }


    public static function test_HasExecutedAfterExhaustingResult()
    {
        $query = self::makeThreadQuery();
        ut::expectEqual($query->hasExecuted(), false);
        ut::expectEqual($query->numRec(), null);

        $query->select();
        ut::expectEqual($query->hasExecuted(), true);
        ut::expectEqual($query->numRec(), 0);

        ut::expectEqual($query->fetch(), false);
        ut::expectEqual($query->numRec(), null);
        ut::expectEqual($query->hasExecuted(), true);
    }


    private static function makeThreadQuery()
    {
        $query = new core_Query();
        $query->mvc = new core_tests_QueryMvc();
        $query->mvc->db = new core_tests_QueryDb();
        $query->fields = array(
            'id' => (object) array('name' => 'id', 'kind' => 'FLD'),
            'title' => (object) array('name' => 'title', 'kind' => 'FLD'),
            'containerSearchKeywords' => (object) array(
                'name' => 'containerSearchKeywords',
                'kind' => 'EXT',
                'externalClass' => 'core_tests_QueryContainers',
                'externalName' => 'searchKeywords',
            ),
        );
        $query->where('`doc_containers`.`thread_id` = `doc_threads`.`id`');
        $query->where("LOCATE(' hartia', #containerSearchKeywords)");
        $query->groupBy('`doc_threads`.`id`');

        return $query;
    }
}


class core_tests_QueryMvc
{
    public $dbTableName = 'doc_threads';
    public $dbIndexes = array();
    public $db;


    public function invoke($event, $args)
    {
        return true;
    }
}


class core_tests_QueryContainers
{
    public $dbTableName = 'doc_containers';
    public $dbIndexes;


    public function __construct()
    {
        $this->dbIndexes = array('search_keywords' => (object) array('type' => 'FULLTEXT', 'fields' => 'searchKeywords'));
    }
}


class core_tests_QueryDb
{
    public $lastQuery;
    public $dbHost = 'localhost';
    public $dbName;
    public $dbUser = 'fixture';
    public $engine = 'InnoDB';
    public $engineReads = 0;


    public function __construct()
    {
        static $nextDatabase = 0;
        $this->dbName = 'query_fixture_' . ++$nextDatabase;
    }


    public function getTableInfo($tableName, $part = null)
    {
        ut::expectEqual($part, 'ENGINE');
        $this->engineReads++;

        return $this->engine;
    }


    public function query($sql)
    {
        $this->lastQuery = $sql;

        return (object) array('num_rows' => 0);
    }


    public function fetchObject($result)
    {
        return (object) array('_count' => '3');
    }


    public function fetchArray($result)
    {
        return false;
    }


    public function freeResult($result)
    {
    }
}
