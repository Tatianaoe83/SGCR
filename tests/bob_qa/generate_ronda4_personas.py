#!/usr/bin/env python3
"""
Ronda 4: simulación de 50 usuarios BÁSICOS × 50 preguntas de chat cada uno.
- No conocen bien jerarquía ni folios.
- Tono WhatsApp, typos, vaguedad, seguimientos cortos.
- Archiva la ronda activa previa en history/ (no sobrescribe historial).

Salida:
  - preguntas_bob_qa.json (activo, ronda 4, formato personas)
  - personas_ronda4.json (detalle por persona)
  - PREGUNTAS_PARA_COPIAR.md
"""

from __future__ import annotations

import csv
import hashlib
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
PERSONAS_JSON = OUT / "personas_ronda4.json"

RONDA = 4
N_PERSONAS = 50
N_PREGUNTAS = 50
SEED = 42


def now_iso() -> str:
    return datetime.now(timezone.utc).astimezone().isoformat(timespec="seconds")


def archive_active() -> None:
    if not ACTIVE_JSON.exists():
        return
    HISTORY.mkdir(parents=True, exist_ok=True)
    data = json.loads(ACTIVE_JSON.read_text(encoding="utf-8"))
    prev = int(data.get("ronda", 3))
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    folder = HISTORY / f"ronda_{prev:03d}_{stamp}"
    folder.mkdir(parents=True, exist_ok=False)
    for name in ("preguntas_bob_qa.json", "preguntas_bob_qa.csv", "PREGUNTAS_PARA_COPIAR.md"):
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
        {"ronda": prev, "carpeta": str(folder.relative_to(OUT)), "archivada_en": resumen["archivada_en"], "resumen": resumen}
    )
    INDEX.write_text(json.dumps(idx, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Archivada ronda {prev} -> {folder}")


PERFILES = [
    ("nuevo_obra", "Residente junior recién entrado", "obra", "no sé folios ni áreas"),
    ("aux_conta", "Auxiliar contable", "finanzas", "solo sé pagos y facturas"),
    ("analista_jr", "Analista junior", "oficina", "no conozco organigrama"),
    ("rh_nuevo", "Apoyo RH nuevo", "rh", "confundido con vacaciones/nómina"),
    ("compras_jr", "Compras junior", "compras", "necesito proveedores y OC"),
    ("calidad_jr", "Calidad junior", "calidad", "busca procedimientos sin nombre"),
    ("ti_soporte", "Soporte TI básico", "ti", "usuarios le preguntan cosas raras"),
    ("juridico_asist", "Asistente jurídico", "juridico", "busca fianzas/contratos"),
    ("almacen", "Almacén / logística", "obra", "materiales y maquinaria"),
    ("recepcion", "Recepción / admin", "corporativo", "preguntas muy generales"),
]


def persona_name(i: int, perfil: str) -> str:
    nombres = [
        "Luis", "Ana", "Diego", "María", "José", "Sofía", "Carlos", "Elena", "Miguel", "Paula",
        "Andrés", "Lucia", "Pedro", "Valeria", "Jorge", "Camila", "Ricardo", "Fernanda", "Héctor", "Diana",
        "Roberto", "Natalia", "Gabriel", "Andrea", "Felipe", "Karla", "Oscar", "Patricia", "Iván", "Monica",
        "Raúl", "Jimena", "Sergio", "Alejandra", "Manuel", "Claudia", "Eduardo", "Isabel", "Francisco", "Laura",
        "Alberto", "Daniela", "Ernesto", "Gabriela", "Hugo", "Verónica", "Tomas", "Adriana", "Bruno", "Silvia",
    ]
    return f"{nombres[i % len(nombres)]} ({perfil})"


def build_chat(persona_idx: int, perfil_id: str, rol: str, area: str, rng: random.Random) -> list[str]:
    """50 turnos: usuario básico, sin contexto de jerarquía/folios."""
    tag = f"[u{persona_idx:02d}|{perfil_id}|r4]"
    docs_vagos = [
        "pagos", "cierre", "campamentos", "capacitar", "presupuestos",
        "compras", "juridico", "calidad", "obra", "maquinaria", "facturas", "vacaciones",
    ]
    tema = rng.choice(docs_vagos)
    tema2 = rng.choice([t for t in docs_vagos if t != tema])

    qs: list[str] = []

    # 1-8: entrada / orientación
    qs += [
        rng.choice(["hola", "holi bob", "buenas", "hey", "hola ayuda"]),
        rng.choice(["no se bien como funciona esto", "soy nuevo aqui", "apenas entro a proser", "me mareo con tantos docs"]),
        rng.choice(["q puedes hacer?", "en que me ayudas", "menu", "ayuda"]),
        f"necesito algo de {tema}",
        rng.choice(["no se el nombre exacto", "no tengo el folio", "no me se como se llama"]),
        rng.choice(["el de siempre", "ese del area", "lo de mi trabajo", "algo relacionado"]),
        f"pa que sirve lo de {tema}?",
        rng.choice(["mas corto pls", "explicame facil", "como para novato"]),
    ]

    # 9-18: búsqueda torpe de procedimientos
    qs += [
        f"hay procedimiento de {tema}?",
        rng.choice([f"solitud de {tema}", f"proc de {tema}", f"documento {tema} urgnt"]),
        "y el objetivo?",
        "alcance?",
        "quien es el responsable",
        "a quien le pregunto yo",
        rng.choice(["mi jefe quien es", "a quien reporto", "quien me puede ayudar con eso"]),
        "no se el puesto de mi jefe",
        f"procedimientos de {area}",
        rng.choice(["mis procedimientos", "los mios", "que me toca a mi"]),
    ]

    # 19-28: directorio / organigrama confuso
    qs += [
        "dime las unidades",
        rng.choice(["que areas hay", "areas de la empresa?", "como esta organizada"]),
        rng.choice(["quien es el coordinador de ti", "cordinador de TI", "encargado de sistemas"]),
        rng.choice(["gerente de rh", "jefe de RH", "recursos humanos quien manda"]),
        "lista de directores",
        "no entiendo la diferencia entre area y unidad",
        "y el de juridico?",
        "personas del area de presupuestos",
        rng.choice(["alberto bas", "quien es alberto", "busca a alberto"]),
        "ok y ahora volvamos a lo del procedimiento",
    ]

    # 29-38: seguimiento / se pierde / sí / no
    qs += [
        rng.choice(["pero ese no es", "ese no era", "no eso"]),
        rng.choice(["el segundo", "el primero", "abre el 1"]),
        "un resumen",
        "las funciones generales",
        "sí",
        "dale",
        rng.choice(["me perdí", "te confundiste", "volvamos"]),
        f"mejor {tema2}",
        "y si tengo una factura telcel a quien se la mando",
        "no se si es gasto o cobro",
    ]

    # 39-46: más ruido básico
    qs += [
        rng.choice(["?", "??", "ayuda otra vez"]),
        "corta solo el folio",
        "mas detalle",
        "en bullets",
        f"version del de {tema}?",
        "aplica a construccion?",
        "relacionados?",
        rng.choice(["gracias", "ok thx", "va gracias"]),
    ]

    # 47-50: cierre / cambio nuevo
    qs += [
        rng.choice(["otra duda", "cambiemos de tema", "otra cosa"]),
        rng.choice(["mis procedimientos otra vez", "unidades otra vez", "directorio"]),
        rng.choice(["eso es todo", "bye", "nos vemos", "listo"]),
        rng.choice(["reset", "empezar de nuevo", "hola de nuevo"]),
    ]

    # Exactamente 50 + tag único
    qs = qs[:N_PREGUNTAS]
    while len(qs) < N_PREGUNTAS:
        qs.append(f"seguimiento basico {len(qs)+1}")
    out = []
    for i, q in enumerate(qs, 1):
        out.append(f"{q} {tag}#{i:02d}")
    return out


def main() -> None:
    rng = random.Random(SEED)
    archive_active()

    personas = []
    casos = []
    case_i = 0

    for p in range(N_PERSONAS):
        perfil_id, rol, area, nota = PERFILES[p % len(PERFILES)]
        # variar un poco perfiles repetidos
        if p >= len(PERFILES):
            nota = nota + f" variante {p // len(PERFILES)}"
        nombre = persona_name(p, perfil_id)
        chat = build_chat(p + 1, perfil_id, rol, area, rng)
        persona = {
            "persona_id": f"P{p+1:02d}",
            "nombre": nombre,
            "perfil": perfil_id,
            "rol_simulado": rol,
            "area_aproximada": area,
            "nivel": "basico",
            "nota": nota,
            "session_id": f"r4-persona-{p+1:02d}",
            "preguntas": chat,
        }
        personas.append(persona)

        for turn, q in enumerate(chat, 1):
            case_i += 1
            # Categoría aproximada por fase del chat
            if turn <= 8:
                cat = "E"
            elif turn <= 18:
                cat = "A"
            elif turn <= 28:
                cat = "A"
            elif turn <= 38:
                cat = "B"
            else:
                cat = "E"
            casos.append(
                {
                    "id": f"BOB-R4-P{p+1:02d}-T{turn:02d}",
                    "ronda": RONDA,
                    "persona_id": persona["persona_id"],
                    "turno": turn,
                    "categoria": cat,
                    "pregunta_enviada": q,
                    "respuesta_esperada": (
                        "Respuesta clara para usuario básico: sin jerga, aclara si falta dato, "
                        "usa BD (proc/puesto/directorio) o rechaza fuera de alcance."
                    ),
                    "senal_falla": (
                        "Alucina jerarquía; pide folio a la fuerza; coincidencias random; "
                        "pierde hilo; menú genérico que confunde; PDF sticky."
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
        "modo": "50_personas_x_50_preguntas",
        "nivel_usuario": "basico",
        "generado_en": now_iso(),
        "total": len(casos),
        "personas": N_PERSONAS,
        "preguntas_por_persona": N_PREGUNTAS,
        "por_categoria": counts,
        "nota": "Usuarios básicos sin contexto amplio de jerarquía/procedimientos. Historial previo en history/.",
        "casos": casos,
    }
    ACTIVE_JSON.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")

    PERSONAS_JSON.write_text(
        json.dumps(
            {"ronda": RONDA, "generado_en": now_iso(), "personas": personas},
            ensure_ascii=False,
            indent=2,
        ),
        encoding="utf-8",
    )

    fields = list(casos[0].keys())
    with ACTIVE_CSV.open("w", encoding="utf-8-sig", newline="") as f:
        w = csv.DictWriter(f, fieldnames=fields)
        w.writeheader()
        w.writerows(casos)

    lines = [
        f"# Ronda {RONDA}: 50 personas basicas x 50 preguntas",
        "",
        "Formato: `ID | PERSONA | TURNO | CAT | mensaje`",
        "",
    ]
    for c in casos:
        q = c["pregunta_enviada"].replace("\n", "\\n")
        lines.append(f"{c['id']} | {c['persona_id']} | T{c['turno']:02d} | {c['categoria']} | {q}")
    ACTIVE_MD.write_text("\n".join(lines), encoding="utf-8")

    idx = json.loads(INDEX.read_text(encoding="utf-8")) if INDEX.exists() else {"rondas": [], "preguntas_usadas": []}
    idx["activa"] = {
        "ronda": RONDA,
        "generada_en": now_iso(),
        "total": len(casos),
        "modo": "50x50_basicos",
    }
    INDEX.write_text(json.dumps(idx, ensure_ascii=False, indent=2), encoding="utf-8")

    print(f"OK ronda {RONDA}: {N_PERSONAS} personas x {N_PREGUNTAS} = {len(casos)}")
    print("Categorias:", counts)
    print(f"Personas: {PERSONAS_JSON}")


if __name__ == "__main__":
    main()
