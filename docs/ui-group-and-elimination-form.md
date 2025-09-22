# UI reference: Group & Elimination phase form

Este documento describe un ejemplo de cómo estructurar la interfaz en Nuxt 3 + Vuetify para capturar las secciones `group_phase` y `elimination_phase` del payload del generador de calendarios. La idea es reutilizar la semántica de los *steps* existentes en `app/components/pages/torneos/calendario/stepper`, pero enfocándonos en los campos que alimentan estos bloques.

## Visión general

El formulario principal puede mantener un objeto reactivo `form` que refleje la estructura que consume el backend:

```ts
const form = reactive({
  group_phase: {
    option_id: null as string | null,
  },
  elimination_phase: {
    teams_to_next_round: 8,
    elimination_round_trip: true,
    phases: [] as TournamentPhasePayload[],
  },
});
```

El API de ajustes expone `group_configuration_options`, por lo que el step puede componer dos tarjetas:

1. **Selector de fase de grupos** – Permite elegir la distribución (ej. `6-5-4|8`).
2. **Configurador de eliminatoria** – Habilita/deshabilita fases y ajusta reglas.

A continuación se muestra un ejemplo de cada tarjeta.

## Componente `GroupPhaseCard`

```vue
<template>
  <VCard :loading="loading">
    <VCardTitle>Fase de grupos</VCardTitle>
    <VCardText class="d-flex flex-column ga-4">
      <VSelect
        v-model="localValue"
        :items="options"
        :item-title="optionLabel"
        item-value="id"
        label="Distribución de grupos"
        hint="Selecciona cómo se reparten los equipos"
        persistent-hint
        clearable
      />

      <VAlert
        v-if="selectedOption"
        type="info"
        variant="tonal"
        :title="`Se generan ${selectedOption.groups} grupos`"
      >
        <p class="mb-2">
          <strong>Tamaños:</strong>
          {{ selectedOption.group_sizes.join(' / ') }}
        </p>
        <p class="mb-0">
          <strong>Clasifican:</strong>
          {{ selectedOption.group_phase.advance_top_n }} por grupo
          <span v-if="selectedOption.group_phase.include_best_thirds">
            + {{ selectedOption.group_phase.best_thirds_count }} mejores terceros
          </span>
          → {{ selectedOption.elimination.label }}
        </p>
      </VAlert>
    </VCardText>
  </VCard>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface GroupOption {
  id: string;
  groups: number;
  group_sizes: number[];
  group_phase: {
    teams_per_group: number;
    advance_top_n: number;
    include_best_thirds: boolean;
    best_thirds_count: number | null;
    group_sizes: number[];
  };
  elimination: {
    teams: number;
    label: string;
    phase_name: string;
  };
}

const props = defineProps<{
  modelValue: string | null;
  options: GroupOption[];
  loading?: boolean;
}>();

const emit = defineEmits(['update:modelValue']);

const localValue = computed({
  get: () => props.modelValue,
  set: value => emit('update:modelValue', value),
});

const selectedOption = computed(() =>
  props.options.find(option => option.id === props.modelValue) || null,
);

const optionLabel = (option: GroupOption) =>
  `${option.id.replace('|', ' → ')} · ${option.groups} grupos`;
</script>
```

Este componente expone un `v-model` simple (`option_id`) que se envía tal cual al backend. Al limpiar la selección se pueden mostrar validaciones para evitar enviar `group_phase` vacío cuando el formato requiere grupos.

## Componente `EliminationPhaseCard`

