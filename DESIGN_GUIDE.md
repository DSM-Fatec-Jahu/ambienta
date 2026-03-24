# Ambienta — Guia de Design e Padrões de UI

> **Este documento é a referência obrigatória para todos os sprints.**
> Todo código de view gerado deve seguir estes padrões sem exceção.

---

## 1. Stack de Frontend

| Tecnologia | Versão | Uso |
|---|---|---|
| Tailwind CSS | v3 | Utilitários de estilo |
| Alpine.js | v3 (CDN) | Reatividade local (modais, dropdowns, sidebar) |
| FullCalendar.js | v6.1.11 (CDN) | Calendário de reservas |
| Inter (Google Fonts) | — | Tipografia (pesos: 400, 500, 600, 700, 800) |

**Compilação CSS:** `npm run build` (produção) / `npm run dev` (watch)

---

## 2. Paleta de Cores

### Tokens principais

```css
--color-primary:        #1D6FA4   /* Ações primárias, links ativos */
--color-primary-dark:   #145680   /* Hover de botões primários */
--color-primary-light:  #E8F4FB   /* Fundos informativos, active nav */
--color-success:        #1A8C5B   /* Status Aprovado, ações confirmar */
--color-warning:        #D4A017   /* Status Pendente, alertas */
--color-danger:         #C0392B   /* Status Recusado/Cancelado, erros */
--color-text-primary:   #0F172A   /* Texto principal */
--color-text-secondary: #64748B   /* Textos auxiliares */
--color-bg-app:         #F8FAFC   /* Fundo geral da aplicação */
```

### Regra de uso de cores
- **Primary blue** → somente em elementos de ação (botões CTA, links, active states)
- **Slate** (escala 50–900) → texto, bordas, fundos, sidebar
- **Status colors** → exclusivamente para badges e alertas de status

---

## 3. Tipografia

```
Família: Inter
Base: 14px (text-sm)

Hierarquia:
  Page title:    text-xl font-bold text-slate-900    (20px)
  Section title: text-base font-semibold text-slate-800 (16px)
  Body:          text-sm text-slate-700              (14px)
  Helper/hint:   text-xs text-slate-400              (12px)
  Label:         text-xs font-semibold uppercase tracking-wider text-slate-400
  Micro:         text-2xs text-slate-500             (10.4px — custom)
```

---

## 4. Layouts

### 4.1 Público (`layouts/public.php`)
- Header: `bg-white/90 backdrop-blur-sm border-b border-slate-100 sticky top-0`
- Logo (esquerda) + nav links (centro/direita) + botão "Entrar"
- Nav links: `text-slate-500 hover:text-slate-900 hover:bg-slate-50` / active: `text-primary bg-primary-light`
- Max-width conteúdo: `max-w-screen-xl mx-auto px-4 sm:px-6`
- Footer: `border-t border-slate-100 bg-white` discreto

### 4.2 Auth (`layouts/auth.php`)
- **Desktop:** Split-screen — esquerda (`bg-slate-900` com branding) + direita (formulário branco)
- **Mobile:** Somente painel do formulário
- Painel esquerdo: `w-[480px]`, decorações com blur circles, feature list
- Formulário: `w-full max-w-sm` centralizado verticalmente

### 4.3 App autenticado (`layouts/app.php`)
- **Sidebar:** `bg-slate-900 w-[248px]` fixa (desktop) / slide-in (mobile)
  - Logo no topo: `h-14 border-b border-slate-800`
  - Nav com seções rotuladas (`nav-section-label`)
  - Itens: `nav-item-default` / `nav-item-active`
  - Rodapé: info do usuário + link de logout
- **Topbar:** `bg-white border-b border-slate-100 h-14 sticky top-0`
  - Hamburguer (mobile) + Page title + Bell + Avatar dropdown
- **Conteúdo:** `p-4 sm:p-6` no main

---

## 5. Componentes (classes em `app.css`)

