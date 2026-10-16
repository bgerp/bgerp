<?php
/**
 * Матрица от синтетични сценарии. Изпълнява се чрез php doc/tests/repair.php.
 *
 * @category bgerp
 * @package doc
 * @author Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2026 Experta EOOD
 * @license GPL 3
 */
if (PHP_SAPI !== 'cli' || !class_exists('Audit', false)) exit("Run repair.php\n");

function repairPipeline()
{
    $result = array();
    $result['folders'] = doc_Folders::repair();
    $result['threads'] = doc_Threads::repair();
    $result['containers'] = doc_Containers::repair();
    $result['covers'] = doc_Folders::repairCover();
    $result['documents'] = doc_Containers::repairDoc();
    doc_Threads::doUpdateThread();
    return $result;
}
function graphFixture($state = 'active')
{
    normalGraph();
    Audit::$syncDocument = true;
    Mode::$flags['dontUpdateThread'] = false;
    Audit::$rows['FixtureDocument'][1]->state = $state;
    Audit::$rows['doc_Containers'][100]->state = $state;
    Audit::$rows['doc_Threads'][10]->state = $state === 'rejected' ? 'rejected' : 'closed';
    Audit::$rows['doc_Threads'][10]->lastState = $state;
    Audit::$rows['doc_Threads'][10]->allDocCnt = $state === 'rejected' ? 0 : 1;
    Audit::put('doc_Folders', array('id' => 2, 'coverId' => 2));
    Audit::put('doc_UnsortedFolders', array('id' => 2, 'folderId' => 2));
    Audit::put('doc_Threads', array('id' => 20, 'folderId' => 2, 'firstContainerId' => 200, 'firstDocId' => 2));
    Audit::put('doc_Containers', array('id' => 200, 'folderId' => 2, 'threadId' => 20, 'docId' => 2, 'containerId' => 200));
    Audit::put('FixtureDocument', array('id' => 2, 'folderId' => 2, 'threadId' => 20, 'containerId' => 200));
    Audit::put('core_Classes', array('id' => 20, 'name' => 'FixtureDocument', 'interfaces' => '|7|'));
}
function neighbourSnapshot()
{
    return serialize(array(Audit::$rows['doc_Folders'][2], Audit::$rows['doc_UnsortedFolders'][2],
        Audit::$rows['doc_Threads'][20], Audit::$rows['doc_Containers'][200], Audit::$rows['FixtureDocument'][2]));
}
function verifyGraph($state, $neighbour)
{
    verify(!Audit::$deletes, 'recoverable graph deleted records');
    $d = Audit::$rows['FixtureDocument'][1]; $c = Audit::$rows['doc_Containers'][100];
    verify($d->containerId == 100 && $d->threadId == 10 && $d->folderId == 1, 'document links were not recovered');
    verify($c->docClass == 20 && $c->docId == 1 && $c->threadId == 10 && $c->folderId == 1, 'container links damaged');
    verify($d->state === $state && $c->state === $state, 'document/container state changed');
    verify(neighbourSnapshot() === $neighbour, 'healthy neighbour changed');
}

