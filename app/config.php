<?php
/*
 * Configuration de la plateforme Dashboard.
 *
 * Les secrets (clé de chiffrement, jeton d'activation, identifiants Microsoft) ne sont PAS
 * dans ce dépôt. Sur le serveur, ils sont lus dans un fichier privé situé HORS du dossier
 * publié : <dossier chemini>/app/config.php (un niveau au-dessus de chemini/site).
 * Ce fichier définit aussi DATA_DIR = <dossier chemini>/data, hors web et conservé
 * d'un déploiement à l'autre.
 */
$privateConfig = dirname(__DIR__, 2) . '/app/config.php';
if (is_file($privateConfig) && realpath($privateConfig) !== realpath(__FILE__)) {
    require $privateConfig;
}

$set = static function (string $name, $value): void { if (!defined($name)) define($name, $value); };

// Valeurs par défaut (développement local) — remplacées par le fichier privé en production.
$set('APP_NAME', 'GID Dashboard');
$set('BASE_URL', getenv('CHEMINI_BASE_URL') ?: 'https://chemini.gidsas.fr');
// Adresses publiques de la plateforme : dashboard.gidsas.fr (communiquée aux équipes) et chemini.gidsas.fr pointent au même endroit
$set('APP_HOSTS', ['dashboard.gidsas.fr', 'chemini.gidsas.fr']);
$set('ASSET_CSS_V', 76); // à garder égal au ?v= de app.css dans les vues
$set('ASSET_ANN_V', 2);
$set('DATA_DIR', dirname(__DIR__) . '/data');
$set('APP_KEY', getenv('CHEMINI_APP_KEY') ?: '');
$set('ADMIN_EMAIL', 'mchemini@gidsas.fr');
$set('ADMIN_NAME', 'M. Chemini');
$set('ADMIN_INITIALS', 'MC');
$set('SETUP_TOKEN', getenv('CHEMINI_SETUP_TOKEN') ?: '');
$set('TRUST_DAYS', 90);
$set('GRAPH_TENANT', 'organizations');
$set('GRAPH_CLIENT_ID', '');
$set('GRAPH_CLIENT_SECRET', '');
$set('MAIL_FROM', 'noreply@gidsas.fr');
$set('SMTP_HOST', '');
$set('SMTP_PORT', 587);
$set('SMTP_USER', '');
$set('SMTP_PASS', '');
$set('FEED_TTL', 3600);
$set('LEGAL_FEEDS', [
    'https://news.google.com/rss/search?q=copropri%C3%A9t%C3%A9+(loi+OR+d%C3%A9cret+OR+arr%C3%AAt%C3%A9+OR+%22Cour+de+cassation%22+OR+%22Journal+officiel%22)+when:30d&hl=fr&gl=FR&ceid=FR:fr',
]);
$set('NEWS_FEEDS', [
    'https://news.google.com/rss/search?q=(syndic+OR+copropri%C3%A9t%C3%A9+OR+copropri%C3%A9taires)+when:14d&hl=fr&gl=FR&ceid=FR:fr',
]);

if (APP_KEY === '' || strlen(APP_KEY) < 32) {
    http_response_code(503);
    exit('Configuration privée manquante.');
}
unset($privateConfig, $set);
