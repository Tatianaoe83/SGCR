# Mejoras de servicio (QA Bob)

Fecha: 2026-08-28

## Ronda 6 — fixes de gaps R5

| Gap | Fix |
|---|---|
| `en bullets` sin PDF en foco | Reancla `last_doc_hint` del hilo |
| `necesito algo de X` → PDF | Siempre aclara + `¿Te refieres a…?` (pending sí) |
| `lista los directores` → IA | Ruta `directory_company_directors` |
| Comparar 2 procedimientos | `conversation_compare_procedures` + chips |
| Chips `[object Object]` | UI lee `label` / `query` |

## Smoke

```bash
php tests/bob_qa/sim_ronda6_sample.php
```

Resultado R6 muestra: **99.6% OK** (239/240), 0 CORROMPE. Detalle: `METRICAS_R6_ALEATORIO.md`.
