from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
from typing import Optional
import random

app = FastAPI(title="LearnQuestFeedbackAPI", version="1.1.0")


class QuizAttempt(BaseModel):
    child_id: str
    age: int = Field(..., ge=4, le=12, description="Age must be between 4 and 12")
    subject: str
    topic: str
    is_correct: bool
    attempts_count: int
    selected_answer: Optional[str] = None


FEEDBACK_BANK = {
    "younger": {
        "correct": [
            "You did it! Your brain just got a little stronger!",
            "Look at that! Keeping your focus helped you find the way.",
            "Wow! You tried a way, and it worked!"
        ],
        "incorrect_first_try": [
            "Oops, not quite! Let's try again. Which one looks fun to try next?",
            "Every mistake is a clue! Tap another one to see what happens.",
            "Our brains learn by trying! Let's give it another go"
        ],
        "incorrect_multiple_tries": [
            "You are working so hard! Let's look closely at the picture together.",
            "I love how you keep trying! Let's take a deep breath and check out the hint"
        ]
    },
    "older": {
        "correct": [
            "Excellent problem-solving. Your strategy really worked out here.",
            "Spot on! Notice how analyzing the details helped you unlock this answer",
            "Great work. You're building a really solid understanding of this concept"
        ],
        "incorrect_first_try": [
            "Not quite, but your logic is on the right track. What could you adjust?",
            "An interesting hypothesis! Take a closer look at the problem and try a different approach",
            "Close! Re-read the question carefully - there might be a small detail you missed"
        ],
        "incorrect_multiple_tries": [
            "You're showing great persistence. Let's pause and rethink the strategy here...",
            "Don't sweat the tough ones. Let's look at the clue to see how we can break this down"
        ]
    }
}


@app.post("/api/v1/feedback")
async def generate_feedback(attempt: QuizAttempt):
    try:
        age_group = "younger" if attempt.age <= 7 else "older"
        bank = FEEDBACK_BANK[age_group]

        if attempt.is_correct:
            category = "correct"
            audio_cue = "sparkle_celebration" if age_group == "younger" else "success_chime"
        elif attempt.attempts_count <= 1:
            category = "incorrect_first_try"
            audio_cue = "try_again_playful" if age_group == "younger" else "neutral_alert"
        else:
            category = "incorrect_multiple_tries"
            audio_cue = "encouraging_guide" if age_group == "younger" else "hint_alert"

        message = random.choice(bank[category])

        return {
            "child_id": attempt.child_id,
            "age_bracket": age_group,
            "feedback": {
                "message": message,
                "audio_cue": audio_cue,
                "ui_config": {
                    "text_size": "large" if age_group == "younger" else "medium",
                    "show_animation": attempt.is_correct or age_group == "younger",
                    "auto_play_audio": age_group == "younger",
                    "trigger_hint_overlay": not attempt.is_correct and attempt.attempts_count >= 2
                }
            }
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


class PerformanceData(BaseModel):
    subject: str
    skill_node: str
    accuracy_rate: float
    avg_response_time: float
    total_questions: int


@app.post('/api/feedback/generate')
async def generate_progress_feedback(data: PerformanceData):
    fb = {'status': 'neutral', 'summary': '', 'action_item': ''}
    sub = data.subject.lower()
    if sub == 'math':
        if data.accuracy_rate >= 0.85 and data.avg_response_time < 5.0:
            fb = {'status': 'mastered', 'summary': f'Your child has mastered {data.skill_node}! They are answering both quickly and accurately.', 'action_item': 'Ready to introduce the next complex concept.'}
        elif data.accuracy_rate >= 0.70 and data.avg_response_time > 10.0:
            fb = {'status': 'good', 'summary': f'They understand {data.skill_node} well, but are taking their time to think through the steps.', 'action_item': 'Encourage them to practice more to build up fluency and confidence.'}
        elif data.accuracy_rate >= 0.50:
            fb = {'status': 'progressing', 'summary': f'They are making steady progress with {data.skill_node}.', 'action_item': 'A little more practice will help them become faster and more confident.'}
        else:
            fb = {'status': 'struggling', 'summary': f'They are finding {data.skill_node} a bit challenging right now.', 'action_item': 'Try practicing with visual objects or breaking the problems into smaller steps.'}
    elif sub == 'english':
        if data.accuracy_rate < 0.50:
            fb = {'status': 'struggling', 'summary': f'They are working hard, but struggling with {data.skill_node} patterns.', 'action_item': 'Try reading out loud together to reinforce these sound or word patterns.'}
        else:
            fb = {'status': 'proficient', 'summary': f'Showing strong progress in {data.skill_node}.', 'action_item': 'Keep up the current reading routine.'}
    return fb