foreach (array('yes', 'no') as $all) {
    foreach (array('active', 'draft', 'rejected', 'closed', 'pending', 'stopped') as $state) {
        runCase("matrix healthy graph {$all}/{$state}", function () use ($all, $state) {
            graphFixture($state); doc_Setup::$all = $all;
            $before = serialize(Audit::$rows);
            $res = repairPipeline();
            verify(serialize(Audit::$rows) === $before && !Audit::$writes && !Audit::$deletes, 'healthy graph was modified');
            foreach ($res as $counts) verify(!array_filter($counts), 'false positive result');
        });
    }
    foreach (array('active', 'draft', 'rejected') as $state) {
        for ($mask = 1; $mask < 8; $mask++) {
            foreach (array(false, true) as $partialContainer) {
                runCase("matrix missing links {$all}/{$state}/{$mask}/" . (int) $partialContainer, function () use ($all, $state, $mask, $partialContainer) {
                    graphFixture($state); doc_Setup::$all = $all; $neighbour = neighbourSnapshot();
                    foreach (array('containerId', 'threadId', 'folderId') as $i => $field) {
                        if ($mask & (1 << $i)) Audit::$rows['FixtureDocument'][1]->{$field} = null;
                    }
                    if ($partialContainer) Audit::$rows['doc_Containers'][100]->searchKeywords = '';
                    repairPipeline(); verifyGraph($state, $neighbour);
                    $before = serialize(Audit::$rows); $writeCount = count(Audit::$writes);
                    repairPipeline();
                    verify(serialize(Audit::$rows) === $before && count(Audit::$writes) === $writeCount, 'repeat repair writes or changes data');
                });
            }
        }
    }
}
foreach (array('active', 'draft', 'rejected') as $state) {
    foreach (array('containerId' => 200, 'threadId' => 20, 'folderId' => 2) as $field => $other) {
        foreach (array(0, 999, $other) as $bad) {
            runCase("matrix incorrect document link {$state}/{$field}/{$bad}", function () use ($state, $field, $bad) {
                graphFixture($state); $neighbour = neighbourSnapshot();
                Audit::$rows['FixtureDocument'][1]->{$field} = $bad;
                doc_Containers::repairDoc(); doc_Threads::doUpdateThread();
                verifyGraph($state, $neighbour);
            });
        }
    }
}
foreach (array('docId' => 2, 'docClass' => 999, 'folderId' => 2, 'threadId' => 20) as $field => $other) {
    foreach (array(null, 0, 999, $other) as $bad) {
        runCase('matrix incorrect container link ' . $field . '/' . var_export($bad, true), function () use ($field, $bad) {
            graphFixture(); $neighbour = neighbourSnapshot();
            Audit::$rows['doc_Containers'][100]->{$field} = $bad;
            repairPipeline(); verifyGraph('active', $neighbour);
        });
    }
}
foreach (array('firstContainerId' => 200, 'firstDocId' => 2, 'firstDocClass' => 90) as $field => $other) {
    foreach (array(null, 0, 999, $other) as $bad) {
        runCase('matrix incorrect thread link ' . $field . '/' . var_export($bad, true), function () use ($field, $bad) {
            graphFixture(); $neighbour = neighbourSnapshot();
            Audit::$rows['doc_Threads'][10]->{$field} = $bad;
            repairPipeline(); verifyGraph('active', $neighbour);
            $t = Audit::$rows['doc_Threads'][10];
            verify($t->firstContainerId == 100 && $t->firstDocId == 1 && $t->firstDocClass == 20, 'first document not corrected');
        });
    }
}
foreach (array('allDocCnt', 'partnerDocCnt') as $field) {
    foreach (array(null, 0, 7) as $bad) {
        foreach (array('yes', 'no') as $visible) {
            runCase('matrix thread counts ' . $field . '/' . var_export($bad, true) . '/' . $visible, function () use ($field, $bad, $visible) {
                graphFixture();
                Audit::$rows['FixtureDocument'][1]->visibleForPartners = $visible;
                Audit::$rows['doc_Containers'][100]->visibleForPartners = $visible;
                $t = Audit::$rows['doc_Threads'][10]; $t->visibleForPartners = $visible;
                $t->partnerDocCnt = $visible === 'yes' ? 1 : 0; $t->partnerDocLast = $visible === 'yes' ? $t->modifiedOn : null;
                $t->{$field} = $bad;
                doc_Threads::repair(); doc_Threads::doUpdateThread();
                $t = Audit::$rows['doc_Threads'][10];
                verify($t->allDocCnt == 1 && $t->partnerDocCnt == ($visible === 'yes' ? 1 : 0), 'counts not corrected');
                verify(!Audit::$deletes, 'healthy documents deleted');
            });
        }
    }
}
foreach (array('inCharge', 'title', 'coverId', 'coverClass') as $field) {
    runCase('matrix missing folder field ' . $field, function () use ($field) {
        graphFixture(); $neighbour = neighbourSnapshot(); Audit::$rows['doc_Folders'][1]->{$field} = null;
        doc_Folders::repair();
        $f = Audit::$rows['doc_Folders'][1];
        verify(!empty($f->{$field}) && !Audit::$deletes, 'folder field not recovered');
        verify(neighbourSnapshot() === $neighbour, 'healthy folder changed');
    });
}
foreach (array(false, true) as $richer) {
    runCase('matrix duplicate cover preserves valid pair ' . (int) $richer, function () use ($richer) {
        graphFixture();
        Audit::put('doc_UnsortedFolders', array('id' => 3, 'folderId' => 1, 'searchKeywords' => $richer ? str_repeat('x', 100) : 'x'));
        $good = clone Audit::$rows['doc_UnsortedFolders'][1]; $folder = clone Audit::$rows['doc_Folders'][1];
        doc_Folders::repairCover();
        verify(Audit::$rows['doc_UnsortedFolders'][1] == $good && Audit::$rows['doc_Folders'][1] == $folder, 'valid cover/folder pair overwritten');
        verify(Audit::$rows['doc_UnsortedFolders'][3]->folderId === null, 'duplicate not cleared');
        $writes = count(Audit::$writes); doc_Folders::repairCover();
        verify(count(Audit::$writes) === $writes, 'cover repeat not stable');
    });
}
runCase('matrix cover wrong pointer finds own folder', function () {
    graphFixture(); Audit::$rows['doc_UnsortedFolders'][1]->folderId = 2;
    doc_Folders::repairCover();
    verify(Audit::$rows['doc_UnsortedFolders'][1]->folderId == 1 && Audit::$rows['doc_UnsortedFolders'][2]->folderId == 2, 'correct reverse references not respected');
});
runCase('matrix stale cover pointer does not steal a valid folder', function () {
    graphFixture(); Audit::$rows['doc_UnsortedFolders'][1]->folderId = 2;
    unset(Audit::$rows['doc_Folders'][1]);
    $f = clone Audit::$rows['doc_Folders'][2];
    doc_Folders::repairCover();
    verify(Audit::$rows['doc_Folders'][2] == $f && Audit::$rows['doc_UnsortedFolders'][2]->folderId == 2, 'unrelated folder stolen');
    verify(Audit::$rows['doc_UnsortedFolders'][1]->folderId === null, 'ambiguous pointer not cleared');
});
foreach (array('doc_Folders', 'doc_Threads', 'doc_Containers') as $class) {
    runCase('matrix redirect restores logging ' . $class, function () use ($class) {
        graphFixture(); Audit::$throwQuery = $class; $caught = false;
        try { $class::repair(); } catch (core_exception_Redirect $e) { $caught = true; }
        verify($caught && core_Debug::$isLogging === true, 'logging/redirect propagation');
    });
}

