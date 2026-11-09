<?php
// Receptor da hospedagem: a rotina diária publica os quizzes aqui (no lugar do GitHub)
// e baixa daqui o pacote de código. Todo pedido precisa de "Authorization: Bearer <chave>".
// A chave não fica no servidor: só o hash dela, em ~/wyb-config.php (fora da pasta pública):
//   <?php return ['chave_sha256' => '<sha256 em hexadecimal da chave>'];
// Na nuvem, a chave é um "network secret" do ambiente para o host whatsyourbubble.com:
// o proxy põe o cabeçalho sozinho e a sessão nunca vê a chave.
//
// GET  ?acao=ping                         confere a chave
// GET  ?acao=quizzes                      todos os quizzes publicados + o índice
// POST ?acao=publicar                     {"quizzes": {slug: quiz, ...}, "indice": {...}}
// GET  ?acao=codigo                       pacote de código (tar.gz) pra rotina
// POST ?acao=codigo&parte=1&total=2&sha256=<hash do arquivo inteiro>   envia o pacote em partes
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
require __DIR__ . '/lib.php';

const MAX_CORPO = 6 * 1024 * 1024;   // por pedido; o pacote de código vai em partes menores que isso

function resp(int $code, array $dados): void {
  http_response_code($code);
  echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function base_dir(): string { return dirname($_SERVER['DOCUMENT_ROOT']); }

function pasta(string $nome, int $modo = 0755): string {
  $d = base_dir() . "/$nome";
  if (!is_dir($d) && !@mkdir($d, $modo)) resp(500, ['ok' => false, 'erro' => "não consegui criar $nome"]);
  return $d;
}

function registra(string $acao, string $resultado): void {
  $d = base_dir() . '/wyb-logs';
  if (!is_dir($d)) @mkdir($d, 0700);
  @file_put_contents("$d/receber.log", gmdate('c') . ' ' . ($_SERVER['REMOTE_ADDR'] ?? '-') . " $acao $resultado\n", FILE_APPEND | LOCK_EX);
}

function chave_recebida(): string {
  $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
  if ($h === '' && function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) if (strcasecmp((string)$k, 'Authorization') === 0) { $h = (string)$v; break; }
  }
  return preg_match('/^Bearer\s+(\S+)$/i', trim($h), $m) ? $m[1] : '';
}

// Grava de uma vez: escreve num temporário e troca, pra nunca ficar arquivo pela metade.
function grava(string $arquivo, string $conteudo): void {
  $tmp = $arquivo . '.tmp' . bin2hex(random_bytes(4));
  if (@file_put_contents($tmp, $conteudo, LOCK_EX) === false || !@rename($tmp, $arquivo)) {
    @unlink($tmp);
    resp(500, ['ok' => false, 'erro' => 'não consegui gravar ' . basename($arquivo)]);
  }
}

function json_arquivo(string $f) {
  return is_file($f) ? json_decode((string)file_get_contents($f), true) : null;
}

$acao = (string)($_GET['acao'] ?? '');
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$cfg = @include base_dir() . '/wyb-config.php';
$hash = is_array($cfg) ? (string)($cfg['chave_sha256'] ?? '') : '';
$chave = chave_recebida();
if (strlen($hash) !== 64 || $chave === '' || !hash_equals($hash, hash('sha256', $chave))) {
  registra($acao, 'negado');
  resp(401, ['ok' => false, 'erro' => 'sem autorização', 'cabecalho_recebido' => $chave !== '']);
}

if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > MAX_CORPO) resp(413, ['ok' => false, 'erro' => 'pedido grande demais']);
$corpo = $metodo === 'POST' ? (string)file_get_contents('php://input') : '';

$quizzes_dir = pasta('wyb-quizzes');

if ($acao === 'ping') {
  registra('ping', 'ok');
  resp(200, ['ok' => true, 'agora' => gmdate('c'), 'quizzes' => count(glob("$quizzes_dir/*.json") ?: []) - (is_file("$quizzes_dir/index.json") ? 1 : 0)]);
}

if ($acao === 'quizzes' && $metodo === 'GET') {
  $todos = [];
  // os da hospedagem valem mais; a cópia antiga do GitHub (wyb-cache) só completa o que faltar
  foreach ([pasta('wyb-cache'), $quizzes_dir] as $d) {
    foreach (glob("$d/*.json") ?: [] as $f) {
      $slug = basename($f, '.json');
      if (wyb_slug($slug) !== $slug) continue;
      $q = json_arquivo($f);
      if (wyb_quiz_ok($q)) $todos[$slug] = $q;
    }
  }
  ksort($todos);
  $indice = json_arquivo("$quizzes_dir/index.json") ?? json_arquivo(pasta('wyb-cache') . '/_indice.json');
  registra('quizzes', 'ok ' . count($todos));
  resp(200, ['ok' => true, 'quizzes' => $todos, 'indice' => $indice]);
}

