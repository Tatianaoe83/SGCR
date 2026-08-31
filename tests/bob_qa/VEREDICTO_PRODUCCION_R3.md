# Veredicto producción — Ronda 3 + smoke live

Fecha: 2026-08-27

## Rondas generadas (historial intacto)

| Ronda | Estado | Preguntas |
|------|--------|-----------|
| 1 | `history/ronda_001_*` | 500 (originales) |
| 2 | `history/ronda_002_*` | 500 (nuevas) |
| 3 | **ACTIVA** | 500 (nuevas otra vez, solape 0) |

IDs activos: `BOB-R3-001` … `BOB-R3-500`  
Lista: `PREGUNTAS_PARA_COPIAR.md`

## Smoke live (23 casos representativos A–E)

Archivo: `smoke_report_r3.json`

| Métrica | Valor |
|---------|-------|
| OK | **21 / 23 (91.3%)** |
| FALLA | 2 |
| CORROMPE | **0** |

### Qué ya funciona bien (listo para usuarios reales en estos caminos)
- Folios claros (`PAA08-PR05`, `PAA06-PR01`, `PAA06-PR03`)
- Unidades de empresa (BD directa, ~40 ms)
- Directorio “quién ocupa Coordinador de TI”
- Catálogo por área (`procedimientos de Jurídico`)
- “quién es mi jefe” → respuesta honesta sin inventar
- Fuera de tema: modelo IA, jailbreak, Bimbo, clima
- Saludo / “sí” sin tumbar el servicio

### Fallas vistas en smoke
1. **Sueldo + puesto** (`cuánto gana un analista jurídico`) → se iba a un PDF de Jurídico. **Corregido** (off-topic sensible gana antes).
2. **Typo campamentos** → en **esta BD local no hay** elemento publicado “Solicitud de Campamentos” (solo menciones en otros docs). Bob cae a semántica (`Renta de Maquinaria`). Comportamiento a vigilar: si el doc no está en BD, debe decir **no encontrado**, no otro PDF.
3. Folio inventado `ZZ99-PR00` → respondió bien (“no hay info”); el juez automático fue estricto (falso negativo).

## ¿Sale a producción?

### Veredicto: **piloto controlado (soft launch), no GA total**

**Sí puede salir a producción limitada** para usuarios reales **si**:
- Se comunica que Bob cubre SGC + directorio Proser (no nómina, no opiniones, no otras empresas).
- Se monitorean analytics/feedback las primeras 1–2 semanas.
- Hay dueño para corregir falsos (campamentos/typos, hilos sticky).

**Aún no GA amplio** porque:
- Latencia IA en docs: **5–14 s** (aceptable con “pensando…”, no ideal).
- Casos ambiguos + PDF sticky todavía pueden confundir (retros Carlos/Toño/Tamay).
- Estrés/F (SQL, payloads largos) no validados en este smoke (el endpoint corta a `max:500`).
- “Mis procedimientos” depende de puesto ligado al usuario logueado.

### Recomendación de salida
1. **Piloto** con 5–15 usuarios internos (los de las retros).
2. Checklist go-live: login + puesto mapeado, OpenAI estable, throttle, log de `method`.
3. Tras 1 semana con `marcar`/`comparar` de ronda 3 → decidir GA.

## Cómo seguir midiendo
```bash
cd tests/bob_qa
php smoke_bob.php
python bob_qa_runner.py marcar BOB-R3-001 OK --version "r3-smoke"
python bob_qa_runner.py comparar
python bob_qa_runner.py cerrar-ronda
```
