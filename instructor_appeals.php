<?php
/**
 * SkillPath Project: Instructor Appeals Page
 * Fixed: Session variable names matching index.php
 */
session_start();
require_once 'db_config.php';

// FIX: Use 'role' instead of 'user_role'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php?status=error&message=Access%20Denied.");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = null;

// --- Handle Appeal Resolution ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resolve_appeal'])) {
    $appeal_id = $_POST['appeal_id'];
    $instructor_response = trim($_POST['instructor_response']);

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
        $sql = "UPDATE grade_appeals SET instructor_response = ?, status = 'resolved' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $instructor_response, $appeal_id);

        if ($stmt->execute()) {
            $message = "Appeal resolved successfully.";
        }
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $message = "Database Error: " . $e->getMessage();
    }
}

// --- Fetch Pending Appeals ---
$appeals = [];
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    $sql = "SELECT ga.id, ga.item_type, ga.item_id, ga.reason, ga.created_at, u.full_name, c.course_code, c.title as course_title 
            FROM grade_appeals ga
            JOIN courses c ON ga.course_id = c.id
            JOIN users u ON ga.student_id = u.id
            WHERE c.instructor_id = ? AND ga.status = 'pending'
            ORDER BY ga.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appeals[] = $row;
    }
    $conn->close();
} catch (Exception $e) {
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manage Appeals | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-5xl bg-white shadow-xl rounded-xl p-8 mt-8 border-t-4 border-red-500">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Student Grade Appeals</h1>
            <a href="instructor_dashboard.php" class="text-indigo-600 hover:underline font-semibold">&larr; Back to
                Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="p-4 mb-6 bg-green-100 text-green-700 rounded-lg text-center font-medium">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($appeals)): ?>
            <div class="p-12 text-center border-2 border-dashed border-gray-300 rounded-lg">
                <p class="text-xl text-gray-500">No pending appeals.</p>
            </div>
        <?php else: ?>
            <div class="grid gap-6">
                <?php foreach ($appeals as $appeal): ?>
                    <div class="p-6 border border-red-200 bg-red-50 rounded-lg shadow-sm">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($appeal['full_name']); ?></h3>
                                <p class="text-sm text-gray-600">
                                    <?= htmlspecialchars($appeal['course_code'] . ' - ' . $appeal['course_title']); ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">Submitted:
                                    <?= date('M j, Y', strtotime($appeal['created_at'])); ?>
                                </p>
                            </div>
                            <span
                                class="px-3 py-1 bg-red-200 text-red-800 text-xs font-bold uppercase rounded-full">Pending</span>
                        </div>

                        <div class="mt-4 bg-white p-4 rounded border border-gray-200">
                            <p class="text-xs font-bold text-gray-500 uppercase mb-1">Reason:</p>
                            <p class="text-gray-800 italic">"<?= nl2br(htmlspecialchars($appeal['reason'])); ?>"</p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-red-200">
                            <form method="POST" action="instructor_appeals.php">
                                <input type="hidden" name="appeal_id" value="<?= $appeal['id']; ?>">
                                <input type="hidden" name="resolve_appeal" value="1">

                                <label class="block text-sm font-medium text-gray-700 mb-2">Resolution Note:</label>
                                <textarea name="instructor_response" rows="2" class="w-full p-2 border rounded mb-3"
                                    placeholder="Enter your response..."></textarea>

                                <button type="submit"
                                    class="px-4 py-2 bg-green-600 text-white font-bold rounded hover:bg-green-700 shadow">
                                    Resolve Appeal
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>