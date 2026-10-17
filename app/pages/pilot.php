<?php
declare(strict_types=1);
require_once APP_DIR . '/lib/pilot.php';
require_once APP_DIR . '/pages/dashboard.php';

function pilot_guard(array $u): void { if (!can_space($u, 'direction')) { http_response_code(403); render('notfound', ['title' => 'Accès réservé', 'message' => 'Réservé à la Direction.'], false); } }

/** Mandats arrivant à échéance + proposition d'honoraires. */
function pilot_mandats(): array {
    $B = pilot_blds(); $today = date('Y-m-d'); $lim = date('Y-m-d', strtotime('+6 months'));
    $vals = []; foreach ($B as $b) if ($b['lots'] && $b['v']) $vals[] = $b['v'] / $b['lots']; sort($vals);
    $med = $vals ? $vals[intdiv(count($vals), 2)] : 0; $out = [];
    foreach (ag_rows()['rows'] as $r) {
        $fm = (string)($r['fin_mandat'] ?? ''); if (!$fm || $fm > $lim) continue;
        $b = $B[$r['code']] ?? null; if (!$b) continue; $r['code'] = (string)$r['code'];
        $f = bld_finance($r['code']);
        $target = $f ? (COST_FIXED + COST_LOT * $f['lots'] + COST_HOUR * $f['hours']) / (1 - MARGIN_GREEN) * 1.2 : 0;
        $prop = max($b['v'] * 1.03, $b['lots'] * $med * 0.85, min($target, $b['v'] * 1.15));
        $prop = ceil($prop / 10) * 10;
        $why = [];
        if ($b['lots'] && $b['v'] / $b['lots'] < $med * 0.85) $why[] = 'prix au lot ' . number_format($b['v'] / $b['lots'], 0, ',', ' ') . ' € contre ' . number_format($med, 0, ',', ' ') . ' € de médiane cabinet';
        if ($f && $f['color'] === 'red') $why[] = 'immeuble non rentable (marge ' . round($f['margin'] * 100) . ' %)';
        if (!$why) $why[] = 'indexation de 3 %';
        $ag = (string)($r['ag_date'] ?? '');
        $out[] = ['code' => $r['code'], 'imm' => $b['imm'], 'gest' => trim(preg_replace('/^(M\.|Mme)\s*/u', '', (string)($r['gest'] ?? ''))), 'fin' => $fm, 'ag' => $ag >= $today ? $ag : '',
                  'expired' => $fm < $today, 'v' => $b['v'], 'prop' => $prop, 'lots' => $b['lots'], 'why' => implode(' ; ', $why), 'color' => $f['color'] ?? null];
    }
    usort($out, fn($a, $b) => strcmp($a['fin'], $b['fin']));
    return ['rows' => $out, 'med' => $med];
}

/** Indicateurs par gestionnaire et charge par collaborateur. */
function pilot_equipes(): array {
    $B = pilot_blds(); $ops = ag_ops(); $today = date('Y-m-d'); $in90 = date('Y-m-d', strtotime('+90 days'));
    $agBy = []; foreach (ag_rows()['rows'] as $r) if (($r['ag_date'] ?? '') >= $today && $r['ag_date'] <= $in90) $agBy[$r['code']] = true;
    $G = []; $P = [];
    foreach ($B as $code => $b) {
        $code = (string)$code; $f = bld_finance($code);
        foreach (['gest' => 'Gestionnaire', 'compta' => 'Comptable', 'assist' => 'Assistante'] as $k => $role) {
            $n = trim((string)($b[$k] ?? '')); if ($n === '' || $n === '(aucun)') continue;
            $p = &$P[$n]; $p = ($p ?? []) + ['name' => $n, 'role' => $role, 'imm' => 0, 'lots' => 0, 'ag90' => 0];
            $p['imm']++; $p['lots'] += $b['lots']; if (isset($agBy[$code])) $p['ag90']++; unset($p);
        }
        $g = trim((string)($b['gest'] ?? '')); if ($g === '') continue;
        $x = &$G[$g]; $x = ($x ?? []) + ['name' => $g, 'imm' => 0, 'lots' => 0, 'v' => 0, 'p' => 0, 'held' => 0, 'green' => 0, 'orange' => 0, 'red' => 0, 'nopv' => 0, 'convoc' => 0];
        $x['imm']++; $x['lots'] += $b['lots']; $x['v'] += $b['v']; $x['p'] += $b['p']; if ($b['dateAG']) $x['held']++;
        if ($f && isset($x[$f['color']])) $x[$f['color']]++;
        unset($x);
    }
    foreach ($ops['nopv'] as $b) { $code = str_pad((string)$b['num'], 5, '0', STR_PAD_LEFT); $g = trim((string)($B[$code]['gest'] ?? '')); if (isset($G[$g])) $G[$g]['nopv']++; }
    foreach ($ops['convoc'] as $r) { $g = trim((string)($B[$r['code']]['gest'] ?? '')); if (isset($G[$g])) $G[$g]['convoc']++; }
    $T = [];
    foreach (q('SELECT person, COUNT(*) n, SUM(CASE WHEN due IS NOT NULL AND due<? THEN 1 ELSE 0 END) late FROM team_tasks WHERE done_at IS NULL AND priv=0 GROUP BY person', [$today])->fetchAll() as $r) $T[$r['person']] = $r;
    foreach ($P as &$p) { $t = $T[team_slug($p['name'])] ?? null; $p['tasks'] = (int)($t['n'] ?? 0); $p['late'] = (int)($t['late'] ?? 0); } unset($p);
    uasort($G, fn($a, $b) => $b['v'] <=> $a['v']); uasort($P, fn($a, $b) => [$a['role'], -$a['lots']] <=> [$b['role'], -$b['lots']]);
    return ['G' => $G, 'P' => $P];
}

