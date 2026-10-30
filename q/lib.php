<?php
// Funções comuns do motor e da vitrine: buscar arquivos do GitHub com cópia local.
const WYB_RAW = 'https://raw.githubusercontent.com/osvaldorsneto-prog/whatsyourbubble/main/';

function wyb_dir() {
  $dir = dirname($_SERVER['DOCUMENT_ROOT']) . '/wyb-cache';
  if (!is_dir($dir)) @mkdir($dir, 0755);
  return $dir;
}

function wyb_fetch($url) {
  if (function_exists('curl_init')) {
    $c = curl_init($url);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6, CURLOPT_FOLLOWLOCATION => true, CURLOPT_USERAGENT => 'whatsyourbubble']);
    $b = curl_exec($c); $code = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
    return ($b !== false && $code == 200) ? $b : false;
  }
  return @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 6]]));
}

// Lê um JSON do repositório, guardando cópia em ~/wyb-cache/<nome>.json por $ttl segundos.
// Se o GitHub falhar, usa a última cópia boa.
function wyb_json($path, $name, $ok, $ttl = 600) {
  $file = wyb_dir() . "/$name.json";
  $data = null;
  if (is_file($file) && time() - filemtime($file) < $ttl) $data = json_decode(file_get_contents($file), true);
  if (!$ok($data)) {
    $raw = wyb_fetch(WYB_RAW . $path);
    $new = $raw ? json_decode($raw, true) : null;
    if ($ok($new)) { $data = $new; @file_put_contents($file, $raw); }
    elseif (is_file($file)) { $data = json_decode(file_get_contents($file), true); @touch($file); }
  }
  return $ok($data) ? $data : null;
}

function wyb_quiz_ok($q) {
  if (!is_array($q)) return false;
  foreach (['pt', 'en', 'zh'] as $l) {
    if (empty($q[$l]['titulo']) || count($q[$l]['Q'] ?? []) !== 8) return false;
    foreach (['A', 'D', 'M', 'F', 'S'] as $k) if (count($q[$l]['T'][$k] ?? []) !== 6) return false;
  }
  return true;
}

function wyb_slug($s) {
  $s = strtolower((string)$s);
  return (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $s) && strlen($s) <= 40) ? $s : '';
}

// Lista de quizzes (quizzes/index.json) + quantas pessoas terminaram cada um.
function wyb_indice() {
  $idx = wyb_json('quizzes/index.json', '_indice', fn($d) => is_array($d) && isset($d['quizzes']) && is_array($d['quizzes']));
  $list = $idx['quizzes'] ?? [];
  $stats = dirname($_SERVER['DOCUMENT_ROOT']) . '/wyb-stats.json';
  $d = is_file($stats) ? json_decode(file_get_contents($stats), true) : [];
  $fin = [];
  if (is_array($d)) foreach ($d as $row) foreach ($row as $k => $v)
    if (preg_match('/^q:([a-z0-9-]+):finish$/', $k, $m)) $fin[$m[1]] = ($fin[$m[1]] ?? 0) + $v;
  foreach ($list as &$q) $q['jogaram'] = $fin[$q['slug']] ?? 0;
  return $list;
}
