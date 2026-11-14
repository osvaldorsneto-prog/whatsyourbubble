<?php
// Publicação dos Reels no Instagram pela API oficial do Meta (Instagram API with Instagram Login).
// Nada de navegador: o servidor do site fala direto com graph.instagram.com.
//
// Arquivos (fora da pasta pública, menos os vídeos):
//   ~/wyb-ig/token.json         token da conta (gravado pelo q/ig-token.php; ninguém mais vê)
//   ~/wyb-ig/fila/<id>.json     Reels agendados (gravados pelo q/receber.php?acao=ig_agendar)
//   ~/wyb-ig/ultimo-ciclo.json  quando o agendador rodou pela última vez e o que fez
//   ~/public_html/ig/<nome>.mp4 vídeo à espera de publicação (o Meta baixa por link público); some depois de publicado
//
// Quem roda o agendador: o cron do cPanel a cada 5 min (q/ig-cron.php), ou o receptor (acao=ig_ciclo).
declare(strict_types=1);
if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) { http_response_code(404); exit; }   // biblioteca: não abre direto pela web

const IG_VERSAO = 'v25.0';
const IG_ANTES = 15 * 60;          // monta o container até 15 min antes do horário (o Meta processa o vídeo)
const IG_ATRASO_MAX = 3 * 3600;    // passou 3 h do horário sem publicar: desiste (não posta Reel da noite de madrugada)
const IG_PROCESSA_MAX = 45 * 60;   // container parado em processamento por mais que isso: erro
const IG_RENOVA = 5 * 86400;       // renova o token (dura 60 dias) a cada 5 dias
const IG_MAX_TENTATIVAS = 3;

function ig_host(): string {
  $h = getenv('WYB_IG_HOST');      // só pra teste local; no servidor fica vazio
  return $h ? rtrim($h, '/') : 'https://graph.instagram.com';
}

function ig_casa(): string {
  $doc = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
  return $doc !== '' ? dirname($doc) : dirname(__DIR__, 2);
}

function ig_publica(): string {
  $doc = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
  return $doc !== '' ? $doc : dirname(__DIR__);
}

function ig_pasta(string $sub = '', int $modo = 0700): string {
  $d = ig_casa() . '/wyb-ig' . ($sub !== '' ? "/$sub" : '');
  if (!is_dir($d)) @mkdir($d, $modo, true);
  return $d;
}

function ig_pasta_videos(): string {
  $d = ig_publica() . '/ig';
  if (!is_dir($d)) @mkdir($d, 0755, true);
  $ht = "$d/.htaccess";
  if (!is_file($ht)) @file_put_contents($ht, "Options -Indexes\n<IfModule mod_headers.c>\nHeader set X-Robots-Tag \"noindex\"\n</IfModule>\n");
  return $d;
}

function ig_url_video(string $nome): string {
  $base = getenv('WYB_IG_BASE_VIDEO') ?: 'https://whatsyourbubble.com/ig/';
  return rtrim($base, '/') . '/' . rawurlencode($nome);
}

function ig_log(string $msg): void {
  $d = ig_casa() . '/wyb-logs';
  if (!is_dir($d)) @mkdir($d, 0700, true);
  @file_put_contents("$d/ig.log", gmdate('c') . " $msg\n", FILE_APPEND | LOCK_EX);
}

function ig_grava_json(string $arquivo, array $dados): bool {
  $tmp = $arquivo . '.tmp' . bin2hex(random_bytes(4));
  $ok = @file_put_contents($tmp, json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX) !== false
    && @rename($tmp, $arquivo);
  if (!$ok) @unlink($tmp);
  return $ok;
}

function ig_le_json(string $arquivo): ?array {
  if (!is_file($arquivo)) return null;
  $d = json_decode((string)file_get_contents($arquivo), true);
  return is_array($d) ? $d : null;
}

// Pedido à API. Devolve [código HTTP, resposta decodificada]. O token vai no cabeçalho, nunca no log.
function ig_api(string $metodo, string $caminho, array $params = [], ?string $token = null, bool $com_versao = true): array {
  $url = ig_host() . ($com_versao ? '/' . IG_VERSAO : '') . '/' . ltrim($caminho, '/');
  $cab = ['User-Agent: whatsyourbubble'];
  if ($token !== null) $cab[] = 'Authorization: Bearer ' . $token;
  $c = curl_init();
  $op = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => $cab];
  if ($metodo === 'POST') {
    $op[CURLOPT_URL] = $url;
    $op[CURLOPT_POST] = true;
    $op[CURLOPT_POSTFIELDS] = http_build_query($params);
  } else {
    $op[CURLOPT_URL] = $url . ($params ? '?' . http_build_query($params) : '');
  }
  curl_setopt_array($c, $op);
  $corpo = curl_exec($c);
  $code = (int)curl_getinfo($c, CURLINFO_HTTP_CODE);
  $erro = curl_error($c);
  curl_close($c);
  if ($corpo === false) return [0, ['error' => ['message' => "sem conexão: $erro"]]];
  $j = json_decode((string)$corpo, true);
  return [$code, is_array($j) ? $j : ['error' => ['message' => 'resposta não é JSON: ' . substr((string)$corpo, 0, 200)]]];
}

