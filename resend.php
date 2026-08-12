<?php
/**
 * Disparo da campanha de conscientizacao.
 *
 * O que mudou em relacao a versao anterior (que enviava 1 e-mail fixo):
 *   - Loop por destinatario, com {{TOKEN}} unico substituido no HTML
 *     (o tracker usa esse token para identificar QUEM clicou).
 *   - Assunto e remetente configuraveis.
 *   - Tratamento de erro por destinatario (uma falha nao derruba o lote).
 *   - Pausa entre envios para respeitar o rate limit do provedor.
 *
 * IMPORTANTE: os destinatarios reais sao dados pessoais — carregue de uma
 * fonte FORA do versionamento (planilha/CSV no .gitignore). Abaixo vao
 * apenas exemplos com placeholders.
 */

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$resend = Resend::client($_ENV['RESEND_MAIL_KEY']);

$from     = 'Nome Exibido <no-reply@SEU-DOMINIO.exemplo>';
$subject  = 'Assunto da campanha';
$template = __DIR__ . '/consultivo_oab_assinatura.html';

// token,email  -> em producao, leia de um CSV fora do git.
$destinatarios = [
    ['token' => 'TOKEN_EXEMPLO_1', 'email' => 'exemplo1@dominio.exemplo'],
    ['token' => 'TOKEN_EXEMPLO_2', 'email' => 'exemplo2@dominio.exemplo'],
];

$tpl = file_get_contents($template);
if ($tpl === false) {
    fwrite(STDERR, "Nao consegui ler o template: $template\n");
    exit(1);
}

$ok = 0;
$fail = 0;
foreach ($destinatarios as $d) {
    $html = str_replace('{{TOKEN}}', $d['token'], $tpl);
    try {
        $res = $resend->emails->send([
            'from'    => $from,
            'to'      => [$d['email']],
            'subject' => $subject,
            'html'    => $html,
        ]);
        echo "[OK] {$d['email']} id={$res->id}\n";
        $ok++;
    } catch (\Throwable $e) {
        fwrite(STDERR, "[FALHA] {$d['email']}: {$e->getMessage()}\n");
        $fail++;
    }
    usleep(700000); // ~1.4 e-mails/s
}

echo "\nResumo: $ok enviados, $fail falhas.\n";
