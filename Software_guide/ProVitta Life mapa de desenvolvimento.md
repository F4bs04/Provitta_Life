**Mapeamento Detalhado do Processo do Formulário**:

---

### **🟢 Fase 1: Acolhimento e Preparação**

*O objetivo desta fase é criar confiança e obter o consentimento (LGPD).*

**Tela 1: Landing Page do App**

* **Visual:** Logo Provitta Life centralizada, fundo limpo.  
* **Texto Principal:** "A tecnologia cuida do protocolo. Você cuida de viver melhor."  
* **Ação:** Botão **\[INICIAR AVALIAÇÃO\]**.  
* *Nota Técnica:* Ao clicar, o sistema cria uma `session_id` única e temporária. Nada é salvo no banco de dados permanente ainda.

---

### **🔵 Fase 2: A Coleta de Dados (O Formulário)**

*Aqui aplicamos a lógica de ramificação. O formulário é uma tela de rolagem única ou dividido em "cards" (um por vez) para não cansar o usuário.*

**Bloco A: Fisiologia Básica (Gatilhos de Módulos Críticos)**

1. **"Você sente dores crônicas ou agudas frequentemente?"**  
   * \[Sim\] → *Ativa flag `need_pain_module`*  
   * \[Não\]  
2. **"Você foi diagnosticado com pressão alta?"**  
   * \[Sim\] → *Ativa flag `need_pressure_module` \+ Tag de alerta "Cuidado com estimulantes"*  
   * \[Não\]  
3. **"Você tem diabetes ou pré-diabetes?"**  
   * \[Sim\] → *Ativa flag `need_diabetes_module`*  
   * \[Não\]

**Bloco B: Bem-Estar e Rotina (Gatilhos de Estilo de Vida)** 

4\. **"Como você classificaria a qualidade do seu sono?"** \* \[Durmo bem\] \* \[Tenho insônia / Dificuldade para dormir\] → *Ativa flag `need_sleep_module`* 

5\. **"Como está seu estado emocional hoje?"** \* \[Estável\] → *Neutro* \* \[Ansioso / Depressivo / Oscilando\] → *Ativa flag `need_emotional_module`*

**Bloco C: Saúde Intestinal (Gatilho de Variação)** 

6\. **"Como funciona o seu intestino?"** \* \[Preso/Lento\] → *Ativa flag `gut_constipated`* \* \[Solto/Diarreia\] → *Ativa flag `gut_loose`* \* \[Normal\] → *Ativa flag `gut_normal`*

**Bloco D: Campo Aberto (Qualitativo)** 

7\. **"Gostaria de detalhar algum sintoma específico?"** \* \[Campo de texto livre\] → *Salvo apenas para constar no PDF final como "Observações do Cliente", não altera a lógica do algoritmo.*

---

### **🟣 Fase 3: O "Cérebro" (Processamento e Lógica)**

*Esta é a tela de carregamento com a molécula de ozônio girando. O usuário vê uma animação de 3 segundos, mas o cálculo leva milésimos.*

**Passo 3.1: Injeção da Base (Invisível ao Usuário)**

* O sistema cria um `Carrinho Virtual` vazio.  
* **Ação Automática:** Adiciona `NXCAP` \+ `Power Trimagnesio`.  
* *Status:* Obrigatórios. Não removíveis.

**Passo 3.2: Empilhamento dos Módulos**

* O sistema verifica as *flags* marcadas na Fase 2\.  
  * *Se `need_pain_module` \= TRUE:* Adiciona Óleo SOFH, Ômega 3, Gel Life Shii.  
  * *Se `need_emotional_module` \= TRUE:* Adiciona Melatonina+CoQ10, Polivitamínico, Sachê Energético.  
  * *(Repete para todos os módulos ativados...)*  
  * 

**Passo 3.3: Deduplicação Inteligente** 

* O algoritmo varre o `Carrinho Virtual` em busca de itens repetidos.  
  * *Cenário:* O usuário tem **Dor** (pede Óleo SOFH) e **Pressão Alta** (também pede Óleo SOFH).  
  * *Ação:* O sistema mantém apenas **1 unidade** de Óleo SOFH.  
  * *Resultado:* Lista limpa, sem redundância de compra.

**Passo 3.4: Cálculo Financeiro**

* Soma os valores unitários dos itens restantes na lista limpa.  
* Gera o valor `Total do Protocolo`.

---

### **🟧 Fase 4: Apresentação (O Protocolo)**

*A tela de resultado. Limpa, direta e focada na solução.*

**Cabeçalho:**

* "Protocolo Personalizado Gerado com Sucesso"

**Corpo (A Lista Única):**

* Aqui não mostramos "Módulo Dor" ou "Módulo Pressão". Mostramos a **Lista Unificada de Produtos**.  
* *Exemplo Visual:*  
  * ⬜ **NXCAP** (Uso Diário)  
  * ⬜ **Power Trimagnesio** (Uso Diário)  
  * ⬜ **Óleo SOFH** (Ingestão)  
  * ⬜ **Life Shii** (Aplicação local)  
  * ... (restante dos itens)

**Rodapé Financeiro:**

* **Investimento Total: R$ XXX,XX**  
* Botões de Ação:  
  1. \[📲 Enviar\] (Gera um link com o resumo)  
  2. \[📄 Baixar PDF\] (Gera o arquivo formatado)  
  3. \[🔄 Refazer\] (Limpa a sessão e volta à Tela 2\)

---

### **🟫 Fase 5: A Entrega (PDF)**

*O documento que o cliente leva para casa.*

**Estrutura do PDF Gerado Automaticamente:**

1. **Topo:** Logo Provitta Life \+ Data.  
2. **Título:** Protocolo de Saúde Personalizado.  
3. **Resumo da Anamnese:**  
   * *Queixas principais:* Dor, Intestino Preso (baseado nas respostas).  
4. **O Protocolo (Tabela):**  
   * Coluna 1: Produto.  
   * Coluna 2: Como usar (Manhã/Noite/Tópico).  
   * *Nota:* O sistema insere automaticamente a regra de horário (ex: "Sachê Energético" recebe a tag "Tomar pela manhã").  
5. **Valor:** R$ Total.  
6. **Disclaimer LGPD/Saúde:** "Sugestão de suplementação. Não é remédio."

---

**Resumo Técnico** 

"O formulário é um **coletor de booleanos (True/False)** que ativam arrays de produtos pré-definidos. O backend deve unir todos os arrays ativados, rodar uma função de **'.unique()'** ou **deduplicação por ID do produto**, somar os valores e renderizar a lista final plana (flat list) para o usuário."

PHP \+ HTMl Css com Tailwind com deploy na **hostinger** ou Vercel;   
