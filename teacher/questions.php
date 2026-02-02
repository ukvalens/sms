<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$message = '';
$error = '';
$examId = $_GET['exam_id'] ?? null;

if (!$examId) {
    header('Location: exams.php');
    exit;
}

// Check if exam_questions table exists
$tableExists = false;
try {
    $db->query("SELECT 1 FROM exam_questions LIMIT 1");
    $db->execute();
    $tableExists = true;
} catch(Exception $e) {
    $tableExists = false;
}

if (!$tableExists) {
    $content = '
    <div class="page-header">
        <h2>Database Setup Required</h2>
        <a href="exams.php" class="btn">Back to Exams</a>
    </div>
    
    <div class="alert alert-error">
        <strong>Database Setup Required!</strong><br>
        The exam_questions table needs to be created before you can add questions.
    </div>
    
    <div class="info-card">
        <h3>Setup Instructions</h3>
        <p>To enable question creation functionality:</p>
        <ol>
            <li>Click the button below to run the database setup</li>
            <li>Wait for the setup to complete</li>
            <li>Return to this page to start creating questions</li>
        </ol>
        
        <div style="margin-top: 20px;">
            <a href="../setup_questions_db.php" class="btn" style="background: #00BFA6; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px;">Run Database Setup</a>
        </div>
    </div>';
    
    echo renderTeacherLayout('Database Setup Required', $content);
    exit;
}

// Handle question deletion
if(isset($_GET['delete'])) {
    $db->query("DELETE FROM exam_questions WHERE id = :id AND exam_id = :exam_id");
    $db->bind(':id', $_GET['delete']);
    $db->bind(':exam_id', $examId);
    
    if($db->execute()) {
        $message = 'Question deleted successfully!';
    } else {
        $error = 'Failed to delete question.';
    }
}

// Handle question creation
if($_POST && isset($_POST['action']) && $_POST['action'] == 'add_question') {
    $db->query("INSERT INTO exam_questions (exam_id, question_text, question_type, marks, options, correct_answer) VALUES (:exam_id, :question_text, :question_type, :marks, :options, :correct_answer)");
    $db->bind(':exam_id', $examId);
    $db->bind(':question_text', $_POST['question_text']);
    $db->bind(':question_type', $_POST['question_type']);
    $db->bind(':marks', $_POST['marks']);
    $db->bind(':options', $_POST['question_type'] == 'mcq' ? json_encode($_POST['options']) : null);
    $db->bind(':correct_answer', $_POST['correct_answer']);
    
    if($db->execute()) {
        $message = 'Question added successfully!';
    } else {
        $error = 'Failed to add question.';
    }
}

// Get exam details
$db->query("SELECT e.*, c.name as class_name, s.name as subject_name FROM exams e JOIN classes c ON e.class_id = c.id JOIN subjects s ON e.subject_id = s.id WHERE e.id = :id");
$db->bind(':id', $examId);
$exam = $db->single();

if (!$exam) {
    header('Location: exams.php');
    exit;
}

// Get existing questions
$db->query("SELECT * FROM exam_questions WHERE exam_id = :exam_id ORDER BY id");
$db->bind(':exam_id', $examId);
$questions = $db->resultset();

$content = '
<div class="page-header">
    <h2>Create Questions - ' . $exam['name'] . '</h2>
    <div>
        <a href="exams.php" class="btn">Back to Exams</a>
        <button class="btn" onclick="showAddForm()">Add Question</button>
        <a href="generate_paper.php?exam_id=' . $examId . '" class="btn" style="background: #00BFA6; color: white; text-decoration: none;">Generate Exam Paper</a>
    </div>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '
' . ($error ? '<div class="alert alert-error">' . $error . '</div>' : '') . '

<div class="info-card">
    <h3>Exam Details</h3>
    <p><strong>Subject:</strong> ' . $exam['subject_name'] . ' | <strong>Class:</strong> ' . $exam['class_name'] . ' | <strong>Max Marks:</strong> ' . $exam['max_marks'] . '</p>
</div>

<div id="viewModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="closeViewModal()">&times;</span>
        <h3>Question Details</h3>
        <div id="questionDetails"></div>
    </div>
</div>

