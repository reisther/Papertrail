import json
import unittest
from types import SimpleNamespace
from unittest.mock import Mock, patch

from fastapi.testclient import TestClient

import gemini_service
import main
from models import TitleAnalysis


TITLES = [
    "AI Adviser Recommendation System",
    "Machine Learning Student Predictor",
    "Smart Attendance Using AI",
    "IoT Smart Classroom",
    "Cloud-Based Student Portal",
]


class GeminiServiceTest(unittest.TestCase):
    def test_structured_response_is_converted_to_searchable_analysis(self) -> None:
        interaction = SimpleNamespace(
            output_text=json.dumps(
                {
                    "summary": "The proposals combine AI and connected systems.",
                    "keywords": ["machine learning", "IoT", "cloud platform"],
                    "expertise": [
                        "Machine Learning",
                        "IoT",
                        "Cloud Computing",
                    ],
                }
            )
        )
        client = Mock()
        client.interactions.create.return_value = interaction

        with patch.object(gemini_service, "_client", return_value=client):
            result = gemini_service.analyze_titles(TITLES)

        self.assertIn("Expertise: Machine Learning, IoT, Cloud Computing", result.analysis)
        self.assertEqual(["machine learning", "IoT", "cloud platform"], result.keywords)
        client.interactions.create.assert_called_once()


class FastApiTest(unittest.TestCase):
    def test_health_endpoint(self) -> None:
        response = TestClient(main.app).get("/health")

        self.assertEqual(200, response.status_code)
        self.assertEqual("ok", response.json()["status"])

    def test_analyze_endpoint(self) -> None:
        result = TitleAnalysis(
            summary="AI-focused proposals.",
            keywords=["machine learning"],
            expertise=["Machine Learning"],
            analysis="AI-focused proposals. Expertise: Machine Learning.",
        )

        with patch.object(main, "analyze_titles", return_value=result):
            response = TestClient(main.app).post(
                "/analyze",
                json={f"title{index}": title for index, title in enumerate(TITLES, start=1)},
            )

        self.assertEqual(200, response.status_code)
        self.assertEqual(result.analysis, response.json()["analysis"])


if __name__ == "__main__":
    unittest.main()
