<?php

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


$resend = Resend::client($_ENV['RESEND_MAIL_KEY']);

$resend->emails->send([
    'from' => 'Consultivo <renato@tjrj.online>',
    'to' => ['renato.j.olveira@gmail.com'],
    'subject' => 'Isso não é um phishing, confia kkk',
    'html' => file_get_contents('consultivo_oab_assinatura.html'),
]);
