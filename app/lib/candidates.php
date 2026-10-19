<?php
declare(strict_types=1);

/* Candidats : candidatures du formulaire « Recrutement » du site gidsas.fr, réservées à la Direction.
   Réception publique (/candidature) appelée par la page Recrutement une fois la candidature acceptée par le formulaire du site.
   Chaque fichier est contrôlé (type réel, contenu actif, macros, virus de test EICAR, ClamAV s'il est présent sur le serveur),
   puis chiffré sur le disque hors du site. Les fichiers refusés restent en quarantaine (jamais téléchargeables).
   Les candidats validés (étoile) forment la base de recrutement interrogeable. Conservation 2 ans au maximum (RGPD, comme annoncé sur le formulaire). */

const CAND_JOBS = [
    'gestionnaire-copro' => 'Gestionnaire de copropriété',
    'assistant-copro' => 'Assistant(e) de copropriété',
    'comptable' => 'Comptable',
    'gestionnaire-locatif' => 'Gestionnaire locatif',
    'negociateur' => 'Négociateur(trice) en transaction',
    'alternance' => 'Alternance / stage',
    'spontanee' => 'Candidature spontanée',
    'autre' => 'Autre poste',
];
const CAND_MAX_FILE = 5 * 1024 * 1024; // 5 Mo, comme indiqué sur le formulaire
const CAND_KEEP_DAYS = 730;            // 2 ans
const CAND_EXT = ['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
const CAND_FILE_KINDS = ['cv' => 'CV', 'lettre' => 'Lettre de motivation'];

function cand_viewer(array $u): bool { return can_space($u, 'direction'); }
function cand_dir(): string { $d = DATA_DIR . '/candidats'; if (!is_dir($d)) mkdir($d, 0700, true); return $d; }
function cand_job_key(string $label): string {
    $l = mb_strtolower(trim($label));
    foreach (CAND_JOBS as $k => $v) if (mb_strtolower($v) === $l) return $k;
    return 'autre';
}

/* ---------- Contrôle des fichiers ---------- */

/** Vérifie un fichier reçu : ['ok' => bool, 'reasons' => [...], 'engine' => 'contrôle intégré' | '… + ClamAV', 'ext' => …]. */
function cand_scan(string $bytes, string $name): array {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $r = []; $engine = 'contrôle intégré';
    if (!isset(CAND_EXT[$ext])) $r[] = 'type de fichier non autorisé (.' . $ext . ')';
    if (strlen($bytes) === 0) $r[] = 'fichier vide';
    if (strlen($bytes) > CAND_MAX_FILE) $r[] = 'fichier trop volumineux';
    // Fichier de test antivirus standard (EICAR) : doit toujours être détecté
    if (str_contains($bytes, 'EICAR-STANDARD-ANTIVIRUS-TEST-FILE')) $r[] = 'signature virale détectée (EICAR)';
    // Exécutables déguisés
    $head = substr($bytes, 0, 8);
    if (str_starts_with($head, 'MZ') || str_starts_with($head, "\x7FELF") || str_starts_with($head, "\xCA\xFE\xBA\xBE") || preg_match('/^\s*(<\?php|#!)/i', $head)) $r[] = 'programme exécutable déguisé';
    if (!$r) {
        if ($ext === 'pdf') $r = array_merge($r, cand_scan_pdf($bytes));
        elseif ($ext === 'docx') $r = array_merge($r, cand_scan_docx($bytes));
        elseif ($ext === 'doc') $r = array_merge($r, cand_scan_doc($bytes));
    }
    // ClamAV, s'il est installé sur le serveur
    $clam = cand_clamav($bytes);
    if ($clam !== null) { $engine .= ' + ClamAV'; if ($clam !== '') $r[] = 'virus détecté par ClamAV : ' . $clam; }
    return ['ok' => !$r, 'reasons' => array_values(array_unique($r)), 'engine' => $engine, 'ext' => $ext];
}

/** PDF : signature, puis recherche de contenu actif (JavaScript, lancement de programme, fichiers joints, Flash, formulaires XFA), y compris dans les flux compressés. */
function cand_scan_pdf(string $b): array {
    if (!str_contains(substr($b, 0, 1024), '%PDF-')) return ['le fichier n\'est pas un vrai PDF'];
    $all = $b;
    foreach (cand_pdf_objects($b) as $o) { $all .= "\n" . $o['d']; if ($o['s'] !== null && strlen($o['s']) < 20_000_000) $all .= "\n" . $o['s']; }
    // noms PDF écrits en hexadécimal (#4A#61…) pour échapper aux contrôles
    $all = preg_replace_callback('/\/([A-Za-z0-9#]+)/', fn($x) => '/' . preg_replace_callback('/#([0-9A-Fa-f]{2})/', fn($h) => chr(hexdec($h[1])), $x[1]), $all);
    $r = [];
    // motifs propres aux dictionnaires PDF (et non au texte du document, où « Node/JS » peut apparaître)
    if (preg_match('#/S\s*/JavaScript\b|/JS\s*(\(|<|\d+\s+\d+\s+R)|/JavaScript\s*(<<|\d+\s+\d+\s+R)#', $all)) $r[] = 'PDF contenant du JavaScript';
    if (preg_match('#/S\s*/Launch\b|/Launch\s*<<#', $all)) $r[] = 'PDF qui lance un programme';
    if (preg_match('#/Type\s*/EmbeddedFile\b|/EmbeddedFiles\s*(<<|\d+\s+\d+\s+R)#', $all)) $r[] = 'PDF contenant des fichiers cachés';
    if (preg_match('#/Subtype\s*/RichMedia\b|/Subtype\s*/Flash\b#', $all)) $r[] = 'PDF contenant du Flash';
    if (preg_match('#/XFA\s*(\[|<<|\d+\s+\d+\s+R)#', $all)) $r[] = 'PDF avec formulaire actif (XFA)';
    if (preg_match('#/S\s*/(SubmitForm|ImportData)\b#', $all)) $r[] = 'PDF qui envoie des données';
    return $r;
}

/** Word (.docx) : archive valide, sans macro, objet embarqué, ActiveX, modèle distant ni champ DDE ; protection contre les bombes zip. */
function cand_scan_docx(string $b): array {
    if (!str_starts_with($b, "PK\x03\x04")) return ['le fichier n\'est pas un vrai document Word'];
    $tmp = tempnam(sys_get_temp_dir(), 'cdx'); file_put_contents($tmp, $b);
    $z = new ZipArchive(); $r = [];
    try {
        if ($z->open($tmp) !== true) return ['document Word illisible'];
        if ($z->locateName('word/document.xml') === false) $r[] = 'le fichier n\'est pas un vrai document Word';
        $total = 0;
        for ($i = 0; $i < $z->numFiles; $i++) {
            $st = $z->statIndex($i); $n = strtolower((string)$st['name']); $total += (int)$st['size'];
            if (str_contains($n, 'vbaproject') || str_ends_with($n, '.bin') && str_contains($n, 'vba')) $r[] = 'document Word contenant des macros';
            elseif (str_contains($n, 'activex')) $r[] = 'document Word contenant des contrôles ActiveX';
            elseif (preg_match('#/embeddings/.*\.(bin|xlsm|docm|pptm|xlsb)$|oleobject#', $n)) $r[] = 'document Word contenant un objet embarqué';
            elseif (preg_match('/\.(exe|dll|js|vbs|ps1|bat|cmd|scr|hta|lnk|jar|msi)$/', $n)) $r[] = 'document Word contenant un programme';
            if ($st['size'] > 0 && $st['comp_size'] > 0 && $st['size'] / $st['comp_size'] > 200 && $st['size'] > 5_000_000) $r[] = 'archive anormale (bombe zip)';
        }
        if ($total > 60_000_000) $r[] = 'archive anormale (bombe zip)';
        $ct = (string)$z->getFromName('[Content_Types].xml');
        if (stripos($ct, 'macroEnabled') !== false || stripos($ct, 'vbaProject') !== false) $r[] = 'document Word contenant des macros';
        foreach (['word/_rels/settings.xml.rels', 'word/_rels/document.xml.rels'] as $rel) {
            $x = (string)$z->getFromName($rel);
            if (preg_match('/attachedTemplate[^>]*TargetMode="External"|TargetMode="External"[^>]*attachedTemplate/i', $x)) $r[] = 'document Word qui charge un modèle à distance';
            if (preg_match('/Type="[^"]*\/(oleObject|frame|subDocument)"[^>]*TargetMode="External"/i', $x)) $r[] = 'document Word qui charge un contenu à distance';
        }
        $doc = (string)$z->getFromName('word/document.xml');
        if (preg_match('/\b(DDEAUTO|DDE)\b/', $doc) && preg_match('/<w:instrText[^>]*>[^<]*\bDDE/i', $doc)) $r[] = 'document Word avec commande DDE';
        $z->close();
    } finally { @unlink($tmp); }
    return $r;
}

/** Word 97-2003 (.doc) : vrai fichier OLE, sans projet de macros ni objet embarqué. */
function cand_scan_doc(string $b): array {
    if (!str_starts_with($b, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) return ['le fichier n\'est pas un vrai document Word'];
    $r = []; $u16 = fn(string $s) => implode("\0", str_split($s)) . "\0";
    if (str_contains($b, $u16('_VBA_PROJECT'))) $r[] = 'document Word contenant des macros';
    if (str_contains($b, $u16('ObjectPool')) && str_contains($b, $u16("\x01Ole10Native"))) $r[] = 'document Word contenant un objet embarqué';
    return $r;
}

/** ClamAV si disponible : null = absent, '' = sain, sinon nom du virus. */
function cand_clamav(string $bytes): ?string {
    static $bin = false;
    if ($bin === false) {
        $bin = null;
        if (function_exists('proc_open') && !in_array('proc_open', array_map('trim', explode(',', (string)ini_get('disable_functions'))), true)) {
            foreach (['/usr/bin/clamdscan', '/usr/local/bin/clamdscan', '/usr/bin/clamscan', '/usr/local/bin/clamscan'] as $p) if (@is_executable($p)) { $bin = $p; break; }
        }
    }
    if (!$bin) return null;
    $tmp = tempnam(sys_get_temp_dir(), 'cav'); file_put_contents($tmp, $bytes); @chmod($tmp, 0644);
    try {
        $p = proc_open([$bin, '--no-summary', $tmp], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($p)) return null;
        $out = stream_get_contents($pipes[1]); fclose($pipes[1]); fclose($pipes[2]); $code = proc_close($p);
        if ($code === 0) return '';
        if ($code === 1 && preg_match('/:\s*(.+?)\s+FOUND/', (string)$out, $m)) return $m[1];
        return null; // erreur du moteur : on s'en tient au contrôle intégré
    } finally { @unlink($tmp); }
}

/* ---------- Texte des documents (recherche dans la base) ---------- */

function cand_text(string $bytes, string $ext): string {
    $t = '';
    try {
        if ($ext === 'docx') {
            $tmp = tempnam(sys_get_temp_dir(), 'ctx'); file_put_contents($tmp, $bytes); $z = new ZipArchive();
            if ($z->open($tmp) === true) { $x = (string)$z->getFromName('word/document.xml'); $z->close();
                $t = html_entity_decode(strip_tags(preg_replace(['#</w:p>#', '#<w:tab/>#', '#<w:br/>#'], ["\n", ' ', "\n"], $x)), ENT_QUOTES | ENT_XML1, 'UTF-8'); }
            @unlink($tmp);
        } elseif ($ext === 'pdf') $t = cand_pdf_text($bytes);
        elseif ($ext === 'doc') { // texte brut des documents Word 97 : suites de caractères lisibles (UTF-16 puis 8 bits)
            preg_match_all('/(?:[\x20-\x7E\xA0-\xFF]\x00){4,}/', $bytes, $m); $t = implode("\n", array_map(fn($s) => mb_convert_encoding($s, 'UTF-8', 'UTF-16LE'), $m[0]));
            if (mb_strlen($t) < 200) { preg_match_all('/[\x20-\x7E\xC0-\xFF]{5,}/', $bytes, $m); $t = mb_convert_encoding(implode("\n", $m[0]), 'UTF-8', 'Windows-1252'); }
        }
    } catch (Throwable) { $t = ''; }
    $t = preg_replace('/[ \t]+/u', ' ', (string)@iconv('UTF-8', 'UTF-8//IGNORE', $t));
    return mb_substr(trim(preg_replace('/\n\s*\n+/u', "\n", $t)), 0, 200000);
}

/** Flux PDF décodé selon ses filtres (Flate, ASCII85, ASCIIHex) ; null si filtre non géré (images…). */
function cand_pdf_decode(string $dict, string $raw): ?string {
    preg_match_all('#/(FlateDecode|Fl|ASCII85Decode|A85|ASCIIHexDecode|AHx|DCTDecode|JPXDecode|CCITTFaxDecode|JBIG2Decode|LZWDecode|RunLengthDecode)\b#', $dict, $m);
    foreach ($m[1] as $f) {
        if ($f === 'FlateDecode' || $f === 'Fl') { $u = @gzuncompress($raw); if ($u === false) $u = @gzinflate(substr($raw, 2)); if ($u === false) $u = @gzinflate($raw); if ($u === false) return null; $raw = $u; }
        elseif ($f === 'ASCII85Decode' || $f === 'A85') $raw = cand_a85($raw);
        elseif ($f === 'ASCIIHexDecode' || $f === 'AHx') $raw = (string)@hex2bin(preg_replace('/[^0-9A-Fa-f]/', '', rtrim($raw, "> \r\n")));
        else return null;
    }
    return $raw;
}
function cand_a85(string $s): string {
    $s = preg_replace('/\s+/', '', $s); if (str_starts_with($s, '<~')) $s = substr($s, 2); $p = strpos($s, '~>'); if ($p !== false) $s = substr($s, 0, $p);
    $o = ''; $t = [];
    for ($i = 0, $n = strlen($s); $i < $n; $i++) {
        if ($s[$i] === 'z' && !$t) { $o .= "\0\0\0\0"; continue; }
        $t[] = ord($s[$i]) - 33;
        if (count($t) === 5) { $v = 0; foreach ($t as $d) $v = $v * 85 + $d; $o .= pack('N', $v & 0xFFFFFFFF); $t = []; }
    }
    if ($t) { $k = count($t); while (count($t) < 5) $t[] = 84; $v = 0; foreach ($t as $d) $v = $v * 85 + $d; $o .= substr(pack('N', $v & 0xFFFFFFFF), 0, $k - 1); }
    return $o;
}
/** Objets d'un PDF (y compris ceux rangés dans des flux d'objets) : [n => ['d' => dictionnaire, 's' => flux décodé ou null]]. */
function cand_pdf_objects(string $b): array {
    $o = [];
    if (!preg_match_all('/(\d+)\s+\d+\s+obj\b(.*?)\bendobj/s', $b, $m, PREG_SET_ORDER)) return [];
    foreach (array_slice($m, 0, 5000) as $x) {
        $body = $x[2]; $sp = strpos($body, 'stream');
        if ($sp !== false && preg_match('/stream\r?\n/s', $body, $mm, PREG_OFFSET_CAPTURE)) {
            $dict = substr($body, 0, $mm[0][1]); $raw = substr($body, $mm[0][1] + strlen($mm[0][0]));
            $raw = preg_replace('/\r?\n?endstream\s*$/s', '', $raw);
            $o[(int)$x[1]] = ['d' => $dict, 's' => cand_pdf_decode($dict, $raw)];
        } else $o[(int)$x[1]] = ['d' => $body, 's' => null];
    }
    foreach ($o as $obj) { // flux d'objets (PDF 1.5+, exports Word)
        if ($obj['s'] === null || !preg_match('#/Type\s*/ObjStm#', $obj['d']) || !preg_match('#/First\s+(\d+)#', $obj['d'], $f)) continue;
        $hdr = preg_split('/\s+/', trim(substr($obj['s'], 0, (int)$f[1]))); $data = substr($obj['s'], (int)$f[1]);
        for ($i = 0; $i + 1 < count($hdr); $i += 2) { $off = (int)$hdr[$i + 1]; $nx = isset($hdr[$i + 3]) ? (int)$hdr[$i + 3] : strlen($data); if (!isset($o[(int)$hdr[$i]])) $o[(int)$hdr[$i]] = ['d' => substr($data, $off, $nx - $off), 's' => null]; }
    }
    return $o;
}
/** Table ToUnicode : [code hexadécimal => texte], largeur des codes (1 ou 2 octets). */
function cand_pdf_cmap(string $s): array {
    $map = []; $w = 1;
    if (preg_match_all('/beginbfchar(.*?)endbfchar/s', $s, $bc)) foreach ($bc[1] as $blk) if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]*)>/', $blk, $pp, PREG_SET_ORDER)) foreach ($pp as $p) { $map[strtoupper($p[1])] = cand_u16($p[2]); $w = max($w, intdiv(strlen($p[1]), 2)); }
    if (preg_match_all('/beginbfrange(.*?)endbfrange/s', $s, $br)) foreach ($br[1] as $blk) {
        if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*(<[0-9A-Fa-f]+>|\[[^\]]*\])/', $blk, $pp, PREG_SET_ORDER)) foreach ($pp as $p) {
            $a = hexdec($p[1]); $z = min(hexdec($p[2]), $a + 4000); $len = strlen($p[1]); $w = max($w, intdiv($len, 2));
            if ($p[3][0] === '[') { preg_match_all('/<([0-9A-Fa-f]*)>/', $p[3], $arr); foreach ($arr[1] as $k => $h) $map[strtoupper(str_pad(dechex($a + $k), $len, '0', STR_PAD_LEFT))] = cand_u16($h); continue; }
            $d = trim($p[3], '<>'); $base = hexdec(substr($d, -4)); $pre = strlen($d) > 4 ? cand_u16(substr($d, 0, -4)) : '';
            for ($i = $a; $i <= $z; $i++) $map[strtoupper(str_pad(dechex($i), $len, '0', STR_PAD_LEFT))] = $pre . mb_chr($base + $i - $a);
        }
    }
    return [$map, $w];
}

