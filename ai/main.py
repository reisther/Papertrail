import logging

from fastapi import FastAPI, HTTPException, status

from gemini_service import (
    GeminiConfigurationError,
    analyze_titles,
    configured_model,
    gemini_is_configured,
)
from models import HealthResponse, TitleAnalysis, TitleRequest


logger = logging.getLogger("papertrail.ai")

app = FastAPI(
    title="PaperTrail AI",
    version="1.0.0",
    description="Local-only thesis title analysis service.",
)


@app.get("/health", response_model=HealthResponse)
def health() -> HealthResponse:
    return HealthResponse(
        gemini_configured=gemini_is_configured(),
        model=configured_model(),
    )


@app.post("/analyze", response_model=TitleAnalysis)
def analyze(data: TitleRequest) -> TitleAnalysis:
    try:
        return analyze_titles(data.as_list())
    except GeminiConfigurationError as exception:
        logger.warning("Gemini is not configured: %s", exception)
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="The title analysis service is not configured.",
        ) from exception
    except Exception as exception:
        logger.exception("Gemini title analysis failed.")
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail="The title analysis provider is temporarily unavailable.",
        ) from exception
