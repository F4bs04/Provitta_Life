# 🏥 Provitta Life - Sistema de Protocolos Personalizados

Sistema completo de avaliação metabólica e geração de protocolos de suplementação personalizados.

## ✨ Funcionalidades

### 🎯 Para o Cliente:

- **Formulário Multi-Etapas (7 etapas)**: Redução da carga cognitiva com perguntas focadas
- **Avaliação Completa**: Dores, condições de saúde, sono, estado emocional, intestino
- **Protocolo Personalizado**: Geração automática baseada em algoritmo metabólico
- **PDF Profissional**: Download do protocolo com logo e informações detalhadas

### 📊 Para o Administrador:

- **Dashboard Kanban Moderno**: Visualização em 4 colunas de pipeline
  - 📋 Orçamento Gerado
  - ✅ Compra Confirmada
  - 🛍️ Produto Comprado
  - 🔄 Recompra
- **Cards Expansíveis**: Clique para ver detalhes completos do lead
- **Gestão de Status**: Arraste ou clique para mover leads entre etapas
- **Estatísticas em Tempo Real**: Contadores por status
- **Informações Detalhadas**: Anamnese completa, produtos, observações

## 🚀 Como Usar

### Desenvolvimento Local (SQLite):

1. **Inicie o servidor:**

   ```powershell
   php -S localhost:8000 -c php.ini
   ```

2. **Acesse:**
   - Landing Page: http://localhost:8000
   - Admin: http://localhost:8000/admin/admin_login.php
   - Credenciais: `admin` / `admin123`

### Deploy para Produção (MySQL - Hostinger):

1. **Configure o banco de dados:**
   - Edite `db.php` linha 3: `$db_type = 'mysql';`
   - Configure credenciais MySQL (linhas 6-9)
   - Execute o schema: `Data-base/schema.sql`

2. **Faça upload dos arquivos**

3. **Crie o usuário admin:**
   - Acesse `setup_db.php` uma vez para criar o usuário padrão

## 📁 Estrutura do Projeto

```
Provitta_Life/
├── index.php              # Landing page
├── form.php               # Formulário multi-etapas
├── process.php            # Processamento e lógica de negócio
├── result.php             # Tela de resultado
├── generate_pdf.php       # Geração do PDF
├── admin/                 # Área administrativa
│   ├── admin_login.php    # Login administrativo
│   ├── admin_dashboard.php # Dashboard Kanban
│   ├── admin_logout.php   # Logout
│   └── lead_card.php      # Componente de card de lead

├── db.php                 # Conexão com banco (SQLite/MySQL)
├── Data-base/
│   └── schema.sql         # Schema MySQL
├── assets/
│   ├── css/style.css      # Tailwind compilado
│   ├── js/background.js   # Animação de fundo
│   └── src/               # Imagens e logos
└── Software_guide/        # Documentação do projeto
```

## 🎨 Design

- **Paleta de Cores:**
  - Primary: `#66FCF1` (Cyan)
  - Secondary: `#45A29E` (Teal)
  - Background: `#1A1A24` (Dark Blue)
  - Surface: `#1F2833` (Charcoal)

- **Tipografia:** System fonts otimizadas
- **Animações:** Transições suaves com Alpine.js
- **Responsivo:** Mobile-first design

## 🔧 Tecnologias

- **Backend:** PHP 8.4
- **Frontend:** Tailwind CSS 3.x, Alpine.js 3.x
- **Banco de Dados:** SQLite (dev) / MySQL (prod)
- **PDF:** TCPDF
- **Build:** PostCSS, Tailwind CLI

## 📝 Changelog

### v2.0.0 (19/01/2026)

- ✨ Dashboard Kanban com 4 colunas de pipeline
- ✨ **Drag & Drop**: Arraste cards entre colunas para atualizar status
- ✨ **Modo Lista**: Visualização alternativa em tabela
- ✨ **Toggle de Visualização**: Alterne entre Kanban e Lista
- ✨ Cards expansíveis com informações detalhadas
- ✨ Sistema de gestão de status de leads
- ✨ Estatísticas em tempo real
- ✨ Formulário dividido em 7 etapas (redução de carga cognitiva)
- 🔧 Suporte a SQLite para desenvolvimento local
- 🎨 Remoção do glow azul da logo
- 🎨 Login administrativo com visual atualizado
- 🐛 Correção de scroll no formulário

### v1.0.0

- 🎉 Lançamento inicial
- Formulário de avaliação
- Geração de protocolo
- Dashboard básico

## 👨‍💻 Desenvolvedor

**Fabian Araújo**

- Email: fabian.ajaraujo@gmail.com
- GitHub: [@F4bs04](https://github.com/F4bs04)

## 📄 Licença

Propriedade de Provitta Life. Todos os direitos reservados.
