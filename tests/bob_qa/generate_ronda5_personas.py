#!/usr/bin/env python3
"""
Ronda 5: 100 personas × 20 preguntas (mix básicos + expertos).
- 50 básicos: vagos, typos, sin folio.
- 50 expertos: folios, nombres exactos, jerarquía/área, seguimientos precisos.
Archiva la ronda activa en history/ (no sobrescribe).

Salida:
  - preguntas_bob_qa.json / .csv / PREGUNTAS_PARA_COPIAR.md
  - personas_ronda5.json
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
PERSONAS_JSON = OUT / "personas_ronda5.json"

RONDA = 5
N_PERSONAS = 100
N_PREGUNTAS = 20
SEED = 505


def now_iso() -> str:
    return datetime.now(timezone.utc).astimezone().isoformat(timespec="seconds")


def archive_active() -> None:
    if not ACTIVE_JSON.exists():
        return
    HISTORY.mkdir(parents=True, exist_ok=True)
    data = json.loads(ACTIVE_JSON.read_text(encoding="utf-8"))
    prev = int(data.get("ronda", 4))
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    folder = HISTORY / f"ronda_{prev:03d}_{stamp}"
    folder.mkdir(parents=True, exist_ok=False)
    for name in (
        "preguntas_bob_qa.json",
        "preguntas_bob_qa.csv",
        "PREGUNTAS_PARA_COPIAR.md",
        "personas_ronda4.json",
        "personas_ronda5.json",
        "metricas_ronda4.json",
        "METRICAS_R4_BASICOS.md",
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
    "Raúl", "Jimena", "Sergio", "Alejandra", "Manuel", "Claudia", "Eduardo", "Isabel", "Francisco", "Laura",
    "Alberto", "Daniela", "Ernesto", "Gabriela", "Hugo", "Verónica", "Tomas", "Adriana", "Bruno", "Silvia",
]

BASICOS = [
    ("nuevo_obra", "Residente junior", "obra"),
    ("aux_conta", "Auxiliar contable", "finanzas"),
    ("analista_jr", "Analista junior", "oficina"),
    ("rh_nuevo", "Apoyo RH nuevo", "rh"),
    ("compras_jr", "Compras junior", "compras"),
    ("calidad_jr", "Calidad junior", "calidad"),
    ("ti_soporte", "Soporte TI básico", "ti"),
    ("juridico_asist", "Asistente jurídico", "juridico"),
    ("almacen", "Almacén", "obra"),
    ("recepcion", "Recepción", "corporativo"),
]

EXPERTOS = [
    ("coord_ti", "Coordinador de TI", "ti"),
    ("gerente_rh", "Gerente RH", "rh"),
    ("dir_juridico", "Director Jurídico", "juridico"),
    ("jefe_compras", "Jefe de Compras", "compras"),
    ("coord_calidad", "Coordinador Calidad", "calidad"),
    ("gerente_obra", "Gerente de Construcción", "obra"),
    ("contador_sr", "Contador senior", "finanzas"),
    ("analista_presup", "Analista de presupuestos", "presupuestos"),
    ("coord_corp", "Coordinador Corporativo", "corporativo"),
    ("auditor_sgc", "Auditor SGC", "calidad"),
]

FOLIOS = [
    ("PAA08-PR05", "Cierre de Mes"),
    ("PAA06-PR01", "Programar Pagos"),
    ("PAA06-PR03", "Ejecutar Pagos"),
]

PROCS = [
    "Cierre de Mes",
    "Programar Pagos",
    "Ejecutar Pagos",
    "Renta de Maquinaria",
]


def build_chat_basico(idx: int, perfil_id: str, area: str, rng: random.Random) -> list[str]:
    tag = f"[u{idx:03d}|{perfil_id}|basico|r5]"
    temas = [
        "pagos", "cierre", "campamentos", "compras", "facturas",
        "obra", "maquinaria", "calidad", "juridico", "vacaciones", "presupuestos",
    ]
    tema = rng.choice(temas)
    tema2 = rng.choice([t for t in temas if t != tema])
    qs = [
        rng.choice(["hola", "holi bob", "buenas", "hey"]),
        f"necesito algo de {tema}",
        rng.choice(["no se el folio", "no me se el nombre exacto", "soy nuevo"]),
        rng.choice([f"hay procedimiento de {tema}?", f"solitud de {tema}", f"pa que sirve lo de {tema}?"]),
        rng.choice(["mi jefe quien es", "a quien reporto", "quien me puede ayudar con eso"]),
        rng.choice(["areas de la empresa?", "como esta organizada", "dime las unidades"]),
        rng.choice(["pero ese no es", "ese no era", "me perdí", "volvamos"]),
        f"mejor {tema2}",
        "un resumen",
        "en bullets",
        rng.choice(["mas corto pls", "explicame facil"]),
        "mis procedimientos",
        rng.choice(["y el objetivo?", "alcance?", "quien es el responsable"]),
        rng.choice(["y si tengo una factura telcel a quien se la mando", "no se si es gasto o cobro"]),
        rng.choice(["cordinador de TI", "gerente de rh", "lista de directores"]),
        rng.choice(["gracias", "ok thx", "va"]),
        rng.choice(["otra cosa", "cambiemos de tema"]),
        rng.choice(["procedimientos de " + area, "los mios"]),
        rng.choice(["??", "ayuda otra vez"]),
        rng.choice(["bye", "listo", "eso es todo"]),
    ]
    return [f"{q} {tag}#{i:02d}" for i, q in enumerate(qs[:N_PREGUNTAS], 1)]


def build_chat_experto(idx: int, perfil_id: str, area: str, rng: random.Random) -> list[str]:
    tag = f"[u{idx:03d}|{perfil_id}|experto|r5]"
    folio, nombre = rng.choice(FOLIOS)
    proc = rng.choice(PROCS)
    otro = rng.choice([p for p in PROCS if p != proc] or PROCS)
    qs = [
        rng.choice(["hola Bob", "buenas", "hola"]),
        f"objetivo del procedimiento {nombre}",
        f"folio {folio}",
        "alcance y responsables",
        "versión vigente?",
        f"quién ocupa el puesto de Coordinador de TI",
        rng.choice(["dime las unidades de la empresa", "lista los directores"]),
        f"procedimientos de {area}",
        "mis procedimientos",
        f"compara mentalmente {proc} con {otro}: ¿cuándo uso cada uno?",
        "en bullets el objetivo",
        "más formal",
        rng.choice(["quién es mi jefe", "a quién reporto según el directorio"]),
        f"áreas vinculadas a {nombre}",
        "documentos relacionados",
        rng.choice(["ese no es el que buscaba", "volvamos al folio"]),
        f"abre de nuevo {folio}",
        "riesgos del procedimiento",
        rng.choice(["fuera de tema: cuánto gana un analista", "qué modelo de IA usas"]),
        rng.choice(["gracias, listo", "bye", "perfecto gracias"]),
    ]
    return [f"{q} {tag}#{i:02d}" for i, q in enumerate(qs[:N_PREGUNTAS], 1)]


def main() -> None:
    rng = random.Random(SEED)
    archive_active()

    personas = []
    casos = []

    for p in range(N_PERSONAS):
        es_experto = p >= 50
        if es_experto:
            perfil_id, rol, area = EXPERTOS[p % len(EXPERTOS)]
            nivel = "experto"
            chat = build_chat_experto(p + 1, perfil_id, area, rng)
        else:
            perfil_id, rol, area = BASICOS[p % len(BASICOS)]
            nivel = "basico"
            chat = build_chat_basico(p + 1, perfil_id, area, rng)

        nombre = f"{NOMBRES[p % len(NOMBRES)]} ({perfil_id})"
        persona = {
            "persona_id": f"P{p+1:03d}",
            "nombre": nombre,
            "perfil": perfil_id,
            "rol_simulado": rol,
            "area_aproximada": area,
            "nivel": nivel,
            "nota": "contexto amplio de jerarquía/folios" if es_experto else "usuario básico post-GA",
            "session_id": f"r5-persona-{p+1:03d}",
            "preguntas": chat,
        }
        personas.append(persona)

        for turn, q in enumerate(chat, 1):
            if turn <= 4:
                cat = "E" if nivel == "basico" else "A"
            elif turn <= 10:
                cat = "A"
            elif turn <= 15:
                cat = "B"
            else:
                cat = "C" if "fuera de tema" in q.lower() or "modelo de ia" in q.lower() else "E"
            casos.append(
                {
                    "id": f"BOB-R5-P{p+1:03d}-T{turn:02d}",
                    "ronda": RONDA,
                    "persona_id": persona["persona_id"],
                    "turno": turn,
                    "nivel": nivel,
                    "categoria": cat,
                    "pregunta_enviada": q,
                    "respuesta_esperada": (
                        "Respuesta alineada a BD; aclara si vago; directorio si persona/área; "
                        "folio/nombre si experto; rechaza fuera de alcance."
                    ),
                    "senal_falla": (
                        "Menú 1/2/3 tras volvamos; PDF vecino; coincidencias; alucina jefe; "
                        "cambia doc en bullets; borra hilo."
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
    niveles = {
        "basico": sum(1 for p in personas if p["nivel"] == "basico"),
        "experto": sum(1 for p in personas if p["nivel"] == "experto"),
    }

    payload = {
        "ronda": RONDA,
        "modo": "100_personas_x_20_preguntas",
        "nivel_usuario": "mixto_basico_experto",
        "generado_en": now_iso(),
        "total": len(casos),
        "personas": N_PERSONAS,
        "preguntas_por_persona": N_PREGUNTAS,
        "por_nivel": niveles,
        "por_categoria": counts,
        "nota": "Ronda 5 post-mitigaciones GA. Historial previo en history/.",
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
        f"# Ronda {RONDA}: 100 personas (50 básicos + 50 expertos) x 20 preguntas",
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
        "modo": "100x20_mixto",
    }
    INDEX.write_text(json.dumps(idx, ensure_ascii=False, indent=2), encoding="utf-8")

    print(f"OK ronda {RONDA}: {N_PERSONAS} x {N_PREGUNTAS} = {len(casos)}")
    print("Niveles:", niveles)
    print("Categorias:", counts)
    print(f"Personas: {PERSONAS_JSON}")


if __name__ == "__main__":
    main()
