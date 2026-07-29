"""Small HTTP surface over the two image tools the API leans on.

It stays deliberately thin: no business logic, no persistence. The API decides what to
do with the bytes and records every attempt in its own operations journal.
"""

import io

import pytesseract
from fastapi import FastAPI, File, UploadFile
from fastapi.responses import JSONResponse, Response
from PIL import Image
from rembg import new_session, remove

api = FastAPI(title="wardrobe ai sidecar")

_session = new_session("u2net")


@api.get("/health")
def health() -> dict[str, str]:
    """Report readiness so the API can tell an unavailable driver from a failing one."""
    return {"status": "ok", "tesseract": str(pytesseract.get_tesseract_version())}


@api.post("/cutout")
async def cutout(image: UploadFile = File(...)) -> Response:
    """Return the image with its background removed, always as a PNG with alpha."""
    source = Image.open(io.BytesIO(await image.read()))
    stripped = remove(source, session=_session)

    buffer = io.BytesIO()
    stripped.save(buffer, format="PNG")

    return Response(content=buffer.getvalue(), media_type="image/png")


@api.post("/ocr")
async def ocr(image: UploadFile = File(...)) -> JSONResponse:
    """Return whatever text the label carries, in French and English."""
    source = Image.open(io.BytesIO(await image.read()))
    text = pytesseract.image_to_string(source, lang="fra+eng")

    return JSONResponse({"text": text})
