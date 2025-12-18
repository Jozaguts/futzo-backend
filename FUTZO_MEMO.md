# Futzo — Memo del proyecto y backlog vivo (v0.1)

**Última actualización:** 2025-12-17
**Stack:** Laravel (API REST) + Laravel Sanctum · MySQL/MariaDB · Stripe (Cashier) · Spatie (Permissions/Media Library) · Excel (Maatwebsite/PhpSpreadsheet)

Nota: El frontend (Nuxt SPA) no está en este repo; este memo refleja el backend actual y su contrato API para la SPA.

---

## 1) Contexto (resumen ejecutivo)

- Las ligas requieren: registro de clubes/deportistas/árbitros, calendarización, comunicación, estadísticas y operación end-to-end.
- Excel/Word no escalan; apostamos por un SaaS multi‑liga con calendario automático, estadísticas y cobro por suscripción.
- Multi‑tenancy lógico por Liga mediante scopes (LeagueScope).

## 2) Módulos existentes (validados con tests)

- Autenticación y verificación: registro con OTP (email/WhatsApp/Twilio Verify), login (Sanctum).
- Onboarding: checklists y hints contextuales (crear liga → sugerir sedes/campos) sin bloqueo; el usuario puede saltar la captura de ubicaciones y aún así crear torneos y generar calendarios desde el día 0.
- Ligas: creación y asociación automática al usuario, estado sincronizado con facturación (ready/suspended) según suscripción o trial DB.
- Ubicaciones y canchas: CRUD; `locations` aportan metadatos/direcciones pero no son requisito para calendarizar; la disponibilidad real vive en `fields` + `league_fields` + ventanas (FieldWindows + LeagueFieldWindows).
- Torneos: CRUD, configuración, asociación de ubicaciones/canchas, exportaciones (standing/stats/schedule por ronda).
- Calendarización automática (v1): generación de calendario para formato liga y grupos/eliminación; evita solapes; pruebas de sugerencias de bracket y confirmación.
- Juegos: reprogramación con validación de ventanas efectivas y no‑solape; eventos (goles/tarjetas/cambios), lineups, completar partido.
- Equipos y jugadores: CRUD, alta masiva de jugadores vía Excel (plantilla .xlsx), descargas de plantillas.
- Dashboard: métricas básicas (siguientes/últimos juegos, conteos por periodo).
- Facturación/operación: middleware `billing.operational` (402 cuando no hay trial/suscripción), flujo de checkout Stripe y listeners de webhook.

## 3) Roadmap por fases (ajustado a lo ya construido)

- Fase 1 (gran parte implementada):
  - Calendarización automática (hecha) y reprogramación sin solapes (hecha).
  - Gestión de equipos/jugadores/cuerpo técnico (hecha; jugador con rol automático "jugador").
  - Estadísticas de juego y exportes (parcial: modelos y endpoints básicos; continuar consolidación y vistas/consultas agregadas).
  - Notificaciones de partidos (pendiente: correos previos/post partido y cambios de horario).

- Fase 2:
  - Suspensión de jugadores (parametrizable por reglas); hoy existe modelo `Penalty`, falta orquestación y políticas.
  - Notificaciones push/websocket en tiempo real (pendiente; hoy solo email/OTP/WhatsApp).
  - Fichas virtuales enriquecidas (pendiente UI; backend expone datos base).
  - Pagos: mejorar conciliación y planes/variantes; ampliar métodos (Stripe ok, evaluar MercadoPago/Conekta si aplica).

## 4) Modelo de datos de sedes/canchas (real)

