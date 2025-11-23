<?php
/**
 * SkillPath Project: Instructor Gradebook Page (Functional)
 *
 * This page allows the instructor to select an assignment, view student submissions,
 * and record/update the grade in the submissions table.
 *
 * FIX: The SQL query is updated to fetch from 'submissions' first, ensuring
 * that all submitted assignments appear, regardless of enrollment status bugs.
 */

session_start();
require_once 'db_config.php';

// Security Check: Must be logged in and the role must be 'instructor'
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: login.html?status=error&message=Access%20Denied.");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = null;
$courses = [];
$assignments = [];
$submissions = []; // Changed from $students
$selected_assignment_id = $_GET['assignment_id'] ?? null;
$selected_course_id = null;

// --- Process Grade Submission (Updates Submissions Table) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['grade_submit'])) {
    $student_id = $_POST['student_id'];
    $grade_value = $_POST['grade_value'];
    $assignment_id = $_POST['assignment_id']; // This is the assignment_id
    $submission_id = $_POST['submission_id']; // This is the specific submission id

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        if ($conn->connect_error) { throw new Exception("Connection failed: " . $conn->connect_error); }
        
        // Update the 'grade' in the 'submissions' table where the ID matches
        $sql = "UPDATE submissions SET grade = ? WHERE id = ? AND student_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $grade_value, $submission_id, $student_id);
        
        if ($stmt->execute()) {
            $message = "Grade of {$grade_value} successfully recorded!";
        } else {
            $message = "Error grading submission: " . $conn->error;
        }
        $stmt->close();
        $conn->close();

    } catch (Exception $e) { $message = "Database Error: " . $e->getMessage(); }
    
    // Redirect back to the page to show the updated data
    header("Location: instructor_gradebook.php?assignment_id=" . urlencode($assignment_id) . "&status=success&message=" . urlencode($message));
    exit();
}

// --- Fetch Instructor's Courses & Assignments ---
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    if ($conn->connect_error) { throw new Exception("Connection failed: " . $conn->connect_error); }
    
    // Fetch courses taught by the instructor
    $sql_courses = "SELECT id, course_code, title FROM courses WHERE instructor_id = ?";
    $stmt_courses = $conn->prepare($sql_courses);
    $stmt_courses->bind_param("i", $user_id);
    $stmt_courses->execute();
    $result_courses = $stmt_courses->get_result();
    while ($row = $result_courses->fetch_assoc()) { $courses[] = $row; }
    $stmt_courses->close();
    
    // Fetch ALL assignments created by this instructor's courses for the dropdown
    if (!empty($courses)) {
        $course_ids = array_column($courses, 'id');
        $sql = "SELECT a.id, a.course_id, a.title, c.course_code 
                FROM assignments a 
                JOIN courses c ON a.course_id = c.id
                WHERE a.course_id IN (" . implode(',', array_map('intval', $course_ids)) . ") 
                ORDER BY a.due_date DESC";
        $assignments = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
    
    // --- Fetch Submissions for Selected Assignment ---
    if ($selected_assignment_id) {
        
        // *** THE FIX IS HERE ***
        // We query from SUBMISSIONS first, then join USERS.
        // This finds the submission (like in your screenshot) regardless of enrollment.
        $sql_subs = "SELECT 
                    u.id AS student_id, 
                    u.full_name, 
                    u.email, 
                    s.id AS submission_id, 
                    s.submitted_at, 
                    s.grade, 
                    s.submission_path 
                FROM submissions s
                JOIN users u ON s.student_id = u.id
                WHERE s.assignment_id = ? AND u.role = 'student'";
        
        $stmt_subs = $conn->prepare($sql_subs);
        $stmt_subs->bind_param("i", $selected_assignment_id);
        $stmt_subs->execute();
        $submissions = $stmt_subs->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_subs->close();
    }
    $conn->close();

} catch (Exception $e) { $message = "Database Error: " . $e->getMessage(); }

// Handle redirect messages
if (isset($_GET['message'])) { $message = $_GET['message']; }