foreach (array('active', 'draft', 'rejected', 'closed', 'pending', 'stopped') as $documentState) {
    foreach (array('active', 'draft', 'rejected', 'closed', 'pending', 'stopped') as $containerState) {
        runCase("matrix state synchronization {$documentState}/{$containerState}", function () use ($documentState, $containerState) {
            graphFixture($documentState); $neighbour = neighbourSnapshot();
            Audit::$rows['doc_Containers'][100]->state = $containerState;
            doc_Containers::repair(); doc_Threads::doUpdateThread();
            verifyGraph($documentState, $neighbour);
            $res = doc_Containers::repair();
            verify(!array_filter($res), 'state synchronization is not idempotent');
        });
    }
}
foreach (array('containerId' => 200, 'threadId' => 20, 'folderId' => 2) as $field => $other) {
    foreach (array(0, 999, $other) as $bad) {
        runCase("matrix full pipeline wrong document link {$field}/{$bad}", function () use ($field, $bad) {
            graphFixture(); $neighbour = neighbourSnapshot(); Audit::$rows['FixtureDocument'][1]->{$field} = $bad;
            Audit::$rows['doc_Containers'][100]->searchKeywords = '';
            repairPipeline(); verifyGraph('active', $neighbour);
        });
    }
}
function addSecondDocument($values = array())
{
    $c = Audit::put('doc_Containers', array_merge(array('id' => 101, 'docId' => 3, 'containerId' => 101, 'createdOn' => '2026-01-01 11:56:00'), $values));
    Audit::put('FixtureDocument', array('id' => 3, 'containerId' => 101, 'threadId' => $c->threadId, 'folderId' => $c->folderId,
        'state' => $c->state, 'visibleForPartners' => $c->visibleForPartners));
    Audit::$rows['doc_Threads'][10]->allDocCnt = $c->state === 'rejected' ? 1 : 2;
}
foreach (array('folderId', 'threadId') as $field) {
    foreach (array(null, 0, 999, 20) as $bad) {
        runCase('matrix non-first container ' . $field . '/' . var_export($bad, true), function () use ($field, $bad) {
            graphFixture(); addSecondDocument(); $neighbour = neighbourSnapshot();
            Audit::$rows['doc_Containers'][101]->{$field} = $bad;
            repairPipeline();
            $c = Audit::$rows['doc_Containers'][101]; $d = Audit::$rows['FixtureDocument'][3];
            verify($c->threadId == 10 && $c->folderId == 1 && $d->threadId == 10 && $d->folderId == 1, 'non-first document moved to a different thread/folder');
            verify(!Audit::$deletes && neighbourSnapshot() === $neighbour, 'neighbour/delete side effects');
        });
    }
}
foreach (array('active', 'draft', 'rejected') as $firstState) {
    foreach (array('yes', 'no') as $firstVisible) {
        foreach (array('active', 'draft', 'rejected') as $lastState) {
            foreach (array('yes', 'no') as $lastVisible) {
                runCase("matrix partner counts {$firstState}/{$firstVisible}/{$lastState}/{$lastVisible}", function () use ($firstState, $firstVisible, $lastState, $lastVisible) {
                    graphFixture($firstState);
                    Audit::$rows['doc_Containers'][100]->visibleForPartners = $firstVisible;
                    Audit::$rows['FixtureDocument'][1]->visibleForPartners = $firstVisible;
                    addSecondDocument(array('state' => $lastState, 'visibleForPartners' => $lastVisible));
                    $t = Audit::$rows['doc_Threads'][10]; $t->allDocCnt = 99; $t->partnerDocCnt = 99;
                    doc_Threads::repair(); doc_Threads::doUpdateThread();
                    $t = Audit::$rows['doc_Threads'][10];
                    $expectedAll = (int) ($firstState !== 'rejected') + (int) ($lastState !== 'rejected');
                    $expectedPartner = $firstVisible === 'no' ? 0 : (int) ($firstState === 'active') + (int) ($lastState === 'active' && $lastVisible === 'yes');
                    verify($t->allDocCnt === $expectedAll && $t->partnerDocCnt === $expectedPartner, 'incorrect multi-document counts');
                    verify($t->firstContainerId === 100 && !Audit::$deletes, 'first document/deletion changed');
                });
            }
        }
    }
}
foreach (array('2026-01-01 11:54:00' => 101, '2026-01-01 11:55:00' => 100, '2026-01-01 11:56:00' => 100) as $created => $expectedFirst) {
    foreach (array('active', 'draft', 'rejected') as $state) {
        runCase("matrix ordering {$created}/{$state}", function () use ($created, $expectedFirst, $state) {
            graphFixture(); addSecondDocument(array('createdOn' => $created, 'state' => $state));
            doc_Threads::repair(); doc_Threads::doUpdateThread();
            $t = Audit::$rows['doc_Threads'][10];
            verify($t->firstContainerId === $expectedFirst && $t->firstDocId === ($expectedFirst === 100 ? 1 : 3), 'createdOn/id ordering changed');
        });
    }
}
foreach (array('lastState', 'lastAuthor', 'allDocCnt', 'partnerDocCnt', 'firstDocId', 'firstDocClass') as $field) {
    runCase('matrix queued thread completion ' . $field, function () use ($field) {
        graphFixture(); Audit::$rows['doc_Threads'][10]->{$field} = null;
        doc_Threads::repair(); doc_Threads::doUpdateThread();
        verify(isset(Audit::$rows['doc_Threads'][10]->{$field}), 'deferred field not populated');
        $n = count(Audit::$writes); doc_Threads::repair(); doc_Threads::doUpdateThread();
        verify($n === count(Audit::$writes), 'repeated deferred update');
    });
}
foreach (array('doc_Folders::repair', 'doc_Threads::repair', 'doc_Containers::repair', 'doc_Folders::repairCover', 'doc_Containers::repairDoc') as $method) {
    foreach (array('11:47:59' => false, '11:48:00' => true, '11:58:00' => true, '11:58:01' => false) as $time => $selected) {
        runCase("matrix actual method date scope {$method}/{$time}", function () use ($method, $time, $selected) {
            graphFixture();
            $tableFields = array('doc_Folders::repair' => array('doc_Folders', 'inCharge'), 'doc_Threads::repair' => array('doc_Threads', 'firstDocId'),
                'doc_Containers::repair' => array('doc_Containers', 'docId'), 'doc_Folders::repairCover' => array('doc_UnsortedFolders', 'folderId'),
                'doc_Containers::repairDoc' => array('FixtureDocument', 'threadId'));
            list($table, $field) = $tableFields[$method];
            $id = $table === 'doc_Threads' ? 10 : ($table === 'doc_Containers' ? 100 : 1);
            $r = Audit::$rows[$table][$id]; $r->{$field} = $method === 'doc_Folders::repairCover' ? 999 : null;
            $r->modifiedOn = $r->createdOn = '2026-01-01 ' . $time;
            $before = clone $r;
            call_user_func($method, '2026-01-01 11:50:00', '2026-01-01 12:00:00', 120);
            verify((Audit::$rows[$table][$id] != $before) === $selected, 'date range selected incorrect records');
            verify(!Audit::$deletes, 'date repair deleted recoverable data');
        });
    }
}
runCase('matrix failed container ID save is not counted', function () {
    graphFixture(); Audit::$rows['doc_Containers'][100]->docId = null; Audit::$failClass = 'doc_Containers';
    $r = doc_Containers::repair();
    verify(($r['docId'] ?? 0) === 0 && !Audit::$deletes, 'failed ID save counted');
});
runCase('matrix failed container class save is not counted', function () {
    graphFixture(); Audit::$rows['doc_Containers'][100]->docClass = null; Audit::$failClass = 'doc_Containers';
    $r = doc_Containers::repair();
    verify(($r['docClass'] ?? 0) === 0 && !Audit::$deletes, 'failed class save counted');
});
foreach (array('repairDoc', 'repairCover') as $method) {
    runCase('matrix redirect from save ' . $method, function () use ($method) {
        graphFixture();
        if ($method === 'repairDoc') { Audit::$rows['FixtureDocument'][1]->folderId = null; Audit::$throwSave = 'FixtureDocument'; }
        else { Audit::$rows['doc_UnsortedFolders'][1]->folderId = 999; Audit::$throwSave = 'doc_UnsortedFolders'; }
        $caught = false;
        try { if ($method === 'repairDoc') doc_Containers::repairDoc(); else doc_Folders::repairCover(); }
        catch (core_exception_Redirect $e) { $caught = true; }
        verify($caught && core_Debug::$isLogging && !Audit::$deletes, 'save redirect was swallowed or caused deletion');
    });
}
runCase('matrix first-container backlink alone cannot move a healthy container', function () {
    graphFixture();
    Audit::$rows['doc_Threads'][10]->firstContainerId = 999;
    Audit::$rows['doc_Threads'][20]->firstContainerId = 100;
    $before = clone Audit::$rows['doc_Containers'][100];
    doc_Containers::repair();
    verify(Audit::$rows['doc_Containers'][100] == $before, 'bad reverse reference moved a healthy container');
});
runCase('matrix limited batch continues after 500 repaired folders', function () {
    doc_Setup::$all = 'no';
    for ($id = 2; $id <= 502; $id++) {
        Audit::put('doc_Folders', array('id' => $id, 'inCharge' => null, 'coverId' => $id));
        Audit::put('doc_UnsortedFolders', array('id' => $id, 'folderId' => $id));
    }
    $one = doc_Folders::repair(); $two = doc_Folders::repair(); $three = doc_Folders::repair();
    verify(($one['inCharge'] ?? 0) === 500 && ($two['inCharge'] ?? 0) === 1 && !$three, 'batch skipped or duplicated records');
});