### Botões
```html
<button class="btn-primary">Ação principal</button>
<button class="btn-secondary">Ação secundária</button>
<button class="btn-ghost">Ação discreta</button>
<button class="btn-danger">Ação destrutiva</button>

<!-- Tamanhos -->
<button class="btn-primary btn-xs">Micro</button>
<button class="btn-primary btn-sm">Pequeno</button>
<!-- padrão (sem modificador) = médio -->
<button class="btn-primary btn-lg">Grande</button>
<button class="btn-primary btn-xl">Extra-grande</button>
```

### Botões de ação em tabelas (padrão obrigatório)

Todos os botões de ação em linhas de tabela devem ser **somente ícone** com `btn-ghost btn-sm p-1.5` e cor aplicada via classe Tailwind de texto. **Nunca usar `btn-warning` ou outros com fundo colorido neste contexto.**

```html
<!-- Ver / Visualizar — ícone olho, azul -->
<a href="..." class="btn-ghost p-2 text-blue-500 hover:bg-blue-50 hover:text-blue-600" aria-label="Ver detalhes">
  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
         -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
  </svg>
</a>

<!-- Editar — ícone lápis, amarelo -->
<button class="btn-ghost p-2 text-yellow-500 hover:bg-yellow-50 hover:text-yellow-600" aria-label="Editar">
  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
         m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
  </svg>
</button>

<!-- Excluir — ícone lixeira, vermelho -->
<button type="submit" class="btn-ghost p-2 text-red-500 hover:bg-red-50 hover:text-red-600" aria-label="Excluir">
  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6
         m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
  </svg>
</button>
```

> **Atenção:** `btn-ghost` define `hover:text-slate-900` via `@apply`. É obrigatório incluir `hover:text-{cor}` explicitamente para manter a cor no hover. Nunca omitir. Sempre usar `p-2` (sem `btn-sm`) para o tamanho padrão destes botões.

**Resumo das cores de ação em tabela:**
| Ação    | Cor normal           | Hover bg             | Hover texto           |
|---------|----------------------|----------------------|-----------------------|
| Ver     | `text-blue-500`      | `hover:bg-blue-50`   | `hover:text-blue-600` |
| Editar  | `text-yellow-500`    | `hover:bg-yellow-50` | `hover:text-yellow-600` |
| Excluir | `text-red-500`       | `hover:bg-red-50`    | `hover:text-red-600`  |

### Cards
```html
<div class="card">
  <div class="card-header">
    <h2 class="text-sm font-semibold text-slate-900">Título</h2>
    <button class="btn-primary btn-sm">Ação</button>
  </div>
  <div class="card-body">Conteúdo</div>
  <div class="card-footer">Rodapé opcional</div>
</div>

<!-- Card clicável -->
<div class="card-hover">...</div>
```

### Formulários
```html
<div class="mb-4">
  <label for="campo" class="form-label form-label-required">Campo</label>
  <input type="text" id="campo" name="campo" class="form-input">
  <p class="form-error">Mensagem de erro</p>
  <p class="form-hint">Texto de ajuda</p>
</div>

<!-- Com ícone à esquerda -->
<div class="input-group">
  <svg class="input-icon-left w-4 h-4">...</svg>
  <input class="form-input-icon-left" ...>
</div>

<!-- Campo com erro -->
<input class="form-input form-input-error" ...>
```

### Badges de status
```html
<span class="badge-pending">Pendente</span>
<span class="badge-approved">Aprovada</span>
<span class="badge-rejected">Recusada</span>
<span class="badge-cancelled">Cancelada</span>
<span class="badge-absent">Ausente</span>
<span class="badge-primary">Custom</span>

<!-- Com dot indicador -->
<span class="badge-approved badge-dot">Aprovada</span>
```

### Alertas
```html
<div class="alert-success" role="alert">
  <svg .../>  <!-- ícone 20px -->
  <span>Mensagem de sucesso</span>
</div>
<!-- Variantes: alert-warning, alert-danger, alert-info -->
```