function ig_msg_erro(array $r): string {
  $e = $r['error'] ?? null;
  if (is_array($e)) return trim(($e['message'] ?? 'erro') . (isset($e['code']) ? " (código {$e['code']})" : ''));
  return 'resposta inesperada';
}

// ---------- token ----------

function ig_token(): ?array {
  $t = ig_le_json(ig_pasta() . '/token.json');
  return ($t && !empty($t['token']) && !empty($t['user_id'])) ? $t : null;
}

// Confere um token novo e grava. Devolve [true, '@usuario'] ou [false, motivo].
function ig_conecta(string $token): array {
  $token = trim($token);
  if (!preg_match('/^[A-Za-z0-9._\-|]{20,1000}$/', $token)) return [false, 'isso não parece um token do Instagram'];
  [$code, $r] = ig_api('GET', 'me', ['fields' => 'user_id,username'], $token);
  if ($code !== 200 || empty($r['user_id'])) return [false, 'o Instagram recusou o token: ' . ig_msg_erro($r)];
  $agora = time();
  $ok = ig_grava_json(ig_pasta() . '/token.json', [
    'token' => $token, 'user_id' => (string)$r['user_id'], 'username' => (string)($r['username'] ?? ''),
    'salvo_em' => $agora, 'renovado_em' => $agora, 'expira_em' => $agora + 60 * 86400,
  ]);
  if (!$ok) return [false, 'não consegui gravar o token no servidor'];
  ig_log('token conectado @' . ($r['username'] ?? '?'));
  return [true, '@' . ($r['username'] ?? '')];
}

function ig_renova_se_preciso(array $t): array {
  if (time() - (int)($t['renovado_em'] ?? 0) < IG_RENOVA) return $t;
  [$code, $r] = ig_api('GET', 'refresh_access_token', ['grant_type' => 'ig_refresh_token', 'access_token' => $t['token']], null, false);
  if ($code === 200 && !empty($r['access_token'])) {
    $t['token'] = (string)$r['access_token'];
    $t['renovado_em'] = time();
    $t['expira_em'] = time() + (int)($r['expires_in'] ?? 60 * 86400);
    ig_grava_json(ig_pasta() . '/token.json', $t);
    ig_log('token renovado');
  } else {
    ig_log('falha ao renovar token: ' . ig_msg_erro($r));
  }
  return $t;
}

// ---------- fila ----------

function ig_itens(): array {
  $itens = [];
  foreach (glob(ig_pasta('fila') . '/*.json') ?: [] as $f) {
    $i = ig_le_json($f);
    if ($i) $itens[] = $i;
  }
  usort($itens, fn($a, $b) => ($a['quando'] ?? 0) <=> ($b['quando'] ?? 0));
  return $itens;
}

function ig_grava_item(array $i): void {
  ig_grava_json(ig_pasta('fila') . "/{$i['id']}.json", $i);
}

function ig_apaga_video(array $i): void {
  $nome = basename((string)($i['video'] ?? ''));
  if ($nome !== '' && is_file(ig_pasta_videos() . "/$nome")) @unlink(ig_pasta_videos() . "/$nome");
}

