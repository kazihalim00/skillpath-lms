<?php
/**
 * SkillPath Project: Instructor Batch Management Tool
 * Displays all batches taught by the instructor. Clicking a batch shows
 * courses taught in that batch and the 60 marks status for all enrolled students.
 */
session_start();
require_once 'db_config.php'; // Assume this file contains DB_HOST, DB_USER, etc.

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header("Location: login.html");
    exit();
}

$instructor_id = $_SESSION['user_id'];
$message = null;
$batches = [];
$batch_courses = [];
$selected_batch_id = $_GET['batch_id'] ?? null;
$selected_batch_name = '';
$course_data = []; // Stores detailed student marks by course

// --- Database Connection Setup ---
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// --- 1. Fetch all unique Batches for this Instructor ---
$sql_batches = "
    SELECT DISTINCT b.id, b.batch_name 
    FROM batches b
    JOIN sections s ON b.id = s.batch_id
    JOIN courses c ON s.id = c.section_id
    WHERE c.instructor_id = ?
    ORDER BY b.batch_name
";
$stmt = $conn->prepare($sql_batches);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();
$batches = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- 2. Fetch Course and Student Details if a Batch is Selected ---
if ($selected_batch_id) {
    // A. Get the name of the selected batch
    foreach ($batches as $batch) {
        if ($batch['id'] == $selected_batch_id) {
            $selected_batch_name = $batch['batch_name'];
            break;
        }
    }

    // B. Get courses taught by this instructor in the selected batch
    $sql_courses = "
        SELECT c.id AS course_id, c.course_code, c.title, s.section_name
        FROM courses c
        JOIN sections s ON c.section_id = s.id
        WHERE c.instructor_id = ? AND s.batch_id = ?
        ORDER BY c.course_code
    ";
    $stmt = $conn->prepare($sql_courses);
    $stmt->bind_param("ii", $instructor_id, $selected_batch_id);
    $stmt->execute();
    $batch_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // C. For each course, fetch the student marks
    foreach ($batch_courses as $course) {
        $course_id = $course['course_id'];

        $sql_students = "
            SELECT 
                u.full_name,
                u.email,
                e.ct_marks, 
                e.assignment_marks, 
                e.quiz_marks, 
                e.viva_marks, 
                e.attendance_marks,
                (e.ct_marks + e.assignment_marks + e.quiz_marks + e.viva_marks + e.attendance_marks) AS total_60
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            WHERE e.course_id = ?
            ORDER BY u.full_name
        ";
        $stmt = $conn->prepare($sql_students);
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $course_data[$course_id] = [
            'info' => $course,
            'students' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
        ];
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ব্যাচ ম্যানেজমেন্ট | SkillPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sidebar {
            width: 250px;
            background-color: #1f2937;
            /* Gray-800 */
        }

        .content {
            margin-left: 250px;
        }

        .batch-link:hover {
            background-color: #374151;
            /* Gray-700 */
        }

        .batch-link.active {
            background-color: #f97316;
            /* Orange-500 */
            color: #fff;
        }
    </style>
</head>

<body class="flex bg-gray-100 min-h-screen">

    <!-- Sidebar: Batch List -->
    <div class="sidebar fixed h-full text-white p-4 shadow-xl">
        <h2 class="text-2xl font-bold mb-6 text-orange-400 border-b border-gray-600 pb-3">আপনার ব্যাচসমূহ</h2>

        <a href="instructor_dashboard.php"
            class="block py-2 px-3 mb-4 rounded text-sm bg-blue-600 hover:bg-blue-700 transition">
            <i class="fas fa-arrow-left"></i> ড্যাশবোর্ডে ফিরে যান
        </a>

        <?php if (!empty($batches)): ?>
            <ul class="space-y-2">
                <?php foreach ($batches as $b): ?>
                    <li>
                        <a href="?batch_id=<?= $b['id'] ?>" class="batch-link block py-3 px-3 rounded transition duration-200 
                                  <?= $selected_batch_id == $b['id'] ? 'active' : 'text-gray-200' ?>">
                            <?= htmlspecialchars($b['batch_name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-gray-400 text-sm">আপনি কোনো ব্যাচের সাথে যুক্ত নন।</p>
        <?php endif; ?>
    </div>

    <!-- Main Content: Batch Details -->
    <div class="content p-8 flex-1">
        <?php if ($selected_batch_id && $selected_batch_name): ?>
            <h1 class="text-4xl font-extrabold text-gray-800 mb-8 border-b-4 border-orange-500 pb-2">
                <?= htmlspecialchars($selected_batch_name) ?> ব্যাচের বিস্তারিত
            </h1>

            <?php if (!empty($course_data)): ?>
                <?php foreach ($course_data as $cid => $data): ?>
                    <div class="bg-white p-6 rounded-xl shadow-lg mb-8 border-l-4 border-teal-500">
                        <h2 class="text-2xl font-bold text-teal-700 mb-4">
                            কোর্স:
                            <?= htmlspecialchars("{$data['info']['course_code']} - {$data['info']['title']} (Section: {$data['info']['section_name']})") ?>
                        </h2>

                        <?php if (!empty($data['students'])): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full border-collapse border border-gray-300 text-sm">
                                    <thead class="bg-teal-100">
                                        <tr>
                                            <th class="border p-3 text-left font-bold">শিক্ষার্থীর নাম</th>
                                            <th class="border p-3 w-16 text-center font-bold">CT (15)</th>
                                            <th class="border p-3 w-16 text-center font-bold">Assign (10)</th>
                                            <th class="border p-3 w-16 text-center font-bold">Quiz (15)</th>
                                            <th class="border p-3 w-16 text-center font-bold">Viva (10)</th>
                                            <th class="border p-3 w-16 text-center font-bold">Attd. (10)</th>
                                            <th class="border p-3 w-20 text-center font-bold bg-teal-300">মোট ৬০ (Total 60)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white">
                                        <?php foreach ($data['students'] as $student): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="border p-3 font-medium">
                                                    <?= htmlspecialchars($student['full_name']) ?>
                                                    <span
                                                        class="block text-xs text-gray-500"><?= htmlspecialchars($student['email']) ?></span>
                                                </td>
                                                <td class="border p-3 text-center"><?= $student['ct_marks'] ?></td>
                                                <td class="border p-3 text-center"><?= $student['assignment_marks'] ?></td>
                                                <td class="border p-3 text-center"><?= $student['quiz_marks'] ?></td>
                                                <td class="border p-3 text-center"><?= $student['viva_marks'] ?></td>
                                                <td class="border p-3 text-center"><?= $student['attendance_marks'] ?></td>
                                                <td class="border p-3 text-center font-bold text-lg bg-teal-200">
                                                    <?= $student['total_60'] ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500">এই কোর্সে কোনো শিক্ষার্থী তালিকাভুক্ত নেই।</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-8 text-center bg-yellow-100 text-yellow-800 rounded-lg border-2 border-dashed border-yellow-400">
                    এই ব্যাচে আপনার কোনো কোর্স বরাদ্দ করা নেই।
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="p-12 text-center bg-indigo-100 text-indigo-800 rounded-xl shadow-lg">
                <p class="text-xl font-semibold mb-4">একটি ব্যাচ নির্বাচন করুন</p>
                <p class="text-gray-600">বাম পাশের তালিকা থেকে একটি ব্যাচের নামে ক্লিক করুন। আপনি ওই ব্যাচে আপনার এনরোল করা
                    কোর্সগুলো এবং সেই কোর্সের শিক্ষার্থীদের ৬০ মার্কসের স্থিতি দেখতে পাবেন।</p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>