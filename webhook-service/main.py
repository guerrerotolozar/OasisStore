import hashlib
import hmac
import html
import json
import logging
import os
import re
import sqlite3
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
ARTURO_LOG_BITACORA = os.getenv("ARTURO_LOG_BITACORA", os.getenv("ARTURO_LOG_TO_VAULT", "true")).strip().lower() == "true"
LOCAL_BITACORA_PATH = os.getenv("LOCAL_BITACORA_PATH", "/srv/storage/bitacora_oasis.db")

MAX_TIMESTAMP_DRIFT_SECONDS = 300


def init_local_db() -> None:
    """Inicializa la base de datos local SQLite para auditoría persistente interna."""
    try:
        db_dir = os.path.dirname(LOCAL_BITACORA_PATH)
        if db_dir and not os.path.exists(db_dir):
            os.makedirs(db_dir, exist_ok=True)
        with sqlite3.connect(LOCAL_BITACORA_PATH) as conn:
            conn.execute("""
                CREATE TABLE IF NOT EXISTS bitacora_eventos (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    evento TEXT NOT NULL,
                    titulo TEXT,
                    invoice_id INTEGER,
                    ticket_id INTEGER,
                    cliente_nombre TEXT,
                    cliente_email TEXT,
                    monto TEXT,
                    moneda TEXT,
                    detalles TEXT,
                    payload_json TEXT NOT NULL,
                    timestamp_origen TEXT,
                    creado_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
                );
            """)
            conn.execute("CREATE INDEX IF NOT EXISTS idx_oasis_evento ON bitacora_eventos(evento);")
            conn.execute("CREATE INDEX IF NOT EXISTS idx_oasis_invoice ON bitacora_eventos(invoice_id);")
            conn.commit()
    except Exception as exc:
        logger.warning("No se pudo inicializar SQLite local de bitácora: %s", exc)


def record_local_event(
    evento: str,
    titulo: str,
    invoice_id: Optional[int],
    ticket_id: Optional[int],
    cliente_nombre: str,
    cliente_email: str,
    monto: str,
    moneda: str,
    detalles: str,
    raw_payload: Dict[str, Any],
    timestamp: str,
) -> None:
    """Guarda una copia del evento en SQLite local (ultra liviano, 0 MB RAM en reposo)."""
    try:
        with sqlite3.connect(LOCAL_BITACORA_PATH) as conn:
            conn.execute("""
                INSERT INTO bitacora_eventos (
                    evento, titulo, invoice_id, ticket_id,
                    cliente_nombre, cliente_email, monto, moneda,
                    detalles, payload_json, timestamp_origen
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            """, (
                evento, titulo, invoice_id, ticket_id,
                cliente_nombre, cliente_email, monto, moneda,
                detalles, json.dumps(raw_payload, ensure_ascii=False), timestamp
            ))
            conn.commit()
    except Exception as exc:
        logger.warning("No se pudo guardar evento en SQLite local: %s", exc)


init_local_db()

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
    is_msg = payload.event == "ticket.message_created"
    inv_label = escape_discord_markdown(inv.number or inv.id)

    if is_msg:
        ticket_id = meta.get("ticket_id", inv.id)
        sender_name = escape_discord_markdown(meta.get("sender_name", inv.user_name or "Cliente"))
        is_admin = meta.get("is_admin", False)
        msg_text = str(meta.get("message_text", "")).strip()
        role_label = "Soporte" if is_admin else "Cliente"

        title = f"💬 Mensaje en Ticket #{ticket_id} — {sender_name} ({role_label})"
        color = 0x10B981 if is_admin else 0x3B82F6
        fields = [
            {
                "name": "Mensaje",
                "value": f"> {escape_discord_markdown(msg_text[:1000])}" if msg_text else "*(Archivo adjunto)*",
                "inline": False,
            },
            {
                "name": "Acción",
                "value": f"🎫 [Abrir Ticket en Dashboard]({payload.dashboard_links.admin_ticket_url})",
                "inline": False,
            },
        ]
    else:
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
    is_msg = payload.event == "ticket.message_created"
    inv_label = html.escape(str(inv.number or inv.id))

    if is_msg:
        ticket_id = meta.get("ticket_id", inv.id)
        sender_name = html.escape(str(meta.get("sender_name", inv.user_name or "Cliente")))
        is_admin = meta.get("is_admin", False)
        msg_text = html.escape(str(meta.get("message_text", "")).strip())
        role_label = "Soporte" if is_admin else "Cliente"

        lines = [
            f"💬 <b>NUEVO MENSAJE EN TICKET #{ticket_id}</b>",
            f"👤 <b>{role_label}:</b> {sender_name}",
            f"\n<blockquote>{msg_text or '<i>(Archivo adjunto)</i>'}</blockquote>\n",
            f"🎫 <a href='{html.escape(payload.dashboard_links.admin_ticket_url, quote=True)}'>Responder en Ticket Dashboard</a>",
        ]
    else:
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
    Despacha eventos en tiempo real hacia el gateway interno Arturo:
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
    is_msg = payload.event == "ticket.message_created"
    inv_label = str(inv.number or inv.id)
    ticket_id = meta.get("ticket_id")

    if is_msg:
        ticket_id = meta.get("ticket_id", inv.id)
        sender_name = meta.get("sender_name", inv.user_name or "Cliente")
        is_admin = meta.get("is_admin", False)
        msg_text = str(meta.get("message_text", "")).strip()
        role_label = "Soporte" if is_admin else "Cliente"
        preview = (msg_text[:120] + "...") if len(msg_text) > 120 else msg_text

        titulo_push = f"💬 OasisStore: Mensaje de {sender_name} (Ticket #{ticket_id})"
        mensaje_push = f"[{role_label}] {preview}" if preview else f"[{role_label}] Adjuntó archivo"
    else:
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

        # Canal 2: Bitácora estructurada en Base de Datos (SQLite/Arturo-Api y Local)
        # Se elimina el volcado a Markdown en la Bóveda para evitar indexaciones vectoriales innecesarias en LanceDB
        raw_dict = payload.model_dump() if hasattr(payload, "model_dump") else payload.dict()

        # Guardar siempre copia local en SQLite
        record_local_event(
            evento=payload.event,
            titulo=titulo_push,
            invoice_id=inv.id,
            ticket_id=ticket_id,
            cliente_nombre=str(inv.user_name or "Cliente"),
            cliente_email=str(inv.user_email or ""),
            monto=str(inv.total),
            moneda=str(inv.currency_code or "COP"),
            detalles=mensaje_push,
            raw_payload=raw_dict,
            timestamp=payload.timestamp,
        )

        if ARTURO_LOG_BITACORA:
            try:
                payload_bitacora = {
                    "proyecto": "OasisStore",
                    "evento": payload.event,
                    "titulo": titulo_push,
                    "invoice_id": inv.id,
                    "ticket_id": ticket_id,
                    "cliente_nombre": inv.user_name,
                    "cliente_email": inv.user_email,
                    "monto": inv.total,
                    "moneda": inv.currency_code,
                    "detalles": mensaje_push,
                    "payload": raw_dict,
                    "timestamp": payload.timestamp,
                }
                resp_bitacora = await client.post(
                    f"{ARTURO_API_URL}/api/bitacora",
                    headers=headers,
                    json=payload_bitacora,
                )
                resp_bitacora.raise_for_status()
            except Exception as exc:
                logger.warning("No se pudo registrar evento en Bitácora vía Arturo-Api: %s", exc)


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
