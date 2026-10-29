<?php
// Contador anônimo do whatsyourbubble: só totais por dia. Sem cookies, sem IP.
header('Cache-Control: no-store');
$ok = ['view','friend','start','finish','share','copy','dl','vid',
       'net_WhatsApp','net_Facebook','net_X','net_Telegram','net_LinkedIn'];
$e = $_GET['e'] ?? '';
$k = strtoupper($_GET['k'] ?? '');
$l = $_GET['l'] ?? '';
if (!in_array($e, $ok, true)) { http_response_code(204); exit; }
$file = dirname($_SERVER['DOCUMENT_ROOT']) . '/wyb-stats.json';   // fora da pasta pública
$fh = fopen($file, 'c+');
if ($fh && flock($fh, LOCK_EX)) {
  $raw = stream_get_contents($fh);
  $d = $raw ? json_decode($raw, true) : [];
  if (!is_array($d)) $d = [];
  $day = gmdate('Y-m-d');
  $d[$day][$e] = ($d[$day][$e] ?? 0) + 1;
  if ($e === 'finish' && preg_match('/^[ADMFS]$/', $k)) $d[$day]['p_'.$k] = ($d[$day]['p_'.$k] ?? 0) + 1;
  if ($e === 'start' && in_array($l, ['pt','en','zh'], true)) $d[$day]['l_'.$l] = ($d[$day]['l_'.$l] ?? 0) + 1;
  ftruncate($fh, 0); rewind($fh); fwrite($fh, json_encode($d));
  fflush($fh); flock($fh, LOCK_UN);
}
if ($fh) fclose($fh);
http_response_code(204);
