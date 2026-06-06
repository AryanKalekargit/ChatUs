# ChatUs - Secure Real-Time Encrypted Chat Web Application

ChatUs is a robust, production-ready, and secure real-time messaging platform built with PHP, MySQL, and Vanilla JavaScript. It provides a vibrant Dark/Light aesthetic, advanced security with AES-256-CBC encryption, and features like once-view images and group chats.

## 🚀 Features

*   **Secure Authentication:** BCRYPT password hashing and Session Fixation prevention.
*   **End-to-End Encryption:** Messages are encrypted in AES-256-CBC *before* hitting the database.
*   **Vibrant UI Design:** Fast and modern UI using Bootstrap 5 with sporty color accents and a Dark/Light mode toggle.
*   **Real-Time Polling:** Efficient AJAX polling architecture for fetching messages instantly without page reloads.
*   **Media Support:** Send Images (with a Once-View option) and record Voice Messages via MediaRecorder API directly in the browser.
*   **Group Chats:** Easily create groups and chat securely with team members.
*   **Admin Dashboard:** Comprehensive dashboard to manage users, messages, and groups quickly.

## 🛠 Tech Stack

*   **Frontend:** HTML5, CSS3, Vanilla JS (Fetch API), Bootstrap 5
*   **Backend:** PHP 8+ (Object-Oriented + PDO)
*   **Database:** MySQL
*   **Deployment Environment:** Apache Server (Shared Hosting compatible)

## 📁 Folder Structure

*   **/admin:** Admin dashboard pages.
*   **/assets:** CSS, images, and JavaScript files (`chat.js`, `theme.js`).
*   **/config:** Database connection using PDO.
*   **/controllers:** Application logic mapped to API endpoints (`MessageController`, `AuthController`).
*   **/encryption:** Core `crypto.php` AES-256-CBC engine.
*   **/middleware:** Session protection scripts.
*   **/models:** Database interactions mapped by entity.
*   **/uploads:** Dynamically generated directories for Audio and Image uploads.

## ⚙️ Installation & Database Setup (Local)

1.  **Clone the Repository** and place it in your `htdocs` or `www` directory (e.g., `C:\xampp\htdocs\ChatUs`).
2.  **Database Configuration:** Open your MySQL client (like phpMyAdmin) and import `database.sql` to build the required schema and `chatus` database.
3.  **Adjust Credentials:** Update `config/database.php` with your local database credentials if they differ from the defaults (root / no password).
4.  **Permissions:** Ensure PHP has write permissions to the `/uploads/images` and `/uploads/audio` directories.
5.  **Run:** Open `http://localhost/ChatUs` in your browser. Register your first user. The first user will register as a standard user. You can modify their status to 'admin' in the database directly to access the admin panel.

## 🔒 Security Practices

*   **Data Masking:** All messaging payloads are encrypted using native OpenSSL extensions.
*   **SQL Injection:** Uses PDO connection with param binding `(?, :param)`.
*   **XSS Protection:** Outputs are sterilized before database transactions using HTML special characters encapsulation.

## 🌍 Depolyment Steps (Shared Hosting / cPanel)

1.  Log in to your cPanel or equivalent hosting manager.
2.  Navigate to **MySQL Databases** and create a new Database & User. Connect the User to the Database with full privileges.
3.  Access **phpMyAdmin** via cPanel and import `database.sql`.
4.  Navigate to **File Manager** -> `public_html` (or your addon domain folder).
5.  Upload the entire project folder contents into `public_html`.
6.  Edit `/config/database.php` to matching the new database credentials created in step 2.
7.  Verify `.htaccess` was uploaded correctly. If you're running Apache, it actively protects directory listings and config files.
8.  Edit `/encryption/crypto.php` and change the `ENCRYPTION_KEY` to a completely random 32-character string before production deployment.
9.  Configure the `/uploads` folder to have `755` permissions for safety.
