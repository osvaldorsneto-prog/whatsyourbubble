<?php
// Agendador dos Reels: o cron do cPanel roda isto a cada 5 minutos.
//   */5 * * * * php /home/<usuário>/public_html/q/ig-cron.php >/dev/null 2>&1
// Pela web não faz nada (quem quiser rodar pela web usa o receptor, com chave: acao=ig_ciclo).
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/ig.php';
echo json_encode(ig_ciclo(), JSON_UNESCAPED_UNICODE) . "\n";