/** Texte d'un PDF sans outil externe : objets et flux décodés, polices et tables ToUnicode, opérateurs de texte. */
function cand_pdf_text(string $b): string {
    $obj = cand_pdf_objects($b); if (!$obj) return '';
    $fontObj = []; // nom de ressource (/F1) => n° d'objet de police
    foreach ($obj as $x) {
        $d = $x['d'];
        if (preg_match_all('#/Font\s*<<(.*?)>>#s', $d, $fm)) foreach ($fm[1] as $blk) if (preg_match_all('#/([^\s/<>\[\]()]+)\s+(\d+)\s+\d+\s+R#', $blk, $pp, PREG_SET_ORDER)) foreach ($pp as $p) $fontObj[$p[1]] ??= (int)$p[2];
        if (preg_match_all('#/Font\s+(\d+)\s+\d+\s+R#', $d, $fr)) foreach ($fr[1] as $n) if (isset($obj[(int)$n]) && preg_match_all('#/([^\s/<>\[\]()]+)\s+(\d+)\s+\d+\s+R#', $obj[(int)$n]['d'], $pp, PREG_SET_ORDER)) foreach ($pp as $p) $fontObj[$p[1]] ??= (int)$p[2];
    }
    $fonts = [];
    foreach ($fontObj as $name => $n) {
        $fd = $obj[$n]['d'] ?? ''; [$map, $w] = [[], 1];
        if (preg_match('#/ToUnicode\s+(\d+)\s+\d+\s+R#', $fd, $tu) && ($cs = $obj[(int)$tu[1]]['s'] ?? null)) [$map, $w] = cand_pdf_cmap($cs);
        elseif (preg_match('#/Subtype\s*/Type0#', $fd)) $w = 2;
        $fonts[$name] = ['map' => $map, 'w' => $w];
    }
    $out = '';
    foreach ($obj as $x) {
        $s = $x['s']; if ($s === null || !str_contains($s, 'BT') || str_contains($s, 'begincmap')) continue;
        $cur = ['map' => [], 'w' => 1];
        preg_match_all('#/([^\s/<>\[\]()]+)\s+[\d.]+\s+Tf|\[((?:[^\]\\\\]|\\\\.)*)\]\s*TJ|\(((?:[^)\\\\]|\\\\.)*)\)\s*(?:Tj|\'|")|<([0-9A-Fa-f\s]*)>\s*Tj|\b(T\*|Td|TD|Tm|ET)\b#s', $s, $ops, PREG_SET_ORDER);
        foreach ($ops as $o) {
            if (($o[1] ?? '') !== '') { $cur = $fonts[$o[1]] ?? ['map' => [], 'w' => 1]; continue; }
            if (($o[5] ?? '') !== '') { $out .= $o[5] === 'ET' ? "\n" : ' '; continue; }
            if (($o[4] ?? '') !== '') { $out .= cand_pdf_codes((string)@hex2bin(preg_replace('/\s+/', '', $o[4]) . (strlen(preg_replace('/\s+/', '', $o[4])) % 2 ? '0' : '')), $cur); continue; }
            if (($o[3] ?? '') !== '') { $out .= cand_pdf_codes(cand_pdf_lit($o[3]), $cur); continue; }
            if (($o[2] ?? '') !== '') {
                preg_match_all('/\(((?:[^)\\\\]|\\\\.)*)\)|<([0-9A-Fa-f\s]*)>|(-?\d+(?:\.\d+)?)/', $o[2], $parts, PREG_SET_ORDER);
                foreach ($parts as $p) {
                    if (($p[1] ?? '') !== '') $out .= cand_pdf_codes(cand_pdf_lit($p[1]), $cur);
                    elseif (($p[2] ?? '') !== '') $out .= cand_pdf_codes((string)@hex2bin(preg_replace('/\s+/', '', $p[2])), $cur);
                    elseif (isset($p[3]) && (float)$p[3] < -180) $out .= ' ';
                }
            }
        }
    }
    return $out;
}
/** Octets d'une chaîne PDF → texte, selon la police courante. */
function cand_pdf_codes(string $bytes, array $font): string {
    if ($font['map']) {
        $w = $font['w']; $o = '';
        for ($i = 0; $i + $w <= strlen($bytes); $i += $w) { $k = strtoupper(bin2hex(substr($bytes, $i, $w))); $o .= $font['map'][$k] ?? ''; }
        return $o;
    }
    if ($font['w'] === 2) return ''; // police composite sans table : illisible sans OCR
    return (string)@mb_convert_encoding($bytes, 'UTF-8', 'Windows-1252');
}
function cand_u16(string $hex): string { $s = @hex2bin(strlen($hex) % 2 ? '0' . $hex : $hex); return $s === false ? '' : (string)@mb_convert_encoding($s, 'UTF-8', 'UTF-16BE'); }
function cand_pdf_lit(string $s): string {
    $s = preg_replace_callback('/\\\\([0-7]{1,3}|.)/s', function ($m) { $c = $m[1]; if (ctype_digit($c)) return chr(octdec($c) & 255); return ['n' => "\n", 'r' => '', 't' => ' ', 'b' => '', 'f' => ''][$c] ?? $c; }, $s);
    return $s; // octets bruts : décodés selon la police
}
/** Le texte extrait est-il exploitable (sinon : document scanné ou police non lisible, à faire lire par l'IA) ? */
function cand_text_ok(string $t): bool {
    $n = mb_strlen($t); if ($n < 120) return false;
    return preg_match_all('/\p{L}/u', $t) / $n > 0.55;
}

