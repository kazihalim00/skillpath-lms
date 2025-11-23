<?php
/**
 * SkillPath Project: Student Grades Page (Functional)
 *
 * FIX: Ensures 'item_type' is lowercase in the database query and link.
 */

session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.html?status=error&message=Access%20Denied.");
    exit();
}
$user_id = $_SESSION['user_id'];
$grades = [];
$message = null;

function getLetterGrade($score)
{
    if ($score >= 80)
        return 'A+';
    elseif ($score >= 75)
        return 'A';
    elseif ($score >= 70)
        return 'A-';
    elseif ($score >= 65)
        return 'B+';
    elseif ($score >= 60)
        return 'B';
    elseif ($score >= 55)
        return 'B-';
    elseif ($score >= 50)
        return 'C+';
    elseif ($score >= 45)
        return 'C';
    elseif ($score >= 40)
        return 'D';
    else
        return 'F';
}

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    // 1. Fetch Assignment Grades (FIX: 'assignment' is lowercase)
    $sql_assign = "SELECT a.id as item_id, a.course_id, a.title AS item_title, s.grade AS score, a.max_points, s.submitted_at AS graded_at, 'assignment' AS item_type 
                   FROM submissions s JOIN assignments a ON s.assignment_id = a.id 
                   WHERE s.student_id = ? AND s.grade IS NOT NULL";

    $stmt = $conn->prepare($sql_assign);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row;
    }
    $stmt->close();

    // 2. Fetch Quiz Grades (FIX: 'quiz' is lowercase)
    $sql_quiz = "SELECT q.id as item_id, q.course_id, q.title AS item_title, a.score AS score, q.max_points, a.completed_at AS graded_at, 'quiz' AS item_type 
                 FROM quiz_attempts a JOIN quizzes q ON a.quiz_id = q.id 
                 WHERE a.student_id = ? AND a.score IS NOT NULL";

    $stmt = $conn->prepare($sql_quiz);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row;
    }
    $stmt->close();

    $conn->close();

    // Sort by date
    usort($grades, function ($a, $b) {
        return strtotime($b['graded_at']) - strtotime($a['graded_at']);
    });

} catch (Exception $e) {
    $message = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center p-4">

    <div class="w-full max-w-5xl bg-white shadow-xl rounded-xl p-8 md:p-12 mt-8">
        <div class="flex justify-between items-center border-b pb-4 mb-6">
            <h1 class="text-3xl font-bold text-purple-700">My Official Grades</h1>
            <a href="student_dashboard.php" class="text-indigo-500 hover:text-indigo-7V00 font-semibold">&larr; Back to
                Dashboard</a>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <div
                class="p-4 mb-4 rounded-lg <?php echo $_GET['status'] == 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($grades)): ?>
            <div class="p-6 text-center text-gray-500 border-dashed border-2 rounded-lg">
                <p class="text-lg">You have not received any grades yet.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 mt-6">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-700">Title</th>
                            <th class="py-2 px-4 border-b text-center text-sm font-semibold text-gray-700">Type</th>
                            <th class="py-2 px-4 border-b text-center text-sm font-semibold text-gray-700">Score</th>
                            <th class.py-2 px-4 border-b text-center text-sm font-semibold text-gray-700">Grade</th>
                            <th class="py-2 px-4 border-b text-center text-sm font-semibold text-gray-700">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grades as $grade):
                            $score = $grade['score'];
                            $max = $grade['max_points'];
                            $pct = ($max > 0) ? round(($score / $max) * 100) : 0;
                            $letter = getLetterGrade($pct);
                            $color = $pct >= 70 ? 'text-green-600' : 'text-red-600';

                            // The value from the DB ($grade['item_type']) is now guaranteed lowercase
                            $item_type_lowercase = $grade['item_type'];
                            ?>
                            <tr>
                                <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($grade['item_title']); ?></td>
                                <td class="py-3 px-4 border-b text-center text-xs uppercase font-bold text-gray-500">
                                    <?php echo htmlspecialchars($grade['item_type']); ?>
                                </td>
                                <td class="py-3 px-4 border-b text-center font-bold <?php echo $color; ?>">
                                    <?php echo $score . " / " . $max; ?>
                                </td>
                                <td class="py-3 px-4 border-b text-center font-extrabold text-lg text-gray-800">
                                    <?php echo $letter; ?>
                                </td>
                                <td class="py-3 px-4 border-b text-center">
                                    <!-- Pass the correct lowercase type to the URL -->
                                    <a href="student_appeal.php?type=<?php echo $item_type_lowercase; ?>&id=<?php echo $grade['item_id']; ?>&course_id=<?php echo $grade['course_id']; ?>&title=<?php echo urlencode($grade['item_title']); ?>"
                                        class="text-xs bg-yellow-500 hover:bg-yellow-600 text-white py-1 px-3 rounded transition">
                                        Appeal / Challenge
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>