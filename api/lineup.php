<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

function lineupJsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function lineupReadJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function ensureTeamLineupsTable(PDO $pdo): void
{
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS team_lineups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team_id INT NOT NULL,
            pg_player_id INT NULL,
            sg_player_id INT NULL,
            sf_player_id INT NULL,
            pf_player_id INT NULL,
            c_player_id INT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_team_lineup (team_id),
            INDEX idx_team_lineup_pg (pg_player_id),
            INDEX idx_team_lineup_sg (sg_player_id),
            INDEX idx_team_lineup_sf (sf_player_id),
            INDEX idx_team_lineup_pf (pf_player_id),
            INDEX idx_team_lineup_c (c_player_id),
            CONSTRAINT fk_lineup_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } catch (Exception $e) {
        lineupJsonResponse(500, ['success' => false, 'error' => 'Erro ao preparar tabela de escalação']);
    }
}

ensureTeamLineupsTable($pdo);

$user = getUserSession();
if (!isset($user['id'])) {
    lineupJsonResponse(401, ['success' => false, 'error' => 'Sessão expirada ou usuário não autenticado.']);
}

$stmtTeam = $pdo->prepare('SELECT id FROM teams WHERE user_id = ? LIMIT 1');
$stmtTeam->execute([$user['id']]);
$teamId = (int)($stmtTeam->fetchColumn() ?: 0);
if ($teamId <= 0) {
    lineupJsonResponse(400, ['success' => false, 'error' => 'Usuário sem time.']);
}

$positions = ['pg', 'sg', 'sf', 'pf', 'c'];

if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT pg_player_id, sg_player_id, sf_player_id, pf_player_id, c_player_id, updated_at FROM team_lineups WHERE team_id = ? LIMIT 1');
    $stmt->execute([$teamId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $lineup = [];
    foreach ($positions as $pos) {
        $key = $pos . '_player_id';
        $lineup[$pos] = isset($row[$key]) && $row[$key] ? (int)$row[$key] : null;
    }

    lineupJsonResponse(200, [
        'success' => true,
        'team_id' => $teamId,
        'lineup' => $lineup,
        'updated_at' => $row['updated_at'] ?? null,
    ]);
}

if ($method === 'PUT') {
    $body = lineupReadJsonBody();
    $incoming = isset($body['lineup']) && is_array($body['lineup']) ? $body['lineup'] : [];

    $normalized = [];
    foreach ($positions as $pos) {
        $value = $incoming[$pos] ?? null;
        if ($value === null || $value === '') {
            $normalized[$pos] = null;
            continue;
        }
        $id = (int)$value;
        $normalized[$pos] = $id > 0 ? $id : null;
    }

    $ids = array_values(array_filter($normalized, static fn($id) => $id !== null));
    $uniqueIds = array_values(array_unique($ids));
    if (count($ids) !== count($uniqueIds)) {
        lineupJsonResponse(422, ['success' => false, 'error' => 'A mesma peça não pode ocupar mais de uma posição.']);
    }

    if (!empty($uniqueIds)) {
        $placeholders = implode(',', array_fill(0, count($uniqueIds), '?'));
        $params = array_merge([$teamId], $uniqueIds);
        $stmtPlayers = $pdo->prepare("SELECT id FROM players WHERE team_id = ? AND id IN ($placeholders)");
        $stmtPlayers->execute($params);
        $validIds = array_map('intval', $stmtPlayers->fetchAll(PDO::FETCH_COLUMN));
        sort($validIds);
        $expected = $uniqueIds;
        sort($expected);
        if ($validIds !== $expected) {
            lineupJsonResponse(422, ['success' => false, 'error' => 'Escalação contém jogadores inválidos para este time.']);
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO team_lineups (team_id, pg_player_id, sg_player_id, sf_player_id, pf_player_id, c_player_id)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            pg_player_id = VALUES(pg_player_id),
            sg_player_id = VALUES(sg_player_id),
            sf_player_id = VALUES(sf_player_id),
            pf_player_id = VALUES(pf_player_id),
            c_player_id = VALUES(c_player_id),
            updated_at = CURRENT_TIMESTAMP'
    );

    $stmt->execute([
        $teamId,
        $normalized['pg'],
        $normalized['sg'],
        $normalized['sf'],
        $normalized['pf'],
        $normalized['c'],
    ]);

    lineupJsonResponse(200, [
        'success' => true,
        'message' => 'Escalação tática salva.',
        'team_id' => $teamId,
        'lineup' => $normalized,
    ]);
}

lineupJsonResponse(405, ['success' => false, 'error' => 'Método não permitido']);
