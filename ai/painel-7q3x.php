<?php
// Painel de números do whatsyourbubble (só totais anônimos)
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
$file = dirname($_SERVER['DOCUMENT_ROOT']) . '/wyb-stats.json';
$d = is_file($file) ? json_decode(file_get_contents($file), true) : [];
if (!is_array($d)) $d = [];
krsort($d);
$tot = [];
foreach ($d as $day => $row) foreach ($row as $k => $v) $tot[$k] = ($tot[$k] ?? 0) + $v;
$g = fn($a, $k) => (int)($a[$k] ?? 0);
$pct = fn($a, $b) => $b ? round($a / $b * 100) . '%' : '–';
$names = ['A'=>'Arquiteto','D'=>'Detetive','M'=>'Maestro','F'=>'Finalizador','S'=>'Secreta'];
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex"><title>Painel whatsyourbubble</title>
<style>
body{margin:0;font:20px/1.45 system-ui,sans-serif;background:#0F1422;color:#E8ECF5;padding:20px max(16px,env(safe-area-inset-left))}
h1{font-size:30px;margin:0 0 14px}h2{font-size:22px;margin:26px 0 10px;color:#9AA4BB}
.k{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.c{background:#182033;border-radius:14px;padding:14px 16px}.c b{display:block;font-size:34px}.c span{color:#9AA4BB;font-size:17px}
table{border-collapse:collapse;width:100%;font-size:17px;font-variant-numeric:tabular-nums}td,th{padding:8px 6px;border-bottom:1px solid #2A3550;text-align:right}th:first-child,td:first-child{text-align:left}
.w{overflow-x:auto}
</style></head><body>
<h1>whatsyourbubble · números</h1>
<div class="k">
 <div class="c"><b><?= $g($tot,'view') ?></b><span>visitas</span></div>
 <div class="c"><b><?= $g($tot,'start') ?></b><span>começaram (<?= $pct($g($tot,'start'),$g($tot,'view')) ?>)</span></div>
 <div class="c"><b><?= $g($tot,'finish') ?></b><span>terminaram (<?= $pct($g($tot,'finish'),$g($tot,'start')) ?>)</span></div>
 <div class="c"><b><?= $g($tot,'share')+$g($tot,'copy')+$g($tot,'dl')+$g($tot,'vid')+$g($tot,'net_WhatsApp')+$g($tot,'net_Facebook')+$g($tot,'net_X')+$g($tot,'net_Telegram')+$g($tot,'net_LinkedIn') ?></b><span>ações de compartilhar</span></div>
 <div class="c"><b><?= $g($tot,'friend') ?></b><span>chegaram por link de amigo</span></div>
</div>
<h2>Perfis</h2><div class="k"><?php foreach ($names as $k=>$n): ?><div class="c"><b><?= $g($tot,'p_'.$k) ?></b><span><?= $n ?></span></div><?php endforeach ?></div>
<h2>Compartilhamento</h2><div class="w"><table><tr><th>Canal</th><th>Toques</th></tr>
<?php foreach (['share'=>'Botão Compartilhar','copy'=>'Copiar link','dl'=>'Baixar selo','vid'=>'Story animado','net_WhatsApp'=>'WhatsApp','net_Facebook'=>'Facebook','net_X'=>'X','net_Telegram'=>'Telegram','net_LinkedIn'=>'LinkedIn'] as $k=>$n): ?>
<tr><td><?= $n ?></td><td><?= $g($tot,$k) ?></td></tr><?php endforeach ?></table></div>
<h2>Idiomas</h2><div class="k"><?php foreach (['pt'=>'Português','en'=>'English','zh'=>'中文'] as $k=>$n): ?><div class="c"><b><?= $g($tot,'l_'.$k) ?></b><span><?= $n ?></span></div><?php endforeach ?></div>
<h2>Por dia</h2><div class="w"><table><tr><th>Dia</th><th>Visitas</th><th>Começaram</th><th>Terminaram</th><th>Amigo</th></tr>
<?php foreach ($d as $day=>$r): ?><tr><td><?= htmlspecialchars($day) ?></td><td><?= $g($r,'view') ?></td><td><?= $g($r,'start') ?></td><td><?= $g($r,'finish') ?></td><td><?= $g($r,'friend') ?></td></tr><?php endforeach ?></table></div>
</body></html>