- `locations` (SoftDeletes): sedes físicas con tags (Spatie Tags) y `fields` relacionados. Ahora son opcionales para programar; sirven como metadata/dirección cuando existen, pero los partidos pueden quedar con `location_id` null o “por confirmar”.
- `fields` (SoftDeletes): canchas (físicas o virtuales) con tipo/dimensiones por defecto; pueden existir sin `location_id` mientras haya ventanas base (24/7 iniciales).
- `league_location` (pivot SoftDeletes: `LeagueLocation`): ligas ↔ sedes (para casos donde sí hay direcciones).
- `league_fields`: ligas ↔ canchas; define las ventanas efectivas por liga‑campo en `league_field_windows` y es la verdadera fuente de disponibilidad para calendarizar.
- `location_tournament` (pivot SoftDeletes: `LocationTournament`): torneos ↔ sedes (si la liga quiere filtrar canchas por torneo).
- `tournament_fields`: relación explícita de canchas usadas por torneo.
- `tournament_field_reservations`: reservas/blackouts por torneo y liga‑campo (resta disponibilidad efectiva).
- Juegos (`games`) referencian `field_id`; `location_id` solo se rellena cuando existe una sede asociada o se define un fallback manual.

Ventanas efectivas por campo (para agendar): FieldWindows ∩ LeagueFieldWindows − TournamentFieldReservations. Al no depender de `locations`, podemos programar tan pronto existan `fields` con ventanas válidas, incluso mientras la liga termina de capturar sus sedes reales.

## 5) Calendarización automática (implementado v1)

- Servicios
  - `ScheduleGeneratorService`: genera fixtures (liga, grupos, eliminación); calcula duración efectiva (tiempo de juego + descanso global + gap admin + buffer) y asigna a `field_id` evitando colisiones. Opera directamente sobre `league_fields`, por lo que ya no depende de `locations` para la disponibilidad.
  - `AvailabilityService`: produce ventanas semanales efectivas (por liga‑campo) y genera slots discretizados sin importar si la cancha tiene sede asociada o es virtual.

- Capacidades actuales
  - Liga (ida/vuelta) y fase de grupos con entrelazado de jornadas por grupo.
  - Eliminación (octavos/cuartos/semis/final) con toma de clasificados desde standings.
  - Validación de no‑solape por campo/fecha y ajuste a ventanas efectivas (liga y reservas de torneo).
  - Reprogramación de partidos con verificación granular de ventanas y colisiones.
  - Generación de partidos incluso si no hay `locations` creadas; el sistema llena `location_id` cuando existe y, de lo contrario, deja el campo vacío/por confirmar sin frenar el calendario.

- Endpoints clave
  - `POST /api/v1/admin/tournaments/{t}/schedule` (genera calendario)
  - `PUT  /api/v1/admin/games/{game}/reschedule` (reprograma con validación)
  - Bracket: `GET /api/v1/admin/tournaments/{t}/bracket/preview`, `POST /api/v1/admin/tournaments/{t}/bracket/confirm`, `GET /api/v1/admin/tournaments/{t}/bracket/suggestions`

- Tests: `ScheduleGenerationTest`, `BracketTest`, `BracketTableFormatTest`, `BracketSuggestionsTest`.

## 6) Roles y vistas (estado real)

- Roles sembrados (Spatie): `super administrador`, `administrador`, `dueño de equipo`, `entrenador`, `jugador`, `arbitro`, `personal administrativo de liga`, `aficionado`, `predeterminado`.
- Registro asigna `predeterminado` y `status=pending_onboarding`.
- Jugador: al crear via builder se asigna `jugador` automáticamente.
- Admin liga: hoy se asigna `administrador` al completar checkout (webhook Stripe). Sugerido: promover al crear su primera liga (pendiente de implementar).
- Gates: `Gate::before` permite todo a `super administrador`. Rutas admin bajo `billing.operational` (trial/suscripción activa).

## 7) Integraciones y seguridad

- Auth: Laravel Sanctum (SPA), OTP por email/WhatsApp; Twilio Verify para códigos.
- Autorización: Spatie Permission + políticas específicas (ej. TournamentPolicy) y LeagueScope para isolar por liga.
- Medios: Spatie Media Library (usuarios/torneos) con conversions.
- Pagos: Stripe (Cashier). Middleware `EnsureOperationalForBilling` devuelve 402 con `checkout_url` cuando no operativo.
- Webhooks: Spatie Webhook Client configurado para Stripe. Listeners: asignación de rol admin y programación de precios intro.
- Importación: Maatwebsite Excel + PhpSpreadsheet (plantillas/exportación de jugadores y lectura .xlsx).

