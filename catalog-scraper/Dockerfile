FROM mcr.microsoft.com/playwright/python:v1.58.0-jammy

WORKDIR /app

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY scraper.py .

RUN mkdir -p /app/data/images /app/public_export

CMD ["python", "scraper.py"]