/** Prévision d'encaissement mensuelle sur l'exercice (1er avril – 31 mars). */
function pilot_cash(): array {
    $B = pilot_blds(); $y = (int)date('Y'); $s = (int)date('n') >= 4 ? $y : $y - 1;
    $hono = array_sum(array_column($B, 'v'));
    $an = annexes_data(); $ann = 0; $annDone = 0; $months = 12; $key = $s . '-' . ($s + 1);
    if ($an && isset($an['years'][$key])) {
        $Y = $an['years'][$key]; foreach (($Y['b'] ?? []) as $vals) foreach ((array)$vals as $v) $annDone += (float)$v;
        if (!empty($Y['end']) && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string)$Y['end'], $m)) $months = max(0.5, ((int)$m[1] - $s) * 12 + ((int)$m[2] - 4) + (int)$m[3] / 30);
        $ann = $annDone / $months * 12;
    }
    $rows = []; $cum = 0;
    for ($i = 0; $i < 12; $i++) {
        $ts = mktime(0, 0, 0, 4 + $i, 1, $s); $h = $hono / 12; $a = $ann / 12; $cum += $h + $a;
        $rows[] = ['m' => $ts, 'h' => $h, 'a' => $a, 'cum' => $cum, 'past' => date('Y-m', $ts) < date('Y-m')];
    }
    return ['rows' => $rows, 'hono' => $hono, 'ann' => $ann, 'annDone' => $annDone, 'ex' => $key];
}

function page_pilot(array $u, string $sub = '', string $arg = ''): never {
    pilot_guard($u);
    if (isset(REGS[$sub])) {
        if ($sub === 'travaux') reg_sync_travaux();
        $items = reg_items($sub); foreach ($items as &$it) $it['al'] = reg_alert($sub, $it['d']); unset($it);
        usort($items, fn($a, $b) => (['crit' => 0, 'warn' => 1, '' => 2, 'ok' => 3][$a['al'][0]] ?? 2) <=> (['crit' => 0, 'warn' => 1, '' => 2, 'ok' => 3][$b['al'][0]] ?? 2));
        render('pilot_reg', ['title' => REGS[$sub][0], 'nav' => 'pilot', 'u' => $u, 'wide' => true, 'reg' => $sub, 'R' => REGS[$sub], 'items' => $items, 'B' => pilot_blds(), 'people' => team_people()]);
    }
    if ($sub === 'mandats') render('pilot_mandats', ['title' => 'Renouvellements de mandat', 'nav' => 'pilot', 'u' => $u, 'wide' => true] + pilot_mandats());
    if ($sub === 'equipes') render('pilot_equipes', ['title' => 'Gestionnaires et charge', 'nav' => 'pilot', 'u' => $u, 'wide' => true] + pilot_equipes());
    if ($sub === 'encaissements') render('pilot_cash', ['title' => 'Prévision d\'encaissement', 'nav' => 'pilot', 'u' => $u] + pilot_cash());
    if ($sub === 'ag' && $arg !== '') {
        $B = pilot_blds(); if (!isset($B[$arg])) { http_response_code(404); render('notfound', ['title' => 'Introuvable']); }
        $r = null; foreach (ag_rows()['rows'] as $x) if ($x['code'] === $arg) $r = $x;
        reg_sync_travaux();
        $tr = array_values(array_filter(reg_items('travaux'), fn($x) => $x['code'] === $arg));
        $mand = array_values(array_filter(pilot_mandats()['rows'], fn($x) => $x['code'] === $arg));
        $info = q('SELECT * FROM building_info WHERE code=?', [$arg])->fetch() ?: [];
        $regs = []; foreach (['contrats', 'echeances', 'sinistres', 'impayes'] as $k) $regs[$k] = array_values(array_filter(reg_items($k), fn($x) => $x['code'] === $arg));
        render('pilot_agfiche', ['title' => 'Préparation d\'AG', 'nav' => 'pilot', 'u' => $u, 'code' => $arg, 'b' => $B[$arg], 'r' => $r, 'f' => bld_finance($arg), 'tr' => $tr, 'mand' => $mand[0] ?? null, 'info' => $info, 'regs' => $regs]);
    }
    if ($sub === 'ag') {
        $today = date('Y-m-d'); $in = date('Y-m-d', strtotime('+75 days')); $B = pilot_blds();
        $rows = array_values(array_filter(ag_rows()['rows'], fn($r) => ($r['ag_date'] ?? '') >= $today && $r['ag_date'] <= $in && isset($B[$r['code']])));
        usort($rows, fn($a, $b) => strcmp($a['ag_date'], $b['ag_date']));
        render('pilot_ag', ['title' => 'Préparation des AG', 'nav' => 'pilot', 'u' => $u, 'rows' => $rows, 'B' => $B, 'ical' => ical_url($u)]);
    }
    // Tableau de bord du pilotage
    $cards = [];
    reg_sync_travaux();
    foreach (REGS as $k => $R) { $it = reg_items($k); $c = ['crit' => 0, 'warn' => 0]; foreach ($it as $x) { $a = reg_alert($k, $x['d'])[0]; if (isset($c[$a])) $c[$a]++; } $cards[$k] = ['n' => count($it)] + $c; }
    $m = pilot_mandats(); $ops = ag_ops(); $cash = pilot_cash();
    render('pilot_hub', ['title' => 'Pilotage', 'nav' => 'pilot', 'u' => $u, 'cards' => $cards, 'mand' => $m['rows'], 'ops' => $ops, 'cash' => $cash]);
}

