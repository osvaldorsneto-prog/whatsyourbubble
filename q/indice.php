<?php
// Lista de quizzes em JSON, pro "próximo quiz" no fim de cada teste.
require __DIR__ . '/lib.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
echo json_encode(['quizzes' => wyb_indice()], JSON_UNESCAPED_UNICODE);
