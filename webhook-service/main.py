import hashlib
import hmac
import html
import json
import logging
import os
import re
from datetime import datetime, timezone
from typing import Any, Dict, Optional

import httpx
from fastapi import FastAPI, Header, HTTPException, Request
from pydantic import BaseModel, ValidationError

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)
logger = logging.getLogger("oasis-webhook")

WEBHOOK_SECRET = os.getenv("WEBHOOK_SECRET", "").strip()
if (
    not WEBHOOK_SECRET
    or WEBHOOK_SECRET == "__GENERAR_CON_DEPLOY_SH__"
    or WEBHOOK_SECRET.startswith("aqui_pon_tu_")
    or len(WEBHOOK_SECRET) < 32
):
    raise RuntimeError(
        "WEBHOOK_SECRET ausente o inseguro. Debe tener al menos 32 caracteres aleatorios en .env"
    )

DISCORD_WEBHOOK_URL = os.getenv("DISCORD_WEBHOOK_URL", "").strip()
TELEGRAM_BOT_TOKEN = os.getenv("TELEGRAM_BOT_TOKEN", "").strip()
TELEGRAM_CHAT_ID = os.getenv("TELEGRAM_CHAT_ID", "").strip()

# --- Integración con el Orquestador / Gateway de Notificaciones Interno ---
ARTURO_API_URL = os.getenv("ARTURO_API_URL", "").strip().rstrip("/")
ARTURO_API_KEY = os.getenv("ARTURO_API_KEY", "").strip()
ARTURO_VAULT_FOLDER = os.getenv("ARTURO_VAULT_FOLDER", "OPERACIONES/OasisStore").strip()
ARTURO_LOG_TO_VAULT = os.getenv("ARTURO_LOG_TO_VAULT", "true").strip().lower() == "true"

MAX_TIMESTAMP_DRIFT_SECONDS = 300

# Desactivar /docs y /openapi.json en producción
app = FastAPI(
    title="OasisStore Webhook Dispatcher",
    docs_url=None,
    redoc_url=None,
    openapi_url=None,
)


class InvoiceInfo(BaseModel):
    id: int
    number: Optional[str] = None
    status: str
    total: str
    currency_code: Optional[str] = "MXN"
    user_name: Optional[str] = "Cliente"
    user_email: Optional[str] = ""


class DashboardLinks(BaseModel):
    admin_invoice_url: str
    admin_ticket_url: str
    admin_service_url: Optional[str] = None
    protected_receipt_url: Optional[str] = None


class PaymenterWebhookPayload(BaseModel):
    event: str
    timestamp: str
    invoice: InvoiceInfo
    dashboard_links: DashboardLinks
    meta: Dict[str, Any] = {}


def verify_hmac_signature(raw_body: bytes, received_signature: Optional[str]) -> bool:
    if not received_signature:
        return False
    expected = hmac.new(
        WEBHOOK_SECRET.encode("utf-8"),
        raw_body,
        hashlib.sha256,
    ).hexdigest()
    return hmac.compare_digest(expected, received_signature)


def is_timestamp_fresh(iso_ts: str) -> bool:
    try:
        parsed = datetime.fromisoformat(iso_ts.replace("Z", "+00:00"))
        now = datetime.now(timezone.utc)
        return abs((now - parsed).total_seconds()) <= MAX_TIMESTAMP_DRIFT_SECONDS
    except ValueError:
        return False


def escape_discord_markdown(text: Any) -> str:
    raw = str(text or "")
    return re.sub(r"([\\_*~`|>#\[\]()@])", r"\\\1", raw)


async def notify_discord(payload: PaymenterWebhookPayload) -> None:
    if not DISCORD_WEBHOOK_URL:
        return

    inv = payload.invoice
    meta = payload.meta
    is_paid = payload.event == "invoice.paid"
    inv_label = escape_discord_markdown(inv.number or inv.id)

    title = (
        f"✅ Factura #{inv_label} PAGADA — Orden en Pending Setup"
        if is_paid
        else f"🧾 Nuevo Comprobante P2P Privado — Factura #{inv_label}"
    )
    color = 0x10B981 if is_paid else 0x6366F1

    safe_user = escape_discord_markdown(inv.user_name)
    safe_email = escape_discord_markdown(inv.user_email)
    safe_total = escape_discord_markdown(f"{inv.total} {inv.currency_code}")
    safe_state = escape_discord_markdown(meta.get("order_state", inv.status))

    fields = [
        {"name": "Cliente", "value": f"{safe_user} (`{safe_email}`)", "inline": True},
        {"name": "Monto", "value": f"**{safe_total}**", "inline": True},
        {"name": "Estado", "value": f"`{safe_state}`", "inline": True},
    ]

    if meta.get("payment_channel"):
        fields.append(
            {
                "name": "Método P2P",
                "value": escape_discord_markdown(meta["payment_channel"]),
                "inline": True,
            }
        )
    if meta.get("transaction_ref"):
        fields.append(
            {
                "name": "Referencia / TxID",
                "value": f"`{escape_discord_markdown(meta['transaction_ref'])}`",
                "inline": True,
            }
        )
    if meta.get("player_identifier"):
        fields.append(
            {
                "name": "ID Destino",
                "value": f"`{escape_discord_markdown(meta['player_identifier'])}`",
                "inline": True,
            }
        )

    links_lines = [
        f"🔗 [Abrir Factura en Admin Dashboard]({payload.dashboard_links.admin_invoice_url})",
        f"🎫 [Abrir Ticket en Admin Dashboard]({payload.dashboard_links.admin_ticket_url})",
    ]
    if payload.dashboard_links.admin_service_url:
        links_lines.append(
            f"📦 [Activar Servicio Manualmente]({payload.dashboard_links.admin_service_url})"
        )
    if payload.dashboard_links.protected_receipt_url:
        links_lines.append(
            f"🔒 [Descargar Comprobante (Requiere Login Admin)]({payload.dashboard_links.protected_receipt_url})"
        )

    fields.append(
        {
            "name": "Acciones en Dashboard (Privado)",
            "value": "\n".join(links_lines),
            "inline": False,
        }
    )

    # Nota de seguridad: jamás se adjunta la imagen del comprobante al CDN público de Discord
    embed: Dict[str, Any] = {
        "title": title,
        "color": color,
        "fields": fields,
        "footer": {"text": f"OasisStore • {payload.timestamp}"},
    }

    async with httpx.AsyncClient(timeout=8.0) as client:
        resp = await client.post(
            DISCORD_WEBHOOK_URL,
            json={
                "embeds": [embed],
                "allowed_mentions": {"parse": []},
            },
        )
        resp.raise_for_status()