## 8) Endpoints REST (principales)

Prefijo común admin: `/api/v1/admin` (con `auth:sanctum` + `billing.operational`)

- Auth (público): `/api/v1/auth/register`, `/api/v1/auth/login`, `/api/v1/verify` y endpoints OTP/reset.
- Checkout: `GET /api/v1/checkout` (Stripe) con `checkout.eligibility`; `POST /api/v1/post-checkout-login`.
- Ligas: `GET/POST /api/v1/admin/leagues`, `GET /api/v1/admin/leagues/{league}/tournaments`, `GET /api/v1/admin/leagues/football/types`, `GET /api/v1/admin/leagues/locations`.
- Ubicaciones: `GET/POST/PUT/DELETE /api/v1/admin/locations`, `GET /api/v1/admin/locations/fields?location_ids=...`.
- Torneos: `GET/POST/PUT /api/v1/admin/tournaments`, stats/standings/schedule/export, `POST /{t}/schedule`, `POST /{t}/locations`, update de fases/estatus.
- Games: detalles, reprogramación, completar, eventos (goles/tarjetas), sustituciones, formaciones.
- Equipos: listado/búsqueda públicos, plantilla CSV/XLSX, roster y alineación por defecto, próximos/últimos juegos.
- Jugadores: `GET /players`, `POST /players` (registro), `POST /players/import` (Excel), descarga de plantilla.
- Público torneos: `GET /api/v1/tournaments/{slug}/can-register`, `GET /.../registrations/catalogs`, `POST /.../pre-register-team`.

Notas: El listado exacto aparece en `routes/*` y `php artisan route:list`. Varias rutas públicas deshabilitan `auth:sanctum` explícitamente.

## 9) Backlog inmediato (siguiente sprint)

1. Rol de administrador de liga al crear la primera liga (idempotente, sin sobreescribir super admin). Tests.
2. Notificaciones de partidos (previas/post y cambios de horario) por email y WhatsApp; plantillas y colas.
3. UI/API para gestión de ventanas por liga‑campo y reservas por torneo (hoy vía payload de LocationController; documentar y exponer endpoints dedicados si aplica).
4. Consolidar “estadísticas de juego” (consultas agregadas y endpoints de resumen por torneo/equipo/jugador; caching ligero).
5. Mejoras de calendarización: soporte multi‑TZ (por liga) y buffers configurables por liga/torneo.
6. Endpoints de administración de roles/staff de liga (asignar `personal administrativo de liga`).
7. Pulir onboarding: ahora que no bloqueamos al usuario por no tener sedes/canchas, definir checklists, nudges y estado `pending_onboarding` acorde para incentivar la captura de ubicaciones reales antes de publicar el calendario.
8. Auditoría básica (model events a tabla de logs) y políticas de retención/borrado de PII.

## 10) ADRs (decisiones actuales)

- ADR‑001: Disponibilidad separada en 3 niveles: FieldWindows (base), LeagueFieldWindows (liga), TournamentFieldReservations (reserva/blackout por torneo). Ventana efectiva = base ∩ liga − reservas.
- ADR‑002: Calendarización síncrona vía `ScheduleGeneratorService` detrás de endpoint; candidata a job en cola con idempotencia y reintentos.
- ADR‑003: Scope de liga obligatorio (LeagueScope) para Team/Tournament/Game/Player; nunca confiar en headers si hay sesión.

## 11) Métricas y logging

- DashboardStatsService: conteos/deltas de equipos y actividad reciente.
- Observers y logs: validaciones con aborts 422 legibles; logging en procesos de checkout/schedule.
- Herramientas: Horizon/Telescope incluidos (acceso restringido a super admin).

## 12) Preguntas abiertas

