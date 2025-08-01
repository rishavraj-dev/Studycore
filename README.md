# StudyCore - An Online Learning & Content Management Platform

StudyCore is a comprehensive, feature-rich web application designed as an all-in-one platform for students and educators. It provides a secure environment for user management, subscription handling, and, most importantly, a dynamic, database-driven system for delivering and managing educational content like courses, chapters, video lectures, and PDF notes.

This project is built from the ground up using a classic LAMP stack (PHP & MySQL) with a focus on modern security practices (like 2FA), a clean user interface with dark mode, and a highly scalable, normalized database architecture.

---

## ✨ Key Features Implemented

### 👤 User Authentication & Advanced Security
-   **Secure Registration Flow:** Mandatory, live email verification (OTP) is required for all new user sign-ups, ensuring a valid user base.
-   **Flexible Two-Factor Authentication (2FA):** A robust 2FA system for privileged users (Admins, Educators) requiring an OTP sent via email upon login. The system is built with a database flag (`is_2fa_enabled`) allowing it to be extended to any user type in the future.
-   **Secure Password Handling:** Implements `password_hash()` and `password_verify()` for all passwords and sensitive data. Includes a full "Forgot Password" flow using PHPMailer and an external SMTP service (like Brevo) for reliable, secure email delivery.
-   **Role-Based Access Control (RBAC):** A clear system with multiple user roles (`student`, `educator`, `admin`, `master_admin`) with segregated dashboards and permissions.
-   **Active Session Management:** Enforces a device limit based on a user's subscription plan. When a limit is exceeded, the user's oldest active session is automatically terminated, preventing account sharing.

### 👨‍🎓 Student-Facing Features
-   **Dynamic Student Homepage:** A central hub displaying available courses (JEE, NEET, Classes). The page implements a content lock, gracefully disabling access with an overlay if the user's subscription has expired.
-   **All-in-One Profile & Settings Page:** A modern, tabbed interface where students can:
    -   View their complete profile details.
    -   Edit their name and phone number.
    -   Securely change their password (requires current password verification).
    -   Toggle 2FA on or off for their account.
-   **Subscription Management Page:** A page for users to view their current active plan and its expiry date. Includes a developer-friendly coupon code system (`DEVFREE100`) to simulate plan activation.
-   **Dynamic Content Delivery System:**
    -   **Course Browser (`course.php`):** A dynamic "Table of Contents" page that displays subjects and chapters for any selected course using a clean, tabbed interface and a live search filter.
    -   **Chapter Hub (`chapter.php`):** A dedicated page for each chapter, featuring a tabbed view (Live Classes, Notes, DPPs, etc.) that displays all associated content.
    -   **Integrated Content Viewers:**
        -   `player.php`: An embedded, distraction-free video player for lectures that also lists all linked study materials (PDFs, notes).
        -   `view.php`: An in-browser PDF viewer for notes and practice problems.

### 👑 Administrator & Content Management System (CMS)
-   **Fully Automated Staff Onboarding:**
    -   Admins can create invitations for new staff members.
    -   The system automatically generates a unique registration link and a secure temporary password (`FirstName@DDMMYY`).
    -   The invitation email is sent **automatically** via PHPMailer, and a 6-day expiry timer is started.
-   **Advanced Employee Management Panel:** A professional, tabbed interface for Admins to manage staff, view pending invitations, and resend new, valid invitations with a single click.
-   **Dynamic Content Management Hub:** A powerful, SPA-like interface (`content_management.php`) that allows admins to browse through Courses, Subjects, Chapters, and Content Items without page reloads, using JavaScript `fetch()` to get data.
-   **Universal Content Creator (`creator.php`):** A single, "smart" page that dynamically changes its form to allow admins to add any new item to the database, from a top-level Course to a specific PDF Note. Includes secure file upload handling for PDFs.
-   **Universal Content Editor (`editor.php`):** A powerful, all-in-one editor page that:
    -   Intelligently detects if the item being edited is a video or a PDF and shows the relevant form fields.
    -   Allows admins to manage the many-to-many relationships between video lectures and their related PDF materials (linking/unlinking).

## 🛠️ Tech Stack & Dependencies
*   **Backend:** PHP
*   **Database:** MySQL / MariaDB
*   **Frontend:** HTML5, CSS3 (Flexbox, Grid, CSS Variables), vanilla JavaScript (ES6+, `fetch` API)
*   **Dependencies:**
    *   **PHPMailer:** For sending all transactional emails (OTP, password resets, invitations) via an external SMTP service.

## 🚀 How to Set Up

1.  **Database:** Create a new MySQL database and import the full SQL schema to create all tables (`users`, `plans`, `employees`, `courses`, `subjects`, `chapters`, `content_items`, `lecture_materials_map`, etc.).

2.  **Configuration:** In `/config/db_connect.php`, enter your database credentials.

3.  **Email Service (Brevo):**
    *   Sign up for a free account with an SMTP provider like **Brevo**.
    *   Verify a "Sender" email address in your Brevo account.
    *   Go to the "SMTP & API" section and get your unique SMTP credentials (Server, Port, Login, and SMTP Key).
    *   Paste these credentials into the PHPMailer configuration block in all relevant handler files (e.g., `handle_forgot_password.php`, `handle_add_employee.php`, etc.).

4.  **PHPMailer:**
    *   Download PHPMailer from GitHub.
    *   Create a `PHPMailer` folder in the project root.
    *   Copy the contents of the `src` folder (e.g., `PHPMailer.php`, `SMTP.php`) into your `PHPMailer` directory.

5.  **File Uploads:**
    *   Create an `uploads` folder in the project root directory.
    *   Ensure your web server has write permissions for this folder.

6.  **Run:** Upload all files to your web server. Navigate to `index.html` to begin.

---
*This README reflects the project status as of the implementation of the full Content Management System.*