async def notify_telegram(payload: PaymenterWebhookPayload) -> None:
    if not (TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID):
        return

    inv = payload.invoice
    meta = payload.meta
    is_paid = payload.event == "invoice.paid"
    inv_label = html.escape(str(inv.number or inv.id))

    header = (
        f"✅ <b>FACTURA #{inv_label} PAGADA (Pending Setup)</b>"
        if is_paid
        else f"🧾 <b>NUEVO COMPROBANTE P2P — FACTURA #{inv_label}</b>"
    )

    lines = [
        header,
        f"👤 <b>Cliente:</b> {html.escape(str(inv.user_name))} ({html.escape(str(inv.user_email))})",
        f"💰 <b>Total:</b> {html.escape(str(inv.total))} {html.escape(str(inv.currency_code))}",
    ]
    if meta.get("payment_channel"):
        lines.append(f"🏦 <b>Método:</b> {html.escape(str(meta['payment_channel']))}")
    if meta.get("transaction_ref"):
        lines.append(f"🔑 <b>Referencia/Hash:</b> <code>{html.escape(str(meta['transaction_ref']))}</code>")
    if meta.get("player_identifier"):
        lines.append(f"🎮 <b>ID Destino:</b> <code>{html.escape(str(meta['player_identifier']))}</code>")

    lines.append(
        f"\n🛡️ <a href='{html.escape(payload.dashboard_links.admin_invoice_url, quote=True)}'>Abrir Factura en Dashboard</a>"
    )
    lines.append(
        f"🎫 <a href='{html.escape(payload.dashboard_links.admin_ticket_url, quote=True)}'>Abrir Ticket en Dashboard</a>"
    )
    if payload.dashboard_links.admin_service_url:
        lines.append(
            f"📦 <a href='{html.escape(payload.dashboard_links.admin_service_url, quote=True)}'>Activar Servicio en Dashboard</a>"
        )
    if payload.dashboard_links.protected_receipt_url:
        lines.append(
            f"🔒 <a href='{html.escape(payload.dashboard_links.protected_receipt_url, quote=True)}'>Ver Comprobante (Con Login)</a>"
        )

    url = f"https://api.telegram.org/bot{TELEGRAM_BOT_TOKEN}/sendMessage"
    async with httpx.AsyncClient(timeout=8.0) as client:
        resp = await client.post(
            url,
            json={
                "chat_id": TELEGRAM_CHAT_ID,
                "text": "\n".join(lines),
                "parse_mode": "HTML",
                "disable_web_page_preview": True,
            },
        )
        resp.raise_for_status()