// Uma passada do agendador: prepara, confere e publica o que estiver na hora.
function ig_ciclo(): array {
  $lock = @fopen(ig_pasta() . '/ciclo.lock', 'c');
  if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) return ['ok' => true, 'aviso' => 'outro ciclo rodando'];
  $t = ig_token();
  if (!$t) {
    $res = ['ok' => false, 'erro' => 'instagram não conectado (falta o token)'];
    ig_grava_json(ig_pasta() . '/ultimo-ciclo.json', ['em' => time()] + $res);
    flock($lock, LOCK_UN);
    return $res;
  }
  $t = ig_renova_se_preciso($t);
  $feito = [];
  foreach (ig_itens() as $i) {
    $st = $i['status'] ?? 'agendado';
    if ($st !== 'agendado' && $st !== 'preparando') continue;
    $agora = time();
    $quando = (int)$i['quando'];
    $msgs = [];
    if ($agora > $quando + IG_ATRASO_MAX) {
      $i['status'] = 'perdido';
      $i['erro'] = 'passou 3 h do horário sem conseguir publicar';
      ig_apaga_video($i);
      $msgs[] = 'perdido (passou 3 h do horário)';
    } else {
      if ($st === 'agendado' && $agora >= $quando - IG_ANTES) {
        [$code, $r] = ig_api('POST', "{$t['user_id']}/media", [
          'media_type' => 'REELS', 'video_url' => ig_url_video((string)$i['video']),
          'caption' => (string)$i['legenda'], 'share_to_feed' => 'true',
        ], $t['token']);
        if ($code === 200 && !empty($r['id'])) {
          $i['status'] = 'preparando';
          $i['container'] = (string)$r['id'];
          $i['container_em'] = $agora;
          unset($i['erro']);
          $msgs[] = 'container criado';
        } else {
          $i['tentativas'] = (int)($i['tentativas'] ?? 0) + 1;
          $i['erro'] = 'falha ao criar container: ' . ig_msg_erro($r);
          if ($i['tentativas'] >= IG_MAX_TENTATIVAS) $i['status'] = 'erro';
          $msgs[] = $i['erro'];
        }
      }
      if (($i['status'] ?? '') === 'preparando') {
        [$code, $r] = ig_api('GET', (string)$i['container'], ['fields' => 'status_code,status'], $t['token']);
        $sc = (string)($r['status_code'] ?? '');
        if ($sc === 'FINISHED' && $agora >= $quando) {
          [$code, $p] = ig_api('POST', "{$t['user_id']}/media_publish", ['creation_id' => $i['container']], $t['token']);
          if ($code === 200 && !empty($p['id'])) {
            $i['status'] = 'publicado';
            $i['media_id'] = (string)$p['id'];
            $i['publicado_em'] = time();
            [, $m] = ig_api('GET', (string)$p['id'], ['fields' => 'permalink'], $t['token']);
            if (!empty($m['permalink'])) $i['link'] = (string)$m['permalink'];
            unset($i['erro']);
            ig_apaga_video($i);
            $msgs[] = 'publicado';
          } else {
            $i['tentativas'] = (int)($i['tentativas'] ?? 0) + 1;
            $i['erro'] = 'falha ao publicar: ' . ig_msg_erro($p);
            if ($i['tentativas'] >= IG_MAX_TENTATIVAS) $i['status'] = 'erro';
            $msgs[] = $i['erro'];
          }
        } elseif ($sc === 'PUBLISHED') {
          $i['status'] = 'publicado';
          $i['publicado_em'] = $i['publicado_em'] ?? time();
          ig_apaga_video($i);
          $msgs[] = 'já estava publicado';
        } elseif ($sc === 'ERROR' || $sc === 'EXPIRED') {
          $i['status'] = 'erro';
          $i['erro'] = "o Meta recusou o vídeo ($sc): " . (string)($r['status'] ?? '');
          $msgs[] = $i['erro'];
        } elseif ($sc === 'IN_PROGRESS') {
          if ($agora - (int)($i['container_em'] ?? $agora) > IG_PROCESSA_MAX) {
            $i['status'] = 'erro';
            $i['erro'] = 'o Meta não terminou de processar o vídeo em 45 min';
            $msgs[] = $i['erro'];
          }
        } elseif ($sc !== 'FINISHED') {
          $i['tentativas'] = (int)($i['tentativas'] ?? 0) + 1;
          $i['erro'] = 'não consegui conferir o container: ' . ig_msg_erro($r);
          if ($i['tentativas'] >= IG_MAX_TENTATIVAS * 3) $i['status'] = 'erro';
          $msgs[] = $i['erro'];
        }
      }
    }
    if ($msgs) {
      $linha = "{$i['id']}: " . implode('; ', $msgs);
      ig_log($linha);
      $feito[] = $linha;
      ig_grava_item($i);
    }
  }
  // vídeo que sobrou no servidor (agendamento que falhou, Reel cancelado) some depois de 2 dias
  $em_uso = [];
  foreach (ig_itens() as $i) if (in_array($i['status'] ?? '', ['agendado', 'preparando'], true)) $em_uso[basename((string)$i['video'])] = true;
  foreach (glob(ig_pasta_videos() . '/*.mp4') ?: [] as $f) {
    if (!isset($em_uso[basename($f)]) && time() - (int)filemtime($f) > 2 * 86400) { @unlink($f); $feito[] = 'apagado vídeo sem uso: ' . basename($f); }
  }
  $res = ['ok' => true, 'feito' => $feito];
  ig_grava_json(ig_pasta() . '/ultimo-ciclo.json', ['em' => time()] + $res);
  flock($lock, LOCK_UN);
  return $res;
}

// Resumo pra conferência (sem o token).
function ig_situacao(): array {
  $t = ig_token();
  return [
    'conectado' => (bool)$t,
    'conta' => $t ? '@' . ($t['username'] ?? '') : null,
    'token_expira_em' => $t ? gmdate('c', (int)($t['expira_em'] ?? 0)) : null,
    'ultimo_ciclo' => ig_le_json(ig_pasta() . '/ultimo-ciclo.json'),
    'fila' => array_map(fn($i) => array_intersect_key($i, array_flip(
      ['id', 'status', 'quando_txt', 'video', 'erro', 'link', 'tentativas'])), ig_itens()),
  ];
}
