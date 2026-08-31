# Ronda 4 — 50 usuarios básicos × 50 preguntas

Fecha: 2026-08-28

## Qué se generó

| | |
|---|---|
| Personas | **50** (perfiles: obra, conta, RH, compras, calidad, TI, jurídico, almacén, recepción…) |
| Preguntas por persona | **50** (chat continuo, tono WhatsApp, typos, sin folios) |
| Total dataset | **2500** |
| Archivos | `preguntas_bob_qa.json`, `personas_ronda4.json`, `PREGUNTAS_PARA_COPIAR.md` |
| Historial | Ronda 3 archivada en `history/` (no sobrescrita) |

Perfil del usuario simulado: **básico**, sin contexto amplio de jerarquía ni nombres exactos de procedimientos.

## Métrica live (muestra)

Se ejecutaron **8 turnos clave × 50 personas = 400 interacciones** reales contra `HybridChatbotService` (misma `session_id` por persona para probar hilo).

Fuente: `metricas_ronda4.json`

| Métrica | Valor |
|---------|-------|
| Interacciones evaluadas | 400 / 2500 del set |
| OK (heurística anti-crash) | **400 (100%)** |
| FALLA dura / CORROMPE | **0** |
| Latencia promedio | **4.3 s** |
| Latencia p50 | **4.1 s** |
| Latencia p95 | **9.9 s** |
| Latencia máx | **32.2 s** |

### Distribución de rutas
- `paid_ai_integrated` 264 (docs con IA)
- `conversation_greeting` / reject-reset 96
- `directory_my_boss_unavailable` 14
- meta áreas/puestos 19
- catálogo / fuera de tema / data_based: pocos

## Lectura honesta (calidad para novatos)

La heurística mide que **no se cae el servicio** y que hay respuesta útil. Revisando snippets, estos son los **puntos a mejorar** para usuarios básicos:

### 1. Latencia (alto impacto UX)
- ~20 turnos >10 s; p95 ≈ 10 s; pico 32 s.
- Un novato percibe “se trabó”.
- **Mejorar:** feedback “Buscando…” en UI; cachear saludo/unidades/mi jefe (ya rápidos); acortar RAG cuando el tema es vago.

### 2. Menú 1/2/3 al recuperar hilo (“volvamos”, “me perdí”)
- 16 casos abrieron menú genérico tras rechazo/reset.
- Para básicos es frío y rompe el tema (“pagos/factura”).
- **Mejorar:** tras “volvamos/me perdí”, retomar el **último tema del chat** (chips del tema), no el menú vacío.

### 3. Preguntas vagas → PDF al azar / semántica
- “necesito algo de facturas/compras/obra” a menudo dispara IA de un documento concreto sin confirmar.
- Bien si acierta; mal si el usuario quería orientación.
- **Mejorar:** 1 aclaración corta (“¿factura de proveedor o cobro a cliente?”) + 2–3 chips, antes de abrir un PDF.

### 4. “¿Quién me puede ayudar con eso?” / “áreas de la empresa?”
- A veces cae a “No encontré coincidencias…” (2 casos) en vez de directorio/unidades.
- **Mejorar:** mapear “quién me ayuda / áreas / cómo está organizada” → directorio/organigrama BD, nunca RAG.

### 5. “en bullets” / seguimientos de formato
- Puede **cambiar de documento** (“Cambiando a Controlar Extras…”) en vez de reformatear el actual.
- **Mejorar:** instrucciones de formato (“bullets”, “más corto”) = seguimiento del PDF en foco, sin topic switch.

### 6. Jerarquía para novatos
- “mi jefe / a quién reporto” responde honesto (no inventa) — bien.
- Falta: guiar con **área del usuario** o “escribe el puesto de tu jefe” + ejemplos del directorio.
- **Mejorar:** chips “Gerente de …”, “Coordinador de …”, “personas de mi área”.

### 7. Dataset vs realidad de BD
- Temas como “campamentos/vacaciones” pueden no existir como elemento publicado → Bob improvisa o se va a docs vecinos.
- **Mejorar:** si no hay match de título/área, decir “no tengo ese procedimiento publicado” + ofrecer áreas cercanas.

## Score orientativo para producción (usuarios básicos)

| Dimensión | Score | Nota |
|-----------|------:|------|
| Estabilidad (no crash) | 10/10 | 0 CORROMPE en 400 |
| Cobertura BD (unidades, jefe, saludos) | 8/10 | Rutas estructuradas OK |
| Orientación a novatos (sin folio) | 6/10 | Aún salta a PDF / menú 1-2-3 |
| Mantener hilo | 7/10 | “ese no es” recupera; “volvamos” a veces resetea a menú |
| Velocidad percibida | 5/10 | p95 ~10 s en docs |
| **Global piloto básicos** | **7/10** | Apto piloto; no GA sin UX de espera + menos menú genérico |

## Mitigaciones aplicadas (2026-08-28)

Tras este reporte se implementaron en `HybridChatbotService` + UI:

1. `conversation_topic_recovery` (volvamos/me perdí)
2. `conversation_vague_topic_clarify` (necesito algo de X)
3. Áreas / quién me ayuda → directorio BD
4. Formato (bullets/corto) = follow-up del PDF
5. Chips enriquecidos en “mi jefe”
6. `unpublished_topic_alternatives` si solo hay vecino semántico
7. Texto UI “Buscando en el SGC…”

Smoke de regresión: `php tests/bob_qa/_ga_smoke.php` (8/8 OK en prueba local).

## Cómo repetir
```bash
cd tests/bob_qa
python generate_ronda4_personas.py   # regenera 2500 (archiva activa)
php sim_ronda4_sample.php            # muestra 400 live
```

Para menos personas: `set SIM_PERSONAS=10` antes del php.
