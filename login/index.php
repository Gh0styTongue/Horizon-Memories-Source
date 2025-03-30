<?php
// Set session cookie lifetime to 7 days (604,800 seconds)
session_set_cookie_params(604800);
session_start();

// Database connection
$host = 'localhost';
$dbname = 'your_database_name';
$username = 'your_username';
$password = 'your_password';

$db_down = false;
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $db_down = true; // Flag for service down state
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../dash");
    exit();
}

// Handle login form submission
$message = '';
if (!$db_down && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Raw password for verification

    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Successful login: set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['family_last_name'] = $user['family_last_name'];
        $_SESSION['owner_first_name'] = $user['owner_first_name'];
        $_SESSION['plan_type'] = $user['plan_type'];

        // Redirect after 5 seconds
        $message = '<meta http-equiv="refresh" content="5;url=../dash">Welcome back, ' . htmlspecialchars($user['owner_first_name']) . ' ' . htmlspecialchars($user['family_last_name']) . '! Redirecting to dashboard in 5 seconds...';
    } else {
        $message = "Invalid email or password. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Horizon Memories</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <script src="https://unpkg.com/typed.js@2.1.0/dist/typed.umd.js"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to bottom, #d8c7b3, #b8a594);
            color: #2d3748;
            overflow-x: hidden;
        }
        .nav-sticky {
            background: rgba(229, 217, 198, 0.9);
            backdrop-filter: blur(5px);
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 20;
        }
        .modal-content {
            background: #e5d9c6;
            padding: 2rem;
            border-radius: 1rem;
            max-width: 600px;
            width: 90%;
            position: relative;
            color: #2d3748;
            max-height: 80vh;
            overflow-y: auto;
        }
        button, a {
            color: #2d3748;
        }
        .action-btn {
            background-color: #ff6f61;
            color: #ffffff;
        }
        .action-btn:hover {
            background-color: #468585;
            color: #ffffff;
        }
        input {
            border: 1px solid #2d3748;
            border-radius: 0.25rem;
            padding: 0.5rem;
            width: 100%;
            margin-bottom: 1rem;
            color: #2d3748;
            background: #fff;
        }
        input:focus {
            outline: none;
            border-color: #468585;
        }
        .disabled-btn {
            background-color: #cccccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <header class="nav-sticky fixed top-0 w-full p-4 flex justify-between items-center z-10">
        <div class="flex items-center">
            <h1 class="text-2xl font-handwritten">Horizon Memories</h1>
            <p class="ml-2 text-sm hidden md:block">Save Today, Open Tomorrow</p>
        </div>
        <nav class="flex space-x-4 items-center">
            <a href="../" class="hover:text-teal">Home</a>
            <a href="../plans/" class="action-btn px-6 py-3 rounded-full text-lg">Sign Up</a>
            <a href="#" class="border border-slate px-6 py-3 rounded-full hover:text-teal">Log In</a>
        </nav>
    </header>

    <?php if ($db_down): ?>
    <div class="fixed top-20 left-1/2 transform -translate-x-1/2 bg-red-500 text-white p-4 rounded-lg z-30 w-11/12 max-w-md text-center">
        Service is currently down due to a database issue. Please try again later.
    </div>
    <?php elseif ($message): ?>
    <div class="fixed top-20 left-1/2 transform -translate-x-1/2 <?php echo strpos($message, 'Welcome') === 0 ? 'bg-green-500' : 'bg-red-500'; ?> text-white p-4 rounded-lg z-30">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <div id="pricing-modal" class="modal">
        <div class="modal-content">
            <button class="close-btn absolute top-2 right-2 hover:text-teal text-xl">X</button>
            <h2 class="text-2xl font-bold mb-4">Pricing</h2>
            <p class="mb-4">Here’s how we’re setting this up—premium’s the way to go:</p>
            <ul class="list-disc list-inside mb-4">
                <li><strong>Free Tier</strong>: One capsule, 250MB total storage, photos and text files only, max 5-year unlock date.</li>
                <li><strong>Premium (TBD/month)</strong>: 10 capsules (up to 20 max), 25GB total storage, all file types, unlimited unlock dates, collaboration, and early access.</li>
            </ul>
        </div>
    </div>
    <div id="roadmap-modal" class="modal">
        <div class="modal-content">
            <button class="close-btn absolute top-2 right-2 hover:text-teal text-xl">X</button>
            <h2 class="text-2xl font-bold mb-4">Roadmap</h2>
            <p class="mb-4">Here’s where we’re headed—premium users get priority:</p>
            <ul class="list-disc list-inside mb-4">
                <li><strong>Q2 2025</strong>: Beta launch—free and premium tiers live.</li>
                <li><strong>Q3 2025</strong>: Collaboration tools (premium only).</li>
                <li><strong>Q4 2025</strong>: Blockchain integration (premium).</li>
                <li><strong>Q1 2026</strong>: AI suggestions (premium).</li>
                <li><strong>Mid-2026</strong>: Mobile app.</li>
                <li><strong>Late 2026</strong>: Storage bump—50GB+ for premium.</li>
            </ul>
        </div>
    </div>

    <section class="min-h-screen flex flex-col justify-center items-center text-center px-4 pt-20">
        <h2 id="login-title" class="text-4xl md:text-5xl font-bold mb-4"></h2>
        <div class="bg-e5d9c6 p-6 rounded-lg shadow-lg max-w-md w-full">
            <form method="POST" action="">
                <input type="email" name="email" placeholder="Email" required <?php echo $db_down ? 'disabled' : ''; ?>>
                <input type="password" name="password" placeholder="Password" required <?php echo $db_down ? 'disabled' : ''; ?>>
                <button type="submit" class="action-btn px-6 py-3 rounded-full text-lg w-full <?php echo $db_down ? 'disabled-btn' : ''; ?>" <?php echo $db_down ? 'disabled' : ''; ?>>Log In</button>
            </form>
            <p class="mt-4">Don’t have an account? <a href="plans/index.php" class="text-teal hover:underline">Sign up here</a>.</p>
        </div>
    </section>

    <footer class="py-8 px-4 bg-darkertaupe text-center">
        <div class="flex justify-center space-x-4 mb-4 footer-links">
            <button class="hover:text-teal modal-btn" data-modal="pricing-modal">Pricing</button>
            <button class="hover:text-teal modal-btn" data-modal="roadmap-modal">Roadmap</button>
        </div>
        <p>Horizon Memories © 2025 - Where Time Meets Tomorrow</p>
    </footer>

    <script>
        gsap.registerPlugin(ScrollTrigger);

        new Typed('#login-title', {
            strings: ["Log In"],
            typeSpeed: 50,
            showCursor: false,
            onComplete: () => {
                anime({
                    targets: '.bg-e5d9c6',
                    translateY: [50, 0],
                    opacity: [0, 1],
                    easing: 'easeOutElastic(1, .8)',
                    duration: 1000
                });
            }
        });

        gsap.from('.footer-links button', {
            opacity: 0,
            y: 20,
            duration: 0.8,
            stagger: 0.2,
            scrollTrigger: {
                trigger: 'footer',
                start: 'top 90%',
            }
        });

        const modalBtns = document.querySelectorAll('.modal-btn');
        const modals = document.querySelectorAll('.modal');
        const closeBtns = document.querySelectorAll('.close-btn');

        modalBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                if (!btn.hasAttribute('disabled')) {
                    const modalId = btn.getAttribute('data-modal');
                    const modal = document.getElementById(modalId);
                    modal.style.display = 'flex';
                    anime({
                        targets: modal.querySelector('.modal-content'),
                        scale: [0.8, 1],
                        opacity: [0, 1],
                        easing: 'easeOutQuad',
                        duration: 300
                    });
                }
            });
        });

        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const modal = btn.closest('.modal');
                anime({
                    targets: modal.querySelector('.modal-content'),
                    scale: [1, 0.8],
                    opacity: [1, 0],
                    easing: 'easeInQuad',
                    duration: 300,
                    complete: () => modal.style.display = 'none'
                });
            });
        });

        modals.forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    anime({
                        targets: modal.querySelector('.modal-content'),
                        scale: [1, 0.8],
                        opacity: [1, 0],
                        easing: 'easeInQuad',
                        duration: 300,
                        complete: () => modal.style.display = 'none'
                    });
                }
            });
        });
    </script>
</body>
</html>