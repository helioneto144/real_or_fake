<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$mail = new PHPMailer(true);

try {
    // Desabilita debug em produção - só usar SMTP::DEBUG_SERVER para diagnóstico
    $mail->SMTPDebug = SMTP::DEBUG_OFF;

    // Configuração SMTP para entrega via Resend
    $mail->isSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->Host       = 'smtp.resend.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'resend';
    $mail->Password   = $_ENV['RESEND_MAIL_KEY'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Timeout de 30 segundos evita conexões penduradas que causam falhas intermitentes
    $mail->Timeout = 30;
    // KeepAlive desabilitado - cada envio abre/fecha conexão explicitamente
    $mail->SMTPKeepAlive = false;

    // Return-Path para bounces - obrigatório para boa reputação em servidores de email
    // Emails que retornam (bounces) precisam de um endereço válido para processamento
    $mail->Sender = 'bounces@tjrj.online';

    // Configuração DKIM para assinatura digital do email
    // DKIM (DomainKeys Identified Mail) prova que o email realmente veio do domínio declarado
    // Sem DKIM configurado, Gmail/Outlook marcam como spam com maior frequência
    $mail->DKIM_domain = 'tjrj.online';
    $mail->DKIM_private = ''; // Adicione o caminho para chave privada DKIM quando disponível
    $mail->DKIM_selector = 'resend'; // Seletor DKIM que deve estar configurado no DNS do domínio
    $mail->DKIM_passphrase = '';
    $mail->DKIM_identity = 'no-replyconsultorianow@tjrj.online';

    // Headers de remetente e destinatário
    $mail->setFrom('no-replyconsultorianow@tjrj.online', 'Portal de Assinaturas');
    $mail->addAddress('c.los.bomfin200@gmail.com');
    $mail->addAddress('renato.oliveira@fass.legal');
    $mail->addAddress('Heliomenezesneto@gmail.com');
    $mail->addAddress('renato.j.olveira@gmail.com');
    $mail->addAddress('freefirerenato@gmail.com');
    $mail->addAddress('oab.portaideasinaturas@gmail.com');
    $mail->addReplyTo('atendimentoconsultorianow@tjrj.online', 'Atendimento');

    // Priority 3 = normal (1=alta, 3=normal, 5=baixa)
    // Alguns clientes de email exibem ícones diferentes baseado nesse valor
    $mail->Priority = 3;
    $mail->addCustomHeader('X-Priority', '3');
    $mail->addCustomHeader('X-MSMail-Priority', 'Normal');

    // X-Mailer identifica o software utilizado - aumenta transparência e confiança
    $mail->addCustomHeader('X-Mailer', 'PHPMailer/7.1 (Resend)');

    // Precedence: bulk indica email transacional/marketing
    // Servidores como Gmail usam esse header para decidir caixa de destino
    $mail->addCustomHeader('Precedence', 'bulk');

    // List-Unsubscribe conforme RFC 8058 - formato One-Click para Gmail/Google
    // O link HTTPS é obrigatório para que Gmail botão de "Cancelar inscrição" funcione
    // Posterior deve ser "List-Unsubscribe=One-Click" para ativar One-Click unsubscribe
    $mail->addCustomHeader('List-Unsubscribe', '<https://tjrj.online/unsubscribe?token={{TOKEN}}>');
    $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');

    // Message-ID único com domínio válido - evita que servidores marquem como duplicado
    // Formato RFC 5322: <unique@domain>
    $mail->MessageID = '<' . bin2hex(random_bytes(16)) . '@tjrj.online>';

    // Date explícito no formato RFC 2822 - alguns filtros verificam validade do timestamp
    $mail->addCustomHeader('Date', date('r'));

    // Conteúdo HTML do email
    $mail->isHTML(true);
    $mail->Subject = 'Documentos aguardando sua assinatura digital';
    $html = file_get_contents('consultivo_oab_assinatura.html');
    $mail->Body = $html;

    // AltBody com conteúdo rico em texto puro - crítico para entregabilidade
    // Servidores como Gmail analisam se o texto corresponde ao HTML (análise de consistência)
    // Se AltBody for apenas strip_tags() do HTML, parece spam porque não tem formatação natural
    $mail->AltBody = "Portal de Assinaturas OAB\n\n" .
        "Gustavo Fonseca (gustavo.fonseca@fass.legal) enviou os seguintes documentos:\n\n" .
        "- Procuracao_ad_judicia_-_Grupo_Completa\n\n" .
        "Para assinar todos os documentos em lote, acesse:\n" .
        "https://oab.portaldeassinaturas.online/Documento/MeusDocumentos\n\n" .
        "Atenciosamente,\n" .
        "Portal OAB\n" .
        "https://oab.portaldeassinaturas.com.br\n\n" .
        "Se voce tiver alguma duvida, visite nosso Fale conosco.\n" .
        "Por favor nao responda esse e-mail. Este endereco de e-mail nao e monitorado.";

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
