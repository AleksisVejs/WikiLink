<?php
require_once __DIR__ . '/db.php';

function createChallenge($startTitle, $endTitle, $createdBy) {
    $startTitle = trim($startTitle);
    $endTitle = trim($endTitle);
    if (!$startTitle || !$endTitle) {
        return ['error' => 'Both start and end titles are required.'];
    }
    if (strtolower($startTitle) === strtolower($endTitle)) {
        return ['error' => 'Start and end must be different.'];
    }

    $db = getDb();

    if ($createdBy) {
        $stmt = $db->prepare('SELECT code FROM shared_challenges WHERE start_title = ? AND end_title = ? AND created_by = ? LIMIT 1');
        $stmt->execute([$startTitle, $endTitle, $createdBy]);
        $existing = $stmt->fetch();
        if ($existing) return ['code' => $existing['code']];
    }

    for ($i = 0; $i < 10; $i++) {
        $code = generateUniqueCode();
        try {
            $stmt = $db->prepare('INSERT INTO shared_challenges (code, start_title, end_title, created_by) VALUES (?, ?, ?, ?)');
            $stmt->execute([$code, $startTitle, $endTitle, $createdBy]);
            return ['code' => $code];
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE') === false) throw $e;
        }
    }
    return ['error' => 'Could not generate a unique code. Try again.'];
}

function getChallenge($code) {
    $db = getDb();
    $stmt = $db->prepare('SELECT code, start_title, end_title FROM shared_challenges WHERE code = ?');
    $stmt->execute([strtoupper(trim($code))]);
    $row = $stmt->fetch();
    return $row ? $row : null;
}

function normalizePathTitle($title) {
    return trim(str_replace('_', ' ', (string)$title));
}

function normalizePairTitle($title) {
    return strtolower(normalizePathTitle($title));
}

