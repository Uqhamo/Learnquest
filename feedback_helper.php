<?php
function get_api_feedback($topic, $is_correct, $attempts_count, $selected) 
{ $child_id = $_SESSION["child_id"] ?? "1"; 
$child_age = $_SESSION["child_age"] ?? 8; 
if (!function_exists("curl_init")) 
    { return "Feedback service is currently unavailable."; }
 $ch = curl_init("http://127.0.0.1:8000/api/v1/feedback");
  curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, 
  CURLOPT_POST => true, 
  CURLOPT_POSTFIELDS => json_encode(["child_id" => (string)$child_id,
   "age" => (int)$child_age, "subject" => "English", "topic" => $topic,
    "is_correct" => $is_correct, "attempts_count" => $attempts_count, 
    "selected_answer" => (string)$selected]), 
    CURLOPT_HTTPHEADER => ["Content-Type: application/json", "Accept: application/json"], 
    CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 5]); 
    $response = curl_exec($ch); curl_close($ch); 
    if ($response === false) { 
        return "Feedback service is currently unavailable."; } 
        $result = json_decode($response, true); 
        return $result["feedback"]["message"] ?? "Feedback could not be generated."; 
        }
