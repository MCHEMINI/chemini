<?php
declare(strict_types=1);

/*
 * Tâches d'équipe : un bloc par collaborateur, regroupés par service.
 * Visibilité : la Direction voit tout ; un collaborateur voit les tâches qui lui sont affectées
 * et celles qu'il a lui-même affectées, jamais celles qu'un membre de la Direction a affectées à d'autres.
 */

const TEAM_SERVICES = ['Direction' => 'Direction', 'Gestionnaire' => 'Gestion', 'Comptable' => 'Comptabilité', 'Assistant(e)' => 'Assistanat'];

function team_slug(string $name): string {
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
    return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-');
}

/** Personnes : membres de la Direction (comptes de la plateforme) + annuaire ImmOpen des collaborateurs. */
function team_people(): array {
    static $cache = null; if ($cache !== null) return $cache;
    $out = [];
    foreach (space_members('direction') as $m) $out[team_slug($m['name'])] = ['key' => team_slug($m['name']), 'name' => $m['name'], 'role' => 'Direction', 'email' => strtolower((string)$m['email']), 'uid' => (int)$m['id']];
    foreach (q('SELECT name, role, email FROM staff ORDER BY name')->fetchAll() as $s) {
        $k = team_slug($s['name']); if (isset($out[$k])) continue;
        $out[$k] = ['key' => $k, 'name' => $s['name'], 'role' => $s['role'] ?: 'Assistant(e)', 'email' => strtolower((string)$s['email']), 'uid' => 0];
    }
    $users = q('SELECT id, email FROM users')->fetchAll();
    foreach ($out as &$p) if (!$p['uid'] && $p['email']) foreach ($users as $x) if (strtolower($x['email']) === $p['email']) $p['uid'] = (int)$x['id'];
    unset($p);
    return $cache = $out;
}

/** Clé de la personne correspondant au compte connecté (ou null s'il n'est pas dans l'annuaire). */
function team_me(array $u): ?string {
    foreach (team_people() as $k => $p) if ($p['uid'] === (int)$u['id'] || ($p['email'] && $p['email'] === strtolower((string)$u['email']))) return $k;
    return null;
}

/** Filtre SQL de visibilité pour l'utilisateur. */
function team_where(array $u): array {
    $me = team_me($u) ?? '-';
    if (can_space($u, 'direction')) return ['(t.priv=0 OR t.person=? OR t.created_by=?)', [$me, (int)$u['id']]];
    return ['(t.person=? OR t.created_by=?)', [$me, (int)$u['id']]];
}

function team_can_edit(array $u, array $t): bool {
    if ((int)($t['priv'] ?? 0)) return $t['person'] === team_me($u) || (int)$t['created_by'] === (int)$u['id'];
    return can_space($u, 'direction') || (int)$t['created_by'] === (int)$u['id'] || $t['person'] === team_me($u);
}
