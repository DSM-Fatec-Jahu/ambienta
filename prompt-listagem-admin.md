# Prompt — Padrão de Listagem Admin com Paginação, Busca e Exportação

> Copie e cole este prompt inteiro, substituindo apenas o bloco **"CONFIGURAÇÃO DA TAREFA"** ao final.

---

## CONTEXTO DO PROJETO

Você é um desenvolvedor sênior trabalhando em um sistema CodeIgniter 4 (PHP 8.2) com o seguinte stack:

- **Frontend:** Alpine.js v3, Tailwind CSS + classes utilitárias customizadas
- **Classes CSS disponíveis:**
  `btn-primary`, `btn-secondary`, `btn-ghost`, `btn-danger`,
  `card`, `card-footer`, `form-input`, `form-label`,
  `table-base`, `badge-primary`, `badge-approved`, `badge-cancelled`, `badge-warning`, `badge-dot`,
  `empty-state`, `empty-state-icon`, `empty-state-title`, `empty-state-description`,
  `modal-overlay`, `modal-panel`, `modal-header`, `modal-body`, `modal-footer`,
  `page-header`, `page-title`
- **Exportação XLSX:** `PhpOffice\PhpSpreadsheet` (já instalado)
- **Exportação PDF:** `Dompdf\Dompdf` (já instalado)
- **CSS crítico:** `app.css` define `.form-input { padding: .5rem .75rem }` sem `!important`

---

## FLUXO DE TRABALHO

Antes de implementar qualquer coisa, siga este fluxo:

### PASSO 1 — Descoberta a partir da rota

A rota informada é: **`{ROTA}`** (ex: `admin/predios`)

1. Localize o controller mapeado para essa rota em `app/Config/Routes.php`
2. Leia o controller para identificar o model utilizado
3. Leia o model para conhecer: tabela, `$allowedFields`, joins existentes e métodos já implementados
4. Leia a view atual (`app/Views/admin/*/index.php`) para identificar as colunas exibidas na tabela e as ações disponíveis por linha

### PASSO 2 — Apresente um plano e aguarde confirmação

Com base na análise, apresente ao usuário:

**a) Filtros sugeridos** (baseados nos campos e joins encontrados no model/tabela):
```
Filtros identificados para implementar na toolbar:
  [ ] Busca textual em: <campo1>, <campo2>, ... (LIKE)
  [ ] Filtro por <FK ou campo enumerado>: <nome do select>
  [ ] Filtro de status: Ativo / Inativo / (outros encontrados)
  [ ] ... (outros que fizer sentido)

Confirme quais implementar ou ajuste conforme necessário:
```

**b) Colunas do XLSX/PDF sugeridas** (as mesmas colunas visíveis na tela, exceto a coluna de Ações):
```
Colunas para exportação (derivadas da view atual):
  [x] <coluna 1> → campo: <campo_no_banco>
  [x] <coluna 2> → campo: <campo_no_banco>
  ... (todas as colunas visíveis exceto Ações)

Confirme, remova ou adicione colunas:
```

**c) Resumo das ações da tabela** (botões por linha encontrados na view):
```
Ações mantidas (sem alteração):
  - <ação 1> (ex: Editar)
  - <ação 2> (ex: Excluir)
  - <ação N> (ex: botão customizado)
```

⚠️ **Não implemente nada ainda. Aguarde a resposta do usuário.**

---

### PASSO 3 — Implementação

Após confirmação, implemente os **5 artefatos** abaixo respeitando rigorosamente os padrões descritos.

---

## PADRÕES DE IMPLEMENTAÇÃO

### 1. Model — Métodos de busca paginada

Adicionar ao model existente (não reescrever métodos já presentes):

