<?php
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

// Create tables if connected
if (!$db_down) {
    // Users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        family_last_name VARCHAR(100) NOT NULL,
        owner_first_name VARCHAR(100) NOT NULL,
        plan_type ENUM('free', 'premium') NOT NULL,
        card_number VARCHAR(20) DEFAULT NULL,
        card_expiry VARCHAR(5) DEFAULT NULL,
        card_cvv VARCHAR(4) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Capsules table
    $sql = "CREATE TABLE IF NOT EXISTS capsules (
        capsule_id VARCHAR(22) PRIMARY KEY,
        user_id INT NOT NULL,
        storage_size BIGINT NOT NULL,
        file_types VARCHAR(50) NOT NULL,
        unlock_date DATETIME NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
}


function generateCapsuleId() {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
    $part1 = substr(str_shuffle($chars), 0, 6); // 6 chars
    $part2 = substr(str_shuffle($chars), 0, 6); // 6 chars
    $part3 = substr(str_shuffle($chars), 0, 8); // 8 chars
    return "$part1-$part2-$part3";
}

// Handle form submissions only if DB is up
$message = '';
if (!$db_down && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
    $family_last_name = filter_input(INPUT_POST, 'family_last_name', FILTER_SANITIZE_STRING);
    $owner_first_name = filter_input(INPUT_POST, 'owner_first_name', FILTER_SANITIZE_STRING);

    if (isset($_POST['free_submit'])) {
        // Free plan submission
        $plan_type = 'free';
        $stmt = $pdo->prepare("INSERT INTO users (email, phone, password, family_last_name, owner_first_name, plan_type) VALUES (?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$email, $phone, $password, $family_last_name, $owner_first_name, $plan_type]);
            $user_id = $pdo->lastInsertId();

            // Create free capsule
            $capsule_id = generateCapsuleId();
            $storage_size = 250 * 1024 * 1024; // 250MB in bytes
            $file_types = 'photos,text';
            $stmt = $pdo->prepare("INSERT INTO capsules (capsule_id, user_id, storage_size, file_types, unlock_date) VALUES (?, ?, ?, ?, NULL)");
            $stmt->execute([$capsule_id, $user_id, $storage_size, $file_types]);

            $message = "Free account created successfully! Your capsule is: $family_last_name's Capsule";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['premium_submit'])) {
        // Premium plan submission
        $plan_type = 'premium';
        $card_number = filter_input(INPUT_POST, 'card_number', FILTER_SANITIZE_STRING);
        $card_expiry = filter_input(INPUT_POST, 'card_expiry', FILTER_SANITIZE_STRING);
        $card_cvv = filter_input(INPUT_POST, 'card_cvv', FILTER_SANITIZE_STRING);

        $stmt = $pdo->prepare("INSERT INTO users (email, phone, password, family_last_name, owner_first_name, plan_type, card_number, card_expiry, card_cvv) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try {
            $stmt->execute([$email, $phone, $password, $family_last_name, $owner_first_name, $plan_type, $card_number, $card_expiry, $card_cvv]);
            $user_id = $pdo->lastInsertId();

            // Create premium capsule
            $capsule_id = generateCapsuleId();
            $storage_size = 25 * 1024 * 1024 * 1024; // 25GB in bytes
            $file_types = 'all';
            $stmt = $pdo->prepare("INSERT INTO capsules (capsule_id, user_id, storage_size, file_types, unlock_date) VALUES (?, ?, ?, ?, NULL)");
            $stmt->execute([$capsule_id, $user_id, $storage_size, $file_types]);

            $message = "Premium account submitted! Processing may take up to 3 business days. Your capsule is: $family_last_name's Capsule";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plans - Horizon Memories</title>
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
        .flip-card {
            perspective: 1000px;
            height: 300px;
        }
        .flip-card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }
        .flip-card:hover .flip-card-inner {
            transform: rotateY(180deg);
        }
        .flip-card-front, .flip-card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            background: #e5d9c6;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            color: #2d3748;
        }
        .flip-card-back {
            transform: rotateY(180deg);
            background: #ff9a8b;
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
            <button class="hover:text-teal modal-btn" data-modal="pricing-modal">Pricing</button>
            <button class="hover:text-teal modal-btn" data-modal="roadmap-modal">Roadmap</button>
            <button class="action-btn px-6 py-3 rounded-full text-lg modal-btn <?php echo $db_down ? 'disabled-btn' : ''; ?>" data-modal="free-signup-modal" <?php echo $db_down ? 'disabled' : ''; ?>>Sign Up</button>
            <a href="../login/" class="border border-slate px-6 py-3 rounded-full hover:text-teal">Log In</a>
        </nav>
    </header>

    <?php if ($db_down): ?>
    <div class="fixed top-20 left-1/2 transform -translate-x-1/2 bg-red-500 text-white p-4 rounded-lg z-30 w-11/12 max-w-md text-center">
        Service is currently down due to a database issue. Please try again later.
    </div>
    <?php elseif ($message): ?>
    <div class="fixed top-20 left-1/2 transform -translate-x-1/2 bg-green-500 text-white p-4 rounded-lg z-30">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <div id="pricing-modal" class="modal">
        <div class="modal-content">
            <button class="close-btn absolute top-2 right-2 hover:text-teal text-xl">X</button>
            <h2 class="text-2xl font-bold mb-4">Pricing</h2>
            <p class="mb-4">Here’s how we’re setting this up—premium’s the way to go:</p>
            <ul class="list-disc list-inside mb-4">
                <li><strong>Free Tier</strong>: One capsule, 250MB total storage, photos and text files only, max 5-year unlock date. A taste, not the full meal.</li>
                <li><strong>Premium (TBD/month)</strong>: 10 capsules included (up to 20 max), 25GB total storage split across capsules, all file types (videos, audio, docs), unlock dates with no time limit, plus collaboration and early access. Want more than 10 capsules? Add them for a small one-time fee per capsule—each new one takes a partition of your 25GB.</li>
            </ul>
            <p class="mb-4">Free’s a teaser—fine for a small photo or note. Premium’s the real deal: 10 capsules to start, 25GB to split as you like (one big 25GB capsule or 10 smaller ones), and no limits on time. Need more? Pay a small fee per extra capsule (up to 20 total), carving out space from your 25GB. Your rate’s locked forever—whatever you sign up for stays fixed unless you cancel. Plan tweaks? We’ll negotiate a fair bump above what you’re paying. If we ever go under, we’ll email you a warning, and you’ll have at least 3 months to grab your capsules.</p>
        </div>
    </div>
    <div id="roadmap-modal" class="modal">
        <div class="modal-content">
            <button class="close-btn absolute top-2 right-2 hover:text-teal text-xl">X</button>
            <h2 class="text-2xl font-bold mb-4">Roadmap</h2>
            <p class="mb-4">Here’s where we’re headed—premium users get priority:</p>
            <ul class="list-disc list-inside mb-4">
                <li><strong>Q2 2025</strong>: Beta launch—free tier (250MB, 5-year max, photos/text) and premium (10 capsules, 25GB, all files) live.</li>
                <li><strong>Q3 2025</strong>: Collaboration tools (premium only)—invite others to your capsules.</li>
                <li><strong>Q4 2025</strong>: Blockchain integration—tamper-proof unlock dates (premium feature).</li>
                <li><strong>Q1 2026</strong>: AI suggestions—auto-generate capsule ideas (premium only).</li>
                <li><strong>Mid-2026</strong>: Mobile app—full access (free tier limited, premium gets all 10+ capsules).</li>
                <li><strong>Late 2026</strong>: Storage bump—50GB+ for premium (more partitions!), free stays at 250MB.</li>
            </ul>
            <p>Free’s a start, but premium’s the future—10 capsules, 25GB, expandable to 20 with a fee. Lock in your flat rate now—stays fixed until you cancel, and we’ll negotiate any changes together! If we go under, we’ll send an email warning, and you’ll have at least 3 months to collect your capsules.</p>
        </div>
    </div>
    <div id="about-modal" class="modal">
        <div class="modal-content">
            <button class="close-btn absolute top-2 right-2 hover:text-teal text-xl">X</button>
            <h2 class="text-2xl font-bold mb-4">About Us</h2>
            <p class="mb-4">Horizon Memories is a passion project in development, aimed at giving you a way to lock away your digital keepsakes for the future.</p>
            <p>We’re a small team obsessed with nostalgia and tech. Go premium for 10 capsules and 25GB—support us and unlock the full experience!</p>
        </div>
    </div>
    <div id="contact-modal" class="modal">
        <div class="modal-content">
            <button class="close-btn absolute top-2 right-2 hover:text-teal text-xl">X</button>
            <h2 class="text-2xl font-bold mb-4">Contact Us</h2>
            <p class="mb-4">Got questions or ideas? We’re all ears during this development phase.</p>
            <p>Reach out at <a href="mailto:hello@horizonmemories.com" class="text-teal hover:underline">hello@horizonmemories.com</a> or follow us on X for updates.</p>
        </div>
    </div>
    <div id="privacy-modal" class="modal">
        <div class="modal-content">
            <button class="close-btn absolute top-2 right-2 hover:text-teal text-xl">X</button>
            <h2 class="text-2xl font-bold mb-4">Privacy</h2>
            <p class="mb-4">Your memories are yours. We’re designing Horizon Memories with top-notch encryption to keep your files private and secure.</p>
            <p>No data sharing, no nonsense—full policy coming soon. Premium gets priority support for any privacy concerns!</p>
        </div>
    </div>
    <div id="terms-modal" class="modal">
        <div class="modal-content">
            <button class="close-btn absolute top-2 right-2 hover:text-teal text-xl">X</button>
            <h2 class="text-2xl font-bold mb-4">Terms</h2>
            <p class="mb-4">By using Horizon Memories, you agree to play nice. We’re still drafting the fine print, but expect standard stuff:</p>
            <ul class="list-disc list-inside mb-4">
                <li>No illegal uploads.</li>
                <li>We’re not liable for lost time-travel dreams.</li>
                <li>If the service or company goes under, we’ll send an email warning, and you’ll have at least 3 months to retrieve your capsules.</li>
            </ul>
            <p class="mt-4">More to come as we launch—premium users get early updates on policy changes.</p>
        </div>
    </div>
    <div id="free-signup-modal" class="modal">
        <div class="modal-content">
            <button class="close-btn absolute top-2 right-2 hover:text-teal text-xl">X</button>
            <h2 class="text-2xl font-bold mb-4">Sign Up for Free</h2>
            <form method="POST" action="">
                <input type="email" name="email" placeholder="Email" required <?php echo $db_down ? 'disabled' : ''; ?>>
                <input type="tel" name="phone" placeholder="Phone Number" required <?php echo $db_down ? 'disabled' : ''; ?>>
                <input type="password" name="password" placeholder="Password" required <?php echo $db_down ? 'disabled' : ''; ?>>
                <input type="text" name="family_last_name" placeholder="Family Last Name" required <?php echo $db_down ? 'disabled' : ''; ?>>
                <input type="text" name="owner_first_name" placeholder="Owner First Name" required <?php echo $db_down ? 'disabled' : ''; ?>>
                <button type="submit" name="free_submit" class="action-btn px-6 py-3 rounded-full text-lg w-full <?php echo $db_down ? 'disabled-btn' : ''; ?>" <?php echo $db_down ? 'disabled' : ''; ?>>Create Free Account</button>
            </form>
        </div>
    </div>
    <div id="premium-signup-modal" class="modal">
        <div class="modal-content">
            <button class="close-btn absolute top-2 right-2 hover:text-teal text-xl">X</button>
            <h2 class="text-2xl font-bold mb-4">Sign Up for Premium</h2>
            <form method="POST" action="">
                <input type="email" name="email" placeholder="Email" required <?php echo $db_down ? 'disabled' : ''; ?>>
                <input type="tel" name="phone" placeholder="Phone Number" required <?php echo $db_down ? 'disabled' : ''; ?>>
                <input type="password" name="password" placeholder="Password" required <?php echo $db_down ? 'disabled' : ''; ?>>
                <input type="text" name="family_last_name" placeholder="Family Last Name" required <?php echo $db_down ? 'disabled' : ''; ?>>
                <input type="text" name="owner_first_name" placeholder="Owner First Name" required <?php echo $db_down ? 'disabled' : ''; ?>>
                <input type="text" name="card_number" placeholder="Credit Card Number" required <?php echo $db_down ? 'disabled' : ''; ?>>
                <div class="flex space-x-4">
                    <input type="text" name="card_expiry" placeholder="Expiration Date (MM/YY)" required class="w-1/2" <?php echo $db_down ? 'disabled' : ''; ?>>
                    <input type="text" name="card_cvv" placeholder="CVV" required class="w-1/2" <?php echo $db_down ? 'disabled' : ''; ?>>
                </div>
                <button type="submit" name="premium_submit" class="action-btn px-6 py-3 rounded-full text-lg w-full <?php echo $db_down ? 'disabled-btn' : ''; ?>" <?php echo $db_down ? 'disabled' : ''; ?>>Create Premium Account</button>
                <p class="text-sm mt-2">Note: Processing your premium account may take up to 3 business days.</p>
            </form>
        </div>
    </div>

    <section class="min-h-screen flex flex-col justify-center items-center text-center px-4 pt-20">
        <h2 id="plans-title" class="text-4xl md:text-5xl font-bold mb-4"></h2>
        <p class="text-lg mb-6">Pick the plan that fits your memories—start free or go premium for the full experience.</p>
    </section>

    <section class="py-16 px-4 bg-taupe">
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 plans">
            <div class="flip-card">
                <div class="flip-card-inner">
                    <div class="flip-card-front">
                        <h3 class="text-2xl font-semibold mb-2">Free Tier</h3>
                        <p class="text-lg mb-4">No Cost</p>
                        <ul class="list-disc list-inside mb-4 text-left">
                            <li>1 capsule</li>
                            <li>250MB storage</li>
                            <li>Photos & text only</li>
                            <li>5-year max unlock</li>
                        </ul>
                        <button class="action-btn px-6 py-3 rounded-full text-lg modal-btn <?php echo $db_down ? 'disabled-btn' : ''; ?>" data-modal="free-signup-modal" <?php echo $db_down ? 'disabled' : ''; ?>>Sign Up Free</button>
                    </div>
                    <div class="flip-card-back">
                        <p class="mb-4">A teaser to get you started—perfect for a small photo or note, but limited in scope. Try it out and see what Horizon Memories can do!</p>
                        <button class="action-btn px-6 py-3 rounded-full text-lg modal-btn <?php echo $db_down ? 'disabled-btn' : ''; ?>" data-modal="free-signup-modal" <?php echo $db_down ? 'disabled' : ''; ?>>Sign Up Free</button>
                    </div>
                </div>
            </div>
            <div class="flip-card">
                <div class="flip-card-inner">
                    <div class="flip-card-front">
                        <h3 class="text-2xl font-semibold mb-2">Premium Tier</h3>
                        <p class="text-lg mb-4">TBD / month</p>
                        <ul class="list-disc list-inside mb-4 text-left">
                            <li>10 capsules (up to 20)</li>
                            <li>25GB storage</li>
                            <li>All file types</li>
                            <li>Unlimited unlock dates</li>
                            <li>Collaboration & extras</li>
                        </ul>
                        <button class="action-btn px-6 py-3 rounded-full text-lg modal-btn <?php echo $db_down ? 'disabled-btn' : ''; ?>" data-modal="premium-signup-modal" <?php echo $db_down ? 'disabled' : ''; ?>>Go Premium</button>
                    </div>
                    <div class="flip-card-back">
                        <p class="mb-4">The full experience—10 capsules to start, 25GB to split as you like, and no time limits. Add up to 20 capsules with a small fee. Your rate’s locked forever, and if we go under, you’ll have at least 3 months to grab your stuff after an email warning.</p>
                        <button class="action-btn px-6 py-3 rounded-full text-lg modal-btn <?php echo $db_down ? 'disabled-btn' : ''; ?>" data-modal="premium-signup-modal" <?php echo $db_down ? 'disabled' : ''; ?>>Go Premium</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-8 px-4 bg-darkertaupe text-center">
        <div class="flex justify-center space-x-4 mb-4 footer-links">
            <button class="hover:text-teal modal-btn" data-modal="about-modal">About</button>
            <button class="hover:text-teal modal-btn" data-modal="contact-modal">Contact</button>
            <button class="hover:text-teal modal-btn" data-modal="privacy-modal">Privacy</button>
            <button class="hover:text-teal modal-btn" data-modal="terms-modal">Terms</button>
        </div>
        <p>Horizon Memories © 2025 - Where Time Meets Tomorrow</p>
    </footer>

    <script>
        gsap.registerPlugin(ScrollTrigger);

        new Typed('#plans-title', {
            strings: ["Choose Your Plan"],
            typeSpeed: 50,
            showCursor: false,
            onComplete: () => {
                anime({
                    targets: '.plans .flip-card',
                    translateY: [50, 0],
                    opacity: [0, 1],
                    easing: 'easeOutElastic(1, .8)',
                    duration: 1000,
                    delay: anime.stagger(200)
                });
            }
        });

        gsap.from('.plans .flip-card', {
            opacity: 0,
            y: 100,
            duration: 1,
            stagger: 0.3,
            scrollTrigger: {
                trigger: '.plans',
                start: 'top 80%',
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