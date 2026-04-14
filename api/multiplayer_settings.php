<?php

const MULTIPLAYER_ALLOWED_MODES = ['classic', 'sprint', 'challenge', 'custom'];
const MULTIPLAYER_ALLOWED_MODIFIERS = ['fog', 'blackout', 'noback', 'speedDecay'];
const MULTIPLAYER_ALLOWED_GENRES = ['random', 'history', 'science', 'geography', 'culture', 'technology', 'sports'];

function normalizeMultiplayerSettings($input) {
    $payload = is_array($input) ? $input : [];

    $mode = isset($payload['mode']) ? strtolower(trim((string)$payload['mode'])) : 'custom';
    if (!in_array($mode, MULTIPLAYER_ALLOWED_MODES, true)) $mode = 'custom';

    $genre = isset($payload['genre']) ? strtolower(trim((string)$payload['genre'])) : 'random';
    if (!in_array($genre, MULTIPLAYER_ALLOWED_GENRES, true)) $genre = 'random';

    $modifiersRaw = isset($payload['modifiers']) && is_array($payload['modifiers']) ? $payload['modifiers'] : [];
    $modifiers = [];
    foreach ($modifiersRaw as $mod) {
        $modId = strtolower(trim((string)$mod));
        if (in_array($modId, MULTIPLAYER_ALLOWED_MODIFIERS, true) && !in_array($modId, $modifiers, true)) {
            $modifiers[] = $modId;
        }
    }
    if (count($modifiers) > 4) $modifiers = array_slice($modifiers, 0, 4);

    $timeLimit = null;
    if ($mode === 'sprint') {
        $timeLimit = isset($payload['timeLimit']) ? (int)$payload['timeLimit'] : 120;
        $timeLimit = max(10, min(3600, $timeLimit));
    }

    $clickLimit = null;
    if ($mode === 'challenge') {
        $clickLimit = isset($payload['clickLimit']) ? (int)$payload['clickLimit'] : 6;
        $clickLimit = max(1, min(200, $clickLimit));
    }

    $customStartTitle = null;
    $customEndTitle = null;
    if ($mode === 'custom') {
        $customStartTitle = isset($payload['customStartTitle']) ? trim((string)$payload['customStartTitle']) : null;
        $customEndTitle = isset($payload['customEndTitle']) ? trim((string)$payload['customEndTitle']) : null;
        if ($customStartTitle === '') $customStartTitle = null;
        if ($customEndTitle === '') $customEndTitle = null;
    }

    return [
        'mode' => $mode,
        'genre' => $genre,
        'modifiers' => $modifiers,
        'timeLimit' => $timeLimit,
        'clickLimit' => $clickLimit,
        'customStartTitle' => $customStartTitle,
        'customEndTitle' => $customEndTitle,
    ];
}

function encodeMultiplayerSettings($settings) {
    return json_encode(normalizeMultiplayerSettings($settings));
}

function decodeMultiplayerSettings($rawSettings) {
    if (!is_string($rawSettings) || trim($rawSettings) === '') {
        return normalizeMultiplayerSettings([]);
    }
    $decoded = json_decode($rawSettings, true);
    return normalizeMultiplayerSettings(is_array($decoded) ? $decoded : []);
}
