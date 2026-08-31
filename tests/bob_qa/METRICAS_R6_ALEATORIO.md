# Ronda 6 — 30 personas × 20 (conocimiento aleatorio)

Fecha: 2026-08-28  
Post-fixes de gaps R5 + chips UI.

## Dataset

| | |
|---|---|
| Personas | **30** (nivel aleatorio: 4 básico, 9 intermedio, 17 experto) |
| Preguntas/persona | **20** |
| Total | **600** |
| Archivos | `personas_ronda6.json`, `preguntas_bob_qa.json` |
| Historial | R5 en `history/ronda_005_*` |

## Fixes aplicados antes de R6

1. **Bullets sin foco** → reancla `last_doc_hint` del hilo  
2. **Necesito algo de X** → siempre confirma (`¿Te refieres a…?` + pending `sí`)  
3. **Lista los directores** → directorio BD  
4. **Comparar A con B** → `conversation_compare_procedures` (uno a la vez + chips)  
5. Chips UI `{label,query}` (ya no `[object Object]`)

## Métrica live

**8 turnos × 30 = 240 interacciones** (`sim_ronda6_sample.php`)

| Métrica | R6 | R5 |
|---------|---:|---:|
| OK | **239/240 (99.6%)** | 98.4% |
| FALLA | **1** | 13 |
| CORROMPE | **0** | 0 |
| Latencia avg | **2.6 s** | 2.7 s |
| p95 | **6.9 s** | 8.2 s |
| Expertos | **100%** | 96.8% |
| Intermedio | **100%** | — |
| Básicos | **96.9%** | 100%* |

\*La única FALLA R6 fue `necesito algo de cierre` con PDF previo en foco (ya corregido: el vago suelta el foco y confirma).

### Señales de los fixes

| Flag / method | Conteo |
|---|---:|
| Directores → BD | 27 |
| Comparar OK | 23 |
| Vague clarify | 3 (+ confirmación en smoke) |
| Topic recovery | 3 |
| Bullets → FALLA “Cambiando a” | **0** (en R5 eran 13) |
| Directores → IA | **0** |

## Score piloto

| | R4 | R5 | R6 |
|--|---:|---:|---:|
| Global | 7 | 8.5 | **~9.2** |

## Qué queda

- Latencia al abrir folio (~11–15 s en algunos turnos).  
- “Cambiando a” aún aparece en cambios **legítimos** de documento (no en bullets).  
- Comparar no genera tabla lado a lado (a propósito: uno a la vez).

## Cómo repetir
```bash
cd tests/bob_qa
python generate_ronda6_personas.py
php sim_ronda6_sample.php
```
