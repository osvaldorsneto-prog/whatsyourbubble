<?php
// Página de configuração (o Osvaldo usa, uma vez):
//  1) cola o token do Instagram gerado no painel do Meta → ~/wyb-ig/token.json
//  2) se marcar, troca a chave do receptor (q/receber.php): a chave nasce aqui no navegador dele,
//     ele copia pro "network secret" do ambiente da nuvem, e o servidor guarda só o hash.
// Nada disso passa pela nuvem nem pelo Claude. Pra usar a página precisa do código de configuração
// (aqui só fica o hash dele).
declare(strict_types=1);
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/ig.php';

const CODIGO_SHA256 = 'c0296a30b958c245e0e6f51a69482083e933beacd12afe1ee4318f8a6be619d6';

function salva_chave_receptor(string $chave): bool {
  $arq = ig_casa() . '/wyb-config.php';
  $cfg = is_file($arq) ? (@include $arq) : [];
  if (!is_array($cfg)) $cfg = [];
  $cfg['chave_sha256'] = hash('sha256', $chave);
  $tmp = $arq . '.tmp' . bin2hex(random_bytes(4));
  $ok = @file_put_contents($tmp, '<?php return ' . var_export($cfg, true) . ";\n", LOCK_EX) !== false && @rename($tmp, $arq);
  if (!$ok) @unlink($tmp);
  @chmod($arq, 0600);
  if ($ok && function_exists('opcache_invalidate')) @opcache_invalidate($arq, true);   // vale na hora, sem esperar o cache do PHP
  return $ok;
}

$msgs = [];
$deu_certo = false;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $codigo = strtoupper(trim((string)($_POST['codigo'] ?? '')));
  if (!hash_equals(CODIGO_SHA256, hash('sha256', $codigo))) {
    ig_log('ig-token: código errado de ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
    sleep(2);
    $msgs[] = [false, 'Código de configuração errado.'];
  } else {
    $token = trim((string)($_POST['token'] ?? ''));
    if ($token !== '') {
      [$ok, $r] = ig_conecta($token);
      $msgs[] = [$ok, $ok ? "Instagram conectado: $r." : "Token: $r."];
    }
    if (!empty($_POST['troca_chave'])) {
      $chave = trim((string)($_POST['chave'] ?? ''));
      if (!preg_match('/^[A-Za-z0-9_-]{32,128}$/', $chave)) $msgs[] = [false, 'Chave do receptor inválida.'];
      elseif (salva_chave_receptor($chave)) { $msgs[] = [true, 'Chave do receptor salva.']; ig_log('chave do receptor trocada'); }
      else $msgs[] = [false, 'Não consegui salvar a chave do receptor.'];
    }
    if (!$msgs) $msgs[] = [false, 'Nada pra salvar: cole o token e/ou marque a troca de chave.'];
    $deu_certo = !in_array(false, array_column($msgs, 0), true);
  }
}
$t = ig_token();
?><!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex"><title>Configurar publicador</title>
<style>
  body{font:17px/1.45 system-ui,sans-serif;margin:0;padding:24px 16px;background:#f6f1ff;color:#141b2e}
  main{max-width:520px;margin:0 auto;background:#fff;border:3px solid #141b2e;border-radius:18px;padding:20px;box-shadow:8px 8px 0 #8c6cf5}
  h1{font-size:22px;margin:0 0 6px} p{margin:8px 0} small{color:#555}
  label{display:block;font-weight:700;margin:16px 0 6px}
  input[type=text],textarea{width:100%;box-sizing:border-box;font:16px monospace;padding:10px;border:2px solid #141b2e;border-radius:10px}
  textarea{height:110px}
  .linha{display:flex;gap:8px;align-items:center;font-weight:600;margin-top:18px}
  #caixa-chave{display:none} #caixa-chave.on{display:block}
  .copiar{margin-top:8px;padding:8px 12px;font:700 15px system-ui;border:2px solid #141b2e;border-radius:10px;background:#12b5cb;color:#fff}
  button[type=submit]{margin-top:20px;width:100%;padding:14px;font:700 18px system-ui;background:#ff5c8a;color:#fff;border:3px solid #141b2e;border-radius:12px}
  .msg{padding:10px 12px;border-radius:10px;margin-top:10px;font-weight:600}
  .bom{background:#e3f9d0} .ruim{background:#ffe0e6}
</style></head><body><main>
<h1>Configurar o publicador</h1>
<p>Instagram: <?= $t ? 'conectado como <b>@' . htmlspecialchars((string)$t['username']) . '</b>' : '<b>não conectado</b>' ?></p>
<?php foreach ($msgs as [$ok, $m]): ?><div class="msg <?= $ok ? 'bom' : 'ruim' ?>"><?= htmlspecialchars($m) ?></div><?php endforeach; ?>
<?php if ($deu_certo): ?><p>Pronto. Pode fechar esta página.</p><?php else: ?>
<form method="post" autocomplete="off">
  <label for="codigo">Código de configuração</label>
  <input type="text" id="codigo" name="codigo" required autocapitalize="characters" spellcheck="false">
  <label for="token">Token do Instagram <small>(do painel do Meta; deixe vazio se não for trocar)</small></label>
  <textarea id="token" name="token" spellcheck="false"></textarea>
  <label class="linha"><input type="checkbox" name="troca_chave" value="1" id="troca"> Trocar a chave do receptor</label>
  <div id="caixa-chave">
    <p><small>Copie esta chave e cole no segredo de rede do ambiente da nuvem <b>antes</b> de salvar.</small></p>
    <input type="text" id="chave" name="chave" readonly>
    <button type="button" class="copiar" id="copiar">Copiar chave</button>
  </div>
  <button type="submit">Salvar</button>
</form>
<script>
  const b = new Uint8Array(32); crypto.getRandomValues(b);
  document.getElementById('chave').value = Array.from(b, x => x.toString(16).padStart(2, '0')).join('');
  document.getElementById('troca').onchange = e => document.getElementById('caixa-chave').classList.toggle('on', e.target.checked);
  document.getElementById('copiar').onclick = async () => {
    const c = document.getElementById('chave'); c.select();
    try { await navigator.clipboard.writeText(c.value); } catch (e) { document.execCommand('copy'); }
    document.getElementById('copiar').textContent = 'Copiada ✓';
  };
</script>
<?php endif; ?>
</main></body></html>
