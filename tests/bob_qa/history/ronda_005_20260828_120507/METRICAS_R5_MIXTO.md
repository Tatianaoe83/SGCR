# Ronda 5 — 100 personas (básicos + expertos) × 20 preguntas

Fecha: 2026-08-28  
Contexto: **post-mitigaciones GA** de la ronda 4.

## Qué se generó

| | |
|---|---|
| Personas | **100** (50 básicos + 50 expertos) |
| Preguntas por persona | **20** (chat continuo) |
| Total dataset | **2000** |
| Archivos | `preguntas_bob_qa.json`, `personas_ronda5.json`, `PREGUNTAS_PARA_COPIAR.md` |
| Historial | Ronda 4 archivada en `history/ronda_004_*` |

- **Básicos:** vagos, typos, “necesito algo de…”, “volvamos”, sin folio.
- **Expertos:** folios (`PAA08-PR05`…), nombres exactos, directorio, comparación de procs, fuera de tema.

## Métrica live (muestra)

**8 turnos clave × 100 personas = 800 interacciones** reales (`sim_ronda5_sample.php`).

Fuente: `metricas_ronda5.json`

| Métrica | Ronda 5 | Ronda 4 (ref.) |
|---------|--------:|---------------:|
| Interacciones | 800 | 400 |
| OK (heurística) | **787 (98.4%)** | 400 (100%*) |
| FALLA | **13** | 0* |
| CORROMPE | **0** | 0 |
| Latencia avg | **2.7 s** | 4.3 s |
| Latencia p50 | **2.6 s** | 4.1 s |
| Latencia p95 | **8.2 s** | 9.9 s |
| Latencia máx | **16.4 s** | 32.2 s |

\*R4 midió anti-crash (100% OK); los gaps de calidad se vieron por red flags, no por FALLA dura.

### Por nivel (R5)

| Nivel | n | OK | FALLA | % OK |
|-------|--:|---:|------:|-----:|
| Básico | 400 | **400** | 0 | **100%** |
| Experto | 400 | 387 | 13 | 96.8% |

### Rutas nuevas (señal de que las correcciones GA se usan)

| Method / flag | Conteo |
|---|---:|
| `conversation_topic_recovery` | 67 |
| `conversation_vague_topic_clarify` | 25 |
| `directory_company_units` / who_can_help | 85 |
| `unpublished_topic_alternatives` | 4 |
| Menú 1/2/3 tras recovery | **0** (R4 tenía 16) |
| Turnos >10 s | **9** (R4: 20) |
| Coincidencias en snip | **0** (R4: 2) |

## ¿Mejoró respecto a R4?

**Sí, de forma clara en los gaps que se mitigaron**, sobre todo para usuarios básicos:

| Gap R4 | ¿Mejoró? | Evidencia R5 |
|--------|:--------:|---|
| Menú 1/2/3 en “volvamos / me perdí” | **Sí** | 0 menús; 67 `topic_recovery` |
| “Necesito algo de X” → PDF | **Sí (parcial)** | 25 aclaran; aún 21 van a PDF si el tema pega fuerte (ej. cierre) |
| Áreas / quién me ayuda → coincidencias | **Sí** | 85 rutas directorio; 0 coincidencias |
| Latencia percibida | **Sí** | avg 4.3→2.7 s; p95 9.9→8.2; máx 32→16; >10s 20→9 |
| “En bullets” cambia de doc | **Parcial** | Básicos OK con “en bullets”; expertos fallan con “en bullets el objetivo” si **no hay PDF en foco** (tras comparar 2 docs) |
| Mi jefe / orientación | **Sí** | Rutas honestas + chips; sin coincidencias |
| Tema no publicado | **Sí (parcial)** | 4 `unpublished`; vagos de campamentos suelen aclarar antes |

**Score orientativo**

| Dimensión | R4 | R5 | Nota |
|-----------|---:|---:|------|
| Estabilidad | 10 | **10** | 0 CORROMPE |
| Orientación novatos | 6 | **8.5** | Aclara + recovery |
| Mantener hilo | 7 | **8.5** | Recovery sin menú vacío |
| Velocidad | 5 | **7** | Menos RAG en vagos |
| Expertos (folios/meta) | n/a | **8** | Bien en folio; flojo bullets sin foco |
| **Global piloto** | **7** | **8.5** | Listo para piloto ampliado |

## Qué falta mejorar

1. **“en bullets el objetivo” sin documento en foco** (13 FALLA expertos)  
   Tras “compara A con B” se suelta el PDF; el follow-up de formato busca otro doc y pone “Cambiando a…”.  
   **Mejora:** si la frase es solo formato (+ sección), pedir “¿sobre cuál procedimiento?” o reanclar el último folio del hilo.

2. **“necesito algo de X” aún abre PDF a veces** (21 casos, ej. cierre)  
   Cuando el nombre solapa fuerte con un título publicado, salta la aclaración.  
   **Mejora:** forzar 1 chip de confirmación aunque haya overlap medio (“¿Te refieres a Cierre de Mes?”).

3. **“lista los directores” a veces cae a IA**  
   Falta señal de directorio si no dice “de la empresa/áreas”.  
   **Mejora:** tratar “lista/listado de directores” como org pura.

4. **Latencia en docs con IA**  
   p95 ~8 s sigue alto para chat WhatsApp.  
   **Mejora:** cache de secciones frecuentes; respuestas cortas en follow-ups de formato.

5. **Comparar dos procedimientos**  
   Expertos piden “compara A con B”; Bob responde de un solo PDF.  
   **Mejora:** ruta explícita multi-doc o aclarar “solo puedo detallar uno a la vez”.

## Cómo repetir
```bash
cd tests/bob_qa
python generate_ronda5_personas.py
php sim_ronda5_sample.php
# opcional: set SIM_PERSONAS=20
```