### Tabelas
```html
<div class="card overflow-hidden">
  <table class="table-base">
    <thead>
      <tr>
        <th>Coluna</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Dado</td>
      </tr>
    </tbody>
  </table>
</div>
```

### Stat cards (Dashboard)
```html
<div class="stat-card">
  <div class="stat-icon bg-primary-light">
    <svg class="w-5 h-5 text-primary">...</svg>
  </div>
  <div>
    <p class="stat-label">Rótulo</p>
    <p class="stat-value">42</p>
  </div>
</div>
```

### Empty states
```html
<div class="card">
  <div class="empty-state">
    <svg class="empty-state-icon">...</svg>
    <p class="empty-state-title">Nenhum item encontrado</p>
    <p class="empty-state-description">Descrição explicando o estado vazio.</p>
    <a href="..." class="btn-primary mt-4">Criar primeiro item</a>
  </div>
</div>
```

### Page header
```html
<div class="page-header">
  <div>
    <h1 class="page-title">Título da Página</h1>
    <p class="page-subtitle">Subtítulo ou descrição breve</p>
  </div>
  <button class="btn-primary">Ação principal</button>
</div>
```

### Modais (Alpine.js)
```html
<div x-show="modalOpen" class="modal-overlay" x-cloak>
  <div class="modal-panel max-w-md" @click.stop>
    <div class="modal-header">
      <h3 class="text-sm font-semibold text-slate-900">Título</h3>
      <button @click="modalOpen = false" class="btn-ghost btn-sm p-1">
        <svg class="w-4 h-4"><!-- X icon --></svg>
      </button>
    </div>
    <div class="modal-body">Conteúdo do modal</div>
    <div class="modal-footer">
      <button @click="modalOpen = false" class="btn-secondary">Cancelar</button>
      <button class="btn-primary">Confirmar</button>
    </div>
  </div>
</div>
```

---

## 6. Wizard de múltiplos passos (Alpine.js)

Formulários complexos devem usar o padrão de wizard com stepper visual. Ver `bookings/create.php` como referência canônica.

### Estrutura do wizard

```
[●1 Pesquisar] → [●2 Escolher sala] → [●3 Detalhes]
```

**Stepper visual:** badges numerados (`h-5 w-5 rounded-full`) com cor `:class="step >= N ? 'bg-primary text-white' : 'bg-slate-200 text-slate-500'"`.

**Regras:**
- Cada etapa é um `<div x-show="step === N">` — a primeira sem `x-cloak`, as demais com
- O `<form>` fica apenas na **última etapa**; campos das etapas anteriores são `<input type="hidden" :value="...">` ligados ao estado Alpine
- Estado de restauração (`restore`): quando `store()` retorna erro de validação, o controller passa `$restoreData` com os dados das etapas anteriores, permitindo reiniciar no passo 3 sem perder dados
- Endpoint AJAX separado para a busca (ex: `GET /reservas/salas-disponiveis?date=&start_time=&end_time=&needs_equipment=`)

### Padrão do endpoint de busca (Controller)

```php
public function availableRooms(): \CodeIgniter\HTTP\ResponseInterface
{
    // valida params, retorna JSON ['rooms' => [...]]
}
```

### Alpine.js skeleton

```javascript
function bookingWizard() {
  const restore = /* PHP: json_encode($restoreData) ?? 'null' */;
  return {
    step: restore?.step ?? 1,
    // step 1 fields
    // step 2 data (loaded via AJAX)
    // step 3 fields
    async searchRooms() { /* fetch + this.step = 2 */ },
    selectRoom(room)    { this.selectedRoom = room; this.step = 3; },
  }
}
```

---

## 7. FullCalendar PT-BR

```html
<!-- No <head> da view (NÃO adicionar CSS separado — o v6 injeta automaticamente) -->

<!-- No final do <body> ou no @section('scripts') -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales-all.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
    locale: 'pt-br',
    initialView: 'dayGridMonth',
    headerToolbar: {
      left:   'prev,next today',
      center: 'title',
      right:  'dayGridMonth,timeGridWeek,timeGridDay',
    },
    height: 'auto',
    // ...
  });
  calendar.render();
});
</script>
```

