# StudyCore - An Online Learning & Test Preparation Platform

StudyCore is a comprehensive, feature-rich web application designed to be an all-in-one platform for students and educators. It provides a secure environment for accessing study materials, managing user accounts, and administering test series. This project is built from the ground up using a classic LAMP stack (PHP & MySQL) with a focus on modern security practices, a clean user interface, and a scalable architecture.

## ✨ Key Features Implemented

### 👤 User Authentication & Security
*   **Complete Registration & Login System:** Secure user registration with mandatory live email verification (OTP).
*   **Two-Factor Authentication (2FA):** Privileged users (Admins, Educators, etc.) are required to verify their identity with a time-sensitive OTP sent to their email upon login, providing an extra layer of security.
*   **Secure Password Handling:** All user passwords and other sensitive data (like security answers) are securely hashed using modern `password_hash()` and `password_verify()` standards.
*   **Forgot Password Flow:** A complete, secure "Forgot Password" system that sends a time-limited reset link to the user's verified email address, using an external SMTP service (PHPMailer) for reliable delivery.
*   **Active Session Management:** The system actively tracks user sessions and enforces a device limit based on the user's subscription plan. When a user logs in on a new device that exceeds their limit, their oldest active session is automatically terminated.
*   **Session Hijacking Protection:** All protected pages validate the user's session token against the database on every page load, preventing unauthorized access.

### 👨‍🎓 Student Dashboard & Profile Management
*   **Dynamic Student Homepage:** A central hub for students, displaying available content sections. The page implements a content lock, greying out all options and prompting the user to upgrade if their subscription has expired.
*   **All-in-One Profile & Settings Page:** A modern, tabbed interface where users can:
    *   View all their profile details.
    *   Edit their name and phone number.
    *   Securely change their password by first verifying their current password.
    *   Enable or disable 2FA for their account with a simple toggle.
*   **Subscription Management Page:** A dedicated page for users to view their current active plan, its expiry date, and see other available plans. Includes a developer-friendly coupon code system (`DEVFREE100`) to simulate plan activation.

### 👑 Administrator & Staff Management
*   **Role-Based Access Control (RBAC):** A robust system with multiple user roles (`student`, `educator`, `admin`, `master_admin`, `support`), with different dashboards and permissions for each.
*   **Automated Privileged User Onboarding:**
    *   Admins can create invitations for new staff members (Educators, Admins, etc.).
    *   The system automatically generates a unique registration link and a temporary password based on the employee's name and DOB.
    *   The invitation email is sent automatically via the integrated email service.
    *   The invitation link has a secure, time-limited validity (6 days).
*   **Advanced Employee Management Panel:** A professional, tabbed interface for Admins to:
    *   View pending invitations and their expiry dates.
    *   View all registered staff members with their unique `EMP ID` (e.g., `25EDU15`).
    *   Add new privileged users.
    *   Automatically resend a new, valid invitation if the original is lost.
*   **Live Search & Filtering:** All tables in the admin panel feature dynamic search bars for easily finding users or invitations.

### 🎨 User Interface & Experience
*   **Responsive Design:** All pages, from the marketing homepage to the admin panels, are designed to be responsive and work on various screen sizes.
*   **Dark Mode:** A consistent, beautiful dark mode is available across the entire application, with the user's preference saved in their browser.
*   **Modern UI:** The application uses clean, card-based layouts, intuitive navigation, and interactive elements to provide a professional user experience.

## 🛠️ Tech Stack & Dependencies
*   **Backend:** PHP
*   **Database:** MySQL / MariaDB
*   **Frontend:** HTML5, CSS3 (with CSS Variables), vanilla JavaScript (for dynamic front-end logic like `fetch()` and DOM manipulation).
*   **Dependencies:**
    *   **PHPMailer:** For sending reliable transactional emails (OTP, password resets, invitations) via an external SMTP service like Brevo.

## 🚀 How to Set Up

1.  **Database Setup:**
    *   Create a new MySQL database.
    *   Import the provided SQL schema to create all necessary tables (`users`, `plans`, `employees`, `otp_codes`, `password_resets`, etc.).

2.  **Configuration:**
    *   Navigate to `/config/db_connect.php` and enter your database credentials (hostname, username, password, database name).

3.  **Email Service Setup:**
    *   Sign up for a free account with an SMTP provider like **Brevo (Sendinblue)**.
    *   In your Brevo account, verify a "Sender" email address.
    *   Go to the "SMTP & API" section and get your unique SMTP credentials (Server, Port, Login, and SMTP Key).
    *   Open all PHP files that use PHPMailer (e.g., `handle_forgot_password.php`, `handle_add_employee.php`) and paste your Brevo SMTP credentials into the designated sections.

4.  **Install PHPMailer:**
    *   Download the PHPMailer library from its official GitHub repository.
    *   Create a `PHPMailer` folder in the project's root directory.
    *   Copy the contents of the `src` folder from the download into your `PHPMailer` directory.

5.  **You're Ready!**
    *   Upload all files to your web server (e.g., in the `/htdocs/` directory).
    *   Navigate to the `index.html` page to begin.