```php
public function search(int $institutionId, string $q, int $filtroId, int $status, int $limit, int $offset): array
{
    return $this->_searchQuery($institutionId, $q, $filtroId, $status)
        ->orderBy('<ordenação padrão>')
        ->limit($limit, $offset)
        ->get()->getResultArray();
}

public function searchCount(int $institutionId, string $q, int $filtroId, int $status): int
{
    return (int) $this->_searchQuery($institutionId, $q, $filtroId, $status)
        ->countAllResults();
}

private function _searchQuery(int $institutionId, string $q, int $filtroId, int $status): \CodeIgniter\Database\BaseBuilder
{
    $qb = $this->db->table('<tabela> t')
        ->select('t.*, <joins necessários>')
        ->join(...)  // joins existentes no model
        ->where('t.institution_id', $institutionId)
        ->where('t.deleted_at IS NULL');

    if ($q !== '') {
        $qb->groupStart()
            ->like('t.<campo1>', $q)
            ->orLike('t.<campo2>', $q)
            // ... campos definidos na confirmação
        ->groupEnd();
    }

    if ($filtroId > 0) { $qb->where('t.<fk_campo>', $filtroId); }

    // status: 0=todos, 1=ativo, 2=inativo, ...
    if ($status === 1)      { $qb->where('t.is_active', 1); }
    elseif ($status === 2)  { $qb->where('t.is_active', 0); }

    return $qb;
}
```

---

### 2. Controller — Novos métodos

Adicionar ao controller existente (manter métodos atuais intactos):

**`index()`** — simplificado, sem carregar registros:
```php
public function index(): string
{
    $institutionId = $this->institution['id'] ?? 0;
    // Carrega apenas dados necessários para os selects dos filtros
    return view('admin/<modulo>/index', $this->viewData([
        'pageTitle' => '<Título>',
        '<dados_filtros>' => ...,
    ]));
}
```

**`data()`** — endpoint JSON para a tabela lazy:
```php
public function data(): \CodeIgniter\HTTP\ResponseInterface
{
    $institutionId = $this->institution['id'] ?? 0;
    $page      = max(1, (int) ($this->request->getGet('page')    ?? 1));
    $q         = trim($this->request->getGet('q')                ?? '');
    $filtroId  = (int) ($this->request->getGet('<filtro>')       ?? 0);
    $status    = (int) ($this->request->getGet('status')         ?? 0);
    $limit     = in_array((int) ($this->request->getGet('limit') ?? 10), [10, 25, 50, 100])
                    ? (int) $this->request->getGet('limit') : 10;
    $offset    = ($page - 1) * $limit;

    $rows  = $this->model->search($institutionId, $q, $filtroId, $status, $limit, $offset);
    $total = $this->model->searchCount($institutionId, $q, $filtroId, $status);

    // Castar todos os tipos para que o JSON seja correto
    foreach ($rows as &$r) {
        $r['id']        = (int)  $r['id'];
        $r['is_active'] = (bool) $r['is_active'];
        // ... demais campos booleanos e inteiros
    }
    unset($r);

    return $this->response->setJSON([
        'data'  => $rows,
        'total' => $total,
        'page'  => $page,
        'pages' => (int) ceil($total / $limit),
        'limit' => $limit,
    ]);
}
```

**`exportXlsx()`**:
```php
public function exportXlsx(): \CodeIgniter\HTTP\ResponseInterface
{
    $institutionId = $this->institution['id'] ?? 0;
    $q        = trim($this->request->getGet('q')         ?? '');
    $filtroId = (int) ($this->request->getGet('<filtro>') ?? 0);
    $status   = (int) ($this->request->getGet('status')  ?? 0);

    $rows = $this->model->search($institutionId, $q, $filtroId, $status, 5000, 0);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet()->setTitle('<Título>');

    // Cabeçalho: azul #1E40AF, texto branco, bold
    $sheet->fromArray([/* colunas confirmadas */], null, 'A1');
    $sheet->getStyle('A1:<última_col>1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                   'startColor' => ['rgb' => '1E40AF']],
    ]);

    $row = 2;
    foreach ($rows as $r) {
        $sheet->fromArray([/* campos confirmados */], null, 'A' . $row);
        // Linhas pares: fundo #F8FAFC
        if ($row % 2 === 0) {
            $sheet->getStyle('A'.$row.':<última_col>'.$row)
                ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F8FAFC');
        }
        $row++;
    }

    foreach (range('A', '<última_col>') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    ob_start(); $writer->save('php://output'); $content = ob_get_clean();

    return $this->response
        ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->setHeader('Content-Disposition', 'attachment; filename="<modulo>_'.date('Y-m-d').'.xlsx"')
        ->setHeader('Cache-Control', 'max-age=0')
        ->setBody($content);
}
```

