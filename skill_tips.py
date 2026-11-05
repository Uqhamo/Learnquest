SKILL_TIPS = {
    "addition": {
        "struggling": "Practise adding with objects (coins, beans). Start with sums under 20, then move up.",
        "progressing": "Try adding in your head first, then check with a number line.",
        "good": "Move to bigger numbers and word problems.",
        "mastered": "Challenge: add three numbers, or time a 10-sum round.",
    },
    "subtraction": {
        "struggling": "Count back on a number line. Check each answer by adding it back.",
        "progressing": "Practise 'taking away' with everyday objects, then borrow with tens.",
        "good": "Try two-step word problems.",
        "mastered": "Challenge: subtract across hundreds.",
    },
    "multiplication": {
        "struggling": "Learn one times table at a time (2, 5, 10 first). Use skip counting.",
        "progressing": "Practise the 3, 4 and 6 tables with flash cards.",
        "good": "Mix the tables in random order.",
        "mastered": "Try 11 and 12 tables and missing-number questions.",
    },
    "division": {
        "struggling": "Share objects into equal groups. Link each division to a times table.",
        "progressing": "Practise 'how many groups?' with sweets or beans.",
        "good": "Try division with remainders.",
        "mastered": "Challenge: word problems mixing multiply and divide.",
    },
    "factors": {
        "struggling": "Find pairs that multiply to the number, starting with 12 and 24.",
        "progressing": "Use divisibility tricks: even, ends in 0 or 5, digits add to 3.",
        "good": "List every factor of numbers up to 100.",
        "mastered": "Challenge: find common factors of two numbers.",
    },
    "fractions": {
        "struggling": "Fold paper or cut a pizza into equal parts. Name each part.",
        "progressing": "Compare fractions with the same bottom number first.",
        "good": "Add fractions with different bottom numbers.",
        "mastered": "Challenge: fractions of amounts, like 3/4 of 20.",
    },
    "squareroots": {
        "struggling": "Learn the squares to 10 x 10, then work backwards.",
        "progressing": "Use flash cards: 49 means 7 x 7.",
        "good": "Estimate roots of numbers that are not perfect squares.",
        "mastered": "Challenge: roots up to 20 x 20.",
    },
    "spelling": {
        "struggling": "Say the word, clap the syllables, then write it three times.",
        "progressing": "Keep a list of missed words and test it each day.",
        "good": "Try longer words with silent letters.",
        "mastered": "Challenge: spell words from your reading book.",
    },
    "sentence": {
        "struggling": "Read the sentence aloud and listen for which word sounds right.",
        "progressing": "Practise past and present: yesterday I played, today I play.",
        "good": "Try comparing words: tall, taller, tallest.",
        "mastered": "Challenge: write your own sentences with the same rules.",
    },
    "opposites": {
        "struggling": "Pair words on cards: hot and cold, big and small.",
        "progressing": "Play 'say the opposite' in the car.",
        "good": "Try less common pairs like 'brave and timid'.",
        "mastered": "Challenge: find two opposites for one word.",
    },
    "unscramble": {
        "struggling": "Find the vowels first, then guess the starting letter.",
        "progressing": "Look for common endings like -ing and -ed.",
        "good": "Try longer words under a time limit.",
        "mastered": "Challenge: make your own scrambled words.",
    },
}

_ALIAS = {"proficient": "good", "processing": "progressing", "neutral": "progressing"}


def skill_tip(skill, status, fallback):
    status = _ALIAS.get(status, status)
    return SKILL_TIPS.get(skill, {}).get(status, fallback)