/* ---------- Réception publique ---------- */

function cand_origins(): array { return ['https://gidsas.fr', 'https://www.gidsas.fr', 'https://preprod.gidsas.fr']; }

/** /candidature : appelé par la page Recrutement du site après l'envoi réussi du formulaire. */
function cand_receive(): never {
    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
    if (in_array($origin, cand_origins(), true)) { header('Access-Control-Allow-Origin: ' . $origin); header('Vary: Origin'); }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { header('Access-Control-Allow-Methods: POST'); header('Access-Control-Max-Age: 86400'); http_response_code(204); exit; }
    $out = function (array $d, int $c = 200): never { http_response_code($c); header('Content-Type: application/json; charset=utf-8'); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; };
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $out(['error' => 'POST attendu'], 405);
    if (!in_array($origin, cand_origins(), true)) $out(['error' => 'origine refusée'], 403);
    $ip = client_ip();
    // Suivi anonyme du résultat des envois du formulaire (aucune donnée personnelle) : pour diagnostiquer un envoi qui n'arrive pas
    if (isset($_POST['_gid_ping'])) {
        if (rate_limited('candping:' . $ip, 40, 3600)) $out(['ok' => true]);
        rate_hit('candping:' . $ip);
        $st = preg_replace('/[^a-z0-9_:\-]/', '', strtolower(substr((string)$_POST['_gid_ping'], 0, 40)));
        $L = json_decode((string)q("SELECT v FROM kv WHERE k='cand_pings'")->fetchColumn(), true) ?: [];
        array_unshift($L, ['at' => time(), 's' => $st, 'ua' => preg_match('/iPhone|iPad|Android/i', (string)($_SERVER['HTTP_USER_AGENT'] ?? '')) ? 'mobile' : 'ordinateur']);
        q("INSERT INTO kv(k,v,updated_at) VALUES('cand_pings',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", [json_encode(array_slice($L, 0, 60)), time()]);
        $out(['ok' => true]);
    }
    if (rate_limited('cand:' . $ip, 6, 3600) || rate_limited('cand-all', 120, 3600)) $out(['error' => 'trop de candidatures, réessayez plus tard'], 429);
    rate_hit('cand:' . $ip); rate_hit('cand-all');
    $p = fn(string $k, int $max) => mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)($_POST[$k] ?? ''))), 0, $max);
    if ($p('_gid_hp', 50) !== '' || (int)$p('_gid_t', 12) < 3000) $out(['ok' => true]); // robot : ignoré sans le signaler
    $last = $p('your-name', 120); $first = $p('your-firstname', 120); $email = strtolower($p('your-email', 190)); $phone = $p('your-phone', 40);
    $job = $p('your-poste', 120); $msg = $p('your-message', 2000); $consent = $p('your-consent', 5) !== '';
    $flag = in_array($p('_gid_status', 20), ['spam', 'mail_failed'], true) ? $p('_gid_status', 20) : null;
    if ($last === '' || $first === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $out(['error' => 'champs obligatoires manquants'], 400);
    $dup = q('SELECT id FROM candidates WHERE email=? AND created_at>?', [$email, time() - 600])->fetchColumn(); // double envoi
    if ($dup) $out(['ok' => true, 'dup' => true]);
    $now = time(); $jk = cand_job_key($job);
    q('INSERT INTO candidates(job,job_label,last_name,first_name,email,phone,message,consent,source,site_flag,ip_hash,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)',
      [$jk, $job !== '' ? $job : CAND_JOBS[$jk], $last, $first, $email, $phone, $msg, $consent ? 1 : 0, 'site', $flag, hash('sha256', $ip . '|' . date('Y-m')), $now, $now]);
    $cid = (int)db()->lastInsertId();
    $texts = []; $bad = 0;
    foreach (['your-cv' => 'cv', 'your-lettre' => 'lettre'] as $field => $kind) {
        $f = $_FILES[$field] ?? null; if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $name = mb_substr(basename(str_replace('\\', '/', (string)$f['name'])), 0, 160) ?: $kind;
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            q('INSERT INTO candidate_files(cand_id,kind,name,mime,size,sha256,path,scan_ok,scan_detail,created_at) VALUES(?,?,?,?,?,?,?,?,?,?)', [$cid, $kind, $name, '', (int)($f['size'] ?? 0), '', '', 0, 'fichier non reçu (trop volumineux ou envoi interrompu)', $now]);
            $bad++; continue;
        }
        $bytes = (string)file_get_contents($f['tmp_name']); @unlink($f['tmp_name']);
        $sc = cand_scan($bytes, $name);
        $path = bin2hex(random_bytes(16)) . '.bin';
        file_put_contents(cand_dir() . '/' . $path, seal($bytes)); @chmod(cand_dir() . '/' . $path, 0600);
        q('INSERT INTO candidate_files(cand_id,kind,name,mime,size,sha256,path,scan_ok,scan_detail,created_at) VALUES(?,?,?,?,?,?,?,?,?,?)',
          [$cid, $kind, $name, CAND_EXT[$sc['ext']] ?? 'application/octet-stream', strlen($bytes), hash('sha256', $bytes), $path, $sc['ok'] ? 1 : 0,
           ($sc['ok'] ? 'Aucune menace détectée' : implode(' ; ', $sc['reasons'])) . ' · ' . $sc['engine'], $now]);
        if ($sc['ok']) { $t = cand_text($bytes, $sc['ext']); if ($t !== '') $texts[] = $t; } else $bad++;
        unset($bytes);
    }
    $txt = implode("\n\n", $texts);
    q('UPDATE candidates SET doc_text=?, needs_text=?, threat=? WHERE id=?', [$txt, cand_text_ok($txt) ? 0 : 1, $bad ? 1 : 0, $cid]);
    $out(['ok' => true]);
}

