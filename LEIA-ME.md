# INSTRUÇÕES IMPORTANTES - LEIA ANTES DE USAR O SISTEMA

## ⚠️ AÇÃO NECESSÁRIA AGORA:

O servidor PHP está rodando com configurações antigas e NÃO consegue acessar o banco de dados SQLite.

### Para corrigir:

1. **Pare o servidor atual:**
   - Vá até o terminal onde está rodando: `php -S localhost:8000 -c php.ini`
   - Pressione **Ctrl + C**

2. **Inicie o servidor novamente:**

   ```powershell
   php -S localhost:8000 -c php.ini
   ```

3. **Acesse o sistema:**
   - Landing Page: http://localhost:8000
   - Admin Login: http://localhost:8000/admin/admin_login.php
   - Credenciais: `admin` / `admin123`

---

## 📦 Deploy para Hostinger (Produção):

Quando for fazer deploy, edite o arquivo `db.php` e mude a linha 3:

```php
$db_type = 'mysql'; // Altere de 'sqlite' para 'mysql'
```

Depois configure as credenciais do MySQL da Hostinger nas linhas 6-9.

---

## 🗂️ Estrutura do Banco:

- **Local (desenvolvimento):** SQLite - arquivo `database.sqlite` criado automaticamente
- **Produção (Hostinger):** MySQL - use o schema em `Data-base/schema.sql`

---

## ✅ Verificar se está funcionando:

Execute no terminal:

```powershell
php -c php.ini test_db.php
```

Se aparecer "✓ All tests passed!" está tudo certo!
