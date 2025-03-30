<?php
// Session management
session_set_cookie_params(604800); // 7 days
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/");
    exit();
}

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
    $db_down = true;
}

// Fetch user's capsules
$capsules = [];
if (!$db_down) {
    $stmt = $pdo->prepare("SELECT * FROM capsules WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $capsules = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle unlock date update
$message = '';
if (!$db_down && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_unlock_date'])) {
    $capsule_id = filter_input(INPUT_POST, 'capsule_id', FILTER_SANITIZE_STRING);
    $unlock_date = filter_input(INPUT_POST, 'unlock_date', FILTER_SANITIZE_STRING);

    // Validate date format (YYYY-MM-DD HH:MM:SS)
    if ($unlock_date && DateTime::createFromFormat('Y-m-d\TH:i', $unlock_date)) {
        $stmt = $pdo->prepare("UPDATE capsules SET unlock_date = ? WHERE capsule_id = ? AND user_id = ?");
        try {
            $stmt->execute([$unlock_date . ':00', $capsule_id, $_SESSION['user_id']]);
            $message = "Unlock date updated successfully for capsule $capsule_id!";
        } catch (PDOException $e) {
            $message = "Error updating unlock date: " . $e->getMessage();
        }
    } else {
        $message = "Invalid date format. Use YYYY-MM-DD HH:MM.";
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../login/");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Horizon Memories</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            background: #e5d9c6;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #2d3748;
        }
        th {
            background: #ff9a8b;
            color: #2d3748;
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
            <a href="../index.html" class="hover:text-teal">Home</a>
            <form method="POST" action="">
                <button type="submit" name="logout" class="action-btn px-6 py-3 rounded-full text-lg">Log Out</button>
            </form>
        </nav>
    </header>

    <?php if ($db_down): ?>
    <div class="fixed top-20 left-1/2 transform -translate-x-1/2 bg-red-500 text-white p-4 rounded-lg z-30 w-11/12 max-w-md text-center">
        Service is currently down due to a database issue. Please try again later.
    </div>
    <?php elseif ($message): ?>
    <div class="fixed top-20 left-1/2 transform -translate-x-1/2 <?php echo strpos($message, 'successfully') !== false ? 'bg-green-500' : 'bg-red-500'; ?> text-white p-4 rounded-lg z-30">
        <?php echo htmlspecialchars($message); ?>
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
        <h2 id="dash-title" class="text-4xl md:text-5xl font-bold mb-4"></h2>
        <div class="max-w-4xl w-full">
            <h3 class="text-2xl mb-6">Welcome, <?php echo htmlspecialchars($_SESSION['owner_first_name'] . ' ' . $_SESSION['family_last_name']); ?>!</h3>
            <?php if (!$db_down && !empty($capsules)): ?>
            <table class="mb-6">
                <thead>
                    <tr>
                        <th>Capsule ID</th>
                        <th>Storage Size</th>
                        <th>File Types</th>
                        <th>Unlock Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($capsules as $capsule): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($capsule['capsule_id']); ?></td>
                        <td><?php echo round($capsule['storage_size'] / (1024 * 1024), 2) . ' MB'; ?></td>
                        <td><?php echo htmlspecialchars($capsule['file_types']); ?></td>
                        <td>
                            <?php 
                            if ($capsule['unlock_date']) {
                                $date = new DateTime($capsule['unlock_date']);
                                echo $date->format('F j, Y, g:i A');
                            } else {
                                echo 'Not Set';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if (!$capsule['unlock_date']): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="capsule_id" value="<?php echo htmlspecialchars($capsule['capsule_id']); ?>">
                                <input type="datetime-local" name="unlock_date" class="mb-2">
                                <button type="submit" name="update_unlock_date" class="action-btn px-4 py-2 rounded-full text-sm">Set Date</button>
                            </form>
                            <?php else: ?>
                            <a href="capsule.php?capsule_id=<?php echo htmlspecialchars($capsule['capsule_id']); ?>" class="action-btn px-4 py-2 rounded-full text-sm">Open Capsule</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php elseif (!$db_down): ?>
            <p class="text-lg">No capsules found. Create one in your account settings!</p>
            <?php endif; ?>
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

        new Typed('#dash-title', {
            strings: ["Your Dashboard"],
            typeSpeed: 50,
            showCursor: false,
            onComplete: () => {
                anime({
                    targets: 'table',
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