FROM python:3.12-alpine

WORKDIR /srv

# Ejecutar como usuario sin privilegios por seguridad
RUN adduser -D -u 10001 oasisbot

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY main.py .

USER oasisbot
EXPOSE 8000

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
