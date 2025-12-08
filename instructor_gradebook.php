<?php
/**
 * SkillPath Project: Instructor Gradebook (File Grading)
 * Fixes: Solved 'Unknown column' error by using 'submission_path'.
 * Updated session security to match index.php.
 */
session_start();
require_once 'db_config.php';

// 1. SECURITY CHECK (Updated to match your login system)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = null;
$assignments = [];
$submissions = [];
$selected_assignment_id = $_GET['assignment_id'] ?? null;

// --- Process Grade Save ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['grade_submit'])) {
    $sub_id = $_POST['submission_id'];
    $grade = $_POST['grade_value'];

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        $stmt = $conn->prepare("UPDATE submissions SET grade = ? WHERE id = ?");
        $stmt->bind_param("ii", $grade, $sub_id);
        if ($stmt->execute()) {
            $message = "Grade recorded successfully!";
        } else {
            $message = "Error saving grade.";
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }
}

// --- Fetch Data ---
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    // 2. GET ASSIGNMENTS
    // We fetch all assignments for this instructor's courses
    $sql = "SELECT a.id, a.title, c.course_code
            FROM assignments a 
            JOIN courses c ON a.course_id = c.id
            WHERE c.instructor_id = ? 
            ORDER BY a.due_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['display'] = htmlspecialchars($row['course_code'] . " - " . $row['title']);
        $assignments[] = $row;
    }
    $stmt->close();

    // 3. GET SUBMISSIONS (If assignment selected)
    if ($selected_assignment_id) {
        // FIX: Changed 'file_path' to 'submission_path'
        $sql_sub = "SELECT sub.id, sub.submitted_at, sub.submission_path, sub.grade, u.full_name 
                    FROM submissions sub
                    JOIN users u ON sub.student_id = u.id
                    WHERE sub.assignment_id = ?
                    ORDER BY sub.submitted_at DESC";

        $stmt = $conn->prepare($sql_sub);
        $stmt->bind_param("i", $selected_assignment_id);
        $stmt->execute();
        $submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    $conn->close();
} catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
}

$user_name = $_SESSION['name'] ?? 'Instructor';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Grade Files | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen p-8 flex justify-center">

    <div class="w-full max-w-6xl bg-white p-8 rounded-xl shadow-lg border-t-4 border-orange-500">
        <div class="flex justify-between items-center mb-8 border-b pb-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Check Assignment Files</h1>
                <p class="text-sm text-gray-500 mt-1">Select an assignment below to view student uploads and enter
                    grades.</p>
            </div>
            <a href="instructor_dashboard.php" class="text-indigo-600 hover:text-indigo-800 font-semibold">&larr; Back
                to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-200 text-green-800 p-4 rounded-lg mb-6 text-center font-medium">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="GET" class="mb-8 bg-gray-50 p-6 rounded-lg border border-gray-200">
            <label class="block text-sm font-bold text-gray-700 mb-2">Select Assignment to Grade</label>
            <div class="flex gap-4">
                <select name="assignment_id"
                    class="border p-2 rounded w-full focus:ring-2 focus:ring-orange-500 outline-none">
                    <option value="">-- Choose Assignment --</option>
                    <?php foreach ($assignments as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $selected_assignment_id == $a['id'] ? 'selected' : '' ?>>
                            <?= $a['display'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit"
                    class="bg-orange-600 text-white px-6 py-2 rounded font-bold hover:bg-orange-700">Load</button>
            </div>
        </form>

        <?php if ($selected_assignment_id): ?>
            <h2 class="text-xl font-bold text-gray-800 mb-4">Student Submissions</h2>

            <?php if (!empty($submissions)): ?>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left">Student Name</th>
                                <th class="py-3 px-6 text-left">Submitted File</th>
                                <th class="py-3 px-6 text-center">Submission Date</th>
                                <th class="py-3 px-6 text-center">Grade (0-100)</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            <?php foreach ($submissions as $s): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                    <td class="py-3 px-6 font-medium text-gray-800">
                                        <?= htmlspecialchars($s['full_name']) ?>
                                    </td>
                                    <td class="py-3 px-6">
                                        <?php if (!empty($s['submission_path'])): ?>
                                            <a href="<?= htmlspecialchars($s['submission_path']) ?>" target="_blank"
                                                class="bg-blue-100 text-blue-700 py-1 px-3 rounded text-xs font-bold hover:bg-blue-200 flex items-center w-fit gap-1">
                                                Download / View
                                                <span class="text-lg">&nearr;</span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-red-400 italic">No file found</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <?= date("M j, g:i a", strtotime($s['submitted_at'])) ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <form method="POST" class="flex justify-center items-center gap-2">
                                            <input type="hidden" name="grade_submit" value="1">
                                            <input type="hidden" name="submission_id" value="<?= $s['id'] ?>">
                                            <input type="hidden" name="assignment_id" value="<?= $selected_assignment_id ?>">

                                            <input type="number" name="grade_value" value="<?= $s['grade'] ?>"
                                                class="w-20 border border-gray-300 p-1 text-center rounded focus:ring-orange-500 focus:border-orange-500"
                                                min="0" max="100" placeholder="-">

                                            <button type="submit"
                                                class="bg-green-600 text-white px-3 py-1 rounded shadow hover:bg-green-700 text-xs font-bold uppercase">
                                                Save
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-8 text-center bg-gray-50 border border-dashed border-gray-300 rounded-lg">
                    <p class="text-gray-500 text-lg">No submissions received for this assignment yet.</p>
                </div>
            <?php endif; ?>

        <?php elseif (empty($assignments)): ?>
            <div class="text-center p-8 text-gray-500">You have no assignments created.</div>
        <?php else: ?>
            <div class="text-center p-8 text-gray-400 italic border-2 border-dashed rounded-lg">
                &uarr; Please select an assignment above to start grading.
            </div>
        <?php endif; ?>
    </div>
</body>

</html>