```vue
<template>
  <VCard>
    <VCardTitle>Fase eliminatoria</VCardTitle>
    <VCardText class="d-flex flex-column ga-6">
      <div class="d-flex flex-wrap ga-4">
        <VSelect
          class="flex-1-0"
          v-model="local.teams"
          :items="bracketSizes"
          label="Equipos que avanzan"
          hint="Debe coincidir con la opción de grupos seleccionada"
          persistent-hint
        />
        <VSwitch
          v-model="local.roundTrip"
          label="Partidos de ida y vuelta"
        />
      </div>

      <VExpansionPanels>
        <VExpansionPanel
          v-for="phase in local.phases"
          :key="phase.id"
        >
          <VExpansionPanelTitle>
            <div class="d-flex align-center justify-space-between w-100">
              <div>
                <span class="text-subtitle-1">{{ phase.name }}</span>
                <VChip
                  v-if="phase.is_active"
                  class="ml-2"
                  size="x-small"
                  color="success"
                  variant="flat"
                >Activa</VChip>
              </div>
              <VSwitch
                v-model="phase.is_active"
                inset
                :label="phase.is_active ? 'Activa' : 'Inactiva'"
              />
            </div>
          </VExpansionPanelTitle>
          <VExpansionPanelText>
            <div class="d-flex flex-wrap ga-4">
              <VSwitch v-model="phase.rules.round_trip" label="Ida y vuelta" />
              <VSwitch v-model="phase.rules.away_goals" label="Gol de visitante" />
              <VSwitch v-model="phase.rules.extra_time" label="Tiempo extra" />
              <VSwitch v-model="phase.rules.penalties" label="Penales" />
              <VSelect
                v-model="phase.rules.advance_if_tie"
                :items="tieBreakers"
                label="Avanza si persiste el empate"
              />
            </div>
          </VExpansionPanelText>
        </VExpansionPanel>
      </VExpansionPanels>
    </VCardText>
  </VCard>
</template>

<script setup lang="ts">
import { computed, reactive, watch } from 'vue';

interface PhaseRule {
  round_trip: boolean;
  away_goals: boolean;
  extra_time: boolean;
  penalties: boolean;
  advance_if_tie: 'better_seed' | 'penalties' | 'away_goals';
}

interface EliminationPhase {
  id: number;
  name: string;
  is_active: boolean;
  is_completed: boolean;
  tournament_id: number;
  rules: PhaseRule;
}

const defaultRules: PhaseRule = {
  round_trip: true,
  away_goals: false,
  extra_time: true,
  penalties: true,
  advance_if_tie: 'better_seed',
};

const props = defineProps<{
  modelValue: {
    teams_to_next_round: number;
    elimination_round_trip: boolean;
    phases: EliminationPhase[];
  };
  bracketSizes: number[];
}>();

const emit = defineEmits(['update:modelValue']);

const local = reactive({
  get teams() {
    return props.modelValue.teams_to_next_round;
  },
  set teams(value: number) {
    emit('update:modelValue', {
      ...props.modelValue,
      teams_to_next_round: value,
    });
  },
  get roundTrip() {
    return props.modelValue.elimination_round_trip;
  },
  set roundTrip(value: boolean) {
    emit('update:modelValue', {
      ...props.modelValue,
      elimination_round_trip: value,
    });
  },
  get phases() {
    return props.modelValue.phases;
  },
});

watch(
  () => props.modelValue.phases,
  phases => {
    phases.forEach(phase => {
      phase.rules = phase.rules ?? { ...defaultRules };
    });
  },
  { immediate: true },
);

const tieBreakers = computed(() => [
  { value: 'better_seed', title: 'Mejor sembrado' },
  { value: 'away_goals', title: 'Gol de visitante' },
  { value: 'penalties', title: 'Penales directos' },
]);
</script>
```

Este panel asume que el backend siempre devolverá reglas (o que se completan con `defaultRules`). El `watch` garantiza que cada fase tenga un bloque `rules` antes de que el usuario interactúe.

## Integración dentro del *stepper*

```vue
<GroupPhaseCard
  v-model="form.group_phase.option_id"
  :options="groupOptions"
  :loading="isLoadingOptions"
/>

<EliminationPhaseCard
  v-model="form.elimination_phase"
  :bracket-sizes="[32, 16, 8, 4, 2]"
/>
```

- `groupOptions` se obtiene de `group_configuration_options` al cargar `/schedule/settings`.
- `form.elimination_phase.phases` se llena con la respuesta de `phases`, sustituyendo `rules = defaultRules` si vienen en `null`.
- Al enviar el formulario, bastará con serializar `form.group_phase` y `form.elimination_phase` junto con el resto de secciones existentes.

## Validaciones recomendadas

1. **Consistencia entre grupos y eliminatoria** – El `teams_to_next_round` debe coincidir con la opción elegida (puedes leer `selectedOption.elimination.teams`).
2. **Obligatoriedad de `option_id`** cuando el formato del torneo requiere fase de grupos.
3. **Reglas completas** – Garantiza que cada fase incluya todas las llaves esperadas (`round_trip`, `away_goals`, etc.) antes de enviar el payload.

Con esta estructura, el usuario selecciona una combinación de grupos balanceada y ajusta las rondas eliminatorias sin salir del flujo habitual del stepper.
