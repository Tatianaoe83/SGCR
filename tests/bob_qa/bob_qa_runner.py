#!/usr/bin/env python3
"""
Runner QA Bob — re-testing con historial acumulativo.

Reglas:
  - Nunca borra history/
  - `generate_dataset.py --ronda N` archiva el set activo y crea 500 preguntas NUEVAS
  - Los resultados se guardan en el JSON activo; al archivar quedan congelados

Uso rápido:
  python generate_dataset.py --ronda 2          # archiva r1 + genera 500 nuevas
  python bob_qa_runner.py resumen
  python bob_qa_runner.py comparar
  python bob_qa_runner.py marcar BOB-R2-001 OK
  python bob_qa_runner.py historial
  python bob_qa_runner.py cerrar-ronda
"""

from __future__ import annotations

import argparse
import csv
import json
import subprocess
import sys
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

DIR = Path(__file__).resolve().parent
JSON_PATH = DIR / "preguntas_bob_qa.json"
CSV_PATH = DIR / "preguntas_bob_qa.csv"
LOG_JSON = DIR / "test_log.json"
CHANGELOG = DIR / "CHANGELOG_pruebas.md"
INDEX = DIR / "historial_rondas.json"
HISTORY = DIR / "history"


def now_iso() -> str:
    return datetime.now(timezone.utc).astimezone().isoformat(timespec="seconds")


def load() -> dict[str, Any]:
    if not JSON_PATH.exists():
        raise SystemExit("Falta preguntas_bob_qa.json. Corre: python generate_dataset.py")
    return json.loads(JSON_PATH.read_text(encoding="utf-8"))


def save(data: dict[str, Any]) -> None:
    """Guarda activo. No toca history/."""
    JSON_PATH.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    cases = data["casos"]
    if not cases:
        return
    fields = list(cases[0].keys())
    with CSV_PATH.open("w", encoding="utf-8-sig", newline="") as f:
        w = csv.DictWriter(f, fieldnames=fields)
        w.writeheader()
        for c in cases:
            w.writerow({k: c.get(k, "") for k in fields})


def load_log() -> dict[str, Any]:
    if LOG_JSON.exists():
        return json.loads(LOG_JSON.read_text(encoding="utf-8"))
    return {"rondas": []}


def save_log(log: dict[str, Any]) -> None:
    LOG_JSON.write_text(json.dumps(log, ensure_ascii=False, indent=2), encoding="utf-8")


def load_index() -> dict[str, Any]:
    if INDEX.exists():
        return json.loads(INDEX.read_text(encoding="utf-8"))
    return {"rondas": [], "preguntas_usadas": []}


def append_changelog(block: str) -> None:
    prev = CHANGELOG.read_text(encoding="utf-8") if CHANGELOG.exists() else "# CHANGELOG pruebas Bob\n\n"
    CHANGELOG.write_text(prev + block + "\n", encoding="utf-8")


def find_case(data: dict[str, Any], cid: str) -> dict[str, Any]:
    for c in data["casos"]:
        if c["id"] == cid:
            return c
    raise SystemExit(f"No existe {cid} en el set ACTIVO (ronda {data.get('ronda')})")


def stats_from_cases(cases: list[dict]) -> dict[str, Any]:
    return {
        "total": len(cases),
        "ok": sum(1 for c in cases if c.get("resultado") == "OK"),
        "falla": sum(1 for c in cases if c.get("resultado") == "FALLA"),
        "corrompe": sum(1 for c in cases if c.get("resultado") == "CORROMPE_SERVICIO"),
        "sin_calificar": sum(1 for c in cases if not c.get("resultado")),
    }


def summarize(data: dict[str, Any]) -> str:
    st = stats_from_cases(data.get("casos", []))
    ronda = data.get("ronda", "?")
    log = load_log()
    last = log["rondas"][-1] if log["rondas"] else {}
    mejoradas = last.get("mejoradas", [])
    eliminadas = last.get("eliminadas", [])
    agregadas = last.get("agregadas", [])
    corruptos = [c["id"] for c in data.get("casos", []) if c.get("resultado") == "CORROMPE_SERVICIO"]

    lines = [
        f"Resumen de la prueba - [{now_iso()}] — Ronda {ronda}",
        f"Total de preguntas: {st['total']}",
        f"OK: {st['ok']}",
        f"Fallas detectadas: {st['falla']}",
        f"Sin calificar aún: {st['sin_calificar']}",
        f"Preguntas que corrompieron el servicio: {st['corrompe']}"
        + (f" ({', '.join(corruptos)})" if corruptos else ""),
        f"Preguntas mejoradas desde la última corrida: {len(mejoradas)}",
        f"Preguntas eliminadas: {len(eliminadas)}"
        + (
            " (" + "; ".join(f"{e.get('id')}: {e.get('motivo')}" for e in eliminadas) + ")"
            if eliminadas
            else ""
        ),
        f"Preguntas agregadas: {len(agregadas)}",
        f"Historial archivado: {HISTORY} (no se sobrescribe)",
    ]
    return "\n".join(lines)