runCase('matrix document cannot claim another documents container', function () {
    graphFixture(); unset(Audit::$rows['doc_Containers'][100]);
    Audit::$rows['FixtureDocument'][1]->containerId = 200;
    $before = clone Audit::$rows['FixtureDocument'][1]; $neighbour = neighbourSnapshot();
    doc_Containers::repairDoc();
    verify(Audit::$rows['FixtureDocument'][1] == $before && neighbourSnapshot() === $neighbour && !Audit::$deletes, 'foreign container was trusted');
});
runCase('matrix contradictory non-first links in same folder are not guessed', function () {
    graphFixture(); addSecondDocument();
    foreach (array('doc_Threads' => 20, 'doc_Containers' => 200, 'FixtureDocument' => 2) as $table => $id) Audit::$rows[$table][$id]->folderId = 1;
    Audit::$rows['doc_Containers'][101]->threadId = 20;
    $docBefore = clone Audit::$rows['FixtureDocument'][3]; $containerBefore = clone Audit::$rows['doc_Containers'][101];
    repairPipeline();
    verify(Audit::$rows['FixtureDocument'][3] == $docBefore && Audit::$rows['doc_Containers'][101] == $containerBefore && !Audit::$deletes, 'ambiguous ownership was guessed');
});
foreach (array('yes', 'no') as $all) {
    foreach (array('active', 'rejected') as $state) {
        runCase("matrix unrecoverable document cleanup {$all}/{$state}", function () use ($all, $state) {
            graphFixture($state); doc_Setup::$all = $all; $neighbour = neighbourSnapshot();
            unset(Audit::$rows['doc_Containers'][100]); Audit::$rows['FixtureDocument'][1]->containerId = null;
            $r = doc_Containers::repairDoc();
            verify(!isset(Audit::$rows['FixtureDocument'][1]) && ($r['del_cnt'] ?? 0) === 1, 'existing orphan cleanup not preserved');
            verify(count(Audit::$deletes) === 1 && neighbourSnapshot() === $neighbour, 'unrelated records deleted');
        });
    }
}
foreach (array('yes', 'no') as $all) {
    runCase('matrix multiple repairs partial and full ' . $all, function () use ($all) {
        graphFixture(); doc_Setup::$all = $all;
        Audit::$rows['doc_Containers'][100]->docId = null;
        Audit::$rows['doc_Containers'][200]->docId = null;
        $r = doc_Containers::repair();
        verify(($r['docId'] ?? 0) === 2 && Audit::$rows['doc_Containers'][100]->docId == 1 && Audit::$rows['doc_Containers'][200]->docId == 2, 'repair counts lost across records');
    });
}
foreach (array('yes', 'no') as $all) {
    runCase('matrix missing container visibility and keywords ' . $all, function () use ($all) {
        graphFixture(); doc_Setup::$all = $all;
        Audit::$rows['doc_Containers'][100]->visibleForPartners = null;
        Audit::$rows['doc_Containers'][100]->searchKeywords = '';
        doc_Containers::repair();
        $r = Audit::$rows['doc_Containers'][100];
        verify($r->visibleForPartners === 'no' && $r->searchKeywords === 'fixture', 'one missing field prevented repairing the other');
    });
}
runCase('matrix valid origin preserved, missing origin cleared', function () {
    graphFixture(); Audit::$rows['FixtureDocument'][1]->originId = 200;
    doc_Containers::repairDoc(); verify(Audit::$rows['FixtureDocument'][1]->originId === 200, 'valid origin cleared');
    Audit::$rows['FixtureDocument'][1]->originId = 999;
    $r = doc_Containers::repairDoc();
    verify(Audit::$rows['FixtureDocument'][1]->originId === null && ($r['originId'] ?? 0) === 1, 'missing origin not cleared');
});
runCase('matrix orphan cover pointer cleared only once', function () {
    graphFixture(); Audit::$rows['doc_UnsortedFolders'][1]->folderId = 999;
    $r = doc_Folders::repairCover();
    verify(($r['folderId'] ?? 0) === 1 && Audit::$rows['doc_UnsortedFolders'][1]->folderId === null, 'orphan cover count');
});
runCase('matrix cron system user fallback for folder owner', function () {
    graphFixture(); core_Users::$current = -1; Audit::$rows['doc_Folders'][1]->inCharge = null;
    (new doc_Containers())->cron_Repair();
    verify(Audit::$rows['doc_Folders'][1]->inCharge === 1, 'system user used as folder owner');
});
runCase('matrix visibility-only optimization with excluded keyword field', function () {
    graphFixture(); Audit::$excludeKeywords = true;
    Audit::$rows['doc_Containers'][100]->visibleForPartners = null;
    $r = doc_Containers::repair();
    verify(($r['updateVisibleForPartners'] ?? 0) === 1 && ($r['updateContainers'] ?? 0) === 0, 'visibility-only repair unnecessarily updated the whole container');
});

