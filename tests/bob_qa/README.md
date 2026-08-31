# QA / Red-teaming Bob — re-testing con historial

## Idea clave

- Cada **ronda** = 500 preguntas.
- Al generar una ronda nueva, la anterior se **archiva** en `history/` (nunca se borra).
- El set **activo** es el que pruebas ahora (`preguntas_bob_qa.json`).
- Puedes **comparar** progreso entre rondas.

## Flujo

```bash
cd tests/bob_qa

# Primera vez (si no hay set)
python generate_dataset.py --ronda 1

# Nueva ronda de 500 preguntas DISTINTAS (archiva la activa)
python bob_qa_runner.py nueva-ronda
# o: python generate_dataset.py --ronda 2

# Probar en el chat (PREGUNTAS_PARA_COPIAR.md) y marcar:
python bob_qa_runner.py marcar BOB-R2-001 OK --version "post-pin-BD"
python bob_qa_runner.py marcar BOB-R2-015 FALLA --nota "doc equivocado"

# Ver progreso
python bob_qa_runner.py resumen
python bob_qa_runner.py comparar
python bob_qa_runner.py historial

# Al terminar la ronda (congela snapshot extra en history/)
python bob_qa_runner.py cerrar-ronda
```

## Carpetas

| Ruta | Qué es |
|---|---|
| `preguntas_bob_qa.json` | Set **activo** (se puede regenerar) |
| `history/ronda_001_*` | Ronda 1 archivada (intocable) |
| `historial_rondas.json` | Índice + preguntas ya usadas |
| `test_log.json` / `CHANGELOG_pruebas.md` | Mejoras/bajas/altas acumuladas |

## Estado actual

- **Ronda 1** archivada en `history/` (500 originales).
- **Ronda 2** activa: 500 nuevas, solape 0 con ronda 1.
  - A120 B90 C90 D70 E70 F60
  - IDs: `BOB-R2-001` … `BOB-R2-500`

## Cómo medir si Bob mejoró

1. Califica un lote de la ronda 2 (sobre todo **A** y **E**).
2. `python bob_qa_runner.py comparar` muestra OK/FALLA/CORR por ronda.
3. El historial no se sobrescribe: puedes volver a abrir `history/ronda_001_*`.

## Smoke post-mejoras (manual)

1. `solitud de campamentos` / `solisitud` → Campamentos  
2. `PAA08-PR05` → Cierre de Mes  
3. `quién es mi jefe` / `a quién le reporto` → directorio honesto  
4. `cuánto gana…` / `tabla salarial` → fuera de tema  
5. Pregunta fuera del PDF → **no** dice que borró el contexto  
