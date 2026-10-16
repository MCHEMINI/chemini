<?php
declare(strict_types=1);

function page_dashboard(array $u): never {
    $uid = (int)$u['id'];
    $items = q("SELECT i.*, u.name AS owner_name, u.initials AS owner_initials, u.id AS oid FROM items i LEFT JOIN users u ON u.id=i.owner_id
               WHERE i.status='open' ORDER BY CASE i.severity WHEN 'crit' THEN 0 WHEN 'warn' THEN 1 ELSE 2 END, i.created_at DESC LIMIT 12")->fetchAll();
    $today = date('Y-m-d');
    $deadlines = q('SELECT * FROM deadlines WHERE done=0 AND due_date>=? ORDER BY due_date ASC LIMIT 8', [$today])->fetchAll();
    $overdue = q('SELECT * FROM deadlines WHERE done=0 AND due_date<? ORDER BY due_date ASC', [$today])->fetchAll();
    $week = date('Y-m-d', strtotime('+7 days'));

    $mails = null; $mailsCount = null;
    $auto = q('SELECT * FROM mail_items WHERE user_id=? AND done=0 ORDER BY CASE priority WHEN \'crit\' THEN 0 WHEN \'warn\' THEN 1 ELSE 2 END, received_at DESC LIMIT 6', [$uid])->fetchAll();
    if ($auto || q('SELECT 1 FROM mail_items WHERE user_id=?', [$uid])->fetchColumn()) {
        $mails = array_map(fn($m) => ['from' => $m['from_name'] ?: $m['from_email'], 'subject' => $m['subject'], 'preview' => $m['reason'] ?: $m['summary'], 'date' => (int)$m['received_at'], 'link' => $m['link'], 'level' => $m['priority'] === 'crit' ? 'crit' : 'warn'], $auto);
        $mailsCount = (int)q('SELECT COUNT(*) FROM mail_items WHERE user_id=? AND done=0', [$uid])->fetchColumn();
    } elseif (graph_enabled() && graph_connected($uid)) {
        $mails = graph_important_mails($uid) ?? [];
        $mailsCount = $_SESSION['mails_' . $uid]['n'] ?? count($mails);
    }

    render('dashboard', [
        'title' => 'Tableau de bord', 'nav' => 'dash', 'u' => $u,
        'items' => $items, 'deadlines' => $deadlines, 'overdue' => $overdue,
        'mails' => $mails, 'mailsCount' => $mailsCount,
        'legal' => feeds_merged(LEGAL_FEEDS, 4), 'news' => feeds_merged(NEWS_FEEDS, 6),
        'stats' => [
            'crit' => count(array_filter($items, fn($i) => $i['severity'] === 'crit')),
            'open' => (int)q("SELECT COUNT(*) FROM items WHERE status='open'")->fetchColumn(),
            'week' => count(array_filter($deadlines, fn($d) => $d['due_date'] <= $week)) + count($overdue),
        ],
        'users' => users_all(),
    ]);
}
