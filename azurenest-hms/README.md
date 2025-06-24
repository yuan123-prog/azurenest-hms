# AzureNest Hotel Management System

A simple, staff-focused hotel management system built with PHP, MySQL, HTML, and CSS.

## Features

- Room reservation and management
- Guest check-in/check-out
- Payment and billing
- Housekeeping and staff task management
- Inventory and supplier management
- Staff scheduling
- Revenue and occupancy reporting
- Guest communication center
- Audit logging for critical actions (add/edit/delete)

## Setup

1. Import `private/sql/azurenest_db.sql` into your MySQL server.
2. Update database credentials in `private/db_connect.php`.
3. Place project in your web server root (e.g., `htdocs` for XAMPP).
4. Access via `http://localhost/azurenest-hms/public/`.

## Security & Best Practices

- All forms that modify data should use CSRF tokens (see `private/auth/csrf.php`).
- All user input is validated and sanitized on both client and server sides.
- Passwords are hashed using PHP's `password_hash()` before storing in the database.
- Session cookies are set to `HttpOnly` and `Secure` (if using HTTPS).
- Role-based access control is enforced for admin and staff actions.
- Audit logs are kept for all add, edit, and delete actions (see `Audit_Log` table).
- Database credentials and sensitive config should be kept outside the web root or in environment variables.
- Regular database backups are recommended (see `private/sql/backups/`).

## Default Admin

- Create a staff user directly in the database with a hashed password using PHP's `password_hash()`.
- You can also use `private/create_free_admin.php` to generate an initial admin account.

## Development & Contribution

- Use a `.gitignore` to exclude sensitive files and backups from version control.
- Follow consistent naming conventions and comment complex logic.
- Consider refactoring to MVC for larger projects.
- Use a CSS framework (e.g., Bootstrap or Tailwind) for improved UI/UX.

## License

MIT