- ¿Promoción de rol admin al crear liga (sugerido) vs. tras checkout (actual)?
- Reglas de suspensión (acumulación tarjetas) por liga vs. global; relación con modelos existentes.
- Estrategia multi‑huso horario y locale por liga.

## 13) Changelog

- v0.2 (2025-12-17): Calendarización ya no depende de `locations`; `league_fields` y sus ventanas son la fuente única de disponibilidad, permitiendo crear torneos/juegos sin capturar sedes. Onboarding deja de bloquear pasos por falta de ubicaciones y se basa en checklists/nudges.
- v0.1 (2025-09-09): Documento inicial basado en código actual. Incluye calendarización v1, onboarding, roles, pagos y endpoints reales.

## 14) Checklist de contexto (para completar)

Negocio y operación
- [ ] Objetivo de la liga (competitiva, formativa, comercial).
- [ ] Formatos de torneo usados (round‑robin, grupos, ida/vuelta, eliminación, playoffs).
- [ ] Reglas de desempate (puntos, DIF goles, enfrentamiento directo, fair play, etc.).
- [ ] Categorías/ramas (varonil, femenil, mixto) y edades.
- [ ] Política de registros (fechas límite, costos, documentos requeridos).

Scheduling/Calendarización
- [ ] Ventanas por sede/cancha (ej. Lun‑Vie 18:00–22:00; Sáb‑Dom 8:00–20:00).
- [ ] Descanso mínimo entre partidos por equipo (mins/hrs/días).
- [ ] Restricciones de viaje/distancia (multi‑ciudad sí/no; radio km).
- [ ] Preferencias de equipos (días/horas vetadas, blackout dates).
- [ ] Capacidad por cancha y buffer de transición (min).
- [ ] Reglas de árbitros (carga equilibrada, conflictos, jerarquía por nivel).

Notificaciones y comunicación
- [ ] Canales (email, push web, WhatsApp/SMS*).
- [ ] Eventos que disparan notificación (alta de partido, reprogramación, resultado final, sanción, pago, etc.).
- [ ] Audiencias por evento (dueño de liga, dueños de equipo, jugadores, árbitros, staff).
- [ ] Contenido base de plantillas (asuntos, variables).

Datos y privacidad
- [ ] PII recolectada (nombre, email, foto, tel, salud?).
- [ ] Políticas de retención/consentimiento y borrado.
- [ ] Cumplimiento local (LFPDPPP MX) y mejores prácticas.

Pagos (planes)
- [ ] Proveedor preferido y monedas (Stripe actual; ¿otros?).
- [ ] Políticas de reembolsos y conciliación.

No funcionales
- [ ] Tenancy (multi‑liga en una instancia vs. aisladas).
- [ ] SLOs (p99 latencia, uptime), RPO/RTO.
- [ ] Entornos (dev/stg/prod), deploy (CI/CD), backups, monitoreo.

## 15) Plantillas rápidas

15.1 `project-context.yaml` (rellenar)

```yaml
business:
  league_name: ""
  objectives: ["competencia justa", "visibilidad", "ingresos"]
  categories: ["Libre varonil", "Femenil", "Sub-17"]
  tournament_formats:
    - name: "round_robin"
      legs: 1
      playoffs: false
      tiebreakers: ["points", "goal_diff", "goals_for", "head_to_head", "fair_play"]

scheduling:
  venues:
    - location: "Complejo A"
      fields:
        - name: "Cancha 1"
          type: "F11"
          windows: ["Mon-Fri 18:00-22:00", "Sat 09:00-19:00"]
          transition_buffer_min: 10
  team_prefs: { default_rest_hours: 48, max_daily_matches: 1 }
  referee_policy: { max_matches_per_day: 3, min_rest_min: 60 }

notifications:
  channels: ["email", "whatsapp"]
  events:
    match_scheduled: { to: ["team_owners", "players", "referees"], lead_time_h: 72 }
    match_rescheduled: { to: ["team_owners", "players", "referees"], immediate: true }
    match_final: { to: ["team_owners", "players"], include_stats: true }

privacy:
  pii: ["nombre", "email", "foto", "tel"]
  retention_policy: "eliminar jugadores inactivos >24 meses"

payments:
  provider: "Stripe"
  currency: "MXN"
```

