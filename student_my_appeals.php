<?php
session_start();
require_once 'db_config.php';
// FIX: use 'role'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$appeals = [];

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    // Fetch all appeals for this student
    $sql = "SELECT ga.id, ga.item_type, ga.item_id, ga.reason, ga.status, ga.instructor_response, ga.created_at, c.course_code 
            FROM grade_appeals ga
            JOIN courses c ON ga.course_id = c.id
            WHERE ga.student_id = ? 
            ORDER BY ga.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Fetch the title of the assignment/quiz for display
        $title_sql = "";
        if ($row['item_type'] == 'quiz') {
            $title_sql = "SELECT title FROM quizzes WHERE id = " . $row['item_id'];
        } else {
            $title_sql = "SELECT title FROM assignments WHERE id = " . $row['item_id'];
        }
        $title_result = $conn->query($title_sql);
        $row['item_title'] = ($title_result && $title_result->num_rows > 0) ? $title_result->fetch_assoc()['title'] : 'Unknown Item';

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
    <title>My Appeals | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex flex-col items-center p-4 bg-gray-100">

    <div class="w-full max-w-5xl bg-white shadow-xl rounded-xl p-8 mt-8">
        <div class="flex justify-between border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-gray-800">My Grade Appeals</h1>
            <a href="student_dashboard.php" class="text-indigo-500 hover:underline font-semibold">Back to Dashboard</a>
        </div>

        <?php if (empty($appeals)): ?>
            <div class="p-12 text-center border-2 border-dashed border-gray-300 rounded-lg">
                <p class="text-xl text-gray-500">You have not submitted any appeals.</p>
            </div>
        <?php else: ?>
            <div class="grid gap-6">
                <?php foreach ($appeals as $appeal):
                    $status_colors = [
                        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                        'resolved' => 'bg-green-100 text-green-800 border-green-200'
                    ];
                    $status_class = $status_colors[$appeal['status']] ?? 'bg-gray-100';
                    ?>
                    <div class="p-6 border rounded-lg shadow-sm bg-white">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">
                                    <?php echo htmlspecialchars($appeal['item_title']); ?>
                                </h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($appeal['course_code']); ?> •
                                    <?php echo ucfirst($appeal['item_type']); ?>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">Submitted:
                                    <?php echo date('M j, Y', strtotime($appeal['created_at'])); ?>
                                </p>
                            </div>
                            <span
                                class="px-3 py-1 text-xs font-bold uppercase rounded-full border <?php echo $status_class; ?>">
                                <?php echo ucfirst($appeal['status']); ?>
                            </span>
                        </div>

                        <div class="mb-4">
                            <p class="text-xs font-bold text-gray-500 uppercase">Your Reason:</p>
                            <p class="text-gray-700 italic bg-gray-50 p-3 rounded border border-gray-100 mt-1">
                                "<?php echo nl2br(htmlspecialchars($appeal['reason'])); ?>"</p>
                        </div>

                        <?php if (!empty($appeal['instructor_response'])): ?>
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <p class="text-xs font-bold text-green-600 uppercase flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                                        </path>
                                    </svg>
                                    Instructor Feedback:
                                </p>
                                <div class="text-gray-800 mt-2 p-3 bg-green-50 border border-green-100 rounded-lg">
                                    <?php echo nl2br(htmlspecialchars($appeal['instructor_response'])); ?>
                                </div>
                            </div>
                        <?php elseif ($appeal['status'] == 'pending'): ?>
                            <p class="text-sm text-yellow-600 mt-2">Waiting for instructor review...</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>