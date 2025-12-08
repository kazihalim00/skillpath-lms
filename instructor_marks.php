<?php
/**
 * SkillPath - Instructor Grading Interface
 * Feature: Fully customizable grading (Names, Max Marks, and optional 4th component).
 */
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$course_id = $_GET['course_id'] ?? 0;
$batch_id = $_GET['batch_id'] ?? 0;
$message = "";

// --- Save Marks & Settings ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $batch_id = $_POST['batch_id'];

    // 1. Update Course Settings (Names & Max Marks)
    $n1 = $_POST['n1'];
    $m1 = intval($_POST['m1']);
    $n2 = $_POST['n2'];
    $m2 = intval($_POST['m2']);
    $n3 = $_POST['n3'];
    $m3 = intval($_POST['m3']);
    $n4 = $_POST['n4'];
    $m4 = intval($_POST['m4']); // 4th component

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    $stmt = $conn->prepare("UPDATE courses SET 
                            assess_1_name=?, assess_1_max=?, 
                            assess_2_name=?, assess_2_max=?, 
                            assess_3_name=?, assess_3_max=?, 
                            assess_4_name=?, assess_4_max=? 
                            WHERE id=?");
    $stmt->bind_param("sisiisisi", $n1, $m1, $n2, $m2, $n3, $m3, $n4, $m4, $course_id);
    $stmt->execute();
    $stmt->close();

    // 2. Save Student Marks
    $student_ids = $_POST['student_ids'] ?? [];
    $stmt = $conn->prepare("INSERT INTO student_marks (student_id, course_id, attendance, class_test, viva, assess_4, total) 
                            VALUES (?, ?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE 
                            attendance=VALUES(attendance), class_test=VALUES(class_test), 
                            viva=VALUES(viva), assess_4=VALUES(assess_4), total=VALUES(total)");

    foreach ($student_ids as $sid) {
        $s1 = intval($_POST['s1_' . $sid]);
        $s2 = intval($_POST['s2_' . $sid]);
        $s3 = intval($_POST['s3_' . $sid]);
        $s4 = intval($_POST['s4_' . $sid]);
        $total = $s1 + $s2 + $s3 + $s4;

        $stmt->bind_param("iiiiiii", $sid, $course_id, $s1, $s2, $s3, $s4, $total);
        $stmt->execute();
    }
    $stmt->close();
    $conn->close();
    $message = "Settings and Marks Saved!";
}

// --- Fetch Data ---
$course = [];
$students = [];
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

    // Get Settings
    $res = $conn->query("SELECT title, assess_1_name, assess_1_max, assess_2_name, assess_2_max, 
                                assess_3_name, assess_3_max, assess_4_name, assess_4_max 
                         FROM courses WHERE id = $course_id");
    $course = $res->fetch_assoc();

    // Get Students & Marks
    $sql = "SELECT u.id, u.full_name, u.email, 
            COALESCE(m.attendance, 0) as s1, 
            COALESCE(m.class_test, 0) as s2, 
            COALESCE(m.viva, 0) as s3,
            COALESCE(m.assess_4, 0) as s4,
            COALESCE(m.total, 0) as total
            FROM users u
            JOIN enrollments e ON u.id = e.student_id
            LEFT JOIN student_marks m ON (u.id = m.student_id AND m.course_id = ?)
            WHERE u.batch_id = ? AND e.course_id = ?
            ORDER BY u.id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $course_id, $batch_id, $course_id);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $conn->close();
} catch (Exception $e) {
}

// Set Defaults if empty
$n1 = $course['assess_1_name'] ?: 'Attendance';
$m1 = $course['assess_1_max'] ?: 10;
$n2 = $course['assess_2_name'] ?: 'Class Test';
$m2 = $course['assess_2_max'] ?: 20;
$n3 = $course['assess_3_name'] ?: 'Viva';
$m3 = $course['assess_3_max'] ?: 30;
$n4 = $course['assess_4_name'];
$m4 = $course['assess_4_max'] ?: 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Grading</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f9;
        }
    </style>
</head>