15.2 Esquemas de importación

- jugadores.xlsx (plantilla disponible vía `GET /api/v1/admin/players/template`)
  - Columnas: nombre, apellido, correo, teléfono, fecha_nacimiento, nacionalidad, posición, numero, altura, peso, pie_dominante, notas_medicas

- equipos.csv (plantilla sugerida para futuro)
```
name,slug,city,colors,crest_url,coach_name,coach_email,category
```

- arbitros.csv (sugerido futuro)
```
first_name,last_name,email,phone,level,zones,available_windows
```

## 16) Comandos para extraer contexto del código

Backend (Laravel)
```
php artisan --version
php -v
composer show --format=json > composer-packages.json
php artisan route:list --json > routes.json
php artisan migrate:status > migrate-status.txt
php artisan test --testsuite=Feature --log-junit=phpunit-feature.xml
```

Datos (si aplica)
```
mysqldump --no-data futurzo_db > schema.sql
```

## 17) Matriz de notificaciones (plantilla)

```
- event: match_scheduled
  recipients: [team_owners, players, referees]
  channel: email
  timing: T-72h
  template_vars: [league, tournament, opponent, date, time, field]
- event: match_rescheduled
  recipients: [team_owners, players, referees]
  channel: email
  timing: immediate
  template_vars: [old_slot, new_slot, reason]
- event: match_final
  recipients: [team_owners, players]
  channel: email
  timing: T+10m
  template_vars: [score, stats]
- event: otp_send
  recipients: [user]
  channel: whatsapp | email
  timing: immediate
  template_vars: [code]
```

## 18) Reglas de suspensión (parametrización sugerida)

```
- code: YC_accumulation
  applies_to: yellow_cards
  threshold: 5
  penalty: { type: matches_ban, value: 1 }
  reset_policy: per_stage
- code: RC_direct
  applies_to: red_cards
  threshold: 1
  penalty: { type: matches_ban, value: 2 }
```

---

Anexos útiles
- LeagueScope asegura que, autenticado, se usa `league_id` del usuario y se ignoran headers.
- SoftDeletes presentes en la mayoría de entidades clave (Locations, Fields, Games, Tournaments, etc.).
- Eliminación de ubicaciones: sólo permitida si no hay partidos programados/en progreso/aplazados relacionados; aplica soft delete (tests en `LocationDeleteTest`).

## 19) Zona horaria por liga (implementación)

- Regla: almacenar todo en UTC y operar en TZ de la liga.
- Liga: `leagues.timezone` (IANA). Default `America/Mexico_City`. Nueva migración añadida.
- Entrada API:
  - Preferido: `starts_at` ISO‑8601 con TZ (ej. `2025-09-12T18:00:00-05:00`).
  - Si llega sin TZ, se interpreta en la TZ de la liga.
  - Back‑compat: se soporta el modo legado (`date` + `selected_time.start` + `day`).
- Persistencia de juegos:
  - Nuevas columnas: `games.starts_at_utc` y `games.ends_at_utc` (timestamps UTC).
  - `match_date`/`match_time` siguen representando la hora local de la liga para compatibilidad y ordenaciones locales.
- Scheduling:
  - `ScheduleGeneratorService` genera slots en TZ de la liga (Carbon con TZ de liga) y persiste `starts_at_utc`/`ends_at_utc`.
  - Ventanas efectivas siguen siendo por día y minutos locales (FieldWindows ∩ LeagueFieldWindows − TournamentReservations).
- UI (guía):
  - Mostrar TZ explícita junto a campos de fecha/hora y en listados.
  - Advertir si la TZ del dispositivo difiere de la TZ de la liga.
- DST: confiar en IANA (Carbon) para el corrimiento horario. Agregadas pruebas con cambio estacional (NY 2025‑03‑09).
