from gemini_service import analyze_titles


if __name__ == "__main__":
    result = analyze_titles(
        [
            "AI Adviser Recommendation System",
            "Machine Learning Student Predictor",
            "Smart Attendance Using AI",
            "IoT Smart Classroom",
            "Cloud-Based Student Portal",
        ]
    )
    print(result.model_dump_json(indent=2))