**`exportPdf()`**:
```php
public function exportPdf(): void
{
    $institutionId = $this->institution['id'] ?? 0;
    // (mesmos filtros do exportXlsx)
    $rows = $this->model->search($institutionId, $q, $filtroId, $status, 5000, 0);

    $html = view('admin/<modulo>/pdf_export', [
        'rows'        => $rows,
        'institution' => $this->institution,
        'generatedAt' => date('d/m/Y H:i'),
    ]);

    $options = new \Dompdf\Options();
    $options->setChroot(ROOTPATH);
    $options->setIsRemoteEnabled(false);

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream('<modulo>_'.date('Y-m-d').'.pdf', ['Attachment' => true]);
    exit;
}
```

---

### 3. Routes

Adicionar **antes** das rotas com `(:num)` dentro do group `admin`:

```php
$routes->get('<rota>/data',          'Admin\<Controller>::data');
$routes->get('<rota>/exportar-xlsx', 'Admin\<Controller>::exportXlsx');
$routes->get('<rota>/exportar-pdf',  'Admin\<Controller>::exportPdf');
```

---

### 4. View — `app/Views/admin/<modulo>/index.php`

#### Toolbar (respeitar esta estrutura exata)

```html
<div class="border-b border-slate-100 flex items-stretch">

  <!-- ESQUERDA: busca + filtros — overflow-x:auto para telas pequenas -->
  <div class="flex items-center gap-3 flex-1 overflow-x-auto p-4 min-w-0">

    <!-- Campo de busca: <label class="form-input"> com ícone e input como
         irmãos em flex. NÃO usar position:absolute + padding-left no <input>,
         pois form-input já tem padding próprio em app.css e conflita. -->
    <label class="flex items-center gap-0 form-input p-0 pl-2 text-sm cursor-text"
           style="flex:1 1 320px; min-width:220px; overflow:hidden;">
      <span class="flex items-center justify-center pl-3 pr-2 text-slate-400 flex-shrink-0 self-stretch"
            style="margin-left:15px">
        <!-- SVG lupa -->
      </span>
      <input type="text" x-model="filters.q" @input.debounce.350ms="goTo(1)"
             class="flex-1 h-full text-sm bg-transparent border-0 outline-none shadow-none py-0 px-0 pr-3"
             style="box-shadow:none; border:none; outline:none">
    </label>

    <!-- Selects de filtro: flex-shrink:0 + min-width explícito evita truncagem -->
    <select class="form-input text-sm flex-shrink-0" style="min-width:170px; width:auto"
            x-model="filters.<nome>" @change="goTo(1)">
      ...
    </select>

  </div>

  <!-- SEPARADOR vertical -->
  <div class="w-px bg-slate-100 self-stretch flex-shrink-0"></div>

  <!-- DIREITA: exports — SEM overflow, para que os dropdowns absolute não sejam clipados -->
  <div class="flex items-center gap-2 p-4 flex-shrink-0">

    <!-- Cada dropdown: botão + div absolute right-0 z-50 -->
    <!-- Dropdown Excel e Dropdown PDF, cada um com duas opções:
         "Exportar filtrado" e "Exportar tudo" -->

  </div>
</div>
```

#### Tabela

