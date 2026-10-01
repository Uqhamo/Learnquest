from fastapi import FastAPI, HTTPException
from pydantic import BaseModel,Field
from typing import Optional
import random

app = FastAPI(title="LearnQuestFeedbackAPI", version="1.1.0")
class QuizAttempt(BaseModel):
    child_id:str
    age: int = Field(...,ge=4,le=12, description = "Age must be between 4 and 12")
    subject:str
    topic:str
    is_correct:bool
    attempts_count:int
    selected_answer: Optional[str] =None

    FEEDBACK_BANK ={
        "younger":{

            "correct":[
                "You did it! Your brain just got a little stronger!",
                "Look at that! Keeping your focus helped you find the way.",
                "Wow! You tried a way, and it worked!"
            ],
            "incorrect_first_try":[
                "Oops,not quite! Lets's try again. Which one looks fun to try next?",
                "Every mistake is clue! Tap another one to see what happens.",
                "Our brains learn by trying! Let's give it another go"
            ],
            "incorrect_multiple_tries":[
                "You are working so hard! Let's look closely at the picture together.",
                "I love how you keep trying! Let's take a deep breath and check out the hint"
            ]
        },
        "older" :{
            "correct":[
                "Excellent problem-solving.Your strategy really worked out here.",
                "Spot on! Notice how analyzing the details helped you unlock this answer",
                "Great work. You're building a really solid understanding of this concept"
            ],
            "incorrect_first_try":[
            "Not quite, but your logic is on the right track. What could you adjust?",
            "An interesting hypothesis! Take a closer look at the problem and try a different approach",
            "Close! Re-read the question carefully-there might be a small detail you missed"
            ],

            "incorrect_multiple_tries":[
            "You're showing great persistence. Let's pause and rethink the strategy here...",
            "Don't sweat the tough ones.Let's look at the clue to see how we can break this down"
            ]
        }
    }
    @app.post("/api/v1/feedback")
    async def generate_feedback(attempt:QuizAttempt):
        try:
            age_group = "younger" if attempt.age <= 7 else "older"
            bank = FEEDBACK_BANK[age_group]

            if attempt.is_correct:
                category ="correct"
                audio_cue = "sparkle_celebration" if age_group == "younger" else "success_chime"
            elif attempt.attempts_count <=1:
                category ="incorrect_first_try"
                audio_cue = "try_again_playful" if age_group == "younger" else "neutral_alert"
            else:
                category = "incorrect_multiple_tries"
                audio_cue = "encouraging_guide" if age_group == "younger" else "hint_alert"    

            message = random.choice(bank[category]
                                    )

            return{
                "child_id": attempt.child_id,
                "age_bracket": age_group,
                "feedback":{
                    "message":message,
                    "audio_cue":audio_cue,
                    "ui_config":{
                        "text_size":"large" if age_group =="younger" else "medium",
                        "show_animation": attempt.is_correct or age_group == "younger",
                        "auto_play_audio": age_group == "younger",
                        "trigger_hint_overlay": not attempt.is_correct and attempt.attempts_count >=2
                    }
                }
            }
        except Exception as e:
            raise HTTPException(status_code = 500, detail=str(e))