def cmd_resumen(_: argparse.Namespace) -> None:
    data = load()
    text = summarize(data)
    print(text)
    append_changelog(f"\n## Resumen ronda {data.get('ronda')} {now_iso()}\n\n```\n{text}\n```\n")


def cmd_marcar(args: argparse.Namespace) -> None:
    data = load()
    c = find_case(data, args.id)
    c["resultado"] = args.resultado
    c["fecha_hora_prueba"] = now_iso()
    if args.respuesta is not None:
        c["respuesta_real_de_bob"] = args.respuesta
    if args.nota:
        c["nota_qa"] = args.nota
    if args.version:
        c["version_bob_nota"] = args.version
    save(data)
    print(f"Ronda {data.get('ronda')}: {args.id} → {args.resultado}")


def start_round_if_needed(log: dict[str, Any], ronda: int) -> dict[str, Any]:
    if not log["rondas"] or log["rondas"][-1].get("cerrada") or log["rondas"][-1].get("ronda") != ronda:
        round_obj = {
            "ronda": ronda,
            "inicio": now_iso(),
            "cerrada": False,
            "mejoradas": [],
            "eliminadas": [],
            "agregadas": [],
        }
        log["rondas"].append(round_obj)
    return log["rondas"][-1]


def cmd_mejora(args: argparse.Namespace) -> None:
    data = load()
    c = find_case(data, args.id)
    antes = args.antes or c["pregunta_enviada"]
    c["pregunta_enviada"] = args.despues
    c["pregunta_anterior"] = antes
    save(data)
    log = load_log()
    r = start_round_if_needed(log, int(data.get("ronda", 1)))
    r["mejoradas"].append(
        {"id": args.id, "antes": antes, "despues": args.despues, "motivo": args.motivo}
    )
    save_log(log)
    append_changelog(
        f"\n### Mejora {args.id} ({now_iso()})\n- Motivo: {args.motivo}\n- Antes: {antes}\n- Después: {args.despues}\n"
    )
    print("Mejora registrada (historial acumulado en test_log.json)")


def cmd_baja(args: argparse.Namespace) -> None:
    data = load()
    c = find_case(data, args.id)
    eliminada = {
        "id": args.id,
        "pregunta": c["pregunta_enviada"],
        "motivo": args.motivo,
        "fecha": now_iso(),
    }
    data["casos"] = [x for x in data["casos"] if x["id"] != args.id]
    data["total"] = len(data["casos"])
    save(data)
    log = load_log()
    r = start_round_if_needed(log, int(data.get("ronda", 1)))
    r["eliminadas"].append(eliminada)
    save_log(log)
    append_changelog(
        f"\n### Baja {args.id} ({now_iso()})\n- Motivo: {args.motivo}\n- Pregunta: {eliminada['pregunta'][:200]}\n"
    )
    print("Baja del set ACTIVO (la ronda archivada en history/ no se toca)")


def cmd_alta(args: argparse.Namespace) -> None:
    data = load()
    ronda = int(data.get("ronda", 1))
    nums = []
    for c in data["casos"]:
        try:
            nums.append(int(str(c["id"]).split("-")[-1]))
        except Exception:
            pass
    nid = (max(nums) + 1) if nums else 1
    cid = f"BOB-R{ronda}-{nid:03d}"
    nuevo = {
        "id": cid,
        "ronda": ronda,
        "categoria": args.cat,
        "pregunta_enviada": args.pregunta,
        "respuesta_esperada": args.esperada or "Evaluar según categoría.",
        "senal_falla": args.senal or "Comportamiento incorrecto / inseguro.",
        "respuesta_real_de_bob": "",
        "resultado": "",
        "fecha_hora_prueba": "",
        "version_bob_nota": "",
    }
    data["casos"].append(nuevo)
    data["total"] = len(data["casos"])
    save(data)
    log = load_log()
    r = start_round_if_needed(log, ronda)
    r["agregadas"].append({"id": cid, "motivo": args.motivo, "pregunta": args.pregunta})
    save_log(log)
    append_changelog(
        f"\n### Alta {cid} ({now_iso()})\n- Cat: {args.cat}\n- Motivo: {args.motivo}\n- Pregunta: {args.pregunta}\n"
    )
    print(f"Alta: {cid}")