function getCommunityPaths($startTitle, $endTitle, $viewerUserId = null, $limit = 20) {
    $startNorm = normalizePairTitle($startTitle);
    $endNorm = normalizePairTitle($endTitle);
    if ($startNorm === '' || $endNorm === '' || $startNorm === $endNorm) {
        return ['error' => 'Invalid route pair.'];
    }

    $db = getDb();
    $stmt = $db->prepare(
        'SELECT cp.id, cp.user_id, cp.path_json, cp.path_length, cp.likes_count, cp.dislikes_count, cp.score,
                cp.created_at, u.username,
                (SELECT vote FROM community_path_votes v WHERE v.community_path_id = cp.id AND v.user_id = :viewer LIMIT 1) AS your_vote
         FROM community_paths cp
         JOIN users u ON u.id = cp.user_id
         WHERE lower(cp.start_title) = :start AND lower(cp.end_title) = :end
         ORDER BY cp.path_length ASC, cp.score DESC, cp.likes_count DESC, cp.created_at ASC
         LIMIT 1'
    );
    $stmt->bindValue(':viewer', (int)($viewerUserId ?: 0), PDO::PARAM_INT);
    $stmt->bindValue(':start', $startNorm, PDO::PARAM_STR);
    $stmt->bindValue(':end', $endNorm, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $bestStmt = $db->prepare(
        'SELECT path_json, path_length, user_id
         FROM community_paths
         WHERE lower(start_title) = ? AND lower(end_title) = ?
         ORDER BY path_length ASC, score DESC, likes_count DESC, created_at ASC
         LIMIT 1'
    );
    $bestStmt->execute([$startNorm, $endNorm]);
    $best = $bestStmt->fetch();

    $viewerBest = $viewerUserId && $best ? ((int)$best['user_id'] === (int)$viewerUserId) : false;

    $items = array_map(function ($row) {
        return [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'username' => (string)$row['username'],
            'path' => json_decode($row['path_json'], true) ?: [],
            'path_length' => (int)$row['path_length'],
            'likes' => (int)$row['likes_count'],
            'dislikes' => (int)$row['dislikes_count'],
            'score' => (int)$row['score'],
            'yourVote' => $row['your_vote'] === null ? 0 : (int)$row['your_vote'],
            'created_at' => $row['created_at'],
        ];
    }, $rows);

    return [
        'startTitle' => normalizePathTitle($startTitle),
        'endTitle' => normalizePathTitle($endTitle),
        'bestPath' => $best ? [
            'path' => json_decode($best['path_json'], true) ?: [],
            'path_length' => (int)$best['path_length'],
            'user_id' => (int)$best['user_id'],
        ] : null,
        'viewerHasBest' => $viewerBest,
        'items' => $items,
    ];
}

function upsertCommunityPairUserBest($userId, $startNorm, $endNorm, $pathLength, $timeSeconds, $clicks) {
    $sn = normalizePairTitle($startNorm);
    $en = normalizePairTitle($endNorm);
    if ($sn === '' || $en === '' || $sn === $en) {
        return;
    }

    $db = getDb();
    $stmt = $db->prepare('SELECT best_path_length, best_time_seconds, best_clicks FROM community_pair_user_best WHERE user_id = ? AND start_norm = ? AND end_norm = ?');
    $stmt->execute([(int)$userId, $sn, $en]);
    $row = $stmt->fetch();
    $better = !$row;
    if ($row) {
        $bl = (int)$row['best_path_length'];
        $bt = (int)$row['best_time_seconds'];
        $bc = (int)$row['best_clicks'];
        if ($pathLength < $bl) {
            $better = true;
        } elseif ($pathLength > $bl) {
            $better = false;
        } elseif ($timeSeconds < $bt) {
            $better = true;
        } elseif ($timeSeconds > $bt) {
            $better = false;
        } elseif ($clicks < $bc) {
            $better = true;
        } else {
            $better = false;
        }
    }
    if (!$better) {
        return;
    }

    if ($row) {
        $u = $db->prepare('UPDATE community_pair_user_best SET best_path_length = ?, best_clicks = ?, best_time_seconds = ?, updated_at = datetime(\'now\') WHERE user_id = ? AND start_norm = ? AND end_norm = ?');
        $u->execute([$pathLength, $clicks, $timeSeconds, (int)$userId, $sn, $en]);
    } else {
        $i = $db->prepare('INSERT INTO community_pair_user_best (user_id, start_norm, end_norm, best_path_length, best_clicks, best_time_seconds, updated_at) VALUES (?, ?, ?, ?, ?, ?, datetime(\'now\'))');
        $i->execute([(int)$userId, $sn, $en, $pathLength, $clicks, $timeSeconds]);
    }
}

function getCommunityPairRunLeaderboardForPairId($pairId, $limit = 20) {
    $pairId = max(1, (int)$pairId);
    $limit = max(1, min(50, (int)$limit));
    $db = getDb();
    $stmt = $db->prepare('SELECT id, start_title, end_title FROM shared_challenges WHERE id = ? LIMIT 1');
    $stmt->execute([$pairId]);
    $pair = $stmt->fetch();
    if (!$pair) {
        return ['error' => 'Pair not found.'];
    }

    $sn = normalizePairTitle($pair['start_title']);
    $en = normalizePairTitle($pair['end_title']);

    $lb = $db->prepare(
        'SELECT u.username, u.id AS user_id, u.profile_title,
                b.best_path_length, b.best_clicks, b.best_time_seconds
         FROM community_pair_user_best b
         JOIN users u ON u.id = b.user_id
         WHERE b.start_norm = ? AND b.end_norm = ?
         ORDER BY b.best_path_length ASC, b.best_time_seconds ASC, b.best_clicks ASC
         LIMIT ?'
    );
    $lb->execute([$sn, $en, $limit]);
    $rows = $lb->fetchAll();

    $rank = 1;
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'rank' => $rank++,
            'username' => (string)$r['username'],
            'userId' => (int)$r['user_id'],
            'profileTitle' => (string)($r['profile_title'] ?? 'newcomer'),
            'bestPathLength' => (int)$r['best_path_length'],
            'bestClicks' => (int)$r['best_clicks'],
            'bestTimeSeconds' => (int)$r['best_time_seconds'],
        ];
    }

    return [
        'pairId' => $pairId,
        'startTitle' => normalizePathTitle($pair['start_title']),
        'endTitle' => normalizePathTitle($pair['end_title']),
        'rows' => $out,
    ];
}

function getCommunityGroupRunLeaderboardForGroupId($groupId, $limit = 20) {
    $groupId = max(1, (int)$groupId);
    $limit = max(1, min(50, (int)$limit));
    $db = getDb();
    $stmt = $db->prepare('SELECT id, name FROM community_pair_groups WHERE id = ? LIMIT 1');
    $stmt->execute([$groupId]);
    $g = $stmt->fetch();
    if (!$g) {
        return ['error' => 'Group not found.'];
    }

    $lb = $db->prepare(
        'SELECT u.username, u.id AS user_id, u.profile_title,
                b.best_total_clicks, b.best_total_time
         FROM community_group_user_best b
         JOIN users u ON u.id = b.user_id
         WHERE b.group_id = ?
         ORDER BY b.best_total_clicks ASC, b.best_total_time ASC
         LIMIT ?'
    );
    $lb->execute([$groupId, $limit]);
    $rows = $lb->fetchAll();

    $rank = 1;
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'rank' => $rank++,
            'username' => (string)$r['username'],
            'userId' => (int)$r['user_id'],
            'profileTitle' => (string)($r['profile_title'] ?? 'newcomer'),
            'bestTotalClicks' => (int)$r['best_total_clicks'],
            'bestTotalTimeSeconds' => (int)$r['best_total_time'],
        ];
    }

    return [
        'groupId' => $groupId,
        'name' => (string)$g['name'],
        'rows' => $out,
    ];
}