$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Submissions | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style> body { font-family: 'Inter', sans-serif; background-color: #f4f7f9; } </style>
</head>
<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-6xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-orange-700">Grade Submissions</h1>
            <a href="instructor_dashboard.php" class="text-indigo-500 hover:text-indigo-700 font-semibold">&larr; Back to Dashboard</a>
        </div>
        
        <?php if (isset($_GET['message'])): ?>
            <div class="p-4 mb-4 rounded-lg <?php echo strpos($_GET['message'], 'successfully') !== false ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Assignment Selection Form -->
        <form method="GET" action="instructor_gradebook.php" class="mb-8 p-4 border border-gray-200 rounded-lg bg-gray-50">
            <label for="assignment_select" class="block text-lg font-medium text-gray-700 mb-2">Select Assignment to Grade</label>
            <div class="flex space-x-4">
                <select id="assignment_select" name="assignment_id" required 
                        class="flex-grow px-4 py-2 border border-gray-300 rounded-lg shadow-sm">
                    <option value="">-- Choose an Assignment --</option>
                    <?php foreach ($assignments as $assign): 
                        $option_label = htmlspecialchars("{$assign['course_code']} - {$assign['title']}");
                    ?>
                        <option value="<?php echo htmlspecialchars($assign['id']); ?>" 
                                <?php echo ($selected_assignment_id == $assign['id']) ? 'selected' : ''; ?>>
                            <?php echo $option_label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg shadow-md hover:bg-indigo-700">
                    Load Submissions
                </button>
            </div>
        </form>

        <!-- Student Grading List -->
        <!-- FIX: Check if $submissions is empty, not $students -->
        <?php if ($selected_assignment_id && !empty($submissions)): ?> 
            <h2 class="text-2xl font-bold text-gray-700 mb-4">Grading Submissions</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted File</th>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted At</th>
                            <th class="py-3 px-6 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Grade (0-100)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($submissions as $sub): 
                            $has_submitted = !is_null($sub['submitted_at']);
                            $grade_text = !is_null($sub['grade']) ? $sub['grade'] : "";
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?php echo htmlspecialchars($sub['full_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if ($has_submitted): ?>
                                    <a href="<?php echo htmlspecialchars($sub['submission_path']); ?>" target="_blank" class="text-blue-600 hover:underline">
                                        View Submitted File
                                    </a>
                                <?php else: ?>
                                    <span class="text-red-600 font-semibold">Not Submitted</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $has_submitted ? date('M j, Y H:i A', strtotime($sub['submitted_at'])) : 'N/A'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <form method="POST" action="instructor_gradebook.php?assignment_id=<?php echo htmlspecialchars($selected_assignment_id); ?>" class="flex justify-center items-center space-x-2">
                                    <input type="hidden" name="grade_submit" value="1">
                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($sub['student_id']); ?>">
                                    <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($sub['submission_id']); ?>">
                                    <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($selected_assignment_id); ?>">
                                    
                                    <input type="number" name="grade_value" min="0" max="100" required
                                           value="<?php echo htmlspecialchars($grade_text); ?>"
                                           class="w-20 px-3 py-1 border border-gray-300 rounded-lg text-center"
                                           <?php echo !$has_submitted ? 'disabled' : ''; ?>>
                                    <button type="submit" class="text-xs bg-orange-500 text-white py-1 px-3 rounded-lg hover:bg-orange-600 transition duration-150"
                                            <?php echo !$has_submitted ? 'disabled title="Cannot grade until submitted"' : ''; ?>>
                                        Save Grade
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($selected_assignment_id): ?>
             <div class="p-6 text-center text-gray-500 border-dashed border-2 border-gray-300 rounded-lg">
                <p class="text-lg mb-2">No submissions found for this assignment.</p>
            </div>
        <?php endif; ?>

        <div class="mt-8 text-center text-gray-400 text-xs border-t pt-4">
            <p>Instructor Page - Role: <?php echo $_SESSION['user_role']; ?> | User ID: <?php echo $_SESSION['user_id']; ?></p>
        </div>
    </div>

</body>
</html>