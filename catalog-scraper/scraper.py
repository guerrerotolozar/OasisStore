"""
OasisStore — Motor Genérico de Extracción y Sincronización de Catálogo (Playwright + BS4)
-----------------------------------------------------------------------------------------
Plantilla abierta y agnóstica para sincronizar catálogos dinámicos (SPAs, tiendas web de
videojuegos o APIs de bienes digitales) hacia el esquema `catalogo.json` consumido por
el tema `oasis` de Paymenter.

Configuración por variables de entorno:
  - TARGET_STORE_URL: URL de la tienda o portal web a inspeccionar.
  - PUBLIC_EXPORT_DIR: Ruta compartida con el volumen público de OasisStore (`/app/public_export`).
  - NOTIFY_URL: Endpoint opcional para notificar fin de sincronización.
  - ARTURO_API_KEY: Llave de autenticación para el webhook de notificación.
"""

import hashlib
import json
import logging
import os
import re
import shutil
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Dict, Optional
from urllib.parse import urljoin, urlparse

import httpx
from bs4 import BeautifulSoup
from playwright.sync_api import sync_playwright

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)
logger = logging.getLogger("catalog-scraper")

BASE_DIR = Path(__file__).resolve().parent
DATA_DIR = BASE_DIR / "data"
IMAGES_DIR = DATA_DIR / "images"
AUTH_FILE = BASE_DIR / "auth.json"

TARGET_STORE_URL = os.getenv("TARGET_STORE_URL", "https://example.com/store").strip()
PUBLIC_EXPORT_DIR = os.getenv("PUBLIC_EXPORT_DIR", "").strip()
NOTIFY_URL = os.getenv("NOTIFY_URL", "").strip()
ARTURO_API_KEY = os.getenv("ARTURO_API_KEY", "").strip()


def parse_price_value(raw_text: str) -> float:
    cleaned = re.sub(r"[^\d.,]", "", raw_text or "")
    if not cleaned:
        return 0.0
    if "," in cleaned and "." in cleaned:
        if cleaned.rfind(",") > cleaned.rfind("."):
            cleaned = cleaned.replace(".", "").replace(",", ".")
        else:
            cleaned = cleaned.replace(",", "")
    elif "," in cleaned:
        cleaned = cleaned.replace(",", ".")
    try:
        return round(float(cleaned), 2)
    except ValueError:
        return 0.0


def download_asset(url: str, subfolder: str = "bundles") -> Optional[str]:
    if not url or not url.startswith(("http://", "https://")):
        return None

    target_dir = IMAGES_DIR / subfolder
    target_dir.mkdir(parents=True, exist_ok=True)

    ext = Path(urlparse(url).path).suffix.lower() or ".webp"
    if ext not in {".webp", ".png", ".jpg", ".jpeg", ".svg"}:
        ext = ".webp"

    digest = hashlib.sha256(url.encode("utf-8")).hexdigest()[:16]
    rel_path = f"images/{subfolder}/{digest}{ext}"
    full_path = DATA_DIR / rel_path

    if full_path.exists() and full_path.stat().st_size > 0:
        return rel_path

    try:
        with httpx.Client(timeout=15.0, follow_redirects=True) as client:
            resp = client.get(url)
            resp.raise_for_status()
            full_path.write_bytes(resp.content)
        return rel_path
    except Exception as exc:
        logger.warning("No se pudo descargar asset (%s): %s", url, exc)
        return None


def extract_catalog_from_dom(html_content: str, base_url: str) -> Dict[str, Any]:
    """
    Parsea el DOM renderizado y construye la estructura estándar `catalogo.json`.
    Personaliza los selectores CSS (`[data-catalog-section]`, `.bundle-card`, etc.)
    según la estructura de la página web o juego que desees integrar.
    """
    soup = BeautifulSoup(html_content, "html.parser")
    catalog: Dict[str, Any] = {
        "ultima_actualizacion": datetime.now(timezone.utc).isoformat(),
        "moneda_base": "COP",
        "categories": {
            "special": {},
            "pase_temporada": None,
            "creditos": {},
        },
    }

    for idx, card in enumerate(soup.select("[data-bundle-card], .bundle-card"), start=1):
        title_el = card.select_one("[data-bundle-title], .bundle-title, h3")
        price_el = card.select_one("[data-bundle-price], .bundle-price")
        img_el = card.select_one("img")

        if not title_el:
            continue

        name = title_el.get_text(strip=True)
        slug = re.sub(r"[^a-z0-9]+", "-", name.lower()).strip("-") or f"bundle-{idx}"
        raw_price = price_el.get_text(strip=True) if price_el else "$ 0,00 COP"
        price_val = parse_price_value(raw_price)

        img_src = img_el.get("src") if img_el else None
        local_img = download_asset(urljoin(base_url, img_src), "bundles") if img_src else None

        catalog["categories"]["special"][slug] = {
            "nombre": name,
            "precio": {
                "cop": price_val,
                "formateado": raw_price,
            },
            "imagenes": {
                "principal": local_img,
            },
            "extras": {
                "descripcion": "Paquete sincronizado automáticamente desde el catálogo origen.",
                "items": [],
            },
        }

    return catalog


def export_to_storefront(catalog: Dict[str, Any]) -> None:
    DATA_DIR.mkdir(parents=True, exist_ok=True)
    catalog_file = DATA_DIR / "catalogo.json"
    catalog_file.write_text(
        json.dumps(catalog, indent=2, ensure_ascii=False),
        encoding="utf-8",
    )
    logger.info("Catálogo guardado en %s", catalog_file)

    if not PUBLIC_EXPORT_DIR:
        return

    export_path = Path(PUBLIC_EXPORT_DIR)
    export_path.mkdir(parents=True, exist_ok=True)
    shutil.copy2(catalog_file, export_path / "catalogo.json")

    if IMAGES_DIR.exists():
        target_images = export_path / "images"
        if target_images.exists():
            shutil.rmtree(target_images)
        shutil.copytree(IMAGES_DIR, target_images)

    logger.info("Assets y catalogo.json sincronizados hacia %s", export_path)


def notify_completion(total_bundles: int, total_credits: int) -> None:
    if not (NOTIFY_URL and ARTURO_API_KEY):
        return
    try:
        with httpx.Client(timeout=8.0) as client:
            client.post(
                NOTIFY_URL,
                headers={"X-Arturo-Key": ARTURO_API_KEY},
                json={
                    "titulo": "OasisStore: Catálogo Sincronizado",
                    "mensaje": f"Bundles activos: {total_bundles} | Paquetes de divisa: {total_credits}",
                    "tipo": "oasis",
                },
            )
    except Exception as exc:
        logger.warning("Aviso webhook omitido: %s", exc)


def main() -> None:
    logger.info("Iniciando sincronización de catálogo desde: %s", TARGET_STORE_URL)
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context_kwargs: Dict[str, Any] = {"locale": "es-CO"}
        if AUTH_FILE.exists():
            context_kwargs["storage_state"] = str(AUTH_FILE)

        context = browser.new_context(**context_kwargs)
        page = context.new_page()
        page.goto(TARGET_STORE_URL, wait_until="networkidle", timeout=60000)
        html = page.content()
        browser.close()

    catalog = extract_catalog_from_dom(html, TARGET_STORE_URL)
    export_to_storefront(catalog)
    notify_completion(
        len(catalog["categories"].get("special", {})),
        len(catalog["categories"].get("creditos", {})),
    )


if __name__ == "__main__":
    main()
