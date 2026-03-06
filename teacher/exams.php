<?php
require_once 'layout.php';
require_once '../config/database.php';

$db = new Database();
$message = '';
$error = '';
$teacherId = $_SESSION['user_id'];

// Handle exam deletion
if($_POST && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $db->query("DELETE FROM exams WHERE id = :id AND created_by = :created_by");
    $db->bind(':id', $_POST['exam_id']);
    $db->bind(':created_by', $teacherId);
    if($db->execute()) {
        header('Location: exams.php?deleted=1');
        exit;
    }
}

// Handle exam creation
if($_POST && isset($_POST['action']) && $_POST['action'] == 'create') {
    // Validate that subject exists
    $db->query("SELECT id FROM subjects WHERE id = :subject_id");
    $db->bind(':subject_id', $_POST['subject_id']);
    if(!$db->single()) {
        $error = 'Selected subject does not exist.';
    } else {
        $db->query("INSERT INTO exams (name, type, class_id, subject_id, exam_date, max_marks, pass_marks, created_by) VALUES (:name, :type, :class_id, :subject_id, :exam_date, :max_marks, :pass_marks, :created_by)");
        $db->bind(':name', $_POST['name']);
        $db->bind(':type', $_POST['type']);
        $db->bind(':class_id', $_POST['class_id']);
        $db->bind(':subject_id', $_POST['subject_id']);
        $db->bind(':exam_date', $_POST['exam_date'] . ' ' . $_POST['exam_time']);
        $db->bind(':max_marks', $_POST['max_marks']);
        $db->bind(':pass_marks', $_POST['pass_marks']);
        $db->bind(':created_by', $teacherId);
        
        if($db->execute()) {
            header('Location: exams.php?created=1');
            exit;
        } else {
            $error = 'Failed to schedule exam/assignment.';
        }
    }
}

if(isset($_GET['created'])) {
    $message = 'Exam/Assignment scheduled successfully!';
}

if($_POST && isset($_POST['action']) && $_POST['action'] == 'reschedule') {
    $db->query("UPDATE exams SET exam_date = :exam_date WHERE id = :exam_id AND created_by = :created_by");
    $db->bind(':exam_date', $_POST['exam_date'] . ' ' . $_POST['exam_time']);
    $db->bind(':exam_id', $_POST['exam_id']);
    $db->bind(':created_by', $teacherId);
    
    if($db->execute()) {
        header('Location: exams.php?rescheduled=1');
        exit;
    }
}

if(isset($_GET['deleted'])) {
    $message = 'Exam deleted successfully!';
} elseif(isset($_GET['rescheduled'])) {
    $message = 'Exam rescheduled successfully!';
}

// Handle exam rescheduling
if($_POST && isset($_POST['action']) && $_POST['action'] == 'reschedule') {
    $db->query("UPDATE exams SET exam_date = :exam_date WHERE id = :exam_id AND created_by = :created_by");
    $db->bind(':exam_date', $_POST['exam_date'] . ' ' . $_POST['exam_time']);
    $db->bind(':exam_id', $_POST['exam_id']);
    $db->bind(':created_by', $teacherId);
    
    if($db->execute()) {
        $message = 'Exam rescheduled successfully!';
    } else {
        $error = 'Failed to reschedule exam.';
    }
}

