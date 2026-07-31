from typing import Literal

from pydantic import BaseModel, Field, field_validator


ExpertiseName = Literal[
    "Machine Learning",
    "AI Integration",
    "Cybersecurity",
    "IoT",
    "Cloud Computing",
    "Data Analytics",
    "Web Development",
    "Mobile Development",
    "Database Systems",
    "Networking",
]


class TitleRequest(BaseModel):
    title1: str = Field(min_length=1, max_length=255)
    title2: str = Field(min_length=1, max_length=255)
    title3: str = Field(min_length=1, max_length=255)
    title4: str = Field(min_length=1, max_length=255)
    title5: str = Field(min_length=1, max_length=255)

    @field_validator("title1", "title2", "title3", "title4", "title5")
    @classmethod
    def normalize_title(cls, value: str) -> str:
        normalized = " ".join(value.split())

        if not normalized:
            raise ValueError("Title must not be blank.")

        return normalized

    def as_list(self) -> list[str]:
        return [self.title1, self.title2, self.title3, self.title4, self.title5]


class GeminiAnalysis(BaseModel):
    summary: str = Field(
        min_length=1,
        max_length=800,
        description="A concise combined analysis of the five thesis proposals.",
    )
    keywords: list[str] = Field(
        min_length=1,
        max_length=15,
        description="Specific research, technology, and methodology keywords.",
    )
    expertise: list[ExpertiseName] = Field(
        min_length=1,
        max_length=5,
        description="The best matching adviser expertise categories.",
    )

    @field_validator("keywords")
    @classmethod
    def normalize_keywords(cls, values: list[str]) -> list[str]:
        normalized = [" ".join(value.split()) for value in values]
        return list(dict.fromkeys(value for value in normalized if value))

    @field_validator("expertise")
    @classmethod
    def unique_expertise(cls, values: list[ExpertiseName]) -> list[ExpertiseName]:
        return list(dict.fromkeys(values))


class TitleAnalysis(GeminiAnalysis):
    analysis: str = Field(
        description="Searchable analysis text used by the Laravel adviser matcher.",
    )


class HealthResponse(BaseModel):
    status: Literal["ok"] = "ok"
    gemini_configured: bool
    model: str