/* ---------- Direction ---------- */

/** Suppression automatique au-delà de 2 ans (une fois par jour). */
function cand_purge(): void {
    if (q("SELECT v FROM kv WHERE k='cand_purge'")->fetchColumn() === date('Y-m-d')) return;
    q("INSERT INTO kv(k,v,updated_at) VALUES('cand_purge',?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v, updated_at=excluded.updated_at", [date('Y-m-d'), time()]);
    foreach (q('SELECT id FROM candidates WHERE created_at<?', [time() - CAND_KEEP_DAYS * 86400])->fetchAll(PDO::FETCH_COLUMN) as $id) cand_delete((int)$id, null);
}

function cand_delete(int $id, ?int $by): bool {
    $c = q('SELECT id FROM candidates WHERE id=?', [$id])->fetchColumn(); if (!$c) return false;
    foreach (q('SELECT path FROM candidate_files WHERE cand_id=?', [$id])->fetchAll(PDO::FETCH_COLUMN) as $p) if ($p !== '' && preg_match('/^[0-9a-f]{32}\.bin$/', $p)) @unlink(cand_dir() . '/' . $p);
    q('DELETE FROM candidate_files WHERE cand_id=?', [$id]);
    q('DELETE FROM candidates WHERE id=?', [$id]);
    audit($by, 'cand_delete:' . $id);
    return true;
}