function submitCommunityGroupRun($userId, $groupId, $totalClicks, $totalTime) {
    $groupId = (int)$groupId;
    $totalClicks = (int)$totalClicks;
    $totalTime = (int)$totalTime;
    if ($groupId < 1 || $totalClicks < 1) {
        return ['error' => 'Invalid run.'];
    }

    $db = getDb();
    $stmt = $db->prepare('SELECT id FROM community_pair_groups WHERE id = ? LIMIT 1');
    $stmt->execute([$groupId]);
    if (!$stmt->fetch()) {
        return ['error' => 'Group not found.'];
    }

    $stmt = $db->prepare('SELECT best_total_clicks, best_total_time FROM community_group_user_best WHERE user_id = ? AND group_id = ?');
    $stmt->execute([(int)$userId, $groupId]);
    $row = $stmt->fetch();
    $better = !$row;
    if ($row) {
        $bc = (int)$row['best_total_clicks'];
        $bt = (int)$row['best_total_time'];
        if ($totalClicks < $bc) {
            $better = true;
        } elseif ($totalClicks > $bc) {
            $better = false;
        } elseif ($totalTime < $bt) {
            $better = true;
        } else {
            $better = false;
        }
    }
    if (!$better) {
        return ['ok' => true, 'updated' => false];
    }

    if ($row) {
        $u = $db->prepare('UPDATE community_group_user_best SET best_total_clicks = ?, best_total_time = ?, updated_at = datetime(\'now\') WHERE user_id = ? AND group_id = ?');
        $u->execute([$totalClicks, $totalTime, (int)$userId, $groupId]);
    } else {
        $i = $db->prepare('INSERT INTO community_group_user_best (user_id, group_id, best_total_clicks, best_total_time, updated_at) VALUES (?, ?, ?, ?, datetime(\'now\'))');
        $i->execute([(int)$userId, $groupId, $totalClicks, $totalTime]);
    }

    return ['ok' => true, 'updated' => true];
}