```html
<div class="overflow-x-auto">
  <table class="table-base">
    <thead>...</thead>
    <tbody>
      <!-- Linhas reais -->
      <template x-for="r in items" :key="r.id">
        <tr>...</tr>
      </template>

      <!-- Skeleton: 8 linhas animate-pulse enquanto loading e items vazio -->
      <template x-if="loading && items.length === 0">
        <template x-for="n in 8" :key="'sk'+n">
          <tr class="animate-pulse">
            <td><div class="h-4 bg-slate-100 rounded w-40"></div></td>
            ...
          </tr>
        </template>
      </template>

      <!-- Estado vazio -->
      <template x-if="!loading && items.length === 0">
        <tr><td colspan="<N>">
          <div class="empty-state py-12">
            <!-- Mensagem diferente se hasFilters() -->
          </div>
        </td></tr>
      </template>
    </tbody>
  </table>
</div>
```

#### Footer — paginação numérica

```html
<div class="card-footer flex flex-wrap items-center justify-between gap-4 px-4 py-3">

  <!-- Por página + contador -->
  <div class="flex items-center gap-2 text-sm text-slate-500">
    <label for="perPageSelect">Exibir</label>
    <!-- width:5rem e padding-right:2rem garantem espaço para número + seta nativa -->
    <select id="perPageSelect" x-model.number="perPage" @change="goTo(1)"
            class="form-input text-sm" style="width:5rem; padding-right:2rem">
      <option value="10">10</option>
      <option value="25">25</option>
      <option value="50">50</option>
      <option value="100">100</option>
    </select>
    <span>por página</span>
    <span class="text-slate-400 hidden sm:inline" x-show="total > 0" x-cloak
          x-text="rangeText()"></span>
  </div>

  <!-- Botões de página -->
  <nav x-show="pages > 1" x-cloak class="flex items-center gap-0.5">
    <button @click="goTo(1)"        :disabled="page===1"     class="pg-btn">⏮</button>
    <button @click="goTo(page-1)"   :disabled="page===1"     class="pg-btn">‹</button>

    <!-- ATENÇÃO: NÃO aninhar <template x-if> dentro de <template x-for> no Alpine.js v3.
         Usar um único <button> com :class ternário. -->
    <template x-for="(p, i) in visiblePages()" :key="i">
      <button
        x-text="p"
        :disabled="p === page || p === '…'"
        @click="typeof p === 'number' && goTo(p)"
        :class="p === page ? 'pg-btn-active' : p === '…' ? 'pg-ellipsis' : 'pg-btn'">
      </button>
    </template>

    <button @click="goTo(page+1)"   :disabled="page===pages" class="pg-btn">›</button>
    <button @click="goTo(pages)"    :disabled="page===pages" class="pg-btn">⏭</button>
  </nav>
</div>
```

#### CSS dos botões de paginação (incluir em `<?= $this->section('scripts') ?>`)

```css
.pg-btn {
  display:inline-flex; align-items:center; justify-content:center;
  min-width:2rem; height:2rem; padding:0 0.4rem;
  font-size:.8125rem; font-weight:500; border-radius:.375rem;
  color:#475569; background:transparent; border:1px solid transparent;
  transition:background 120ms,color 120ms; cursor:pointer; user-select:none;
}
.pg-btn:hover:not(:disabled) { background:#f1f5f9; color:#0f172a; border-color:#e2e8f0; }
.pg-btn:disabled              { opacity:.3; cursor:not-allowed; }
.pg-btn-active {
  display:inline-flex; align-items:center; justify-content:center;
  min-width:2rem; height:2rem; padding:0 0.4rem;
  font-size:.8125rem; font-weight:600; border-radius:.375rem;
  color:#fff; background:var(--color-primary,#3b82f6);
  border:1px solid transparent; cursor:default; user-select:none;
}
.pg-ellipsis {
  display:inline-flex; align-items:center; justify-content:center;
  min-width:1.5rem; height:2rem; font-size:.875rem;
  color:#94a3b8; cursor:default; pointer-events:none;
}
```

#### Alpine.js

