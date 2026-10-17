<?php
declare(strict_types=1);
require_once APP_DIR . '/lib/versions.php';

/* Ancienne adresse de l'historique : renvoie vers le Versioning filtré sur les Tâches IA. */
function page_assistant_history(array $u): never { header('Location: /versioning?f=ia', true, 302); exit; }

/* Tableau des Tâches IA : En attente / En cours / Fait (mois en cours), 10 lignes visibles par bloc. */
function page_assistant(array $u): never {
    $uid = (int)$u['id']; $m0 = strtotime(date('Y-m-01 00:00:00'));
    render('assistant_board', [
        'title' => 'Tâches IA', 'nav' => 'ia', 'u' => $u,
        'ia_todo' => q("SELECT * FROM consignes WHERE user_id=? AND status='todo' ORDER BY CASE priority WHEN 'haute' THEN 0 WHEN 'normale' THEN 1 ELSE 2 END, created_at", [$uid])->fetchAll(),
        'ia_doing' => q("SELECT * FROM consignes WHERE user_id=? AND status='doing' ORDER BY updated_at DESC", [$uid])->fetchAll(),
        'ia_done' => q("SELECT * FROM consignes WHERE user_id=? AND status='done' AND COALESCE(done_at, updated_at)>=CAST(? AS INTEGER) ORDER BY COALESCE(done_at, updated_at) DESC", [$uid, $m0])->fetchAll(),
        'ia_done_all' => (int)q("SELECT COUNT(*) FROM consignes WHERE user_id=? AND status='done'", [$uid])->fetchColumn(),
    ]);
}

/* Versioning : tout ce qui a été fait sur la plateforme, selon les espaces de chacun.
   Perso = soi seul ; Famille, Direction = membres de l'espace ; Équipe = tous. */
function page_versioning(array $u): never {
    $uid = (int)$u['id']; $dir = can_space($u, 'direction'); $fam = can_space($u, 'famille');
    $ev = [];
    $sp = ["c.user_id=$uid", "c.space='gid'"]; if ($fam) $sp[] = "c.space='famille'"; if ($dir) $sp[] = "c.space='direction'";
    $rows = q("SELECT c.*, us.name AS owner FROM consignes c LEFT JOIN users us ON us.id=c.user_id
               WHERE c.status='done' AND (c.space<>'perso' OR c.user_id=?) AND (" . implode(' OR ', $sp) . ") ORDER BY COALESCE(c.done_at, c.updated_at) DESC", [$uid])->fetchAll();
    foreach ($rows as $c) $ev[] = ['at' => (int)($c['done_at'] ?: $c['updated_at']), 'kind' => 'ia', 'space' => $c['space'] ?? 'perso', 'c' => $c,
        'who' => (int)$c['user_id'] === $uid ? '' : (string)$c['owner']];
    foreach (VERSIONS as [$d, $s, $t, $x]) {
        if (($s === 'direction' && !$dir) || ($s === 'famille' && !$fam)) continue;
        $ev[] = ['at' => strtotime($d), 'kind' => 'evo', 'space' => $s, 't' => $t, 'x' => $x];
    }
    if ($dir) {
        if (function_exists('hono_backfill')) { hono_backfill(); hono_daily(); }
        $hc = q("SELECT date(at,'unixepoch','localtime') AS d, COUNT(*) AS n, MAX(at) AS at, SUM(source='auto') AS auto FROM hono_changes WHERE source<>'evolution' GROUP BY d")->fetchAll();
        foreach ($hc as $h) $ev[] = ['at' => (int)$h['at'], 'kind' => 'hono', 'space' => 'direction', 'n' => (int)$h['n'], 'auto' => (int)$h['auto']];
    }
    usort($ev, fn($a, $b) => $b['at'] <=> $a['at']);
    $f = in_array($_GET['f'] ?? '', ['ia', 'evo', 'hono'], true) ? $_GET['f'] : '';
    $count = ['' => count($ev)] + array_count_values(array_column($ev, 'kind'));
    if ($f) $ev = array_values(array_filter($ev, fn($e) => $e['kind'] === $f));
    render('versioning', ['title' => 'Versioning', 'nav' => 'versioning', 'u' => $u, 'ev' => $ev, 'f' => $f, 'count' => $count, 'dir' => $dir]);
}
