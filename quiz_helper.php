<?php
require_once __DIR__ . '/database.php';

function save_quiz_attempt($child_id, $operation, $operator, $num1, $num2, $correct, $answer, $is_correct)
{
    global $pdo;
    try {
        $st = $pdo->prepare("INSERT INTO quiz_attempts (child_id, operation, operator, num1, num2, correct_answer, user_answer, is_correct) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $st->execute([(int) $child_id, $operation, $operator, (int) $num1, (int) $num2, (int) $correct, (int) $answer, $is_correct ? 1 : 0]);
        return true;
    } catch (Exception $e) {
        error_log('quiz_attempts insert failed: ' . $e->getMessage());
        return false;
    }
}

function save_progress_snapshot($pdo, $child_id, $r)
{
    static $ready = false;
    try {
        if (!$ready) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS progress_reports (
                id INT AUTO_INCREMENT PRIMARY KEY,
                child_id INT NOT NULL,
                subject VARCHAR(20) NOT NULL,
                skill VARCHAR(30) NOT NULL,
                total_questions INT NOT NULL,
                accuracy_percent INT NOT NULL,
                status VARCHAR(20) NOT NULL,
                summary TEXT,
                action_item TEXT,
                generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_child_skill (child_id, subject, skill),
                FOREIGN KEY (child_id) REFERENCES children(id) ON DELETE CASCADE
            ) ENGINE=InnoDB");
            $ready = true;
        }
        $st = $pdo->prepare("INSERT INTO progress_reports (child_id, subject, skill, total_questions, accuracy_percent, status, summary, action_item)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE total_questions = VALUES(total_questions), accuracy_percent = VALUES(accuracy_percent),
                status = VALUES(status), summary = VALUES(summary), action_item = VALUES(action_item)");
        $st->execute([(int) $child_id, $r['subject'], $r['skill'], (int) $r['total'], (int) $r['accuracy'], $r['status'], $r['summary'], $r['action']]);
    } catch (Exception $e) {
        error_log('progress_reports save failed: ' . $e->getMessage());
    }
}