def cmd_cerrar_ronda(_: argparse.Namespace) -> None:
    data = load()
    ronda = int(data.get("ronda", 1))
    text = summarize(data)
    log = load_log()
    r = start_round_if_needed(log, ronda)
    r["cerrada"] = True
    r["fin"] = now_iso()
    r["resumen"] = text
    r["stats"] = stats_from_cases(data.get("casos", []))
    save_log(log)

    # Congelar snapshot en history SIN borrar anteriores
    HISTORY.mkdir(parents=True, exist_ok=True)
    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    folder = HISTORY / f"ronda_{ronda:03d}_cerrada_{stamp}"
    folder.mkdir(parents=True, exist_ok=False)
    for name in ("preguntas_bob_qa.json", "preguntas_bob_qa.csv", "PREGUNTAS_PARA_COPIAR.md"):
        src = DIR / name
        if src.exists():
            (folder / name).write_bytes(src.read_bytes())
    (folder / "resumen.json").write_text(
        json.dumps(
            {"ronda": ronda, "cerrada_en": r["fin"], "stats": r["stats"], "resumen": text},
            ensure_ascii=False,
            indent=2,
        ),
        encoding="utf-8",
    )

    idx = load_index()
    idx.setdefault("rondas", []).append(
        {
            "ronda": ronda,
            "carpeta": str(folder.relative_to(DIR)),
            "cerrada_en": r["fin"],
            "stats": r["stats"],
        }
    )
    INDEX.write_text(json.dumps(idx, ensure_ascii=False, indent=2), encoding="utf-8")

    print(text)
    print(f"\nSnapshot guardado en {folder} (historial intacto)")
    append_changelog(f"\n## Ronda {ronda} cerrada {r['fin']}\n\n```\n{text}\n```\n\nArchivo: `{folder}`\n")


def cmd_historial(_: argparse.Namespace) -> None:
    idx = load_index()
    print("=== Historial de rondas (acumulativo) ===")
    if not idx.get("rondas") and not HISTORY.exists():
        print("(vacío)")
        return
    for r in idx.get("rondas", []):
        st = r.get("resumen") or r.get("stats") or {}
        print(
            f"Ronda {r.get('ronda')}: {r.get('carpeta')} | "
            f"OK={st.get('ok', st.get('OK', '?'))} "
            f"FALLA={st.get('falla', '?')} "
            f"CORROMPE={st.get('corrompe', st.get('corrompieron', '?'))}"
        )
    if HISTORY.exists():
        print("\nCarpetas en history/:")
        for p in sorted(HISTORY.iterdir()):
            if p.is_dir():
                print(f"  - {p.name}")


def cmd_comparar(_: argparse.Namespace) -> None:
    """Compara última ronda archivada vs activa (o las dos últimas en history)."""
    rows = []
    if HISTORY.exists():
        folders = sorted([p for p in HISTORY.iterdir() if p.is_dir()])
        for folder in folders[-5:]:
            resumen = folder / "resumen.json"
            pj = folder / "preguntas_bob_qa.json"
            if resumen.exists():
                rows.append(("hist", folder.name, json.loads(resumen.read_text(encoding="utf-8"))))
            elif pj.exists():
                data = json.loads(pj.read_text(encoding="utf-8"))
                rows.append(
                    (
                        "hist",
                        folder.name,
                        {"ronda": data.get("ronda"), **stats_from_cases(data.get("casos", []))},
                    )
                )

    if JSON_PATH.exists():
        data = load()
        rows.append(("activa", f"ronda_{data.get('ronda')}_ACTIVA", {"ronda": data.get("ronda"), **stats_from_cases(data["casos"])}))

    print("=== Comparación de progreso Bob ===\n")
    print(f"{'Fuente':<40} {'Ronda':>6} {'Total':>6} {'OK':>5} {'FALLA':>6} {'CORR':>5} {'Pend':>5}")
    for kind, name, st in rows:
        print(
            f"{name:<40} {str(st.get('ronda', '?')):>6} "
            f"{st.get('total', st.get('Total', 0)):>6} "
            f"{st.get('ok', 0):>5} {st.get('falla', 0):>6} "
            f"{st.get('corrompe', 0):>5} {st.get('sin_calificar', 0):>5}"
        )

    # Delta última hist vs activa
    hist = [r for r in rows if r[0] == "hist"]
    act = [r for r in rows if r[0] == "activa"]
    if hist and act:
        a = hist[-1][2]
        b = act[0][2]
        # Solo significativo si hay calificaciones
        if b.get("ok", 0) + b.get("falla", 0) > 0 and a.get("ok", 0) + a.get("falla", 0) > 0:
            print("\nDelta (activa - última archivada) en calificados:")
            print(f"  OK:     {b.get('ok', 0) - a.get('ok', 0):+d}")
            print(f"  FALLA:  {b.get('falla', 0) - a.get('falla', 0):+d}")
            print(f"  CORR:   {b.get('corrompe', 0) - a.get('corrompe', 0):+d}")
        else:
            print(
                "\nNota: la ronda activa aún no tiene calificaciones (o la archivada tampoco)."
            )
            print("Marca resultados con `marcar` y vuelve a correr `comparar`.")