if ($acao === 'publicar' && $metodo === 'POST') {
  $p = json_decode($corpo, true);
  if (!is_array($p)) resp(400, ['ok' => false, 'erro' => 'corpo não é JSON']);
  $novos = $p['quizzes'] ?? [];
  if (!is_array($novos)) resp(400, ['ok' => false, 'erro' => '"quizzes" precisa ser um objeto {slug: quiz}']);
  foreach ($novos as $slug => $q) {
    if (wyb_slug((string)$slug) !== (string)$slug) resp(400, ['ok' => false, 'erro' => "slug inválido: $slug"]);
    if (!wyb_quiz_ok($q)) resp(400, ['ok' => false, 'erro' => "quiz fora do formato: $slug"]);
  }
  $indice = $p['indice'] ?? null;
  if ($indice !== null) {
    if (!is_array($indice) || !is_array($indice['quizzes'] ?? null)) resp(400, ['ok' => false, 'erro' => 'índice fora do formato']);
    foreach ($indice['quizzes'] as $item) {
      $s = (string)($item['slug'] ?? '');
      if (wyb_slug($s) !== $s) resp(400, ['ok' => false, 'erro' => "slug inválido no índice: $s"]);
      if (strpos((string)($item['url'] ?? ''), '/q/?s=') !== 0) continue;   // quiz fora do motor (o de IA mora em /ai/)
      $existe = isset($novos[$s]) || is_file("$quizzes_dir/$s.json") || is_file(pasta('wyb-cache') . "/$s.json");
      if (!$existe) resp(400, ['ok' => false, 'erro' => "o índice cita um quiz que não existe: $s"]);
    }
  }
  $gravados = [];
  foreach ($novos as $slug => $q) {
    grava("$quizzes_dir/$slug.json", json_encode($q, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    $gravados[] = (string)$slug;
  }
  if ($indice !== null) grava("$quizzes_dir/index.json", json_encode($indice, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
  registra('publicar', 'ok ' . implode(',', $gravados) . ($indice !== null ? ' +indice' : ''));
  resp(200, ['ok' => true, 'gravados' => $gravados, 'indice' => $indice !== null]);
}

if ($acao === 'codigo') {
  $dir = pasta('wyb-pacotes', 0700);
  $final = "$dir/codigo.tar.gz";
  if ($metodo === 'GET') {
    if (!is_file($final)) resp(404, ['ok' => false, 'erro' => 'ainda não tem pacote de código']);
    registra('codigo', 'baixado');
    header('Content-Type: application/gzip');
    header('Content-Length: ' . filesize($final));
    header('X-Pacote-Sha256: ' . hash_file('sha256', $final));
    readfile($final);
    exit;
  }
  if ($metodo === 'POST') {
    $parte = (int)($_GET['parte'] ?? 0);
    $total = (int)($_GET['total'] ?? 0);
    $sha = strtolower((string)($_GET['sha256'] ?? ''));
    if ($total < 1 || $total > 50 || $parte < 1 || $parte > $total || !preg_match('/^[0-9a-f]{64}$/', $sha)) {
      resp(400, ['ok' => false, 'erro' => 'use parte=1..total, total<=50 e sha256 do arquivo inteiro']);
    }
    if ($corpo === '') resp(400, ['ok' => false, 'erro' => 'parte vazia']);
    grava("$dir/parte-$sha-$parte", $corpo);
    $partes = [];
    for ($i = 1; $i <= $total; $i++) $partes[] = "$dir/parte-$sha-$i";
    if (count(array_filter($partes, 'is_file')) < $total) resp(200, ['ok' => true, 'recebida' => $parte, 'faltam' => $total - count(array_filter($partes, 'is_file'))]);
    $tmp = "$final.novo";
    @unlink($tmp);
    foreach ($partes as $f) file_put_contents($tmp, file_get_contents($f), FILE_APPEND);
    foreach ($partes as $f) @unlink($f);
    if (hash_file('sha256', $tmp) !== $sha) { @unlink($tmp); resp(400, ['ok' => false, 'erro' => 'o arquivo montado não bate com o sha256']); }
    if (!@rename($tmp, $final)) resp(500, ['ok' => false, 'erro' => 'não consegui trocar o pacote']);
    registra('codigo', 'novo ' . filesize($final) . 'B');
    resp(200, ['ok' => true, 'pacote' => filesize($final), 'sha256' => $sha]);
  }
}

resp(400, ['ok' => false, 'erro' => 'ação desconhecida', 'acoes' => ['ping', 'quizzes', 'publicar', 'codigo']]);