runCase('matrix manager fetch rejects invalid conditions like core_Mvc', function () {
    foreach (array(null, false, 1.5, new stdClass()) as $condition) {
        $caught = false;
        try { doc_Folders::fetch($condition); } catch (core_exception_Expect $e) { $caught = true; }
        verify($caught, 'invalid manager fetch condition accepted');
    }
});
foreach (array(null, 0) as $coverId) {
    foreach (array('yes', 'no') as $all) {
        runCase('matrix cover class without cover ID ' . var_export($coverId, true) . '/' . $all, function () use ($coverId, $all) {
            graphFixture(); doc_Setup::$all = $all;
            Audit::$rows['doc_Folders'][1]->coverId = $coverId;
            $neighbour = neighbourSnapshot();
            $res = doc_Folders::repairCover();
            verify(!Audit::$errors, 'missing cover ID passed to manager fetch');
            verify(Audit::$rows['doc_Folders'][1]->coverId === ($all === 'yes' ? 1 : $coverId), 'cover repair scope or result changed');
            verify(($res['coverId'] ?? 0) === ($all === 'yes' ? 1 : 0), 'incorrect cover repair count');
            verify(Audit::$rows['doc_UnsortedFolders'][1]->folderId === 1 && neighbourSnapshot() === $neighbour && !Audit::$deletes, 'existing cover detached or neighbour changed');
            $writes = count(Audit::$writes); doc_Folders::repairCover();
            verify(count(Audit::$writes) === $writes, 'cover repair is not stable');
        });
    }
}
function queuedRepairThreads()
{
    $p = new ReflectionProperty('doc_Threads', 'updateQueue'); $p->setAccessible(true);
    $ids = array_keys($p->getValue()); sort($ids);
    return $ids;
}
foreach (array('manual', 'cron') as $entry) {
    foreach (array(100, 101) as $movedId) {
        foreach (array('yes', 'no') as $visible) {
            runCase("matrix moved container refreshes both threads {$entry}/{$movedId}/{$visible}", function () use ($entry, $movedId, $visible) {
                graphFixture(); addSecondDocument();
                foreach (Audit::$rows['doc_Containers'] as $c) $c->visibleForPartners = $visible;
                foreach (Audit::$rows['FixtureDocument'] as $d) $d->visibleForPartners = $visible;
                // Съхранените броячи отговарят на грешното разположение преди поправката.
                Audit::$rows['doc_Containers'][$movedId]->threadId = 20;
                doc_Threads::doUpdateThread(array(10 => true, 20 => true));
                foreach (Audit::$rows['doc_Threads'] as $t) $t->modifiedOn = '2025-01-01 00:00:00';
                foreach (Audit::$rows['doc_Folders'] as $f) $f->createdOn = '2025-01-01 00:00:00';
                verify(Audit::$rows['doc_Threads'][10]->allDocCnt === 1 && Audit::$rows['doc_Threads'][20]->allDocCnt === 2, 'fixture counts');
                $documents = serialize(Audit::$rows['FixtureDocument']);
                Audit::$writes = array();
                $res = $entry === 'cron' ? (new doc_Containers())->cron_Repair()['containers'] : doc_Containers::repairAll();
                verify(Audit::$rows['doc_Containers'][$movedId]->threadId === 10 && ($res['threadId'] ?? 0) === 1, 'container not returned');
                verify(queuedRepairThreads() === array(10, 20), 'both existing threads must be queued');
                doc_Threads::doUpdateThread();
                $new = Audit::$rows['doc_Threads'][10]; $old = Audit::$rows['doc_Threads'][20];
                verify($new->allDocCnt === 2 && $old->allDocCnt === 1, 'counts stale after move');
                verify($new->firstContainerId === 100 && $new->firstDocId === 1 && $old->firstContainerId === 200 && $old->firstDocId === 2, 'first documents stale after move');
                verify($new->partnerDocCnt === ($visible === 'yes' ? 2 : 0) && $old->partnerDocCnt === ($visible === 'yes' ? 1 : 0), 'partner counts stale after move');
                verify(serialize(Audit::$rows['FixtureDocument']) === $documents && !Audit::$deletes, 'documents changed or removed');
                verify(queuedRepairThreads() === array(), 'thread queue not drained');
                $writes = count(Audit::$writes); doc_Containers::repairAll(); doc_Threads::doUpdateThread();
                verify(count(Audit::$writes) === $writes, 'healthy repeat queues or writes');
            });
        }
    }
}
foreach (array(null, 0, 999) as $oldThreadId) {
    runCase('matrix missing old thread queues only destination ' . var_export($oldThreadId, true), function () use ($oldThreadId) {
        graphFixture(); Audit::$rows['doc_Containers'][100]->threadId = $oldThreadId;
        doc_Containers::repairAll();
        verify(queuedRepairThreads() === array(10), 'missing old thread queued or destination omitted');
        doc_Threads::doUpdateThread();
        verify(Audit::$rows['doc_Threads'][10]->allDocCnt === 1 && !Audit::$deletes, 'destination not refreshed');
    });
}
foreach (array('unchanged', 'folder-only', 'failed-save', 'ambiguous') as $case) {
    runCase('matrix structural repair does not queue a move for ' . $case, function () use ($case) {
        graphFixture();
        $rec = clone Audit::$rows['doc_Containers'][100];
        if ($case === 'folder-only') $rec->folderId = null;
        if ($case === 'failed-save') { $rec->threadId = 20; Audit::$failClass = 'doc_Containers'; }
        if ($case === 'ambiguous') {
            $rec->threadId = 20;
            Audit::$rows['doc_Threads'][10]->firstContainerId = 999;
            Audit::$rows['doc_Threads'][20]->folderId = 1;
        }
        $before = clone $rec; $res = array();
        $method = new ReflectionMethod('doc_Containers', 'repairContainerLinksFromDocument'); $method->setAccessible(true);
        $method->invokeArgs(null, array($rec, &$res));
        verify(queuedRepairThreads() === array(), 'no successful thread move but thread queued');
        verify(($res['threadId'] ?? 0) === 0 && $rec->threadId === $before->threadId, 'thread change counted or retained without save');
        if ($case === 'folder-only') verify($rec->folderId === 1 && ($res['folderId'] ?? 0) === 1, 'folder-only repair failed');
        else verify(!Audit::$writes && $rec == $before, 'unchanged/failed/ambiguous repair mutated data');
    });
}