function ical_url(array $u): string {
    $t = (string)(user_prefs((int)$u['id'])['ical'] ?? '');
    if ($t === '') { $t = bin2hex(random_bytes(16)); q('INSERT INTO user_prefs(user_id,k,v,updated_at) VALUES(?,?,?,?) ON CONFLICT(user_id,k) DO UPDATE SET v=excluded.v', [(int)$u['id'], 'ical', json_encode($t), time()]); }
    return BASE_URL . '/calendrier/' . $t . '.ics';
}

/** Flux iCal des AG (abonnement agenda iPhone) : jeton personnel, Direction uniquement. */
function ical_feed(string $tok): never {
    $uid = 0;
    foreach (q("SELECT user_id, v FROM user_prefs WHERE k='ical'")->fetchAll() as $r) if (hash_equals((string)json_decode($r['v'], true), $tok)) $uid = (int)$r['user_id'];
    $u = $uid ? q('SELECT * FROM users WHERE id=?', [$uid])->fetch() : null;
    if (!$u || !can_space($u, 'direction')) { http_response_code(404); exit; }
    $B = pilot_blds(); $esc = fn($s) => str_replace(["\\", ';', ',', "\n"], ["\\\\", '\;', '\\,', '\\n'], (string)$s);
    $out = ["BEGIN:VCALENDAR", "VERSION:2.0", "PRODID:-//GID//Chemini//FR", "CALSCALE:GREGORIAN", "X-WR-CALNAME:AG GID", "X-WR-TIMEZONE:Europe/Paris"];
    foreach (ag_rows()['rows'] as $r) {
        $d = (string)($r['ag_date'] ?? ''); if (!$d || $d < date('Y-m-d', strtotime('-30 days'))) continue;
        $h = preg_match('/(\d{1,2})[:h](\d{2})/', (string)($r['ag_heure'] ?? ''), $m) ? sprintf('%02d%02d00', $m[1], $m[2]) : '180000';
        $start = str_replace('-', '', $d) . 'T' . $h; $end = date('Ymd\THis', strtotime($d . ' ' . substr($h, 0, 2) . ':' . substr($h, 2, 2) . ' +2 hours'));
        $out[] = "BEGIN:VEVENT"; $out[] = 'UID:ag-' . $r['code'] . '-' . $d . '@chemini.gidsas.fr'; $out[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
        $out[] = 'DTSTART;TZID=Europe/Paris:' . $start; $out[] = 'DTEND;TZID=Europe/Paris:' . $end;
        $out[] = 'SUMMARY:' . $esc('AG ' . $r['code'] . ' ' . ($B[$r['code']]['imm'] ?? ($r['nom'] ?? '')));
        $out[] = 'DESCRIPTION:' . $esc('Gestionnaire : ' . trim(preg_replace('/^(M\.|Mme)\s*/u', '', (string)($r['gest'] ?? ''))) . (empty($r['convoc']) ? ' · convocation à envoyer' : ''));
        $out[] = 'URL:' . BASE_URL . '/pilotage/ag/' . $r['code']; $out[] = "END:VEVENT";
    }
    $out[] = "END:VCALENDAR";
    header('Content-Type: text/calendar; charset=utf-8'); header('Cache-Control: no-store');
    echo implode("\r\n", $out) . "\r\n"; exit;
}
