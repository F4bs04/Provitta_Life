# Product Requirements Document (PRD) - ProVitta Life

## 1. Visão Geral

O objetivo é desenvolver uma aplicação web para gerar protocolos de saúde personalizados baseados em uma anamnese interativa. O sistema deve coletar dados do usuário, processar regras de negócios (fisiologia, estilo de vida, etc.) e gerar um protocolo único de suplementação, incluindo um PDF para download.

## 2. Fluxo do Usuário

### Fase 1: Acolhimento (Landing Page)

- **Objetivo**: Criar confiança e iniciar a avaliação.
- **Elementos**:
  - Logo Provitta Life.
  - Texto: "A tecnologia cuida do protocolo. Você cuida de viver melhor."
  - Botão: [INICIAR AVALIAÇÃO].
- **Ação Técnica**: Gerar `session_id` única.

### Fase 2: Coleta de Dados (Formulário)

- **Formato**: Perguntas condicionais (Ramificação).
- **Blocos**:
  - **A. Fisiologia Básica**: Dores, Pressão Alta, Diabetes.
  - **B. Bem-Estar e Rotina**: Sono, Estado Emocional.
  - **C. Saúde Intestinal**: Constipação, Diarreia, Normal.
  - **D. Qualitativo**: Campo aberto para observações.

### Fase 3: Processamento ("O Cérebro")

- **Interface**: Tela de carregamento (animação molécula de ozônio).
- **Lógica**:
  - Base: NXCAP + Power Trimagnesio (Obrigatórios).
  - Regras Condicionais: Adição de produtos baseada nas flags (ex: Dor -> Óleo SOFH).
  - Deduplicação: Remover itens duplicados.
  - Cálculo Financeiro: Somar total.
  - **Persistência**: Salvar dados do lead e protocolo no banco de dados MySQL.

### Fase 4: Apresentação (Protocolo)

- **Conteúdo**:
  - Mensagem de sucesso.
  - Lista unificada de produtos (sem separação por módulos).
  - Investimento Total.
- **Ações**:
  - Enviar (Link).
  - Baixar PDF.
  - Refazer.

### Fase 5: Entrega (PDF)

- **Conteúdo do PDF**:
  - Cabeçalho (Logo + Data).
  - Resumo da Anamnese.
  - Tabela do Protocolo (Produto, Como usar).
  - Valor Total.
  - Disclaimer LGPD/Saúde.

## 3. Requisitos Técnicos

- **Stack**: PHP + HTML/CSS (Tailwind).
- **Banco de Dados**: MySQL.
- **Infraestrutura**: Hostinger ou Vercel.
- **Estilo**: Tailwind CSS (via npm build process).
- **PDF**: Biblioteca `dompdf` via Composer.

## 4. Referências

- Documento base: `ProVitta Life mapa de desenvolvimento.md`
