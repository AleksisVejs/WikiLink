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