function cand_star(array $u, int $id, bool $on): bool {
    return q('UPDATE candidates SET starred=?, starred_at=?, starred_by=?, seen_at=COALESCE(seen_at,?), updated_at=? WHERE id=?',
             [$on ? 1 : 0, $on ? time() : null, $on ? (int)$u['id'] : null, time(), time(), $id])->rowCount() > 0;
}

/** Liste, filtrée : job, starred (base de recrutement), q (mots dans le nom, le message et le texte des documents). */
function cand_list(array $f = []): array {
    cand_purge();
    $w = ['1=1']; $p = [];
    if (!empty($f['job']) && isset(CAND_JOBS[$f['job']])) { $w[] = 'c.job=?'; $p[] = $f['job']; }
    if (!empty($f['starred'])) $w[] = 'c.starred=1';
    $terms = array_values(array_filter(preg_split('/\s+/u', mb_strtolower(trim((string)($f['q'] ?? '')))), fn($t) => mb_strlen($t) >= 2));
    foreach (array_slice($terms, 0, 6) as $t) {
        $w[] = "(lower(c.last_name||' '||c.first_name||' '||c.email||' '||c.job_label||' '||c.message||' '||COALESCE(c.note,'')||' '||COALESCE(c.doc_text,'')) LIKE ?)"; $p[] = '%' . $t . '%';
    }
    $rows = q('SELECT c.*, u.name AS starred_name FROM candidates c LEFT JOIN users u ON u.id=c.starred_by WHERE ' . implode(' AND ', $w) . ' ORDER BY c.starred DESC, c.created_at DESC LIMIT 500', $p)->fetchAll();
    if (!$rows) return [];
    $ids = array_column($rows, 'id'); $files = [];
    foreach (q('SELECT id,cand_id,kind,name,size,scan_ok,scan_detail FROM candidate_files WHERE cand_id IN (' . implode(',', array_map('intval', $ids)) . ') ORDER BY kind', [])->fetchAll() as $fl) $files[(int)$fl['cand_id']][] = $fl;
    $prev = [];
    foreach (q('SELECT email, COUNT(*) n FROM candidates GROUP BY email HAVING n>1')->fetchAll() as $x) $prev[$x['email']] = (int)$x['n'];
    foreach ($rows as &$r) {
        $r['files'] = $files[(int)$r['id']] ?? [];
        $r['times'] = $prev[$r['email']] ?? 1;
        $r['snip'] = $terms ? search_snip((string)$r['doc_text'] . ' ' . $r['message'], $terms, 200) : '';
        $r['purge_at'] = (int)$r['created_at'] + CAND_KEEP_DAYS * 86400;
    }
    return $rows;
}

