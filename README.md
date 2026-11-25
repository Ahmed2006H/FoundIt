
# FoundIt Campus

FoundIt Campus is a web-based Lost and Found Management System designed to help university students report, find, and claim lost belongings on campus.

## Features

- User registration and login
- Report lost and found items
- Browse and search reported items
- Submit and manage claims
- Notifications
- AI-powered item matching using Groq
- Admin dashboard and management

## Technologies

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- Groq API

## Requirements

- XAMPP
- PHP
- MySQL
- Web browser
- Groq API key for AI matching

## Installation

1. Clone or download this repository.
2. Place the project folder inside the XAMPP `htdocs` directory.
3. Start Apache and MySQL from XAMPP.
4. Create a MySQL database named `foundit_campus_db`.
5. Import the provided database SQL file.
6. Configure your local database connection.
7. Configure your Groq API key locally.
8. Open the project in your browser:

   http://localhost/foundit/

## Security

Never upload API keys, passwords, private configuration files, or personal user data to GitHub.

## Project Structure

- `admin/` - Admin pages
- `ai/` - AI matching functionality
- `auth/` - Authentication pages
- `config/` - Database configuration
- `user/` - User pages
- `assets/` - Static assets
- `uploads/` - Uploaded item images
- `dashboard.php` - Main dashboard
- `index.php` - Home page
