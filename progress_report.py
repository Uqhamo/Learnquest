from fastapi import FastAPI
from pydantic import BaseModel
from typing import List

app = FastAPI()

class PerformanceData(BaseModel):
    subject: str
    skill_node: str
    accuracy_rate: float
    avg_response_time: float
    total_questions: int

@app.post("/api/feedback/generate")
async def generate_feedback(data: PerformanceData):
   
    feedback = {
        "status": "neutral",
        "summary": "",
        "action_item": ""
    }
    
    
    if data.subject.lower() == "math":
        if data.accuracy_rate >= 0.85 and data.avg_response_time < 5.0:
            feedback["status"] = "mastered"
            feedback["summary"] = f"Your child has mastered {data.skill_node}! They are answering both quickly and accurately."
            feedback["action_item"] = "Ready to introduce the next complex concept."
        elif data.accuracy_rate >= 0.70 and data.avg_response_time > 10.0:
            feedback["status"] = "processing"
            feedback["summary"] = f"They understand {data.skill_node} well, but are taking their time to think through the steps."
            feedback["action_item"] = "Encourage them to practice more to build up their fluency and confidence."
        else:
            feedback["status"] = "struggling"
            feedback["summary"] = f"They are finding {data.skill_node} a bit challenging right now."
            feedback["action_item"] = "Try practicing with visual objects or breaking the problems into smaller steps."

    
    elif data.subject.lower() == "english":
        if data.accuracy_rate < 0.70:
            feedback["status"] = "struggling"
            feedback["summary"] = f"They are working hard, but struggling with {data.skill_node} context patterns."
            feedback["action_item"] = "Try reading out loud together to help reinforce these sound or word patterns."
        else:
            feedback["status"] = "proficient"
            feedback["summary"] = f"Showing strong progress in {data.skill_node}."
            feedback["action_item"] = "Keep up the current reading routine."

    return feedback