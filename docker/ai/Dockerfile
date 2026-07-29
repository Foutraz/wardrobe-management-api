FROM python:3.12-slim

ENV PYTHONUNBUFFERED=1 \
    U2NET_HOME=/models

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        tesseract-ocr \
        tesseract-ocr-fra \
        tesseract-ocr-eng \
        curl \
    && rm -rf /var/lib/apt/lists/*

RUN pip install --no-cache-dir \
        "fastapi==0.115.*" \
        "uvicorn[standard]==0.32.*" \
        "python-multipart==0.0.*" \
        "pytesseract==0.3.*" \
        "rembg[cpu]==2.0.*" \
        "pillow==11.*"

RUN mkdir -p /models && python -c "from rembg import new_session; new_session('u2net')"

WORKDIR /app

COPY app.py /app/app.py

EXPOSE 9100

HEALTHCHECK --interval=15s --timeout=5s --start-period=20s \
    CMD curl -sf http://localhost:9100/health || exit 1

CMD ["uvicorn", "app:api", "--host", "0.0.0.0", "--port", "9100"]
