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
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
                toggleText.innerHTML = 'New user? <a href="#" onclick="toggleForm()" class="text-primary-blue hover:text-primary-dark font-medium">Create an account</a>';
                document.getElementById('form-title').textContent = 'Welcome Back to SkillPath';
            } else {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                toggleText.innerHTML = 'Already have an account? <a href="#" onclick="toggleForm()" class="text-primary-blue hover:text-primary-dark font-medium">Log in here</a>';
                document.getElementById('form-title').textContent = 'Start Your Journey with SkillPath';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            toggleForm();
            toggleForm();

            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const message = urlParams.get('message');
            const messageContainer = document.createElement('div');
            messageContainer.className = 'p-4 rounded-lg mb-4 text-center font-medium';

            if (status === 'success') {
                messageContainer.classList.add('bg-green-100', 'text-green-700');
                messageContainer.textContent = 'Registration successful! You can now log in.';
            } else if (status === 'error') {
                messageContainer.classList.add('bg-red-100', 'text-red-700');
                messageContainer.textContent = message || 'An error occurred during registration.';
            } else if (status === 'logout') {
                messageContainer.classList.add('bg-indigo-100', 'text-indigo-700');
                messageContainer.textContent = 'You have been successfully logged out.';
            }

            if (status) {
                const formContainer = document.querySelector('.bg-white');
                formContainer.prepend(messageContainer);
            }
        });

    </script>
</head>

<body class="min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-lg">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-extrabold text-primary-dark">SkillPath</h1>
            <p id="form-title" class="text-xl mt-2 text-gray-600">Welcome Back to SkillPath</p>
        </div>

        <div class="bg-white p-8 md:p-10 rounded-xl card-shadow">

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

            <form id="register-form" action="register.php" method="POST" class="space-y-6 hidden">
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
                    <label for="register-password" class="block text-sm font-medium text-gray-700 mb-1">Choose
                        Password</label>
                    <input type="password" id="register-password" name="password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-blue focus:border-primary-blue transition duration-150 ease-in-out"
                        placeholder="Create a strong password">
                </div>
                <div>
                    <label for="register-role" class="block text-sm font-medium text-gray-700 mb-1">I am a...</label>
                    <select id="register-role" name="role" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-blue focus:border-primary-blue bg-white transition duration-150 ease-in-out">
                        <option value="student">Student</option>
                        <option value="instructor">Instructor</option>
                        <option value="admin">Administrator (Requires Approval)</option>
                    </select>
                </div>
                <button type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-lg font-semibold text-white bg-accent-red hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent-red transition duration-200 ease-in-out">
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