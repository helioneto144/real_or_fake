-- Exemplo de cadastro de destinatarios (token,email,name,template).
-- Em producao gere este arquivo a partir da sua lista (fora do git) e nao versione.
INSERT OR REPLACE INTO recipients (token, email, name, template) VALUES
 ('TOKEN_EXEMPLO_1', 'exemplo1@dominio.exemplo', 'Fulano de Tal', 'contencioso'),
 ('TOKEN_EXEMPLO_2', 'exemplo2@dominio.exemplo', 'Ciclano de Tal', 'contencioso');
