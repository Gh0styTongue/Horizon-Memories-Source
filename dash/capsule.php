<?php
session_set_cookie_params(604800);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../plans/");
    exit();
}

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

$capsule_id = filter_input(INPUT_GET, 'capsule_id', FILTER_SANITIZE_STRING);
if (!$capsule_id || !$db_down) {
    $stmt = $pdo->prepare("SELECT * FROM capsules WHERE capsule_id = ? AND user_id = ?");
    $stmt->execute([$capsule_id, $_SESSION['user_id']]);
    $capsule = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$capsule) {
        header("Location: ../dash/");
        exit();
    }
}

if (!$db_down) {
    $sql = "CREATE TABLE IF NOT EXISTS files_capsule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        capsule_id VARCHAR(22) NOT NULL,
        user_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        file_date DATETIME NOT NULL,
        date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        file_blob MEDIUMBLOB NOT NULL,
        byte_size BIGINT NOT NULL,
        FOREIGN KEY (capsule_id) REFERENCES capsules(capsule_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
}

$files = [];
$total_size = 0;
if (!$db_down) {
    $stmt = $pdo->prepare("SELECT * FROM files_capsule WHERE capsule_id = ?");
    $stmt->execute([$capsule_id]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($files as $file) {
        $total_size += $file['byte_size'];
    }
}

$message = '';
if (!$db_down && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filename = $file['name'];
    $file_date = filter_input(INPUT_POST, 'file_date', FILTER_SANITIZE_STRING);
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_type = mime_content_type($file_tmp);
    $allowed_types = array_map('trim', explode(',', $capsule['file_types']));
    $mime_map = [
        'photos' => ['image/jpeg', 'image/png', 'image/gif'],
        'text' => ['text/plain', 'application/pdf']
    ];
    $allowed_mimes = [];
    foreach ($allowed_types as $type) {
        if (isset($mime_map[$type])) {
            $allowed_mimes = array_merge($allowed_mimes, $mime_map[$type]);
        }
    }

    if ($total_size + $file_size > $capsule['storage_size']) {
        $message = "Upload blocked: Total size would exceed capsule storage limit (" . formatBytes($capsule['storage_size']) . ")!";
    } elseif ($file['error'] === UPLOAD_ERR_OK) {
        if (!$file_date || !DateTime::createFromFormat('Y-m-d\TH:i', $file_date)) {
            $message = "File date is required and must be in YYYY-MM-DD HH:MM format.";
        } elseif (!in_array($file_type, $allowed_mimes)) {
            $message = "File type '$file_type' not allowed. Allowed types: " . implode(', ', $allowed_types) . ".";
        } else {
            $file_blob = file_get_contents($file_tmp);
            $stmt = $pdo->prepare("INSERT INTO files_capsule (capsule_id, user_id, filename, file_date, file_blob, byte_size) VALUES (?, ?, ?, ?, ?, ?)");
            try {
                $stmt->execute([
                    $capsule_id,
                    $_SESSION['user_id'],
                    $filename,
                    $file_date . ':00',
                    $file_blob,
                    $file_size
                ]);
                $message = "File '$filename' uploaded successfully!";
            } catch (PDOException $e) {
                $message = "Error uploading file: " . $e->getMessage();
            }
        }
    } else {
        $message = "File upload error!";
    }
}

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $size = $bytes;
    $unitIndex = 0;
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    return round($size, 2) . ' ' . $units[$unitIndex];
}

if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../plans/");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capsule - Horizon Memories</title>
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
        .progress-container {
            margin-bottom: 1rem;
            display: none;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #ddd;
            border-radius: 0.25rem;
            overflow: hidden;
        }
        .progress {
            height: 100%;
            background: #468585;
            width: 0;
            transition: width 0.3s ease;
        }
        .progress-info {
            text-align: center;
            margin-top: 0.5rem;
            font-size: 0.9rem;
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
            <a href="../dash/index.php" class="hover:text-teal">Dashboard</a>
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

    <section class="min-h-screen flex flex-col justify-center items-center text-center px-4 pt-20">
        <h2 id="capsule-title" class="text-4xl md:text-5xl font-bold mb-4"></h2>
        <div class="max-w-4xl w-full">
            <h3 class="text-2xl mb-6">Capsule: <?php echo htmlspecialchars($capsule_id); ?></h3>
            <p class="mb-4">Total Storage: <?php echo formatBytes($capsule['storage_size']); ?> | Used: <?php echo formatBytes($total_size); ?> | Unlock Date: <?php echo $capsule['unlock_date'] ? (new DateTime($capsule['unlock_date']))->format('F j, Y, g:i A') : 'Not Set'; ?></p>

            <form id="uploadForm" method="POST" enctype="multipart/form-data" class="bg-e5d9c6 p-6 rounded-lg shadow-lg mb-6">
                <input type="file" name="file" id="fileInput" required class="mb-2">
                <input type="datetime-local" name="file_date" id="fileDate" required class="mb-2">
                <div class="progress-container" id="progressContainer">
                    <div class="progress-bar" id="progressBar">
                        <div class="progress" id="progress"></div>
                    </div>
                    <div class="progress-info" id="progressInfo">0% - ETA: Calculating...</div>
                </div>
                <button type="submit" class="action-btn px-6 py-3 rounded-full text-lg w-full">Upload File</button>
            </form>

            <?php if (!empty($files)): ?>
            <table class="mb-6">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>File Date</th>
                        <th>Date Added</th>
                        <th>Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($file['filename']); ?></td>
                        <td><?php echo (new DateTime($file['file_date']))->format('F j, Y, g:i A'); ?></td>
                        <td><?php echo (new DateTime($file['date_added']))->format('F j, Y, g:i A'); ?></td>
                        <td><?php echo formatBytes($file['byte_size']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p class="text-lg">No files uploaded yet.</p>
            <?php endif; ?>
        </div>
    </section>

    <footer class="py-8 px-4 bg-darkertaupe text-center">
        <p>Horizon Memories © 2025 - Where Time Meets Tomorrow</p>
    </footer>

    <script>
        gsap.registerPlugin(ScrollTrigger);

        new Typed('#capsule-title', {
            strings: ["Manage Capsule"],
            typeSpeed: 50,
            showCursor: false,
            onComplete: () => {
                anime({
                    targets: '.bg-e5d9c6, table',
                    translateY: [50, 0],
                    opacity: [0, 1],
                    easing: 'easeOutElastic(1, .8)',
                    duration: 1000,
                    delay: anime.stagger(200)
                });
            }
        });

        gsap.from('footer p', {
            opacity: 0,
            y: 20,
            duration: 0.8,
            scrollTrigger: {
                trigger: 'footer',
                start: 'top 90%',
            }
        });

        const form = document.getElementById('uploadForm');
        const fileInput = document.getElementById('fileInput');
        const progressContainer = document.getElementById('progressContainer');
        const progress = document.getElementById('progress');
        const progressInfo = document.getElementById('progressInfo');

        form.addEventListener('submit', function(e) {
            const file = fileInput.files[0];
            if (file) {
                const totalSize = <?php echo $total_size; ?>;
                const capsuleSize = <?php echo $capsule['storage_size']; ?>;
                if (totalSize + file.size > capsuleSize) {
                    alert("Upload blocked: Total size would exceed capsule storage limit!");
                    e.preventDefault();
                    return;
                }

                progressContainer.style.display = 'block';
                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action, true);

                let startTime = null;
                xhr.upload.onprogress = function(event) {
                    if (event.lengthComputable) {
                        if (!startTime) startTime = Date.now();
                        const percentComplete = (event.loaded / event.total) * 100;
                        progress.style.width = percentComplete + '%';

                        const elapsed = (Date.now() - startTime) / 1000;
                        const speed = event.loaded / elapsed;
                        const remainingBytes = event.total - event.loaded;
                        const etaSeconds = remainingBytes / speed;
                        const eta = etaSeconds > 60 ? Math.round(etaSeconds / 60) + ' min' : Math.round(etaSeconds) + ' sec';

                        progressInfo.textContent = `${Math.round(percentComplete)}% - ETA: ${eta}`;
                    }
                };

                xhr.onload = function() {
                    progressContainer.style.display = 'none';
                    if (xhr.status === 200) {
                        location.reload();
                    }
                };

                const formData = new FormData(form);
                xhr.send(formData);
                e.preventDefault();
            }
        });
    </script>
</body>
</html>