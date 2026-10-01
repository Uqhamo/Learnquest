from fastapi import FastAPI

app = FastAPI(
    title="LearnQuest Feedback API",
    version="1.0.0"
)

@app.get("/")
def home():
    return {
        "message": "LearnQuest Feedback API is working!"
    }