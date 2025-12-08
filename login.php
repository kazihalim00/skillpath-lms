<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillPath - Login / Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles for aesthetic enhancements */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f7f9fb;
        }

        .card-shadow {
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-blue': '#1c64f2',
                        'primary-dark': '#0f3a6e',
                        'accent-red': '#ef4444',
                    }
                }
            }
        }

        function toggleForm() {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const toggleText = document.getElementById('toggle-text');

            if (loginForm.classList.contains('hidden')) {
                // Show Login
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                toggleText.innerHTML = 'New user? <a href="#" onclick="toggleForm()" class="text-primary-blue hover:text-primary-dark font-medium">Create an account</a>';
                document.getElementById('form-title').textContent = 'Welcome Back to SkillPath';
            } else {
                // Show Register
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                toggleText.innerHTML = 'Already have an account? <a href="#" onclick="toggleForm()" class="text-primary-blue hover:text-primary-dark font-medium">Log in here</a>';
                document.getElementById('form-title').textContent = 'Start Your Journey with SkillPath';
            }
        }

        // Logic to show warning if Admin is selected
        function checkRole(selectObject) {
            const approvalMsg = document.getElementById('approval-msg');
            const role = selectObject.value;

            if (role === 'admin') {
                approvalMsg.classList.remove('hidden');
            } else {
                approvalMsg.classList.add('hidden');
            }
        }
    </script>
</head>

<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-lg">

        <div class="bg-white p-8 md:p-10 rounded-xl card-shadow">

            <div class="text-center mb-8">
                <h1 class="text-4xl font-extrabold text-primary-dark">SkillPath</h1>
                <p id="form-title" class="text-xl mt-2 text-gray-600">Welcome Back to SkillPath</p>
            </div>

            <?php if (isset($_GET['status'])): ?>
                <div
                    class="mb-6 p-4 rounded-lg text-sm text-center font-medium border <?php echo $_GET['status'] == 'error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200'; ?>">
                    <?php echo htmlspecialchars($_GET['message'] ?? ''); ?>
                </div>
            <?php endif; ?>

            <form id="login-form" action="index.php" method="POST" class="space-y-6">
                <div>
                    <label for="login-email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <input type="email" id="login-email" name="email" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-blue focus:border-primary-blue transition duration-150 ease-in-out"
                        placeholder="your.email@university.edu">
                </div>
                <div>
                    <label for="login-password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="login-password" name="password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-blue focus:border-primary-blue transition duration-150 ease-in-out"
                        placeholder="••••••••">
                </div>
                <button type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-lg font-semibold text-white bg-primary-blue hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-blue transition duration-200 ease-in-out">
                    Log In
                </button>
            </form>

            <form id="register-form" action="register.php" method="POST" class="space-y-5 hidden">

                <div>
                    <label for="register-name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" id="register-name" name="name" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-blue focus:border-primary-blue transition duration-150 ease-in-out"
                        placeholder="Kazi Abdul Halim">
                </div>

                <div>
                    <label for="register-email" class="block text-sm font-medium text-gray-700 mb-1">Email
                        Address</label>
                    <input type="email" id="register-email" name="email" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-blue focus:border-primary-blue transition duration-150 ease-in-out"
                        placeholder="your.email@university.edu">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select name="gender" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-blue focus:border-primary-blue bg-white transition duration-150 ease-in-out">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" required onchange="checkRole(this)"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-blue focus:border-primary-blue bg-white transition duration-150 ease-in-out">
                            <option value="student">Student</option>
                            <option value="instructor">Instructor</option>
                            <option value="admin">Administration</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select name="department_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-blue focus:border-primary-blue bg-white transition duration-150 ease-in-out">
                            <option value="">-- Select --</option>
                            <option value="1">SWE</option>
                        </select>
                    </div>
                </div>

                <div id="approval-msg"
                    class="hidden text-sm bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-2 rounded-lg">
                    ⚠️ <strong>Note:</strong> Administration accounts require manual approval before you can log in.
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batch (Students Only)</label>
                    <select name="batch_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-blue focus:border-primary-blue bg-white transition duration-150 ease-in-out">
                        <option value="">-- Select Batch --</option>
                        <option value="3">SWE Batch 3</option>
                        <option value="4">SWE Batch 4</option>
                        <option value="5">SWE Batch 5</option>
                        <option value="6">SWE Batch 6</option>
                        <option value="7">SWE Batch 7</option>
                        <option value="8">SWE Batch 8</option>
                        <option value="9">SWE Batch 9</option>
                        <option value="10">SWE Batch 10</option>
                    </select>
                </div>

                <div>
                    <label for="register-password" class="block text-sm font-medium text-gray-700 mb-1">Choose
                        Password</label>
                    <input type="password" id="register-password" name="password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-blue focus:border-primary-blue transition duration-150 ease-in-out"
                        placeholder="Create a strong password">
                </div>

                <button type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-lg font-semibold text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200 ease-in-out">
                    Register Now
                </button>
            </form>

            <p id="toggle-text" class="mt-6 text-center text-sm text-gray-600">
                New user? <a href="#" onclick="toggleForm()"
                    class="text-primary-blue hover:text-primary-dark font-medium">Create an account</a>
            </p>
        </div>

    </div>

</body>

</html>