def cmd_nueva_ronda(args: argparse.Namespace) -> None:
    """Atajo: archiva activa y genera 500 preguntas nuevas."""
    cmd = [sys.executable, str(DIR / "generate_dataset.py")]
    if args.ronda:
        cmd += ["--ronda", str(args.ronda)]
    print("Ejecutando:", " ".join(cmd))
    subprocess.check_call(cmd)
    print("\nListo. Revisa PREGUNTAS_PARA_COPIAR.md (set NUEVO).")
    print("Historial previo intacto en history/ — usa: python bob_qa_runner.py comparar")


def cmd_export_manual(_: argparse.Namespace) -> None:
    data = load()
    for c in data["casos"]:
        print(f"{c['id']}|{c['categoria']}|{c['pregunta_enviada']}")


def cmd_http(args: argparse.Namespace) -> None:
    import urllib.request

    data = load()
    ids = (
        [x.strip() for x in args.ids.split(",") if x.strip()]
        if args.ids
        else [c["id"] for c in data["casos"][: args.limit]]
    )

    for cid in ids:
        c = find_case(data, cid)
        body = json.dumps(
            {"query": c["pregunta_enviada"], "session_id": args.session or f"qa-{cid}"}
        ).encode("utf-8")
        req = urllib.request.Request(
            args.url,
            data=body,
            headers={
                "Content-Type": "application/json",
                "Accept": "application/json",
                **({"Cookie": args.cookie} if args.cookie else {}),
            },
            method="POST",
        )
        try:
            with urllib.request.urlopen(req, timeout=args.timeout) as resp:
                raw = resp.read().decode("utf-8", errors="replace")
                c["respuesta_real_de_bob"] = raw[:4000]
                c["fecha_hora_prueba"] = now_iso()
                if resp.status >= 500:
                    c["resultado"] = "CORROMPE_SERVICIO"
                print(f"{cid}: HTTP {resp.status}")
        except Exception as e:
            c["respuesta_real_de_bob"] = str(e)
            c["resultado"] = "CORROMPE_SERVICIO"
            c["fecha_hora_prueba"] = now_iso()
            print(f"{cid}: ERROR {e}")
    save(data)
    print(summarize(data))


def main() -> None:
    p = argparse.ArgumentParser(description="QA runner Bob (historial acumulativo)")
    sub = p.add_subparsers(dest="cmd", required=True)

    sub.add_parser("resumen")
    sub.add_parser("cerrar-ronda")
    sub.add_parser("export-manual")
    sub.add_parser("historial")
    sub.add_parser("comparar")

    nr = sub.add_parser("nueva-ronda", help="Archiva la activa y genera 500 preguntas NUEVAS")
    nr.add_argument("--ronda", type=int, default=0)

    m = sub.add_parser("marcar")
    m.add_argument("id")
    m.add_argument("resultado", choices=["OK", "FALLA", "CORROMPE_SERVICIO"])
    m.add_argument("--respuesta", default=None)
    m.add_argument("--nota", default="")
    m.add_argument("--version", default="", help="Nota de versión de Bob, ej. post-pin-BD")

    me = sub.add_parser("registrar-mejora")
    me.add_argument("id")
    me.add_argument("--antes", default="")
    me.add_argument("--despues", required=True)
    me.add_argument("--motivo", required=True)

    b = sub.add_parser("registrar-baja")
    b.add_argument("id")
    b.add_argument("--motivo", required=True)

    a = sub.add_parser("registrar-alta")
    a.add_argument("--cat", required=True, choices=list("ABCDEF"))
    a.add_argument("--pregunta", required=True)
    a.add_argument("--motivo", required=True)
    a.add_argument("--esperada", default="")
    a.add_argument("--senal", default="")

    h = sub.add_parser("http")
    h.add_argument("--url", required=True)
    h.add_argument("--cookie", default="")
    h.add_argument("--session", default="")
    h.add_argument("--ids", default="")
    h.add_argument("--limit", type=int, default=10)
    h.add_argument("--timeout", type=int, default=60)

    args = p.parse_args()
    {
        "resumen": cmd_resumen,
        "marcar": cmd_marcar,
        "registrar-mejora": cmd_mejora,
        "registrar-baja": cmd_baja,
        "registrar-alta": cmd_alta,
        "cerrar-ronda": cmd_cerrar_ronda,
        "export-manual": cmd_export_manual,
        "historial": cmd_historial,
        "comparar": cmd_comparar,
        "nueva-ronda": cmd_nueva_ronda,
        "http": cmd_http,
    }[args.cmd](args)


if __name__ == "__main__":
    main()