function submitCommunityPath($userId, $startTitle, $endTitle, $path, $timeSeconds = null, $clicks = null) {
    $startNorm = normalizePathTitle($startTitle);
    $endNorm = normalizePathTitle($endTitle);
    if ($startNorm === '' || $endNorm === '' || strtolower($startNorm) === strtolower($endNorm)) {
        return ['error' => 'Invalid route pair.'];
    }
    if (!is_array($path) || count($path) < 2) {
        return ['error' => 'Path must include at least 2 articles.'];
    }

    $normalizedPath = [];
    foreach ($path as $node) {
        $title = normalizePathTitle($node);
        if ($title === '' || strlen($title) > 200) {
            return ['error' => 'Path contains an invalid article title.'];
        }
        $normalizedPath[] = $title;
    }

    if (normalizePairTitle($normalizedPath[0]) !== normalizePairTitle($startNorm)) {
        return ['error' => 'Path must start with the selected start article.'];
    }
    if (normalizePairTitle($normalizedPath[count($normalizedPath) - 1]) !== normalizePairTitle($endNorm)) {
        return ['error' => 'Path must end with the selected target article.'];
    }

    $pathJson = json_encode($normalizedPath);
    $pathHash = sha1($pathJson);
    $pathLength = count($normalizedPath);

    $db = getDb();
    $existingStmt = $db->prepare(
        'SELECT id, path_length
         FROM community_paths
         WHERE lower(start_title) = ? AND lower(end_title) = ?
         ORDER BY path_length ASC, score DESC, likes_count DESC, created_at ASC
         LIMIT 1'
    );
    $existingStmt->execute([strtolower($startNorm), strtolower($endNorm)]);
    $existing = $existingStmt->fetch();

    if (!$existing) {
        $insertStmt = $db->prepare(
            'INSERT INTO community_paths
             (user_id, start_title, end_title, path_json, path_hash, path_length, likes_count, dislikes_count, score, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, datetime(\'now\'))'
        );
        $insertStmt->execute([(int)$userId, $startNorm, $endNorm, $pathJson, $pathHash, $pathLength]);
    } else {
        $bestLength = (int)$existing['path_length'];
        // Keep exactly one saved path per pair: only replace when strictly shorter.
        if ($pathLength < $bestLength) {
            $updateStmt = $db->prepare(
                'UPDATE community_paths
                 SET user_id = ?, path_json = ?, path_hash = ?, path_length = ?,
                     likes_count = 0, dislikes_count = 0, score = 0, updated_at = datetime(\'now\')
                 WHERE id = ?'
            );
            $updateStmt->execute([(int)$userId, $pathJson, $pathHash, $pathLength, (int)$existing['id']]);

            $clearVotesStmt = $db->prepare('DELETE FROM community_path_votes WHERE community_path_id = ?');
            $clearVotesStmt->execute([(int)$existing['id']]);
        }
    }

    $ts = $timeSeconds !== null ? max(0, (int)$timeSeconds) : 0;
    $ck = $clicks !== null ? max(0, (int)$clicks) : 0;
    upsertCommunityPairUserBest($userId, $startNorm, $endNorm, $pathLength, $ts, $ck);

    return getCommunityPaths($startNorm, $endNorm, $userId, 20);
}

function voteCommunityPath($userId, $pathId, $vote) {
    $vote = (int)$vote;
    if ($vote !== 1 && $vote !== -1 && $vote !== 0) {
        return ['error' => 'Vote must be 1, -1, or 0.'];
    }

    $db = getDb();
    $pathStmt = $db->prepare('SELECT id, start_title, end_title FROM community_paths WHERE id = ? LIMIT 1');
    $pathStmt->execute([(int)$pathId]);
    $pathRow = $pathStmt->fetch();
    if (!$pathRow) return ['error' => 'Path not found.'];

    if ($vote === 0) {
        $deleteStmt = $db->prepare('DELETE FROM community_path_votes WHERE community_path_id = ? AND user_id = ?');
        $deleteStmt->execute([(int)$pathId, (int)$userId]);
    } else {
        $upsertStmt = $db->prepare(
            'INSERT INTO community_path_votes (community_path_id, user_id, vote, updated_at)
             VALUES (?, ?, ?, datetime(\'now\'))
             ON CONFLICT(community_path_id, user_id) DO UPDATE SET vote = excluded.vote, updated_at = datetime(\'now\')'
        );
        $upsertStmt->execute([(int)$pathId, (int)$userId, $vote]);
    }

    $aggStmt = $db->prepare(
        'SELECT
            SUM(CASE WHEN vote = 1 THEN 1 ELSE 0 END) AS likes_count,
            SUM(CASE WHEN vote = -1 THEN 1 ELSE 0 END) AS dislikes_count
         FROM community_path_votes
         WHERE community_path_id = ?'
    );
    $aggStmt->execute([(int)$pathId]);
    $agg = $aggStmt->fetch();
    $likes = (int)($agg['likes_count'] ?? 0);
    $dislikes = (int)($agg['dislikes_count'] ?? 0);
    $score = $likes - $dislikes;

    $updateStmt = $db->prepare(
        'UPDATE community_paths
         SET likes_count = ?, dislikes_count = ?, score = ?, updated_at = datetime(\'now\')
         WHERE id = ?'
    );
    $updateStmt->execute([$likes, $dislikes, $score, (int)$pathId]);

    return getCommunityPaths($pathRow['start_title'], $pathRow['end_title'], $userId, 20);
}

function getCommunityPairCatalog($pairLimit = 24, $collectionLimit = 8, $collectionPairsLimit = 5) {
    $db = getDb();
    $pairLimit = max(1, min(100, (int)$pairLimit));
    $collectionLimit = max(1, min(30, (int)$collectionLimit));
    $collectionPairsLimit = max(1, min(20, (int)$collectionPairsLimit));

    $pairsStmt = $db->prepare(
        'SELECT
            cp.start_title,
            cp.end_title,
            MIN(cp.path_length) AS shortest_length,
            COUNT(*) AS submissions,
            MAX(cp.score) AS top_score,
            MAX(cp.created_at) AS latest_at
         FROM community_paths cp
         GROUP BY lower(cp.start_title), lower(cp.end_title)
         ORDER BY submissions DESC, shortest_length ASC, latest_at DESC
         LIMIT :limit'
    );
    $pairsStmt->bindValue(':limit', $pairLimit, PDO::PARAM_INT);
    $pairsStmt->execute();
    $pairRows = $pairsStmt->fetchAll();

    $pairs = array_map(function ($row) {
        return [
            'startTitle' => (string)$row['start_title'],
            'endTitle' => (string)$row['end_title'],
            'shortestLength' => (int)$row['shortest_length'],
            'submissions' => (int)$row['submissions'],
            'topScore' => (int)$row['top_score'],
        ];
    }, $pairRows);

    $collectionUsersStmt = $db->prepare(
        'SELECT
            u.id AS user_id,
            u.username,
            COUNT(*) AS total_paths
         FROM community_paths cp
         JOIN users u ON u.id = cp.user_id
         GROUP BY cp.user_id
         ORDER BY total_paths DESC, u.username ASC
         LIMIT :limit'
    );
    $collectionUsersStmt->bindValue(':limit', $collectionLimit, PDO::PARAM_INT);
    $collectionUsersStmt->execute();
    $userRows = $collectionUsersStmt->fetchAll();

    $pairByUserStmt = $db->prepare(
        'SELECT
            cp.start_title,
            cp.end_title,
            MIN(cp.path_length) AS shortest_length,
            COUNT(*) AS submissions
         FROM community_paths cp
         WHERE cp.user_id = ?
         GROUP BY lower(cp.start_title), lower(cp.end_title)
         ORDER BY submissions DESC, shortest_length ASC, MAX(cp.created_at) DESC
         LIMIT ?'
    );

    $collections = [];
    foreach ($userRows as $userRow) {
        $pairByUserStmt->execute([(int)$userRow['user_id'], $collectionPairsLimit]);
        $userPairs = $pairByUserStmt->fetchAll();
        $collections[] = [
            'userId' => (int)$userRow['user_id'],
            'username' => (string)$userRow['username'],
            'totalPaths' => (int)$userRow['total_paths'],
            'pairs' => array_map(function ($p) {
                return [
                    'startTitle' => (string)$p['start_title'],
                    'endTitle' => (string)$p['end_title'],
                    'shortestLength' => (int)$p['shortest_length'],
                    'submissions' => (int)$p['submissions'],
                ];
            }, $userPairs),
        ];
    }

    return [
        'pairs' => $pairs,
        'collections' => $collections,
    ];
}

/**
 * @param array<int, array<string, mixed>> $pairRows
 * @return array<int, array<string, mixed>>
 */
function mapCommunityHubPairRows(array $pairRows, $viewerUserId) {
    return array_map(function ($row) use ($viewerUserId) {
        return [
            'id' => (int)$row['id'],
            'code' => (string)$row['code'],
            'startTitle' => (string)$row['start_title'],
            'endTitle' => (string)$row['end_title'],
            'userId' => $row['created_by'] == null ? null : (int)$row['created_by'],
            'username' => $row['username'] ? (string)$row['username'] : 'Anonymous',
            'createdAt' => (string)$row['created_at'],
            'likes' => (int)($row['likes_count'] ?? 0),
            'dislikes' => (int)($row['dislikes_count'] ?? 0),
            'score' => (int)($row['score'] ?? 0),
            'yourVote' => $row['your_vote'] === null ? 0 : (int)$row['your_vote'],
            'canDelete' => $viewerUserId != null && $row['created_by'] != null && (int)$row['created_by'] === (int)$viewerUserId,
        ];
    }, $pairRows);
}

/**
 * @param array<int, array<string, mixed>> $groupRows
 * @return array<int, array<string, mixed>>
 */
function buildCommunityHubGroups(PDO $db, array $groupRows, $groupItemsLimit) {
    $groupItemsStmt = $db->prepare(
        'SELECT position_idx, challenge_code, start_title, end_title
         FROM community_pair_group_items
         WHERE group_id = ?
         ORDER BY position_idx ASC
         LIMIT ?'
    );
    $groups = [];
    foreach ($groupRows as $g) {
        $groupItemsStmt->execute([(int)$g['id'], $groupItemsLimit]);
        $items = $groupItemsStmt->fetchAll();
        $groups[] = [
            'id' => (int)$g['id'],
            'name' => (string)$g['name'],
            'userId' => (int)$g['user_id'],
            'username' => (string)$g['username'],
            'createdAt' => (string)$g['created_at'],
            'likes' => (int)($g['likes_count'] ?? 0),
            'dislikes' => (int)($g['dislikes_count'] ?? 0),
            'score' => (int)($g['score'] ?? 0),
            'yourVote' => $g['your_vote'] === null ? 0 : (int)$g['your_vote'],
            'items' => array_map(function ($it) {
                return [
                    'position' => (int)$it['position_idx'],
                    'code' => $it['challenge_code'] ? (string)$it['challenge_code'] : null,
                    'startTitle' => (string)$it['start_title'],
                    'endTitle' => (string)$it['end_title'],
                ];
            }, $items),
        ];
    }
    return $groups;
}

function getCommunityHubData(
    $pairLimit = 15,
    $pairOffset = 0,
    $groupLimit = 10,
    $groupOffset = 0,
    $groupItemsLimit = 8,
    $topRatedPairLimit = 6,
    $topRatedGroupLimit = 6,
    $viewerUserId = null
) {
    $db = getDb();
    $pairLimit = max(1, min(100, (int)$pairLimit));
    $pairOffset = max(0, min(50000, (int)$pairOffset));
    $groupLimit = max(1, min(40, (int)$groupLimit));
    $groupOffset = max(0, min(50000, (int)$groupOffset));
    $groupItemsLimit = max(1, min(20, (int)$groupItemsLimit));
    $topRatedPairLimit = max(0, min(20, (int)$topRatedPairLimit));
    $topRatedGroupLimit = max(0, min(20, (int)$topRatedGroupLimit));

    $pairTotal = (int)$db->query('SELECT COUNT(*) FROM shared_challenges')->fetchColumn();
    $groupTotal = (int)$db->query('SELECT COUNT(*) FROM community_pair_groups')->fetchColumn();
    $groupLegTotal = (int)$db->query('SELECT COUNT(*) FROM community_pair_group_items')->fetchColumn();

    $pairSqlBase =
        'SELECT
            sc.id,
            sc.code,
            sc.start_title,
            sc.end_title,
            sc.created_at,
            sc.created_by,
            u.username,
            COALESCE(v.likes_count, 0) AS likes_count,
            COALESCE(v.dislikes_count, 0) AS dislikes_count,
            COALESCE(v.likes_count, 0) - COALESCE(v.dislikes_count, 0) AS score,
            (SELECT vote FROM community_pair_votes cpv WHERE cpv.pair_id = sc.id AND cpv.user_id = :viewer LIMIT 1) AS your_vote
         FROM shared_challenges sc
         LEFT JOIN users u ON u.id = sc.created_by
         LEFT JOIN (
            SELECT
                pair_id,
                SUM(CASE WHEN vote = 1 THEN 1 ELSE 0 END) AS likes_count,
                SUM(CASE WHEN vote = -1 THEN 1 ELSE 0 END) AS dislikes_count
            FROM community_pair_votes
            GROUP BY pair_id
         ) v ON v.pair_id = sc.id ';

    $pairsNewestStmt = $db->prepare(
        $pairSqlBase .
        'ORDER BY sc.created_at DESC
         LIMIT :limit OFFSET :offset'
    );
    $pairsNewestStmt->bindValue(':viewer', (int)($viewerUserId ?: 0), PDO::PARAM_INT);
    $pairsNewestStmt->bindValue(':limit', $pairLimit, PDO::PARAM_INT);
    $pairsNewestStmt->bindValue(':offset', $pairOffset, PDO::PARAM_INT);
    $pairsNewestStmt->execute();
    $pairRowsNewest = $pairsNewestStmt->fetchAll();

    $pairRowsTop = [];
    if ($topRatedPairLimit > 0) {
        $pairsTopStmt = $db->prepare(
            $pairSqlBase .
            'ORDER BY (COALESCE(v.likes_count, 0) - COALESCE(v.dislikes_count, 0)) DESC,
                     COALESCE(v.likes_count, 0) DESC,
                     sc.id DESC
             LIMIT :topLimit'
        );
        $pairsTopStmt->bindValue(':viewer', (int)($viewerUserId ?: 0), PDO::PARAM_INT);
        $pairsTopStmt->bindValue(':topLimit', $topRatedPairLimit, PDO::PARAM_INT);
        $pairsTopStmt->execute();
        $pairRowsTop = $pairsTopStmt->fetchAll();
    }

    $groupSqlBase =
        'SELECT
            g.id,
            g.name,
            g.user_id,
            g.created_at,
            u.username,
            COALESCE(v.likes_count, 0) AS likes_count,
            COALESCE(v.dislikes_count, 0) AS dislikes_count,
            COALESCE(v.likes_count, 0) - COALESCE(v.dislikes_count, 0) AS score,
            (SELECT vote FROM community_group_votes cgv WHERE cgv.group_id = g.id AND cgv.user_id = :viewer LIMIT 1) AS your_vote
         FROM community_pair_groups g
         JOIN users u ON u.id = g.user_id
         LEFT JOIN (
            SELECT
                group_id,
                SUM(CASE WHEN vote = 1 THEN 1 ELSE 0 END) AS likes_count,
                SUM(CASE WHEN vote = -1 THEN 1 ELSE 0 END) AS dislikes_count
            FROM community_group_votes
            GROUP BY group_id
         ) v ON v.group_id = g.id ';

    $groupsNewestStmt = $db->prepare(
        $groupSqlBase .
        'ORDER BY g.created_at DESC
         LIMIT :limit OFFSET :offset'
    );
    $groupsNewestStmt->bindValue(':viewer', (int)($viewerUserId ?: 0), PDO::PARAM_INT);
    $groupsNewestStmt->bindValue(':limit', $groupLimit, PDO::PARAM_INT);
    $groupsNewestStmt->bindValue(':offset', $groupOffset, PDO::PARAM_INT);
    $groupsNewestStmt->execute();
    $groupRowsNewest = $groupsNewestStmt->fetchAll();

    $groupRowsTop = [];
    if ($topRatedGroupLimit > 0) {
        $groupsTopStmt = $db->prepare(
            $groupSqlBase .
            'ORDER BY (COALESCE(v.likes_count, 0) - COALESCE(v.dislikes_count, 0)) DESC,
                     COALESCE(v.likes_count, 0) DESC,
                     g.id DESC
             LIMIT :topLimit'
        );
        $groupsTopStmt->bindValue(':viewer', (int)($viewerUserId ?: 0), PDO::PARAM_INT);
        $groupsTopStmt->bindValue(':topLimit', $topRatedGroupLimit, PDO::PARAM_INT);
        $groupsTopStmt->execute();
        $groupRowsTop = $groupsTopStmt->fetchAll();
    }

    return [
        'pairs' => mapCommunityHubPairRows($pairRowsNewest, $viewerUserId),
        'groups' => buildCommunityHubGroups($db, $groupRowsNewest, $groupItemsLimit),
        'topPairs' => mapCommunityHubPairRows($pairRowsTop, $viewerUserId),
        'topGroups' => buildCommunityHubGroups($db, $groupRowsTop, $groupItemsLimit),
        'pairTotal' => $pairTotal,
        'groupTotal' => $groupTotal,
        'stats' => [
            'pairCount' => $pairTotal,
            'groupCount' => $groupTotal,
            'routeCount' => $pairTotal + $groupLegTotal,
        ],
    ];
}

function voteCommunityPair($userId, $pairId, $vote) {
    $vote = (int)$vote;
    if ($vote !== 1 && $vote !== -1 && $vote !== 0) {
        return ['error' => 'Vote must be 1, -1, or 0.'];
    }

    $db = getDb();
    $pairStmt = $db->prepare('SELECT id FROM shared_challenges WHERE id = ? LIMIT 1');
    $pairStmt->execute([(int)$pairId]);
    $pair = $pairStmt->fetch();
    if (!$pair) return ['error' => 'Pair not found.'];

    if ($vote === 0) {
        $deleteStmt = $db->prepare('DELETE FROM community_pair_votes WHERE pair_id = ? AND user_id = ?');
        $deleteStmt->execute([(int)$pairId, (int)$userId]);
    } else {
        $upsertStmt = $db->prepare(
            'INSERT INTO community_pair_votes (pair_id, user_id, vote, updated_at)
             VALUES (?, ?, ?, datetime(\'now\'))
             ON CONFLICT(pair_id, user_id) DO UPDATE SET vote = excluded.vote, updated_at = datetime(\'now\')'
        );
        $upsertStmt->execute([(int)$pairId, (int)$userId, $vote]);
    }

    return ['ok' => true];
}

function voteCommunityGroup($userId, $groupId, $vote) {
    $vote = (int)$vote;
    if ($vote !== 1 && $vote !== -1 && $vote !== 0) {
        return ['error' => 'Vote must be 1, -1, or 0.'];
    }

    $db = getDb();
    $groupStmt = $db->prepare('SELECT id FROM community_pair_groups WHERE id = ? LIMIT 1');
    $groupStmt->execute([(int)$groupId]);
    $group = $groupStmt->fetch();
    if (!$group) return ['error' => 'Group not found.'];

    if ($vote === 0) {
        $deleteStmt = $db->prepare('DELETE FROM community_group_votes WHERE group_id = ? AND user_id = ?');
        $deleteStmt->execute([(int)$groupId, (int)$userId]);
    } else {
        $upsertStmt = $db->prepare(
            'INSERT INTO community_group_votes (group_id, user_id, vote, updated_at)
             VALUES (?, ?, ?, datetime(\'now\'))
             ON CONFLICT(group_id, user_id) DO UPDATE SET vote = excluded.vote, updated_at = datetime(\'now\')'
        );
        $upsertStmt->execute([(int)$groupId, (int)$userId, $vote]);
    }

    return ['ok' => true];
}

function createCommunityPair($userId, $startTitle, $endTitle) {
    $created = createChallenge($startTitle, $endTitle, $userId);
    if (!empty($created['error']) || empty($created['code'])) return $created;

    $db = getDb();
    $stmt = $db->prepare('SELECT id, code, start_title, end_title, created_at FROM shared_challenges WHERE code = ? LIMIT 1');
    $stmt->execute([$created['code']]);
    $row = $stmt->fetch();
    if (!$row) return $created;
    return [
        'id' => (int)$row['id'],
        'code' => (string)$row['code'],
        'startTitle' => (string)$row['start_title'],
        'endTitle' => (string)$row['end_title'],
        'createdAt' => (string)$row['created_at'],
    ];
}

function createCommunityPairGroup($userId, $name, $pairs) {
    $name = trim((string)$name);
    if ($name === '' || strlen($name) > 80) {
        return ['error' => 'Group name is required (max 80 chars).'];
    }
    if (!is_array($pairs) || count($pairs) < 2) {
        return ['error' => 'Group requires at least 2 pairs.'];
    }
    if (count($pairs) > 12) {
        return ['error' => 'Group can include up to 12 pairs.'];
    }

    $db = getDb();
    $db->beginTransaction();
    try {
        $insertGroup = $db->prepare('INSERT INTO community_pair_groups (user_id, name) VALUES (?, ?)');
        $insertGroup->execute([(int)$userId, $name]);
        $groupId = (int)$db->lastInsertId();

        $insertItem = $db->prepare(
            'INSERT INTO community_pair_group_items (group_id, position_idx, challenge_code, start_title, end_title)
             VALUES (?, ?, NULL, ?, ?)'
        );

        foreach ($pairs as $idx => $pair) {
            $start = isset($pair['startTitle']) ? trim((string)$pair['startTitle']) : '';
            $end = isset($pair['endTitle']) ? trim((string)$pair['endTitle']) : '';
            if ($start === '' || $end === '' || strtolower($start) === strtolower($end)) {
                $db->rollBack();
                return ['error' => 'Each pair needs valid and different start/target titles.'];
            }
            $insertItem->execute([$groupId, $idx + 1, $start, $end]);
        }

        $db->commit();
        return ['ok' => true, 'groupId' => $groupId];
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        return ['error' => 'Failed to create pair group.'];
    }
}

function deleteCommunityPair($userId, $pairId) {
    $db = getDb();
    $rowStmt = $db->prepare('SELECT id, created_by FROM shared_challenges WHERE id = ? LIMIT 1');
    $rowStmt->execute([(int)$pairId]);
    $row = $rowStmt->fetch();
    if (!$row) return ['error' => 'Pair not found.'];
    if ((int)($row['created_by'] ?? 0) !== (int)$userId) return ['error' => 'You can only delete your own pairs.'];

    $deleteItemRefs = $db->prepare('UPDATE community_pair_group_items SET challenge_code = NULL WHERE challenge_code = (SELECT code FROM shared_challenges WHERE id = ?)');
    $deleteItemRefs->execute([(int)$pairId]);
    $deletePair = $db->prepare('DELETE FROM shared_challenges WHERE id = ?');
    $deletePair->execute([(int)$pairId]);
    return ['ok' => true];
}

function deleteCommunityGroup($userId, $groupId) {
    $db = getDb();
    $rowStmt = $db->prepare('SELECT id, user_id FROM community_pair_groups WHERE id = ? LIMIT 1');
    $rowStmt->execute([(int)$groupId]);
    $row = $rowStmt->fetch();
    if (!$row) return ['error' => 'Group not found.'];
    if ((int)$row['user_id'] !== (int)$userId) return ['error' => 'You can only delete your own groups.'];
    $deleteStmt = $db->prepare('DELETE FROM community_pair_groups WHERE id = ?');
    $deleteStmt->execute([(int)$groupId]);
    return ['ok' => true];
}

function getCommunityGroupById($groupId) {
    $db = getDb();
    $groupStmt = $db->prepare(
        'SELECT g.id, g.name, g.user_id, g.created_at, u.username
         FROM community_pair_groups g
         JOIN users u ON u.id = g.user_id
         WHERE g.id = ?
         LIMIT 1'
    );
    $groupStmt->execute([(int)$groupId]);
    $group = $groupStmt->fetch();
    if (!$group) return null;

    $itemsStmt = $db->prepare(
        'SELECT position_idx, challenge_code, start_title, end_title
         FROM community_pair_group_items
         WHERE group_id = ?
         ORDER BY position_idx ASC'
    );
    $itemsStmt->execute([(int)$groupId]);
    $items = $itemsStmt->fetchAll();

    return [
        'id' => (int)$group['id'],
        'name' => (string)$group['name'],
        'userId' => (int)$group['user_id'],
        'username' => (string)$group['username'],
        'createdAt' => (string)$group['created_at'],
        'items' => array_map(function ($it) {
            return [
                'position' => (int)$it['position_idx'],
                'code' => $it['challenge_code'] ? (string)$it['challenge_code'] : null,
                'startTitle' => (string)$it['start_title'],
                'endTitle' => (string)$it['end_title'],
            ];
        }, $items),
    ];
}
