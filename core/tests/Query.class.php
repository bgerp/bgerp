<?php


/**
 * Проверки на проекцията при преброяване на групирани заявки
 *
 * @category  ef
 * @package   core
 */
class core_tests_Query extends unit_Class
{
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
    public $db;


    public function invoke($event, $args)
    {
        return true;
    }
}


class core_tests_QueryContainers
{
    public $dbTableName = 'doc_containers';
}


class core_tests_QueryDb
{
    public $lastQuery;


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
