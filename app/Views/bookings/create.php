<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>

<style>[x-cloak]{display:none!important}</style>

<div class="page-header">
  <div>
    <h1 class="page-title">Nova Reserva</h1>
    <p class="page-subtitle">Encontre e reserve um ambiente para sua atividade</p>
  </div>
  <a href="<?= base_url('reservas') ?>" class="btn-secondary">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
    </svg>
    Voltar
  </a>
</div>

<?php if (empty($rooms)): ?>
  <div class="card">
    <div class="empty-state">
      <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
      </svg>
      <p class="empty-state-title">Nenhum ambiente disponível</p>
      <p class="empty-state-description">Não há ambientes cadastrados e ativos no momento. Entre em contato com a administração.</p>
    </div>
  </div>
<?php else: ?>

<div x-data="bookingWizard()" x-cloak>

  <!-- Stepper breadcrumb -->
  <div class="flex items-center gap-2 mb-6 text-xs select-none">
    <div class="flex items-center gap-1.5">
      <span :class="step >= 1 ? 'bg-primary text-white' : 'bg-slate-200 text-slate-500'"
            class="h-5 w-5 rounded-full flex items-center justify-center font-semibold text-2xs">1</span>
      <span :class="step >= 1 ? 'text-primary font-semibold' : 'text-slate-400'">Pesquisar</span>
    </div>
    <svg class="w-4 h-4 text-slate-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <div class="flex items-center gap-1.5">
      <span :class="step >= 2 ? 'bg-primary text-white' : 'bg-slate-200 text-slate-500'"
            class="h-5 w-5 rounded-full flex items-center justify-center font-semibold text-2xs">2</span>
      <span :class="step >= 2 ? 'text-primary font-semibold' : 'text-slate-400'">Escolher sala</span>
    </div>
    <svg class="w-4 h-4 text-slate-300 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <div class="flex items-center gap-1.5">
      <span :class="step >= 3 ? 'bg-primary text-white' : 'bg-slate-200 text-slate-500'"
            class="h-5 w-5 rounded-full flex items-center justify-center font-semibold text-2xs">3</span>
      <span :class="step >= 3 ? 'text-primary font-semibold' : 'text-slate-400'">Detalhes</span>
    </div>
  </div>

  <!-- ══ STEP 1: Search ══════════════════════════════════════════════════════ -->
  <div x-show="step === 1">
    <div class="max-w-2xl">
      <div class="card">
        <div class="card-header">
          <h2 class="text-sm font-semibold text-slate-900">Quando você precisa do ambiente?</h2>
        </div>
        <div class="card-body space-y-5">

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="form-label form-label-required">Data</label>
              <input type="date" x-model="searchDate"
                     min="<?= date('Y-m-d') ?>"
                     class="form-input">
            </div>
            <div>
              <label class="form-label form-label-required">Início</label>
              <input type="time" x-model="searchStart" step="1800" class="form-input">
            </div>
            <div>
              <label class="form-label form-label-required">Término</label>
              <input type="time" x-model="searchEnd" step="1800" class="form-input">
            </div>
          </div>

          <p x-show="searchError" x-cloak class="form-error" x-text="searchError"></p>

        </div>
        <div class="card-footer">
          <button type="button"
                  @click="searchRooms()"
                  :disabled="loading || !searchDate || !searchStart || !searchEnd"
                  class="btn-primary"
                  :class="(loading || !searchDate || !searchStart || !searchEnd) ? 'opacity-60 cursor-not-allowed' : ''">
            <!-- Search icon -->
            <svg x-show="!loading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
            </svg>
            <!-- Spinner -->
            <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span x-text="loading ? 'Buscando...' : 'Buscar Salas Disponíveis'"></span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ STEP 2: Results ═════════════════════════════════════════════════════ -->
  <div x-show="step === 2" x-cloak>

    <!-- Context bar -->
    <div class="flex flex-wrap items-center gap-3 mb-4">
      <button type="button" @click="step = 1"
              class="btn-ghost p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 flex items-center gap-1.5 text-sm">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Modificar busca
      </button>
      <div class="h-4 w-px bg-slate-200"></div>
      <p class="text-sm text-slate-600">
        <span class="font-semibold text-slate-900" x-text="availableRooms.length"></span>
        sala<span x-text="availableRooms.length !== 1 ? 's' : ''"></span>
        disponível<span x-text="availableRooms.length !== 1 ? 'is' : ''"></span> para
        <span class="font-medium" x-text="formatDate(searchDate)"></span> ·
        <span class="font-medium" x-text="searchStart + ' – ' + searchEnd"></span>
      </p>
      <!-- Re-search spinner -->
      <div x-show="loading" x-cloak class="flex items-center gap-1.5 text-xs text-slate-400 ml-auto">
        <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Atualizando...
      </div>
    </div>

    <!-- Equipment filter panel -->
    <div x-show="availableEquipment.length > 0" x-cloak class="mb-5">
      <div class="card">
        <div class="card-body">
          <div class="flex flex-wrap items-center gap-3 mb-3">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
              </svg>
              <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Filtrar por equipamento</h3>
            </div>
            <span x-show="equipmentFilter.length > 0" x-cloak
                  class="badge-primary text-2xs"
                  x-text="equipmentFilter.length + ' selecionado(s)'"></span>
            <button type="button"
                    x-show="equipmentFilter.length > 0" x-cloak
                    @click="equipmentFilter = []; searchRooms()"
                    class="text-xs text-slate-400 hover:text-slate-700 ml-auto underline underline-offset-2">
              Limpar filtro
            </button>
          </div>

          <div class="flex flex-wrap gap-2">
            <template x-for="eq in availableEquipment" :key="eq.id">
              <label :class="equipmentFilter.includes(eq.id)
                               ? 'ring-2 ring-primary bg-primary-light text-primary'
                               : eq.available_qty === 0
                                 ? 'ring-1 ring-slate-100 bg-slate-50 opacity-50 cursor-not-allowed'
                                 : 'ring-1 ring-slate-200 hover:ring-primary/50 cursor-pointer'"
                     class="flex items-center gap-2 px-3 py-1.5 rounded-xl transition-all select-none">
                <input type="checkbox"
                       :value="eq.id"
                       :disabled="eq.available_qty === 0"
                       x-model="equipmentFilter"
                       @change="searchRooms()"
                       class="sr-only">
                <span class="text-sm font-medium" x-text="eq.name"></span>
                <span :class="eq.available_qty > 0 ? 'bg-emerald-100 text-success' : 'bg-slate-100 text-slate-400'"
                      class="text-2xs font-semibold px-1.5 py-0.5 rounded-full whitespace-nowrap"
                      x-text="eq.available_qty + ' disp.'"></span>
              </label>
            </template>
          </div>

          <p x-show="equipmentFilter.length > 0" x-cloak class="text-xs text-slate-400 mt-2.5 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Exibindo apenas salas com empréstimo de equipamentos habilitado
          </p>
        </div>
      </div>
    </div>

    <!-- Empty result -->
    <div x-show="availableRooms.length === 0 && !loading" x-cloak class="card">
      <div class="empty-state">
        <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="empty-state-title">Nenhuma sala disponível</p>
        <p class="empty-state-description"
           x-text="equipmentFilter.length > 0
             ? 'Nenhuma sala com empréstimo de equipamentos disponível para este filtro. Tente remover alguns itens do filtro.'
             : 'Todos os ambientes estão ocupados neste horário. Tente outro dia ou intervalo de tempo.'">
        </p>
        <div class="flex gap-2 mt-4">
          <button type="button" x-show="equipmentFilter.length > 0" x-cloak
                  @click="equipmentFilter = []; searchRooms()"
                  class="btn-secondary">
            Limpar filtro
          </button>
          <button type="button" @click="step = 1" class="btn-secondary">Alterar busca</button>
        </div>
      </div>
    </div>

    <!-- Room cards grid -->
    <div x-show="availableRooms.length > 0"
         class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
         :class="loading ? 'opacity-50 pointer-events-none' : ''">
      <template x-for="room in availableRooms" :key="room.id">
        <div @click="selectRoom(room)"
             class="card cursor-pointer hover:ring-2 hover:ring-primary hover:shadow-card-hover transition-all group">
          <div class="card-body flex flex-col gap-2 h-full">

            <div class="flex items-start justify-between gap-2">
              <p class="text-sm font-semibold text-slate-900 leading-snug group-hover:text-primary transition-colors"
                 x-text="room.name"></p>
              <span x-show="room.code"
                    class="badge-primary text-2xs flex-shrink-0 mt-0.5"
                    x-text="room.code"></span>
            </div>

            <p class="text-xs text-slate-500 -mt-1"
               x-show="room.building_name"
               x-text="room.building_name + (room.floor ? ' · ' + room.floor : '')"></p>

            <div class="flex flex-wrap items-center gap-3 mt-1">
              <span x-show="room.capacity > 0"
                    class="text-xs text-slate-400 flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857
                       M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857
                       m0 0a5.002 5.002 0 019.288 0"/>
                </svg>
                <span x-text="room.capacity + ' pessoas'"></span>
              </span>

              <!-- Equipment lending badge + view button -->
              <div x-show="room.allows_equipment_lending" class="flex items-center gap-2">
                <span class="text-xs text-success flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  Emp. equipamentos
                </span>
                <button type="button"
                        @click.stop="equipModalOpen = true"
                        class="text-xs text-blue-500 hover:text-blue-600 flex items-center gap-0.5 font-medium">
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7
                         -1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                  Ver
                </button>
              </div>
            </div>

            <div class="mt-auto pt-3 border-t border-slate-100 flex items-center justify-end gap-1
                        text-xs font-medium text-primary group-hover:gap-2 transition-all">
              Reservar esta sala
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>

          </div>
        </div>
      </template>
    </div>

  </div>

  <!-- ══ Equipment modal ════════════════════════════════════════════════════ -->
  <div x-show="equipModalOpen" x-cloak
       class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
       @click="equipModalOpen = false"
       x-transition:enter="transition duration-150"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition duration-100"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-modal"
         @click.stop
         x-transition:enter="transition duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100">

      <div class="flex items-start justify-between px-5 pt-5 pb-3">
        <div>
          <h3 class="text-sm font-semibold text-slate-900">Equipamentos disponíveis</h3>
          <p class="text-xs text-slate-400 mt-0.5">
            <span x-text="formatDate(searchDate)"></span> · <span x-text="searchStart + ' – ' + searchEnd"></span>
          </p>
        </div>
        <button type="button" @click="equipModalOpen = false"
                class="btn-ghost p-1.5 text-slate-400 hover:text-slate-600 -mt-0.5 -mr-1">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <div class="px-5 pb-2 max-h-72 overflow-y-auto">
        <template x-if="availableEquipment.length === 0">
          <p class="text-sm text-slate-400 text-center py-6">Nenhum equipamento cadastrado.</p>
        </template>
        <div class="divide-y divide-slate-100">
          <template x-for="eq in availableEquipment" :key="eq.id">
            <div class="flex items-center justify-between py-2.5">
              <div class="min-w-0">
                <p class="text-sm font-medium text-slate-900 truncate" x-text="eq.name"></p>
                <p class="text-xs text-slate-400" x-show="eq.code" x-text="eq.code"></p>
              </div>
              <span :class="eq.available_qty > 0 ? 'badge-approved' : 'badge-cancelled'"
                    class="ml-3 flex-shrink-0"
                    x-text="eq.available_qty > 0 ? eq.available_qty + ' disp.' : 'Esgotado'"></span>
            </div>
          </template>
        </div>
      </div>

      <div class="px-5 py-3 border-t border-slate-100 bg-slate-50 rounded-b-2xl">
        <p class="text-xs text-slate-400">
          Equipamentos são recursos da instituição disponíveis em salas com empréstimo habilitado.
        </p>
      </div>
    </div>
  </div>

  <!-- ══ STEP 3: Booking form ════════════════════════════════════════════════ -->
  <div x-show="step === 3" x-cloak>
  <form method="POST" action="<?= base_url('reservas') ?>" @submit="return validateForm()">
    <?= csrf_field() ?>

    <!-- Hidden fields from wizard steps -->
    <input type="hidden" name="room_id"     :value="selectedRoom ? selectedRoom.id : ''">
    <input type="hidden" name="date"        :value="searchDate">
    <input type="hidden" name="start_time"  :value="searchStart">
    <input type="hidden" name="end_time"    :value="searchEnd">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- Left: form fields -->
      <div class="lg:col-span-2 space-y-4">

        <!-- Selected slot summary -->
        <div class="card border border-primary/25 bg-primary-light">
          <div class="card-body">
            <div class="flex items-start justify-between gap-4">
              <div class="space-y-1">
                <p class="text-xs text-slate-400 uppercase font-semibold tracking-wide">Ambiente selecionado</p>
                <div class="flex items-center gap-2 flex-wrap">
                  <p class="text-sm font-semibold text-slate-900" x-text="selectedRoom?.name"></p>
                  <span x-show="selectedRoom?.code"
                        class="badge-primary text-2xs"
                        x-text="selectedRoom?.code"></span>
                </div>
                <p class="text-xs text-slate-500"
                   x-show="selectedRoom?.building_name"
                   x-text="selectedRoom?.building_name + (selectedRoom?.floor ? ' · ' + selectedRoom.floor : '')"></p>
                <p class="text-xs text-slate-600 mt-1 flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  <span x-text="formatDate(searchDate)"></span>
                  <span class="text-slate-400">·</span>
                  <span x-text="searchStart + ' – ' + searchEnd"></span>
                </p>
              </div>
              <button type="button" @click="step = 2"
                      class="btn-ghost p-2 text-primary hover:bg-primary/10 hover:text-primary flex-shrink-0 text-xs font-medium flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Trocar sala
              </button>
            </div>
          </div>
        </div>

        <!-- Activity info -->
        <div class="card">
          <div class="card-header">
            <h2 class="text-sm font-semibold text-slate-900">Informações da atividade</h2>
          </div>
          <div class="card-body space-y-4">

            <div>
              <label for="bk_title" class="form-label form-label-required">Título / Finalidade</label>
              <input type="text" id="bk_title" name="title"
                     value="<?= esc(old('title')) ?>"
                     class="form-input" placeholder="Ex: Aula de Programação Web — Turma 3A"
                     maxlength="300" required>
              <p class="form-hint">Descreva brevemente a atividade que será realizada.</p>
            </div>

            <div>
              <label for="bk_desc" class="form-label">Descrição / Observações</label>
              <textarea id="bk_desc" name="description" rows="3"
                        class="form-input resize-none"
                        placeholder="Detalhes adicionais, necessidades especiais..."><?= esc(old('description')) ?></textarea>
            </div>

            <div>
              <label for="bk_attendees" class="form-label form-label-required">Número de participantes</label>
              <input type="number" id="bk_attendees" name="attendees_count"
                     value="<?= esc(old('attendees_count', 1)) ?>"
                     class="form-input" min="1" max="9999" required x-model="attendees">
              <p class="form-hint text-warning font-medium"
                 x-show="selectedRoom && selectedRoom.capacity > 0 && attendees > selectedRoom.capacity"
                 x-cloak>
                ⚠ Atenção: Este ambiente comporta <span x-text="selectedRoom?.capacity"></span> pessoas.
              </p>
            </div>

          </div>
        </div>

        <!-- Recurrence -->
        <div class="card" x-data="{ recurrence: '<?= esc(old('recurrence_type', 'none')) ?>' }">
          <div class="card-header">
            <h2 class="text-sm font-semibold text-slate-900">Recorrência <span class="text-slate-400 font-normal">(opcional)</span></h2>
          </div>
          <div class="card-body space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label for="bk_recurrence" class="form-label">Repetir reserva</label>
                <select id="bk_recurrence" name="recurrence_type" class="form-input" x-model="recurrence">
                  <option value="none">Não repetir</option>
                  <option value="weekly" <?= old('recurrence_type') === 'weekly' ? 'selected' : '' ?>>Semanalmente</option>
                  <option value="daily"  <?= old('recurrence_type') === 'daily'  ? 'selected' : '' ?>>Diariamente</option>
                </select>
                <p class="form-hint">A reserva será criada para cada data no período.</p>
              </div>
              <div x-show="recurrence !== 'none'" x-cloak>
                <label for="bk_recurrence_end" class="form-label form-label-required">Até a data</label>
                <input type="date" id="bk_recurrence_end" name="recurrence_end_date"
                       value="<?= esc(old('recurrence_end_date')) ?>"
                       :min="searchDate"
                       class="form-input"
                       :required="recurrence !== 'none'">
                <p class="form-hint text-amber-600">Feriados e conflitos serão ignorados automaticamente.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Book on behalf of another user (staff only) -->
        <?php if (!empty($forUsers)): ?>
        <div x-show="step >= 3" class="mt-4">
          <div class="card">
            <div class="card-header">
              <h2 class="text-sm font-semibold text-slate-900">Reservar em nome de <span class="text-slate-400 font-normal">(opcional)</span></h2>
            </div>
            <div class="card-body">
              <label for="for_user_id" class="form-label">Reservar em nome de</label>
              <select id="for_user_id" name="for_user_id" class="form-input">
                <option value="">— Para mim mesmo —</option>
                <?php foreach ($forUsers as $u): ?>
                  <option value="<?= $u['id'] ?>" <?= old('for_user_id') == $u['id'] ? 'selected' : '' ?>>
                    <?= esc($u['name']) ?> (<?= esc($u['email']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <p class="form-hint">Deixe em branco para reservar para você mesmo.</p>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Equipment (only if room allows) -->
        <?php if (!empty($equipment)): ?>
        <div class="card" x-show="selectedRoom && selectedRoom.allows_equipment_lending" x-cloak>
          <div class="card-header">
            <h2 class="text-sm font-semibold text-slate-900">Equipamentos <span class="text-slate-400 font-normal">(opcional)</span></h2>
          </div>
          <div class="card-body">
            <p class="form-hint mb-3">Selecione os equipamentos que deseja utilizar durante a reserva.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <?php foreach ($equipment as $eq): ?>
              <label x-data="{ checked: <?= in_array($eq['id'], (array) old('equipment_ids', [])) ? 'true' : 'false' ?> }"
                     :class="checked ? 'ring-2 ring-primary bg-primary-light' : 'ring-1 ring-slate-200'"
                     class="flex items-start gap-3 p-3 rounded-xl cursor-pointer transition-all">
                <input type="checkbox" name="equipment_ids[]" value="<?= $eq['id'] ?>"
                       class="mt-0.5 rounded border-slate-300 text-primary"
                       x-model="checked">
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-slate-900"><?= esc($eq['name']) ?></p>
                  <?php if ($eq['code']): ?>
                    <p class="text-xs text-slate-400"><?= esc($eq['code']) ?></p>
                  <?php endif; ?>
                  <div x-show="checked" x-cloak class="mt-2">
                    <label class="text-xs text-slate-500">Quantidade:</label>
                    <input type="number" name="equipment_qty_<?= $eq['id'] ?>"
                           min="1" max="<?= (int) $eq['quantity_total'] ?>"
                           value="<?= (int) old('equipment_qty_' . $eq['id'], 1) ?>"
                           class="form-input py-1 text-xs w-20 mt-0.5">
                  </div>
                </div>
                <span class="text-xs text-slate-400 whitespace-nowrap">Disp: <?= (int) $eq['quantity_total'] ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div><!-- /left col -->

      <!-- Right: sticky summary + submit -->
      <div>
        <div class="card sticky top-20">
          <div class="card-header">
            <h2 class="text-sm font-semibold text-slate-900">Resumo da Reserva</h2>
          </div>
          <div class="card-body space-y-3">

            <div>
              <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold">Ambiente</p>
              <p class="text-sm font-medium text-slate-900 mt-0.5" x-text="selectedRoom?.name"></p>
              <p class="text-xs text-slate-500"
                 x-show="selectedRoom?.building_name"
                 x-text="selectedRoom?.building_name + (selectedRoom?.floor ? ' · ' + selectedRoom.floor : '')"></p>
            </div>

            <div>
              <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold">Data</p>
              <p class="text-sm text-slate-700 mt-0.5" x-text="formatDate(searchDate)"></p>
            </div>

            <div>
              <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold">Horário</p>
              <p class="text-sm text-slate-700 mt-0.5" x-text="searchStart + ' – ' + searchEnd"></p>
            </div>

            <div x-show="attendees">
              <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold">Participantes</p>
              <p class="text-sm text-slate-700 mt-0.5" x-text="attendees + ' pessoa(s)'"></p>
            </div>

          </div>
          <div class="card-footer space-y-3">
            <div class="alert-info" role="alert">
              <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span class="text-xs">A reserva ficará <strong>pendente</strong> até ser aprovada pela equipe.</span>
            </div>
            <button type="submit" class="btn-primary w-full">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Solicitar Reserva
            </button>
          </div>
        </div>
      </div>

    </div><!-- /grid -->
  </form>
  </div><!-- /step 3 -->

</div><!-- /x-data -->

<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function bookingWizard() {
  const restore = <?= isset($restoreData) && $restoreData ? json_encode($restoreData) : 'null' ?>;

  return {
    step:        restore?.step        ?? 1,
    searchDate:  restore?.searchDate  ?? '',
    searchStart: restore?.searchStart ?? '',
    searchEnd:   restore?.searchEnd   ?? '',

    availableRooms:     [],
    availableEquipment: [],
    equipmentFilter:    [],
    loading:            false,
    searchError:        '',
    equipModalOpen:     false,

    selectedRoom: restore?.selectedRoom ?? null,
    attendees:    <?= old('attendees_count', 1) ?>,

    async searchRooms() {
      if (!this.searchDate || !this.searchStart || !this.searchEnd) {
        this.searchError = 'Preencha a data e o horário.';
        return;
      }
      if (this.searchStart >= this.searchEnd) {
        this.searchError = 'O término deve ser posterior ao início.';
        return;
      }
      this.loading     = true;
      this.searchError = '';
      try {
        const params = new URLSearchParams({
          date:       this.searchDate,
          start_time: this.searchStart,
          end_time:   this.searchEnd,
        });
        this.equipmentFilter.forEach(id => params.append('equipment_ids[]', id));

        const res  = await fetch(`<?= base_url('reservas/salas-disponiveis') ?>?${params}`);
        const data = await res.json();

        this.availableRooms     = data.rooms      || [];
        this.availableEquipment = data.equipment  || [];

        // Remove from filter any item that no longer has stock
        this.equipmentFilter = this.equipmentFilter.filter(id => {
          const eq = this.availableEquipment.find(e => e.id === id);
          return eq && eq.available_qty > 0;
        });

        if (this.step !== 2) this.step = 2;
      } catch {
        this.searchError = 'Erro ao buscar salas. Tente novamente.';
      } finally {
        this.loading = false;
      }
    },

    selectRoom(room) {
      this.selectedRoom = room;
      this.step = 3;
    },

    formatDate(dateStr) {
      if (!dateStr) return '';
      const [y, m, d] = dateStr.split('-');
      const days = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
      const day  = new Date(`${y}-${m}-${d}T12:00:00`).getDay();
      return `${days[day]}, ${d}/${m}/${y}`;
    },

    validateForm() {
      return true;
    },
  }
}
</script>
<?= $this->endSection() ?>
