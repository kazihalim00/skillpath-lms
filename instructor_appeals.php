<?php
/**
 * SkillPath Project: Instructor Appeals View
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: login.html");
    exit();
}
$user_id = $_SESSION['user_id'];
$appeals = [];

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    // Fetch appeals for courses taught by this instructor
    $sql = "SELECT ga.id, ga.item_type, ga.reason, ga.created_at, u.full_name, c.course_code 
            FROM grade_appeals ga
            JOIN courses c ON ga.course_id = c.id
            JOIN users u ON ga.student_id = u.id
            WHERE c.instructor_id = ? ORDER BY ga.created_at DESC";
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
    <meta charset="UTF-8">
    <title>Grade Appeals | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col items-center p-4 bg-gray-100">
    <div class="w-full max-w-5xl bg-white shadow-xl rounded-xl p-8 mt-8">
        <div class="flex justify-between border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Student Appeals</h1>
            <a href="instructor_dashboard.php" class="text-indigo-500 hover:underline">Back to Dashboard</a>
        </div>

        <?php if (empty($appeals)): ?>
            <p class="text-gray-500 text-center p-8">No active appeals found.</p>
        <?php else: ?>
            <div class="grid gap-4">
                <?php foreach ($appeals as $appeal): ?>
                    <div class="p-4 border rounded-lg bg-yellow-50 border-yellow-200">
                        <div class="flex justify-between">
                            <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($appeal['full_name']); ?> <span
                                    class="text-sm font-normal text-gray-500">(<?php echo $appeal['course_code']; ?>)</span>
                            </h3>
                            <span
                                class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($appeal['created_at'])); ?></span>
                        </div>
                        <p class="mt-2 text-gray-700 text-sm"><strong>Challenging:</strong>
                            <?php echo ucfirst($appeal['item_type']); ?></p>
                        <p class="mt-1 text-gray-800 italic">"<?php echo htmlspecialchars($appeal['reason']); ?>"</p>
                        <div class="mt-3 text-right">
                            <a href="instructor_gradebook.php"
                                class="text-xs bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Go to Gradebook to
                                Review</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>