<body class="p-8 flex justify-center">
    <div class="w-full max-w-7xl bg-white shadow-xl rounded-xl p-8 border-t-4 border-indigo-600">

        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold text-gray-800">Grading: <?= htmlspecialchars($course['title'] ?? '') ?></h1>
            <a href="instructor_batch_courses.php?batch_id=<?= $batch_id ?>"
                class="text-indigo-600 hover:underline">&larr; Back</a>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-center font-bold"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="course_id" value="<?= $course_id ?>">
            <input type="hidden" name="batch_id" value="<?= $batch_id ?>">

            <div class="bg-indigo-50 p-6 rounded-lg mb-8 border border-indigo-200">
                <h3 class="font-bold text-indigo-800 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Customize Grading Schema
                </h3>
                <div class="grid grid-cols-4 gap-4 text-sm">
                    <div class="font-semibold text-gray-500">Component 1</div>
                    <div class="font-semibold text-gray-500">Component 2</div>
                    <div class="font-semibold text-gray-500">Component 3</div>
                    <div class="font-semibold text-gray-500">Extra Component (Optional)</div>

                    <div class="flex gap-2">
                        <input type="text" name="n1" value="<?= htmlspecialchars($n1) ?>"
                            class="w-full border p-2 rounded" placeholder="Name">
                        <input type="number" name="m1" value="<?= $m1 ?>" class="w-16 border p-2 rounded text-center"
                            placeholder="Max">
                    </div>
                    <div class="flex gap-2">
                        <input type="text" name="n2" value="<?= htmlspecialchars($n2) ?>"
                            class="w-full border p-2 rounded" placeholder="Name">
                        <input type="number" name="m2" value="<?= $m2 ?>" class="w-16 border p-2 rounded text-center"
                            placeholder="Max">
                    </div>
                    <div class="flex gap-2">
                        <input type="text" name="n3" value="<?= htmlspecialchars($n3) ?>"
                            class="w-full border p-2 rounded" placeholder="Name">
                        <input type="number" name="m3" value="<?= $m3 ?>" class="w-16 border p-2 rounded text-center"
                            placeholder="Max">
                    </div>
                    <div class="flex gap-2">
                        <input type="text" name="n4" value="<?= htmlspecialchars($n4) ?>"
                            class="w-full border p-2 rounded" placeholder="e.g. Lab">
                        <input type="number" name="m4" value="<?= $m4 ?>" class="w-16 border p-2 rounded text-center"
                            placeholder="0 if unused">
                    </div>
                </div>
                <p class="text-xs text-indigo-600 mt-2">* Set Max Marks to 0 to hide a column.</p>
            </div>

            <div class="overflow-x-auto rounded border border-gray-200">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700 text-xs uppercase font-bold">
                        <tr>
                            <th class="p-4 border-b">Student</th>
                            <?php if ($m1 > 0): ?>
                                <th class="p-4 border-b text-center w-32"><?= htmlspecialchars($n1) ?> (<?= $m1 ?>)</th>
                            <?php endif; ?>
                            <?php if ($m2 > 0): ?>
                                <th class="p-4 border-b text-center w-32"><?= htmlspecialchars($n2) ?> (<?= $m2 ?>)</th>
                            <?php endif; ?>
                            <?php if ($m3 > 0): ?>
                                <th class="p-4 border-b text-center w-32"><?= htmlspecialchars($n3) ?> (<?= $m3 ?>)</th>
                            <?php endif; ?>
                            <?php if ($m4 > 0): ?>
                                <th class="p-4 border-b text-center w-32 bg-yellow-50"><?= htmlspecialchars($n4) ?>
                                    (<?= $m4 ?>)</th><?php endif; ?>
                            <th class="p-4 border-b text-center w-32 bg-gray-200">Total (<?= $m1 + $m2 + $m3 + $m4 ?>)
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $stu): ?>
                            <tr class="hover:bg-gray-50 border-b">
                                <td class="p-4 font-bold text-gray-800">
                                    <?= htmlspecialchars($stu['full_name']) ?>
                                    <input type="hidden" name="student_ids[]" value="<?= $stu['id'] ?>">
                                </td>

                                <?php if ($m1 > 0): ?>
                                    <td class="p-4">
                                        <input type="number" name="s1_<?= $stu['id'] ?>" value="<?= $stu['s1'] ?>" min="0"
                                            max="<?= $m1 ?>"
                                            class="w-full border p-2 rounded text-center focus:ring-2 focus:ring-blue-500">
                                    </td>
                                <?php else: ?><input type="hidden" name="s1_<?= $stu['id'] ?>" value="0"><?php endif; ?>

                                <?php if ($m2 > 0): ?>
                                    <td class="p-4">
                                        <input type="number" name="s2_<?= $stu['id'] ?>" value="<?= $stu['s2'] ?>" min="0"
                                            max="<?= $m2 ?>"
                                            class="w-full border p-2 rounded text-center focus:ring-2 focus:ring-blue-500">
                                    </td>
                                <?php else: ?><input type="hidden" name="s2_<?= $stu['id'] ?>" value="0"><?php endif; ?>

                                <?php if ($m3 > 0): ?>
                                    <td class="p-4">
                                        <input type="number" name="s3_<?= $stu['id'] ?>" value="<?= $stu['s3'] ?>" min="0"
                                            max="<?= $m3 ?>"
                                            class="w-full border p-2 rounded text-center focus:ring-2 focus:ring-blue-500">
                                    </td>
                                <?php else: ?><input type="hidden" name="s3_<?= $stu['id'] ?>" value="0"><?php endif; ?>

                                <?php if ($m4 > 0): ?>
                                    <td class="p-4 bg-yellow-50">
                                        <input type="number" name="s4_<?= $stu['id'] ?>" value="<?= $stu['s4'] ?>" min="0"
                                            max="<?= $m4 ?>"
                                            class="w-full border p-2 rounded text-center border-yellow-300 focus:ring-2 focus:ring-yellow-500">
                                    </td>
                                <?php else: ?><input type="hidden" name="s4_<?= $stu['id'] ?>" value="0"><?php endif; ?>

                                <td class="p-4 text-center font-bold text-lg text-gray-800 bg-gray-100"><?= $stu['total'] ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    class="bg-green-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-green-700 shadow-md transition duration-200">
                    Save Configuration & Marks
                </button>
            </div>
        </form>
    </div>
</body>

</html>