O CSS do FullCalendar é **sobrescrito** pelo bloco `.fc { ... }` no `app.css` para manter consistência visual.

---

## 8. Regras gerais de código de view

1. **Escapar sempre:** usar `esc()` em TODA saída de variáveis PHP nas views
2. **Sem PHP lógico nas views:** views só recebem dados processados pelo Controller
3. **CSRF:** `<?= csrf_field() ?>` em todo `<form method="POST">`
4. **Acessibilidade:**
   - `<label for="id">` associado a todo input
   - `aria-label` em botões apenas com ícone
   - `role="alert"` em mensagens de erro/sucesso
   - Contraste mínimo WCAG AA
5. **Responsividade mobile-first:** testar em 375px, 768px, 1280px
6. **Ícones:** SVG inline (stroke, não fill) — `w-4 h-4` para botões, `w-5 h-5` para standalone
7. **Lazy loading:** usar `x-cloak` em elementos Alpine ocultos para evitar flash

---

## 9. Padrão de página autenticada (template base)

```php
<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<!-- Page header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Título</h1>
    <p class="page-subtitle">Subtítulo</p>
  </div>
  <a href="<?= base_url('rota/nova') ?>" class="btn-primary">
    <svg class="w-4 h-4"><!-- plus icon --></svg>
    Novo item
  </a>
</div>

<!-- Content -->
<div class="card">
  <div class="card-header">
    <h2 class="text-sm font-semibold text-slate-900">Lista</h2>
  </div>
  <div class="overflow-x-auto">
    <table class="table-base">
      ...
    </table>
  </div>
  <div class="card-footer flex items-center justify-between text-xs text-slate-400">
    <!-- Paginação -->
  </div>
</div>
<?= $this->endSection() ?>
```

---

## 10. Google SSO — Configuração e regras de domínio

### Domínio autorizado
Apenas contas `@fatecjahu.edu.br` (Google for Education) podem usar o SSO.
Usuários novos são criados automaticamente com `role_requester` na primeira autenticação.

### Configuração de credenciais
1. [Google Cloud Console](https://console.cloud.google.com) → APIs & Services → Credentials
2. Criar **OAuth 2.0 Client ID** (tipo: Web application)
3. Em *Authorized redirect URIs* adicionar `http://localhost:8080/auth/google/callback` (dev) e a URL de produção
4. Preencher no `.env`:
```ini
GOOGLE_CLIENT_ID     = seu-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET = seu-client-secret
GOOGLE_REDIRECT_URI  = http://localhost:8080/auth/google/callback
```

### Configuração na institution (DB)
```json
{
  "auth": {
    "sso_google_enabled": true,
    "sso_allowed_domains": ["fatecjahu.edu.br"],
    "local_login_enabled": true
  }
}
```

### Fluxo de criação de conta via SSO
- **Já existe conta com mesmo Google ID**: atualiza nome e avatar
- **Já existe conta com mesmo e-mail**: vincula Google ID à conta existente
- **Usuário novo**: cria conta com `role_requester`, `is_active = 1`
- **Domínio não autorizado**: rejeita com mensagem de erro
- **Conta inativa**: rejeita mesmo se o domínio for válido

---

## 11. Status de reservas — mapeamento visual

| Status | Badge class | Cor de fundo no calendário |
|---|---|---|
| `pending` | `badge-pending` | `#FEF3C7` (amber-100) |
| `approved` | `badge-approved` | `#D1FAE5` (emerald-100) |
| `rejected` | `badge-rejected` | `#FEE2E2` (red-100) |
| `cancelled` | `badge-cancelled` | `#F1F5F9` (slate-100) |
| `absent` | `badge-absent` | `#FEF3C7` (amber-100) |