async def notify_arturo(payload: PaymenterWebhookPayload) -> None:
    """
    Despacha eventos en tiempo real hacia el gateway interno configurado:
      1. Notificación Push en tiempo real (SSE) via POST /api/notifications.
      2. Registro estructurado en bitácora Markdown via POST /api/notas.
    """
    if (
        not (ARTURO_API_URL and ARTURO_API_KEY)
        or ARTURO_API_KEY == "__GENERAR_CON_DEPLOY_SH__"
        or ARTURO_API_KEY.startswith("aqui_pon_tu_")
    ):
        return

    inv = payload.invoice
    meta = payload.meta
    is_paid = payload.event == "invoice.paid"
    inv_label = str(inv.number or inv.id)

    titulo_push = (
        f"OasisStore: Factura #{inv_label} PAGADA"
        if is_paid
        else f"OasisStore: Comprobante P2P (#{inv_label})"
    )

    detalles = [
        f"Cliente: {inv.user_name} ({inv.user_email})",
        f"Total: {inv.total} {inv.currency_code}",
    ]
    if meta.get("payment_channel"):
        detalles.append(f"Método: {meta['payment_channel']}")
    if meta.get("transaction_ref"):
        detalles.append(f"Ref: {meta['transaction_ref']}")
    if meta.get("player_identifier"):
        detalles.append(f"ID Destino: {meta['player_identifier']}")

    mensaje_push = " | ".join(detalles)
    headers = {
        "Content-Type": "application/json",
        "X-Arturo-Key": ARTURO_API_KEY,
    }

    async with httpx.AsyncClient(timeout=8.0) as client:
        # Canal 1: Push instantáneo SSE hacia ArturoApp
        try:
            resp_push = await client.post(
                f"{ARTURO_API_URL}/api/notifications",
                headers=headers,
                json={
                    "titulo": titulo_push,
                    "mensaje": mensaje_push,
                    "tipo": "oasis",
                },
            )
            resp_push.raise_for_status()
        except Exception as exc:
            logger.warning("No se pudo enviar Push a Arturo-Api: %s", exc)

        # Canal 2: Bitácora Markdown estructurada
        if ARTURO_LOG_TO_VAULT:
            try:
                estado_orden = meta.get("order_state", inv.status)
                lineas_md = [
                    f"### {titulo_push}",
                    f"- **Evento:** `{payload.event}`",
                    f"- **Fecha UTC:** `{payload.timestamp}`",
                    f"- **Cliente:** {inv.user_name} (`{inv.user_email}`)",
                    f"- **Monto:** **{inv.total} {inv.currency_code}**",
                    f"- **Estado Orden:** `{estado_orden}`",
                ]
                if meta.get("payment_channel"):
                    lineas_md.append(f"- **Canal P2P:** {meta['payment_channel']}")
                if meta.get("transaction_ref"):
                    lineas_md.append(f"- **Referencia / TxID:** `{meta['transaction_ref']}`")
                if meta.get("player_identifier"):
                    lineas_md.append(f"- **ID Destino:** `{meta['player_identifier']}`")
                lineas_md.append(f"- **Factura Admin:** {payload.dashboard_links.admin_invoice_url}")

                resp_nota = await client.post(
                    f"{ARTURO_API_URL}/api/notas",
                    headers=headers,
                    json={
                        "titulo": "Bitacora_Ordenes_OasisStore",
                        "carpeta": ARTURO_VAULT_FOLDER,
                        "contexto": "\n".join(lineas_md),
                    },
                )
                resp_nota.raise_for_status()
            except Exception as exc:
                logger.warning("No se pudo registrar nota en Bóveda vía Arturo-Api: %s", exc)


async def request_arturo_inference(prompt: str, model: str = "gemma4:12b-it-qat") -> Optional[str]:
    """
    Helper para solicitar inferencia LLM a través de la cola centralizada del orquestador.
    """
    if not (ARTURO_API_URL and ARTURO_API_KEY):
        return None

    headers = {
        "Content-Type": "application/json",
        "X-Arturo-Key": ARTURO_API_KEY,
    }
    async with httpx.AsyncClient(timeout=180.0) as client:
        resp = await client.post(
            f"{ARTURO_API_URL}/api/bot/inference",
            headers=headers,
            json={"prompt": prompt, "model": model},
        )
        resp.raise_for_status()
        data = resp.json()
        return data.get("result")


@app.get("/health")
async def healthcheck():
    return {
        "status": "ok",
        "service": "oasis-webhook",
        "arturo_api_url": ARTURO_API_URL,
        "arturo_key_configured": bool(
            ARTURO_API_KEY and ARTURO_API_KEY != "__GENERAR_CON_DEPLOY_SH__"
        ),
    }


@app.post("/webhook/paymenter")
async def receive_paymenter_webhook(
    request: Request,
    x_oasis_signature: Optional[str] = Header(default=None),
):
    raw_body = await request.body()

    if not verify_hmac_signature(raw_body, x_oasis_signature):
        logger.warning("Firma HMAC inválida rechazada")
        raise HTTPException(status_code=401, detail="Firma HMAC inválida")

    try:
        data = json.loads(raw_body.decode("utf-8"))
        payload = PaymenterWebhookPayload(**data)
    except (UnicodeDecodeError, json.JSONDecodeError, ValidationError) as exc:
        logger.warning("Payload inválido en webhook: %s", exc)
        raise HTTPException(status_code=400, detail="Payload JSON inválido")

    if not is_timestamp_fresh(payload.timestamp):
        logger.warning("Webhook rechazado por timestamp fuera de ventana: %s", payload.timestamp)
        raise HTTPException(status_code=401, detail="Timestamp expirado (posible replay)")

    logger.info(
        "Evento recibido: %s | Factura #%s | Estado: %s",
        payload.event,
        payload.invoice.id,
        payload.invoice.status,
    )

    try:
        await notify_arturo(payload)
        await notify_discord(payload)
        await notify_telegram(payload)
    except Exception as exc:
        logger.error("Error enviando alerta externa: %s", exc)

    return {
        "ok": True,
        "event": payload.event,
        "invoice_id": payload.invoice.id,
    }