<div id="addModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Add New Question</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_question">
            
            <div class="form-group">
                <label>Question Type:</label>
                <select name="question_type" required class="form-control" onchange="toggleOptions()">
                    <option value="">Select Type</option>
                    <option value="mcq">Multiple Choice</option>
                    <option value="short">Short Answer</option>
                    <option value="essay">Essay</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Question Text:</label>
                <textarea name="question_text" required class="form-control" rows="3" placeholder="Enter your question here..."></textarea>
            </div>
            
            <div id="mcqOptions" style="display:none;">
                <div class="form-group">
                    <label>Options:</label>
                    <input type="text" name="options[]" class="form-control" placeholder="Option A">
                    <input type="text" name="options[]" class="form-control" placeholder="Option B">
                    <input type="text" name="options[]" class="form-control" placeholder="Option C">
                    <input type="text" name="options[]" class="form-control" placeholder="Option D">
                </div>
                <div class="form-group">
                    <label>Correct Answer:</label>
                    <select name="correct_answer" class="form-control">
                        <option value="A">Option A</option>
                        <option value="B">Option B</option>
                        <option value="C">Option C</option>
                        <option value="D">Option D</option>
                    </select>
                </div>
            </div>
            
            <div id="textAnswer" style="display:none;">
                <div class="form-group">
                    <label>Sample Answer/Keywords:</label>
                    <textarea name="correct_answer" class="form-control" rows="2" placeholder="Enter sample answer or keywords..."></textarea>
                </div>
            </div>
            
            <div class="form-group">
                <label>Marks:</label>
                <input type="number" name="marks" required class="form-control" min="1" placeholder="5">
            </div>
            
            <button type="submit" class="btn">Add Question</button>
        </form>
    </div>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>Question</th>
                <th>Type</th>
                <th>Marks</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';

if(empty($questions)) {
    $content .= '<tr><td colspan="4" style="text-align: center; color: #6C757D;">No questions added yet</td></tr>';
} else {
    foreach($questions as $question) {
        $content .= '
            <tr>
                <td>' . substr($question['question_text'], 0, 100) . (strlen($question['question_text']) > 100 ? '...' : '') . '</td>
                <td>' . strtoupper($question['question_type']) . '</td>
                <td>' . $question['marks'] . '</td>
                <td>
                    <button class="btn-small btn-view" onclick="viewQuestion(' . $question['id'] . ')">View</button>
                    <button class="btn-small btn-delete" onclick="deleteQuestion(' . $question['id'] . ')">Delete</button>
                </td>
            </tr>';
    }
}

$content .= '
        </tbody>
    </table>
</div>

<script>
function showAddForm() {
    document.getElementById("addModal").style.display = "block";
}

function closeModal() {
    document.getElementById("addModal").style.display = "none";
}

function closeViewModal() {
    document.getElementById("viewModal").style.display = "none";
}

function toggleOptions() {
    var type = document.querySelector("select[name=question_type]").value;
    var mcqOptions = document.getElementById("mcqOptions");
    var textAnswer = document.getElementById("textAnswer");
    
    if(type === "mcq") {
        mcqOptions.style.display = "block";
        textAnswer.style.display = "none";
    } else if(type === "short" || type === "essay") {
        mcqOptions.style.display = "none";
        textAnswer.style.display = "block";
    } else {
        mcqOptions.style.display = "none";
        textAnswer.style.display = "none";
    }
}

function viewQuestion(id) {
    fetch("view_question.php?id=" + id)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if(data.error) {
                alert(data.error);
                return;
            }
            
            var html = "<div class=\"question-details\">";
            html += "<div class=\"question-meta\">";
            html += "<span class=\"question-type\">" + data.question_type + "</span>";
            html += "<span class=\"question-marks\">" + data.marks + " marks</span>";
            html += "</div>";
            html += "<div><h4>Question:</h4><div class=\"question-text\">" + data.question_text + "</div></div>";
            
            if(data.question_type === "mcq" && data.options) {
                html += "<div><h4>Options:</h4><div class=\"question-options\">";
                var options = JSON.parse(data.options);
                var labels = ["A", "B", "C", "D"];
                for(var i = 0; i < options.length; i++) {
                    var isCorrect = labels[i] === data.correct_answer;
                    html += "<div style=\"" + (isCorrect ? "font-weight: bold; color: #2A9D8F;" : "") + "\">";
                    html += "(" + labels[i] + ") " + options[i] + (isCorrect ? " ✓" : "");
                    html += "</div>";
                }
                html += "</div><div style=\"margin-top: 15px;\"><strong>Correct Answer:</strong> Option " + data.correct_answer + "</div></div>";
            } else if(data.correct_answer) {
                html += "<div><h4>Sample Answer/Keywords:</h4><div class=\"correct-answer\">" + data.correct_answer + "</div></div>";
            }
            
            html += "</div>";
            document.getElementById("questionDetails").innerHTML = html;
            document.getElementById("viewModal").style.display = "block";
        })
        .catch(function() { alert("Failed to load question details"); });
}

function deleteQuestion(id) {
    if(confirm("Are you sure you want to delete this question?")) {
        window.location.href = "questions.php?exam_id=' . $examId . '&delete=" + id;
    }
}
</script>';

echo renderTeacherLayout('Create Questions', $content);
?>