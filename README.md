# ExpenseTracker

A personal finance management web application with OCR-powered receipt scanning.

---

## Quick Setup Guide

### Prerequisites
- XAMPP (or any PHP 7.4+ with MySQL)
- Web browser
- Google Cloud Vision API key (optional, for receipt scanning)

### Installation Steps

**1. Clone or Download the Project**
```bash
git clone https://github.com/Moaaz-Aboualyan/ExpenseTracker.git
cd ExpenseTracker
```

**2. Start XAMPP**
- Launch XAMPP Control Panel
- Start **Apache** and **MySQL** services

**3. Create Database**
- Open phpMyAdmin: `http://localhost/phpmyadmin`
- Create a new database named `expense_tracker`
- Import the database schema:
  - Click on `expense_tracker` database
  - Go to **Import** tab
  - Choose `install.sql` file
  - Click **Go**
- (optional) import dummy_data.sql for dummy test user:
  - email: testuser@example.com
  - password: password

**4. Configure Database Connection** (if needed)
- Open `DatabaseConfiguration.php`
- Update credentials if different from defaults:
  ```php
  $DB_HOST = 'localhost';
  $DB_NAME = 'expense_tracker';
  $DB_USER = 'root';
  $DB_PASS = '';  // Usually empty for XAMPP
  ```

**5. Setup OCR (Optional)**
- Copy `.env.example` to `.env`
- Get a Google Cloud Vision API key from [Google Cloud Console](https://console.cloud.google.com/)
- Add your API key to `.env`:
  ```
  GOOGLE_VISION_API_KEY=your_api_key_here
  ```
- **Important**: Never commit the `.env` file to Git

**6. Access the Application**
- Open browser and go to: `http://localhost/wap/`
- Register a new account
- Start tracking your expenses!