function cand_counts(): array {
    $o = ['total' => 0, 'new' => 0, 'starred' => 0, 'threat' => 0, 'jobs' => []];
    foreach (q('SELECT job, COUNT(*) n, SUM(seen_at IS NULL) nw, SUM(starred) st, SUM(threat) th FROM candidates GROUP BY job')->fetchAll() as $r) {
        $o['jobs'][$r['job']] = ['n' => (int)$r['n'], 'new' => (int)$r['nw'], 'starred' => (int)$r['st']];
        $o['total'] += (int)$r['n']; $o['new'] += (int)$r['nw']; $o['starred'] += (int)$r['st']; $o['threat'] += (int)$r['th'];
    }
    return $o;
}

/** Téléchargement d'un fichier sain (Direction uniquement). */
function cand_file_serve(array $u, int $fid): never {
    if (!cand_viewer($u)) { http_response_code(403); exit('Accès réservé'); }
    $f = q('SELECT f.*, c.first_name, c.last_name FROM candidate_files f JOIN candidates c ON c.id=f.cand_id WHERE f.id=?', [$fid])->fetch();
    if (!$f || !(int)$f['scan_ok'] || !preg_match('/^[0-9a-f]{32}\.bin$/', (string)$f['path']) || !is_file(cand_dir() . '/' . $f['path'])) { http_response_code(404); exit('Fichier indisponible'); }
    $bytes = unseal((string)file_get_contents(cand_dir() . '/' . $f['path']));
    if ($bytes === null || !hash_equals((string)$f['sha256'], hash('sha256', $bytes))) { http_response_code(410); exit('Fichier altéré'); }
    q('UPDATE candidates SET seen_at=COALESCE(seen_at,?) WHERE id=?', [time(), (int)$f['cand_id']]);
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    $dl = (CAND_FILE_KINDS[$f['kind']] ?? 'Document') . ' ' . $f['first_name'] . ' ' . $f['last_name'] . '.' . $ext;
    $dl = preg_replace('/[^\p{L}\p{N} ._-]/u', '', $dl);
    header('Content-Type: ' . (CAND_EXT[$ext] ?? 'application/octet-stream'));
    header('Content-Disposition: ' . ($ext === 'pdf' ? 'inline' : 'attachment') . "; filename=\"" . preg_replace('/[^A-Za-z0-9 ._-]/', '_', $dl) . "\"; filename*=UTF-8''" . rawurlencode($dl));
    header('Content-Length: ' . strlen($bytes)); header('Cache-Control: private, no-store'); header('X-Content-Type-Options: nosniff');
    echo $bytes; exit;
}

