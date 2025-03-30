# Horizon Memories Source

**Save Today, Open Tomorrow**  
Horizon Memories is a web application that lets users create time capsules to store files (photos, text, PDFs, etc.) with unlock dates, preserving memories for the future. Built with PHP, MySQL, and a modern frontend, it offers a user-friendly interface for uploading, viewing, and managing files securely.

## Features

- **User Authentication**: Secure signup and login system with 7-day session persistence.
- **Time Capsules**: Create capsules with storage limits, file type restrictions, and unlock dates.
- **File Management**:
  - Multi-file uploads with progress bars, percentage, and ETA.
- **File Type Enforcement**: Supports photos (JPEG, PNG, GIF) and text (TXT, PDF, DOC, DOCX) based on capsule settings.
- **Responsive Design**: Built with Tailwind CSS, animated with GSAP and Anime.js.

## Prerequisites

- **PHP**: 7.4+ with PDO and `mime_content_type` enabled.
- **MySQL**: 5.7+ for database storage.
- **Web Server**: Apache or Nginx.
- **Composer**: Optional, for dependency management (not currently used).

## Installation

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/Gh0styTongue/Horizon-Memories-Source.git
   cd horizon-memories
   ```

2. **Set Up the Database**:
   - Create a MySQL database (e.g., `database`).
   - Import the initial schema (adjust as needed):
     ```sql
     CREATE TABLE users (
         id INT AUTO_INCREMENT PRIMARY KEY,
         email VARCHAR(255) NOT NULL UNIQUE,
         password VARCHAR(255) NOT NULL,
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
     );

     CREATE TABLE capsules (
         capsule_id VARCHAR(22) NOT NULL PRIMARY KEY,
         user_id INT NOT NULL,
         storage_size BIGINT NOT NULL,
         file_types VARCHAR(255) NOT NULL,
         unlock_date DATETIME,
         FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
     );

     CREATE TABLE files_capsule (
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
     );
     ```

3. **Configure Database Connection**:
   - Open `dash/capsule.php` (and other files like `plans/login.php` if present).
   - Update the database credentials:
     ```php
     $host = 'localhost';
     $dbname = 'your_database_name';
     $username = 'your_username';
     $password = 'your_password';
     ```

4. **Deploy to Server**:
   - Upload the files to your web server (e.g., `/var/www/html/hm`).
   - Ensure the directory is writable for session data.

5. **Access the Site**:
   - Navigate to `/plans/login.php` to start.

## Usage

1. **Sign Up / Log In**: Create an account or log in.
2. **Create a Capsule**: Set storage size, file types (e.g., "photos,text"), and an unlock date.
3. **Upload Files**: Select multiple files, set a file date, and upload.
4. **Manage Files**: View files in a modal or delete multiple files with a confirmation warning.
5. **Log Out**: End your session securely.

## Project Structure

```
horizon-memories/
├── dash/
│   └── capsule.php      # Main capsule management page
├── plans/
│   └── login.php        # Login page
├── README.md            # This file
└── index.html           # homepage
```

### Guidelines
- Follow PHP PSR-12 coding standards.
- Test locally before submitting.
- Add features like DOCX parsing, per-file progress, or better error handling.

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.

## Acknowledgements

- **Tailwind CSS**: For responsive styling.
- **GSAP & Anime.js**: For animations.
- **Typed.js**: For typing effects.
- **xAI**: Inspiration from Grok’s assistance in development.
