<?php
require_once '../config/database.php';

$db = new Database();
$examId = $_GET['exam_id'] ?? null;

if (!$examId) {
    header('Location: exams.php');
    exit;
}

// Get exam details
$db->query("SELECT e.*, c.name as class_name, s.name as subject_name FROM exams e JOIN classes c ON e.class_id = c.id JOIN subjects s ON e.subject_id = s.id WHERE e.id = :id");
$db->bind(':id', $examId);
$exam = $db->single();

if (!$exam) {
    header('Location: exams.php');
    exit;
}

// Get questions
$db->query("SELECT * FROM exam_questions WHERE exam_id = :exam_id ORDER BY id");
$db->bind(':exam_id', $examId);
$questions = $db->resultset();

if (empty($questions)) {
    header('Location: questions.php?exam_id=' . $examId . '&error=No questions found');
    exit;
}

// Generate filename
$filename = preg_replace('/[^A-Za-z0-9_-]/', '_', $exam['name']) . '_' . $exam['class_name'] . '_' . date('Y-m-d') . '.html';

// Set headers for download
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Generate HTML document
echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($exam['name']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .exam-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .question { margin-bottom: 25px; page-break-inside: avoid; }
        .question-number { font-weight: bold; color: #0077B6; }
        .question-text { margin: 10px 0; }
        .options { margin-left: 20px; }
        .option { margin: 5px 0; }
        .marks { float: right; font-weight: bold; color: #666; }
        .answer-space { border-bottom: 1px solid #ccc; height: 40px; margin: 10px 0; }
        .instructions { background: #f5f5f5; padding: 15px; margin-bottom: 20px; border-left: 4px solid #0077B6; }
        @media print { body { margin: 20px; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($exam['name']) . '</h1>
        <h2>' . htmlspecialchars($exam['subject_name']) . ' - ' . htmlspecialchars($exam['class_name']) . '</h2>
    </div>
    
    <div class="exam-info">
        <div><strong>Date:</strong> ' . date('M d, Y', strtotime($exam['exam_date'])) . '</div>
        <div><strong>Time:</strong> _____ hrs</div>
        <div><strong>Max Marks:</strong> ' . $exam['max_marks'] . '</div>
    </div>
    
    <div class="instructions">
        <h3>Instructions:</h3>
        <ul>
            <li>Read all questions carefully before answering</li>
            <li>Answer all questions</li>
            <li>Write clearly and legibly</li>
            <li>Marks are indicated against each question</li>
        </ul>
    </div>
    
    <div class="questions">';

$questionNumber = 1;
foreach($questions as $question) {
    echo '<div class="question">
        <div class="question-number">Q' . $questionNumber . '. <span class="marks">[' . $question['marks'] . ' marks]</span></div>
        <div class="question-text">' . nl2br(htmlspecialchars($question['question_text'])) . '</div>';
    
    if ($question['question_type'] == 'mcq' && $question['options']) {
        $options = json_decode($question['options'], true);
        if ($options) {
            echo '<div class="options">';
            $optionLabels = ['A', 'B', 'C', 'D'];
            for ($i = 0; $i < count($options) && $i < 4; $i++) {
                if (!empty($options[$i])) {
                    echo '<div class="option">(' . $optionLabels[$i] . ') ' . htmlspecialchars($options[$i]) . '</div>';
                }
            }
            echo '</div>';
        }
    } else {
        // Add answer space for short answer and essay questions
        $spaces = $question['question_type'] == 'essay' ? 8 : 3;
        for ($i = 0; $i < $spaces; $i++) {
            echo '<div class="answer-space"></div>';
        }
    }
    
    echo '</div>';
    $questionNumber++;
}

echo '    </div>
    
    <div style="margin-top: 50px; text-align: center; color: #666;">
        <p>--- End of Question Paper ---</p>
        <p>Generated on ' . date('Y-m-d H:i:s') . '</p>
    </div>
</body>
</html>';
?>