/** Question posée sur la base de recrutement : devient une Tâche IA de la Direction. */
function cand_ask(array $u, string $question): array {
    $question = mb_substr(trim($question), 0, 1000);
    if (mb_strlen($question) < 5) return ['error' => 'Écrivez votre question.'];
    $n = (int)q('SELECT COUNT(*) FROM candidates WHERE starred=1')->fetchColumn();
    $body = "Base de recrutement — question de " . $u['name'] . " :\n" . $question . "\n\n"
          . "Répondre à partir des candidats validés (" . $n . " à ce jour) de la section Candidats du tableau de bord Direction (ingest GET candidats?starred=1) : "
          . "citer les candidats pertinents (nom, poste visé, date de candidature) et expliquer pourquoi, sans recopier de coordonnées dans la réponse.";
    q('INSERT INTO consignes(user_id,body,priority,status,space,created_at,updated_at) VALUES(?,?,?,?,?,?,?)', [(int)$u['id'], mb_substr($body, 0, 4000), 'normale', 'todo', 'direction', time(), time()]);
    return ['ok' => true, 'message' => 'Question envoyée : la réponse arrivera dans les Tâches IA.'];
}

/** /rc.js : script de la page Recrutement (chargé par le script de mesure déjà présent sur le site, jamais mis en cache longtemps). */
function cand_script(): never {
    header('Content-Type: application/javascript; charset=utf-8'); header('Cache-Control: public, max-age=600'); header('X-Robots-Tag: noindex');
    header('Access-Control-Allow-Origin: *');
    echo str_replace('__BASE__', BASE_URL, (string)file_get_contents(APP_DIR . '/pages/rc.js'));
    exit;
}
