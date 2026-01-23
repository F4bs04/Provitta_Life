# Guia de Deploy - Provitta Life

Este documento explica como configurar o sistema para produção (Hostinger/MySQL) após o desenvolvimento local (SQLite).

## 1. Configuração do Banco de Dados

No arquivo `db.php`, você deve alterar o tipo de banco de dados e fornecer as credenciais do seu servidor MySQL.

### Passo a Passo:

1.  Abra o arquivo `db.php`.
2.  Localize a linha 3 e altere de:
    ```php
    $db_type = 'sqlite';
    ```
    Para:
    ```php
    $db_type = 'mysql';
    ```
3.  Preencha as informações de conexão nas linhas 6 a 9:
    ```php
    $host = 'seu_host';          // Geralmente 'localhost' na Hostinger
    $db   = 'seu_nome_do_banco';
    $user = 'seu_usuario';
    $pass = 'sua_senha';
    ```

## 2. Importação do Schema

Antes de rodar o sistema em produção, você deve criar as tabelas no MySQL:

1.  Acesse o **phpMyAdmin** no painel da Hostinger.
2.  Selecione o banco de dados criado.
3.  Vá na aba **Importar**.
4.  Selecione o arquivo `Data-base/schema.sql` que está na raiz do projeto.
5.  Clique em **Executar**.

## 3. Inicialização do Usuário Admin

Após configurar o banco e subir os arquivos:

1.  Acesse `https://seu-dominio.com.br/setup_db.php` uma única vez.
2.  Isso criará o usuário administrador padrão (`admin` / `admin123`) no MySQL.
3.  **IMPORTANTE**: Por segurança, delete o arquivo `setup_db.php` do servidor após o uso.

---

> [!TIP]
> Sempre verifique se a versão do PHP no servidor é 7.4 ou superior (recomendado 8.x).