// Get teacher's assigned classes and subjects
$db->query("SELECT DISTINCT c.id as class_id, c.name as class_name, s.id as section_id, s.name as section_name, sub.id as subject_id, sub.name as subject_name
           FROM teacher_subjects ts
           JOIN teachers t ON ts.teacher_id = t.id
           JOIN classes c ON ts.class_id = c.id
           JOIN sections s ON ts.section_id = s.id
           JOIN subjects sub ON ts.subject_id = sub.id
           WHERE t.user_id = :teacher_id
           ORDER BY c.name, s.name, sub.name");
$db->bind(':teacher_id', $teacherId);
$assignments = $db->resultset();

// Get all classes and subjects from materials uploaded by this teacher
$db->query("SELECT DISTINCT c.id as class_id, c.name as class_name, sub.id as subject_id, sub.name as subject_name
           FROM study_materials sm
           JOIN classes c ON sm.class_id = c.id
           JOIN subjects sub ON sm.subject_id = sub.id
           WHERE sm.uploaded_by = :teacher_id
           ORDER BY c.name, sub.name");
$db->bind(':teacher_id', $teacherId);
$materialAssignments = $db->resultset();

// Merge assignments
foreach($materialAssignments as $ma) {
    $exists = false;
    foreach($assignments as $a) {
        if($a['class_id'] == $ma['class_id'] && $a['subject_id'] == $ma['subject_id']) {
            $exists = true;
            break;
        }
    }
    if(!$exists) {
        $assignments[] = ['class_id' => $ma['class_id'], 'class_name' => $ma['class_name'], 'section_name' => '', 'subject_id' => $ma['subject_id'], 'subject_name' => $ma['subject_name']];
    }
}

// Get teacher's exams
$db->query("SELECT e.*, c.name as class_name, s.name as subject_name 
           FROM exams e 
           JOIN classes c ON e.class_id = c.id 
           JOIN subjects s ON e.subject_id = s.id 
           WHERE e.created_by = :user_id 
           ORDER BY e.exam_date DESC");
$db->bind(':user_id', $teacherId);
$exams = $db->resultset();

if (!$exams) {
    $exams = [];
}

$content = '
<div class="page-header">
    <h2>Schedule Exams & Assignments</h2>
    <button class="btn" onclick="showCreateForm()">Schedule New Exam/Assignment</button>
</div>

' . ($message ? '<div class="alert alert-success">' . $message . '</div>' : '') . '
' . ($error ? '<div class="alert alert-error">' . $error . '</div>' : '') . '

<!-- Create Exam Modal -->
<div id="createModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Schedule New Exam/Assignment</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" required class="form-control" placeholder="e.g., Mid Term Exam, Math Assignment">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Type:</label>
                    <select name="type" required class="form-control">
                        <option value="">Select Type</option>
                        <option value="quiz">Quiz</option>
                        <option value="test">Test</option>
                        <option value="midterm">Mid Term</option>
                        <option value="final">Final Exam</option>
                        <option value="assignment">Assignment</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date:</label>
                    <input type="date" name="exam_date" required class="form-control">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Time:</label>
                    <input type="time" name="exam_time" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Duration (minutes):</label>
                    <input type="number" name="duration" required class="form-control" min="15" placeholder="60">
                </div>
            </div>
            
            <div class="form-group">
                <label>Class & Subject:</label>
                <select name="assignment" required class="form-control" onchange="updateAssignment()">
                    <option value="">Select Class & Subject</option>';

foreach($assignments as $assignment) {
    $sectionName = $assignment['section_name'] ? ' - ' . $assignment['section_name'] : '';
    $content .= '<option value="' . $assignment['class_id'] . ',' . $assignment['subject_id'] . '">' . $assignment['class_name'] . $sectionName . ' - ' . $assignment['subject_name'] . '</option>';
}

$content .= '
                </select>
                <input type="hidden" name="class_id" id="classId">
                <input type="hidden" name="subject_id" id="subjectId">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Maximum Marks:</label>
                    <input type="number" name="max_marks" required class="form-control" min="1" placeholder="100">
                </div>
                <div class="form-group">
                    <label>Pass Marks:</label>
                    <input type="number" name="pass_marks" required class="form-control" min="1" placeholder="40">
                </div>
            </div>
            
            <button type="submit" class="btn">Schedule Exam/Assignment</button>
        </form>
    </div>
</div>

<!-- Reschedule Exam Modal -->
<div id="rescheduleModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 400px;">
        <span class="close" onclick="closeRescheduleModal()">&times;</span>
        <h3>Reschedule Exam</h3>
        <form method="POST">
            <input type="hidden" name="action" value="reschedule">
            <input type="hidden" name="exam_id" id="rescheduleExamId">
            
            <div class="form-group">
                <label>New Date:</label>
                <input type="date" name="exam_date" required class="form-control">
            </div>
            
            <div class="form-group">
                <label>New Time:</label>
                <input type="time" name="exam_time" required class="form-control">
            </div>
            
            <button type="submit" class="btn">Reschedule Exam</button>
        </form>
    </div>
</div>

<div class="data-table">
    <table>
        <thead>
            <tr>
                <th>Exam Name</th>
                <th>Type</th>
                <th>Class</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Max Marks</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>';

if(empty($exams)) {
    $content .= '<tr><td colspan="7" style="text-align: center; color: #6C757D;">No exams created yet</td></tr>';
} else {
    foreach($exams as $exam) {
        $examDate = strtotime($exam['exam_date']);
        $currentDate = time();
        $isExpired = $examDate < $currentDate;
        
        $content .= '
            <tr>
                <td>' . $exam['name'] . '</td>
                <td>' . ucfirst($exam['type']) . '</td>
                <td>' . $exam['class_name'] . '</td>
                <td>' . $exam['subject_name'] . '</td>
                <td>' . date('M d, Y H:i', strtotime($exam['exam_date'])) . ($isExpired ? ' <span style="color: #E63946; font-size: 0.8em;">(Expired)</span>' : '') . '</td>
                <td>' . $exam['max_marks'] . '</td>
                <td>
                    <button class="btn-small btn-edit" onclick="createQuestions(' . $exam['id'] . ')">Create Questions</button>
                    <button class="btn-small btn-view" onclick="enterMarks(' . $exam['id'] . ')">Enter Marks</button>
                    <button class="btn-small btn-secondary" onclick="rescheduleExam(' . $exam['id'] . ')">Reschedule</button>
                    <button class="btn-small btn-delete" onclick="deleteExam(' . $exam['id'] . ')">Delete</button>
                </td>
            </tr>';
    }
}

$content .= '
        </tbody>
    </table>
</div>

<script>
function showCreateForm() {
    document.getElementById("createModal").style.display = "block";
}

function closeModal() {
    document.getElementById("createModal").style.display = "none";
}

function closeRescheduleModal() {
    document.getElementById("rescheduleModal").style.display = "none";
}

function rescheduleExam(examId) {
    document.getElementById("rescheduleExamId").value = examId;
    document.getElementById("rescheduleModal").style.display = "block";
}

function updateAssignment() {
    const select = document.querySelector("select[name=assignment]");
    const value = select.value;
    if(value) {
        const [classId, subjectId] = value.split(",");
        document.getElementById("classId").value = classId;
        document.getElementById("subjectId").value = subjectId;
    }
}

function createQuestions(examId) {
    window.location.href = "questions.php?exam_id=" + examId;
}

function enterMarks(examId) {
    window.location.href = "marks.php?exam_id=" + examId;
}

function deleteExam(examId) {
    if(confirm("Are you sure you want to delete this exam? This action cannot be undone.")) {
        const form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `<input name="action" value="delete"><input name="exam_id" value="${examId}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewExam(examId) {
    alert("Loading exam details...");
}
</script>';

echo renderTeacherLayout('Exam Management', $content);
?>

<!-- Exam Details Modal -->
<div id="examModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="closeExamModal()">&times;</span>
        <div id="examContent">
            <!-- Exam details will be loaded here -->
        </div>
    </div>
</div>

<script>
function viewExam(examId) {
    fetch('view_exam.php?id=' + examId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Response:', data);
            if(data.success) {
                var content = '<h3>' + data.exam.name + '</h3>' +
                    '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">' +
                        '<div><strong>Type:</strong> ' + data.exam.type.charAt(0).toUpperCase() + data.exam.type.slice(1) + '</div>' +
                        '<div><strong>Class:</strong> ' + data.exam.class_name + '</div>' +
                        '<div><strong>Subject:</strong> ' + data.exam.subject_name + '</div>' +
                        '<div><strong>Date:</strong> ' + data.exam.exam_date + '</div>' +
                        '<div><strong>Max Marks:</strong> ' + data.exam.max_marks + '</div>' +
                        '<div><strong>Pass Marks:</strong> ' + data.exam.pass_marks + '</div>' +
                    '</div>' +
                    '<div style="margin-top: 20px;">' +
                        '<strong>Questions:</strong> ' + data.exam.question_count + ' questions<br>' +
                        '<strong>Students Enrolled:</strong> ' + data.exam.student_count + ' students<br>' +
                        '<strong>Status:</strong> ' + (data.exam.is_expired ? '<span style="color: #E63946;">Expired</span>' : '<span style="color: #0077B6;">Active</span>') +
                    '</div>';
                document.getElementById('examContent').innerHTML = content;
                document.getElementById('examModal').style.display = 'block';
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading exam details: ' + error.message);
        });
}

function closeExamModal() {
    document.getElementById('examModal').style.display = 'none';
}
</script>