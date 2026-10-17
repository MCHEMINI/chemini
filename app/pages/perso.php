<?php
declare(strict_types=1);

function page_perso(array $u): never {
    $uid = (int)$u['id'];
    render('perso', [
        'title' => 'Mails à traiter', 'nav' => 'perso', 'u' => $u,
        'mails' => q('SELECT * FROM mail_items WHERE user_id=? AND (done=0 OR done_at > ?) ORDER BY done ASC, CASE priority WHEN \'crit\' THEN 0 WHEN \'warn\' THEN 1 ELSE 2 END, received_at DESC LIMIT 40', [$uid, time() - 7 * 86400])->fetchAll(),
        'mine' => q("SELECT * FROM items WHERE owner_id=? AND status='open' ORDER BY CASE severity WHEN 'crit' THEN 0 WHEN 'warn' THEN 1 ELSE 2 END", [$uid])->fetchAll(),
    ]);
}

function page_tasks(array $u): never {
    require_once APP_DIR . '/pages/team.php';
    $me = team_me($u);
    $all = $me ? team_load($u, $me) : [];
    $open = array_values(array_filter($all, fn($t) => !$t['done_at'])); $done = array_values(array_filter($all, fn($t) => $t['done_at'] && $t['title'] !== SURVEY_TASK));
    usort($done, fn($a, $b) => $b['done_at'] <=> $a['done_at']);
    $spaces = array_values(array_filter(array_keys(SPACES), fn($s) => can_space($u, $s)));
    $shared = $spaces ? q('SELECT t.*, u.name AS by_name FROM tasks t LEFT JOIN users u ON u.id=t.user_id WHERE t.space IN (' . implode(',', array_fill(0, count($spaces), '?')) . ') ORDER BY t.done, t.created_at DESC LIMIT 200', $spaces)->fetchAll() : [];
    render('perso_tasks', ['title' => 'Mes tâches', 'nav' => 'tasks', 'u' => $u, 'me' => $me, 'open' => $open, 'done' => $done, 'isDir' => can_space($u, 'direction'), 'spaces' => $spaces, 'shared' => $shared]);
}

function page_notes(array $u): never {
    render('perso_notes', [
        'title' => 'Bloc-notes', 'nav' => 'notes', 'u' => $u,
        'note' => q('SELECT * FROM notes WHERE user_id=?', [(int)$u['id']])->fetch() ?: null,
    ]);
}

/** Tableau de bord Perso : synthèse de toute la plateforme, adaptée au profil (chaque bloc est indépendant). */
function page_home(array $u): never {
    $uid = (int)$u['id']; $dir = can_space($u, 'direction'); $fam = can_space($u, 'famille'); $today = date('Y-m-d');
    $safe = function (callable $f, $def = null) { try { return $f(); } catch (Throwable $e) { error_log('home: ' . $e->getMessage()); return $def; } };
    $D = [];
    $D['mails'] = $safe(fn() => [
        'n' => (int)q('SELECT COUNT(*) FROM mail_items WHERE user_id=? AND done=0', [$uid])->fetchColumn(),
        'crit' => (int)q("SELECT COUNT(*) FROM mail_items WHERE user_id=? AND done=0 AND priority='crit'", [$uid])->fetchColumn(),
        'top' => q("SELECT subject, from_name, from_email, priority FROM mail_items WHERE user_id=? AND done=0 ORDER BY CASE priority WHEN 'crit' THEN 0 WHEN 'warn' THEN 1 ELSE 2 END, received_at DESC LIMIT 3", [$uid])->fetchAll(),
        'at' => (int)q('SELECT MAX(created_at) FROM mail_items WHERE user_id=?', [$uid])->fetchColumn(),
    ], ['n' => 0, 'crit' => 0, 'top' => [], 'at' => 0]);
    $D['tasks'] = $safe(function () use ($u, $today) {
        require_once APP_DIR . '/pages/team.php'; $me = team_me($u); if (!$me) return null;
        $open = array_values(array_filter(team_load($u, $me), fn($t) => !$t['done_at'] && $t['person'] === $me));
        usort($open, fn($a, $b) => [$a['due'] ?: '9999', $a['created_at']] <=> [$b['due'] ?: '9999', $b['created_at']]);
        return ['n' => count($open), 'late' => count(array_filter($open, fn($t) => $t['due'] && $t['due'] < $today)), 'today' => count(array_filter($open, fn($t) => $t['due'] === $today)), 'top' => array_slice($open, 0, 3)];
    });
    $D['ia'] = $safe(fn() => ['n' => (int)q("SELECT COUNT(*) FROM consignes WHERE user_id=? AND status<>'done'", [$uid])->fetchColumn(), 'done' => (int)q("SELECT COUNT(*) FROM consignes WHERE user_id=? AND status='done' AND updated_at>?", [$uid, strtotime(date('Y-m-01'))])->fetchColumn()], ['n' => 0, 'done' => 0]);
    $D['ops'] = $safe(fn() => ag_ops());
    $D['board'] = $safe(fn() => q('SELECT title, created_at FROM board_posts ORDER BY pinned DESC, created_at DESC LIMIT 2')->fetchAll(), []);
    $D['digest'] = $safe(fn() => json_decode((string)q("SELECT v FROM kv WHERE k='news_digest'")->fetchColumn(), true) ?: null);
    $D['pw'] = $safe(fn() => pw_latest_published());
    $D['pwAlert'] = pw_editor($u) ? $safe(fn() => pw_alert()) : null;
    if ($dir) {
        $D['over'] = $safe(fn() => dir_overview($u));
        $D['regs'] = $safe(function () {
            require_once APP_DIR . '/lib/pilot.php'; $n = ['crit' => 0, 'warn' => 0];
            foreach (array_keys(REGS) as $k) foreach (reg_items($k) as $it) { $a = reg_alert($k, $it['d'])[0]; if (isset($n[$a])) $n[$a]++; }
            return $n;
        });
        $D['sig'] = $safe(function () {
            $c = sig_current(); $h = json_decode((string)q("SELECT v FROM kv WHERE k='sig_hits'")->fetchColumn(), true) ?: [];
            return ['cur' => $c, 'linked' => !empty($h['ext']) && $h['ext'] > time() - 30 * 86400];
        });
        $D['survey'] = $safe(fn() => ['n' => (int)q('SELECT COUNT(*) FROM survey_answers WHERE submitted_at IS NOT NULL')->fetchColumn(), 'staff' => (int)q('SELECT COUNT(*) FROM staff WHERE archived_at IS NULL')->fetchColumn()]);
    }
    if ($fam) $D['trip'] = $safe(fn() => q("SELECT id, destination, duration FROM trips WHERE space='famille' ORDER BY updated_at DESC LIMIT 1")->fetch() ?: null);
    render('home', ['title' => 'Tableau de bord', 'nav' => 'home', 'u' => $u, 'D' => $D, 'dir' => $dir, 'fam' => $fam]);
}