```javascript
function <modulo>Page() {
  return {
    items:[], total:0, page:1, pages:1, perPage:10, loading:false,
    filters: { q:'', <filtro1>:'0', <filtro2>:'0' },
    // ... estado dos modais

    init() { this.fetchPage(); },

    async fetchPage() {
      this.loading = true; this.items = [];
      const params = new URLSearchParams({
        page: this.page, limit: this.perPage,
        q: this.filters.q,
        <filtro1>: this.filters.<filtro1>,
        <filtro2>: this.filters.<filtro2>,
      });
      try {
        const json = await (await fetch(`<?= base_url('admin/<rota>/data') ?>?${params}`)).json();
        this.items = json.data; this.total = json.total; this.pages = json.pages;
        if (this.page > this.pages && this.pages > 0) {
          this.page = this.pages; return this.fetchPage();
        }
      } catch(e) { console.error(e); } finally { this.loading = false; }
    },

    goTo(n) {
      this.page = Math.max(1, Math.min(n, this.pages || 1));
      this.fetchPage();
    },

    exportUrl(action, all = false) {
      if (all) return `<?= base_url('admin/<rota>/') ?>${action}`;
      const p = new URLSearchParams({ q: this.filters.q,
        <filtro1>: this.filters.<filtro1>, <filtro2>: this.filters.<filtro2> });
      return `<?= base_url('admin/<rota>/') ?>${action}?${p}`;
    },

    confirmDelete(id, name) {
      if (!confirm(`Excluir «${name}»?`)) return;
      const f = this.$refs.deleteForm;
      f.action = `<?= base_url('admin/<rota>/') ?>${id}/delete`;
      f.submit();
    },

    visiblePages() {
      const P = this.pages, p = this.page;
      if (P <= 7) return Array.from({ length: P }, (_, i) => i + 1);
      const arr = [1];
      if (p > 3) arr.push('…');
      const s = Math.max(2, p - 1), e = Math.min(P - 1, p + 1);
      for (let i = s; i <= e; i++) arr.push(i);
      if (p < P - 2) arr.push('…');
      arr.push(P); return arr;
    },

    hasFilters() {
      return this.filters.q !== '' || this.filters.<filtro1> !== '0' || ...;
    },

    rangeText() {
      if (!this.total) return '';
      const from = (this.page - 1) * this.perPage + 1;
      const to   = Math.min(this.page * this.perPage, this.total);
      return `${from}–${to} de ${this.total} registro${this.total !== 1 ? 's' : ''}`;
    },

    fmtDate(iso) {
      if (!iso) return '';
      const [, m, d] = iso.split('-'); return `${d}/${m}`;
    },
  }
}
```

---

### 5. View PDF — `app/Views/admin/<modulo>/pdf_export.php`

```html
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:DejaVu Sans,sans-serif; font-size:8pt; color:#1e293b; margin:1cm; }
  .header { margin-bottom:14px; border-bottom:2px solid #1E40AF; padding-bottom:10px; }
  .header h1 { font-size:13pt; color:#1e40af; }
  .header p  { font-size:7.5pt; color:#64748b; margin-top:3px; }
  table { width:100%; border-collapse:collapse; }
  thead th { background:#1e40af; color:#fff; padding:5px; text-align:left; font-size:7.5pt; }
  tbody tr:nth-child(even) { background:#f8fafc; }
  tbody td { padding:4px 5px; border-bottom:1px solid #e2e8f0; font-size:7.5pt; vertical-align:middle; }
  .badge { display:inline-block; padding:1px 6px; border-radius:3px; font-size:6.5pt; font-weight:bold; }
  .footer { margin-top:14px; font-size:7pt; color:#94a3b8; text-align:center; }
</style>
</head>
<body>
  <div class="header">
    <h1><?= esc($institution['name'] ?? 'Sistema') ?> — <Título do Relatório></h1>
    <p>Gerado em: <?= $generatedAt ?> &nbsp;|&nbsp; Total: <?= count($rows) ?> registro(s)</p>
  </div>
  <!-- tabela com os campos confirmados, badges inline, sem classes externas -->
  <div class="footer">Sistema — <?= $generatedAt ?></div>
</body>
</html>
```

---

## CONFIGURAÇÃO DA TAREFA

```
Rota: admin/SUBSTITUA_AQUI
```
