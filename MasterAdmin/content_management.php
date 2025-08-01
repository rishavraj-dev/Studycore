<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'master_admin'])) {
    header('Location: ../Login/login.php?error=' . urlencode('Access Denied.'));
    exit();
}
$admin_name = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Management - StudyCore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_style.css">
    <style>
        .view-container { display: none; }
        .view-container.active { display: block; animation: fadeIn .3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        .breadcrumb { margin-bottom: 1.5rem; font-weight: 500; font-size: 1.1rem; }
        .breadcrumb a { color: var(--primary-color); text-decoration: none; transition: opacity .2s; }
        .breadcrumb a:hover { opacity: .8; }
        .breadcrumb span { color: var(--text-light); }
        .table-toolbar { margin-bottom: 1rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="navbar-brand">StudyCore Admin</a>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle dark mode">☀️</button>
            <a href="../Login/logout.php" class="btn" style="background-color: var(--danger-color); color: white;">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h1>Content Management System</h1>
            <p><a href="dashboard.php">← Back to Dashboard</a></p>
        </div>
        <div id="breadcrumb" class="breadcrumb"></div>

        <!-- View: Courses -->
        <div id="coursesView" class="view-container active">
            <div class="content-card">
                <div class="table-toolbar">
                    <h2>All Courses</h2>
                    <a href="creator.php?type=course" class="btn btn-success">Add Course</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Course Name</th><th>Actions</th></tr></thead>
                        <tbody id="coursesTableBody"><!-- AJAX loaded --></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- View: Subjects -->
        <div id="subjectsView" class="view-container">
            <div class="content-card">
                <div class="table-toolbar">
                    <h2 id="subjectsHeader"></h2>
                    <a id="addSubjectBtn" href="#" class="btn btn-success">Add Subject</a>
                </div>
                <input type="search" class="search-bar" id="subjectsSearch" placeholder="Search subjects...">
                <div class="table-responsive">
                    <table class="table-fixed">
                        <thead><tr><th>Subject Name</th><th>Actions</th></tr></thead>
                        <tbody id="subjectsTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- View: Chapters -->
        <div id="chaptersView" class="view-container">
            <div class="content-card">
                <div class="table-toolbar">
                    <h2 id="chaptersHeader"></h2>
                    <a id="addChapterBtn" href="#" class="btn btn-success">Add Chapter</a>
                </div>
                <input type="search" class="search-bar" id="chaptersSearch" placeholder="Search chapters...">
                <div class="table-responsive">
                    <table class="table-fixed">
                        <thead><tr><th>Chapter Name</th><th>Actions</th></tr></thead>
                        <tbody id="chaptersTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- View: Content Items -->
        <div id="contentItemsView" class="view-container">
            <div class="content-card">
                <div class="table-toolbar">
                    <h2 id="contentItemsHeader"></h2>
                    <a id="addContentItemBtn" href="#" class="btn btn-success">Add Content</a>
                </div>
                <input type="search" class="search-bar" id="contentItemsSearch" placeholder="Search content...">
                <div class="table-responsive">
                    <table class="table-fixed">
                        <thead><tr><th>Title</th><th>Category</th><th>Actions</th></tr></thead>
                        <tbody id="contentItemsTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dark Mode
        const toggleButton = document.getElementById('darkModeToggle');
        if (toggleButton) {
            const applyTheme = (theme) => {
                document.body.classList.toggle('dark-mode', theme === 'dark');
                toggleButton.textContent = theme === 'dark' ? '🌙' : '☀️';
            };
            const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            applyTheme(savedTheme);
            toggleButton.addEventListener('click', () => {
                const newTheme = document.body.classList.contains('dark-mode') ? 'light' : 'dark';
                localStorage.setItem('theme', newTheme);
                applyTheme(newTheme);
            });
        }

        // SPA View State
        const views = {
            courses: document.getElementById('coursesView'),
            subjects: document.getElementById('subjectsView'),
            chapters: document.getElementById('chaptersView'),
            contentItems: document.getElementById('contentItemsView')
        };
        const breadcrumbContainer = document.getElementById('breadcrumb');
        let breadcrumbTrail = [];

        function showView(viewName) {
            Object.values(views).forEach(view => {
                view.style.display = 'none';
                view.classList.remove('active');
            });
            views[viewName].style.display = 'block';
            views[viewName].classList.add('active');
        }

        function updateBreadcrumb() {
            breadcrumbContainer.innerHTML = '';
            breadcrumbTrail.forEach((item, index) => {
                if (index > 0) breadcrumbContainer.innerHTML += ' <span style="margin:0 0.5rem;">></span> ';
                if (item.action) {
                    const link = document.createElement('a');
                    link.href = '#';
                    link.textContent = item.name;
                    link.onclick = (e) => { e.preventDefault(); item.action(); };
                    breadcrumbContainer.appendChild(link);
                } else {
                    breadcrumbContainer.innerHTML += `<span>${item.name}</span>`;
                }
            });
        }

        async function fetchData(url, tableBody, rowBuilder) {
            tableBody.innerHTML = `<tr><td colspan="100%">Loading...</td></tr>`;
            try {
                const response = await fetch(url);
                const data = await response.json();
                if (data.error) throw new Error(data.error || 'Unknown error');
                tableBody.innerHTML = '';
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(item => rowBuilder(item, tableBody));
                } else {
                    tableBody.innerHTML = `<tr><td colspan="100%">No items found.</td></tr>`;
                }
            } catch (error) {
                tableBody.innerHTML = `<tr><td colspan="100%">${error.message}</td></tr>`;
            }
        }

        // Loaders
        window.loadCourses = function() {
            fetchData('get_cms_data.php?action=get_courses', document.getElementById('coursesTableBody'), (item, tableBody) => {
                const row = tableBody.insertRow();
                row.innerHTML = `
                    <td>${item.course_name}</td>
                    <td><button class="btn btn-primary" onclick="loadSubjects(${item.id}, '${item.course_name.replace(/'/g,"\\'")}')">Manage Subjects</button></td>
                `;
            });
            breadcrumbTrail = [{ name: 'Courses', action: () => loadCourses() }];
            updateBreadcrumb();
            showView('courses');
        };

        window.loadSubjects = function(courseId, courseName) {
            document.getElementById('subjectsHeader').textContent = `Subjects in: ${courseName}`;
            document.getElementById('addSubjectBtn').href = `creator.php?type=subject&course_id=${courseId}`;
            fetchData(`get_cms_data.php?action=get_subjects&course_id=${courseId}`, document.getElementById('subjectsTableBody'), (item, tableBody) => {
                const row = tableBody.insertRow();
                row.innerHTML = `<td>${item.subject_name}</td>
                    <td><button class="btn btn-primary" onclick="loadChapters(${courseId}, '${courseName.replace(/'/g,"\\'")}', ${item.id}, '${item.subject_name.replace(/'/g,"\\'")}')">Manage Chapters</button></td>`;
            });
            breadcrumbTrail = [
                { name: 'Courses', action: () => loadCourses() },
                { name: courseName, action: () => loadSubjects(courseId, courseName) }
            ];
            updateBreadcrumb();
            showView('subjects');
        };

        window.loadChapters = function(courseId, courseName, subjectId, subjectName) {
            document.getElementById('chaptersHeader').textContent = `Chapters in: ${subjectName}`;
            document.getElementById('addChapterBtn').href = `creator.php?type=chapter&subject_id=${subjectId}`;
            fetchData(`get_cms_data.php?action=get_chapters&subject_id=${subjectId}`, document.getElementById('chaptersTableBody'), (item, tableBody) => {
                const row = tableBody.insertRow();
                row.innerHTML = `<td>${item.chapter_name}</td>
                    <td><button class="btn btn-primary" onclick="loadContentItems(${courseId}, '${courseName.replace(/'/g,"\\'")}', ${subjectId}, '${subjectName.replace(/'/g,"\\'")}', ${item.id}, '${item.chapter_name.replace(/'/g,"\\'")}')">Manage Content</button></td>`;
            });
            breadcrumbTrail = [
                { name: 'Courses', action: () => loadCourses() },
                { name: courseName, action: () => loadSubjects(courseId, courseName) },
                { name: subjectName, action: () => loadChapters(courseId, courseName, subjectId, subjectName) }
            ];
            updateBreadcrumb();
            showView('chapters');
        };

       // --- THIS IS THE CORRECTED FUNCTION ---

window.loadContentItems = function(courseId, courseName, subjectId, subjectName, chapterId, chapterName) {
    document.getElementById('contentItemsHeader').textContent = `Content in: ${chapterName}`;
    document.getElementById('addContentItemBtn').href = `creator.php?type=content_item&chapter_id=${chapterId}`;
    
    fetchData(`get_cms_data.php?action=get_content_items&chapter_id=${chapterId}`, document.getElementById('contentItemsTableBody'), (item, tableBody) => {
        const row = tableBody.insertRow();
        
        // ** THE FIX IS HERE **
        // The href attribute now correctly points to editor.php with the item's ID.
        row.innerHTML = `
            <td>${item.title}</td>
            <td>${item.content_category}</td>
            <td>
                <a href="editor.php?type=content_item&id=${item.id}" class="btn btn-primary" style="font-size:0.8rem;">Edit</a>
            </td>
        `;
    });
    
    breadcrumbTrail = [
        { name: 'Courses', action: () => loadCourses() },
        { name: courseName, action: () => loadSubjects(courseId, courseName) },
        { name: subjectName, action: () => loadChapters(courseId, courseName, subjectId, subjectName) },
        { name: chapterName, action: () => loadContentItems(courseId, courseName, subjectId, subjectName, chapterId, chapterName) }
    ];
    updateBreadcrumb();
    showView('contentItems');
};


        // --- Search Filters ---
        function setupSearch(inputId, tableBodyId) {
            const searchInput = document.getElementById(inputId);
            const tableBody = document.getElementById(tableBodyId);
            if (!searchInput || !tableBody) return;
            searchInput.addEventListener('keyup', () => {
                const filter = searchInput.value.toLowerCase();
                const rows = tableBody.getElementsByTagName('tr');
                for (const row of rows) {
                    if (row.getElementsByTagName('td').length > 0) {
                        row.style.display = (row.textContent || row.innerText).toLowerCase().includes(filter) ? "" : "none";
                    }
                }
            });
        }
        setupSearch('subjectsSearch', 'subjectsTableBody');
        setupSearch('chaptersSearch', 'chaptersTableBody');
        setupSearch('contentItemsSearch', 'contentItemsTableBody');

        // Initial Load
        loadCourses();
    });
    </script>
</body>
</html>
