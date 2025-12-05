# COMSA Tracker

A PHP-based event and task management system for Computer Science Students Association (COMSA) - EARIST Manila

## Features
- User authentication (admin and regular users)
- Task assignment and tracking
- Event management
- Email notifications (via PHPMailer)
- Responsive dashboard and user pages

## Requirements
- PHP 7.4 or higher
- MySQL/MariaDB
- Composer (for dependency management)
- Web server (Apache recommended)

## Installation

### 1. Clone the repository
```
git clone https://github.com/your-org/COMSA-Tracker.git
cd COMSA-Tracker
```

### 2. Install dependencies
This project uses [PHPMailer](https://github.com/PHPMailer/PHPMailer) for sending emails.
Install dependencies via Composer:
```
composer install
```

### 3. Set up the database
- Import the provided `comsa_tracker.sql` file into your MySQL/MariaDB server:
```
mysql -u root -p comsa_tracker < comsa_tracker.sql
```
- Update `functions/config.php` with your database credentials if needed.

### 4. Configure email settings
- Edit `functions/email_config.php` and set your SMTP credentials:
```
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
define('MAIL_PORT', 587);
define('MAIL_SENDER_NAME', 'COMSA-Tracker');
define('MAIL_SENDER_EMAIL', 'your-email@gmail.com');
```
- For Gmail, you may need to create an App Password.

### 5. Run the application
- Place the project folder in your web server's root (e.g., `htdocs` for XAMPP).
- Access the app via `http://localhost/COMSA-Tracker/`

## Troubleshooting
- Make sure `vendor/` is present after running `composer install`.
- Check PHP and MySQL versions.
- Ensure SMTP credentials are correct for email sending.

## Dependencies
- [PHPMailer](https://github.com/PHPMailer/PHPMailer)
- Bootstrap, Remixicon, Bootstrap Icons (via CDN)

## License
This project is licensed under the [MIT License](LICENSE).
