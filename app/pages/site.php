<?php
declare(strict_types=1);

/* Site Web (Direction) : statistiques, annonces, pages et SEO du site WordPress, connexion. */
function page_site(array $u, string $sub = ''): never {
    if (!wp_viewer($u)) { http_response_code(403); render('notfound', ['title' => 'Accès réservé', 'message' => 'Réservé à la Direction.']); }
    $views = ['' => ['site', 'Site Web · statistiques', 'site-stats'], 'annonces' => ['site_annonces', 'Site Web · annonces', 'site-ann'], 'pages' => ['site_pages', 'Site Web · pages et SEO', 'site-pages'], 'connexion' => ['site_conn', 'Site Web · connexion', 'site-conn']];
    if (!isset($views[$sub])) { http_response_code(404); render('notfound', ['title' => 'Introuvable']); }
    [$view, $title, $nav] = $views[$sub];
    $conn = wp_conn();
    $snap = ($conn && $sub !== 'connexion') ? wp_snapshot(isset($_GET['actualiser'])) : null;
    render($view, ['title' => $title, 'nav' => $nav, 'u' => $u, 'wide' => true, 'conn' => $conn, 'snap' => $snap, 'admin' => wp_admin($u)]);
}
