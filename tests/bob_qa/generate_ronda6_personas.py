#!/usr/bin/env python3
"""
Ronda 6: 30 personas × 20 preguntas, nivel de conocimiento ALEATORIO
(basico / intermedio / experto). Post-fixes de gaps R5.

Archiva ronda activa en history/.
"""

from __future__ import annotations

import csv
import json
import random
import shutil
from datetime import datetime, timezone
from pathlib import Path

OUT = Path(__file__).resolve().parent
HISTORY = OUT / "history"
INDEX = OUT / "historial_rondas.json"
ACTIVE_JSON = OUT / "preguntas_bob_qa.json"
ACTIVE_CSV = OUT / "preguntas_bob_qa.csv"
ACTIVE_MD = OUT / "PREGUNTAS_PARA_COPIAR.md"
PERSONAS_JSON = OUT / "personas_ronda6.json"

RONDA = 6
N_PERSONAS = 30
N_PREGUNTAS = 20
SEED = 606


def now_iso() -> str:
    return datetime.now(timezone.utc).astimezone().isoformat(timespec="seconds")


def archive_active() -> None:
    if not ACTIVE_JSON.exists():
        return
    HISTORY.mkdir(parents=True, exist_ok=True)
    data = json.loads(ACTIVE_JSON.read_text(encoding="utf-8"))
    prev = int(data.get("ronda", 5))
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    folder = HISTORY / f"ronda_{prev:03d}_{stamp}"
    folder.mkdir(parents=True, exist_ok=False)
    for name in (
        "preguntas_bob_qa.json",
        "preguntas_bob_qa.csv",
        "PREGUNTAS_PARA_COPIAR.md",
        "personas_ronda5.json",
        "personas_ronda6.json",
        "metricas_ronda5.json",
        "METRICAS_R5_MIXTO.md",
    ):
        src = OUT / name
        if src.exists():
            shutil.copy2(src, folder / name)
    resumen = {
        "ronda": prev,
        "archivada_en": now_iso(),
        "total": data.get("total", len(data.get("casos", []))),
        "por_categoria": data.get("por_categoria", {}),
        "ok": sum(1 for c in data.get("casos", []) if c.get("resultado") == "OK"),
        "falla": sum(1 for c in data.get("casos", []) if c.get("resultado") == "FALLA"),
        "corrompe": sum(
            1 for c in data.get("casos", []) if c.get("resultado") == "CORROMPE_SERVICIO"
        ),
        "sin_calificar": sum(1 for c in data.get("casos", []) if not c.get("resultado")),
    }
    (folder / "resumen.json").write_text(
        json.dumps(resumen, ensure_ascii=False, indent=2), encoding="utf-8"
    )
    idx = json.loads(INDEX.read_text(encoding="utf-8")) if INDEX.exists() else {"rondas": [], "preguntas_usadas": []}
    idx.setdefault("rondas", []).append(
        {
            "ronda": prev,
            "carpeta": str(folder.relative_to(OUT)),
            "archivada_en": resumen["archivada_en"],
            "resumen": resumen,
        }
    )
    INDEX.write_text(json.dumps(idx, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Archivada ronda {prev} -> {folder}")


NOMBRES = [
    "Luis", "Ana", "Diego", "María", "José", "Sofía", "Carlos", "Elena", "Miguel", "Paula",
    "Andrés", "Lucia", "Pedro", "Valeria", "Jorge", "Camila", "Ricardo", "Fernanda", "Héctor", "Diana",
    "Roberto", "Natalia", "Gabriel", "Andrea", "Felipe", "Karla", "Oscar", "Patricia", "Iván", "Monica",
]

AREAS = ["obra", "finanzas", "rh", "compras", "calidad", "ti", "juridico", "corporativo", "presupuestos"]
FOLIOS = [("PAA08-PR05", "Cierre de Mes"), ("PAA06-PR01", "Programar Pagos"), ("PAA06-PR03", "Ejecutar Pagos")]
PROCS = ["Cierre de Mes", "Programar Pagos", "Ejecutar Pagos", "Renta de Maquinaria"]
TEMAS = ["pagos", "cierre", "campamentos", "compras", "facturas", "obra", "calidad", "juridico", "maquinaria"]


def build_chat(idx: int, nivel: str, area: str, rng: random.Random) -> list[str]:
    tag = f"[u{idx:02d}|{nivel}|{area}|r6]"
    tema = rng.choice(TEMAS)
    tema2 = rng.choice([t for t in TEMAS if t != tema])
    folio, nombre = rng.choice(FOLIOS)
    proc_a, proc_b = rng.sample(PROCS, 2)

    pool_basico = [
        rng.choice(["hola", "holi bob", "buenas", "hey"]),
        f"necesito algo de {tema}",
        rng.choice(["no se el folio", "soy nuevo", "no me se el nombre"]),
        rng.choice([f"hay procedimiento de {tema}?", f"solitud de {tema}"]),
        rng.choice(["mi jefe quien es", "quien me puede ayudar", "a quien reporto"]),
        rng.choice(["areas de la empresa?", "como esta organizada", "dime las unidades"]),
        "lista los directores",
        rng.choice(["me perdí", "volvamos", "ese no es"]),
        f"mejor {tema2}",
        "en bullets",
        "mas corto pls",
        "mis procedimientos",
        rng.choice(["y el objetivo?", "quien es el responsable"]),
        rng.choice(["factura telcel a quien", "gasto o cobro?"]),
        rng.choice(["cordinador de TI", "gerente de rh"]),
        rng.choice(["gracias", "ok thx"]),
        rng.choice(["otra cosa", "cambiemos de tema"]),
        f"procedimientos de {area}",
        rng.choice(["??", "ayuda"]),
        rng.choice(["bye", "listo"]),
    ]

    pool_inter = [
        rng.choice(["hola Bob", "buenas"]),
        f"hay procedimiento de {tema}?",
        f"objetivo de {nombre}",
        "alcance?",
        "lista los directores",
        "dime las unidades",
        f"procedimientos de {area}",
        "mis procedimientos",
        f"necesito algo de {tema2}",
        "sí",
        "en bullets el objetivo",
        "más formal",
        rng.choice(["quién ocupa Coordinador de TI", "gerente de Recursos Humanos"]),
        f"compara {proc_a} con {proc_b}",
        rng.choice(["volvamos", "ese no es el que buscaba"]),
        f"abre {folio}",
        "riesgos?",
        "documentos relacionados",
        rng.choice(["gracias", "ok"]),
        rng.choice(["listo", "bye"]),
    ]

    pool_experto = [
        rng.choice(["hola Bob", "buenas"]),
        f"folio {folio}",
        f"objetivo del procedimiento {nombre}",
        "alcance y responsables",
        "versión vigente?",
        "quién ocupa el puesto de Coordinador de TI",
        "lista los directores",
        f"procedimientos de {area}",
        "mis procedimientos",
        f"compara mentalmente {proc_a} con {proc_b}: ¿cuándo uso cada uno?",
        "en bullets el objetivo",
        "más formal",
        "quién es mi jefe",
        f"áreas vinculadas a {nombre}",
        "documentos relacionados",
        rng.choice(["ese no es el que buscaba", "volvamos al folio"]),
        f"abre de nuevo {folio}",
        "riesgos del procedimiento",
        rng.choice(["fuera de tema: cuánto gana un analista", "qué modelo de IA usas"]),
        rng.choice(["gracias, listo", "bye"]),
    ]

    if nivel == "basico":
        qs = pool_basico
    elif nivel == "intermedio":
        qs = pool_inter
    else:
        qs = pool_experto

    # Mezcla ligera: 2 turnos del otro pool para aleatoriedad
    other = pool_experto if nivel == "basico" else pool_basico
    for pos in rng.sample(range(4, 18), k=2):
        qs[pos] = other[pos]

    return [f"{q} {tag}#{i:02d}" for i, q in enumerate(qs[:N_PREGUNTAS], 1)]


def main() -> None:
    rng = random.Random(SEED)
    archive_active()

    personas = []
    casos = []
    niveles_count = {"basico": 0, "intermedio": 0, "experto": 0}

    for p in range(N_PERSONAS):
        nivel = rng.choice(["basico", "intermedio", "experto"])
        area = rng.choice(AREAS)
        niveles_count[nivel] += 1
        chat = build_chat(p + 1, nivel, area, rng)
        persona = {
            "persona_id": f"P{p+1:02d}",
            "nombre": f"{NOMBRES[p % len(NOMBRES)]} ({nivel})",
            "perfil": f"rand_{nivel}",
            "rol_simulado": f"usuario {nivel}",
            "area_aproximada": area,
            "nivel": nivel,
            "nota": "conocimiento aleatorio ronda 6 post-fixes",
            "session_id": f"r6-persona-{p+1:02d}",
            "preguntas": chat,
        }
        personas.append(persona)

        for turn, q in enumerate(chat, 1):
            ql = q.lower()
            if "fuera de tema" in ql or "modelo de ia" in ql:
                cat = "C"
            elif turn <= 5:
                cat = "E" if nivel == "basico" else "A"
            elif "compara" in ql:
                cat = "B"
            elif turn <= 14:
                cat = "A"
            else:
                cat = "E"
            casos.append(
                {
                    "id": f"BOB-R6-P{p+1:02d}-T{turn:02d}",
                    "ronda": RONDA,
                    "persona_id": persona["persona_id"],
                    "turno": turn,
                    "nivel": nivel,
                    "categoria": cat,
                    "pregunta_enviada": q,
                    "respuesta_esperada": (
                        "Aclara vagos; directorio en directores; compare=uno a la vez; "
                        "bullets sin Cambiando a; BD primero."
                    ),
                    "senal_falla": (
                        "Cambiando a en bullets; vago→PDF sin confirmar; directores→IA; "
                        "compara abre un solo PDF; menú 1/2/3; [object Object]."
                    ),
                    "respuesta_real_de_bob": "",
                    "resultado": "",
                    "fecha_hora_prueba": "",
                    "version_bob_nota": "",
                }
            )

    assert len(casos) == N_PERSONAS * N_PREGUNTAS
    counts: dict[str, int] = {}
    for c in casos:
        counts[c["categoria"]] = counts.get(c["categoria"], 0) + 1

    payload = {
        "ronda": RONDA,
        "modo": "30_personas_x_20_conocimiento_aleatorio",
        "nivel_usuario": "aleatorio",
        "generado_en": now_iso(),
        "total": len(casos),
        "personas": N_PERSONAS,
        "preguntas_por_persona": N_PREGUNTAS,
        "por_nivel": niveles_count,
        "por_categoria": counts,
        "nota": "Ronda 6 post-fixes gaps R5. Historial en history/.",
        "casos": casos,
    }
    ACTIVE_JSON.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    PERSONAS_JSON.write_text(
        json.dumps({"ronda": RONDA, "generado_en": now_iso(), "personas": personas}, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    fields = list(casos[0].keys())
    with ACTIVE_CSV.open("w", encoding="utf-8-sig", newline="") as f:
        w = csv.DictWriter(f, fieldnames=fields)
        w.writeheader()
        w.writerows(casos)

    lines = [
        f"# Ronda {RONDA}: 30 personas conocimiento aleatorio x 20 preguntas",
        "",
        "Formato: `ID | PERSONA | NIVEL | TURNO | CAT | mensaje`",
        "",
    ]
    for c in casos:
        q = c["pregunta_enviada"].replace("\n", "\\n")
        lines.append(
            f"{c['id']} | {c['persona_id']} | {c['nivel']} | T{c['turno']:02d} | {c['categoria']} | {q}"
        )
    ACTIVE_MD.write_text("\n".join(lines), encoding="utf-8")

    idx = json.loads(INDEX.read_text(encoding="utf-8")) if INDEX.exists() else {"rondas": [], "preguntas_usadas": []}
    idx["activa"] = {
        "ronda": RONDA,
        "generada_en": now_iso(),
        "total": len(casos),
        "modo": "30x20_aleatorio",
    }
    INDEX.write_text(json.dumps(idx, ensure_ascii=False, indent=2), encoding="utf-8")

    print(f"OK ronda {RONDA}: {N_PERSONAS} x {N_PREGUNTAS} = {len(casos)}")
    print("Niveles:", niveles_count)
    print(f"Personas: {PERSONAS_JSON}")


if __name__ == "__main__":
    main()
