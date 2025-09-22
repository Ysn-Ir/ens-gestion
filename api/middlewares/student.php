<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Étudiant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --border-radius: 10px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            transition: var(--transition);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: var(--transition);
            border-left: 4px solid transparent;
        }

        .menu-item:hover, .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--success);
        }

        .menu-item i {
            margin-right: 10px;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        .menu-item span {
            font-weight: 500;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: var(--transition);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .header h1 {
            font-size: 1.8rem;
            color: var(--primary);
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }

        /* Card Styles */
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 20px;
            overflow: hidden;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            padding: 15px 20px;
            background-color: var(--primary);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }

        /* Dashboard Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: 'Poppins', sans-serif;
            transition: var(--transition);
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary);
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #3aa8d0;
        }

        /* Content Sections */
        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        /* Loading Spinner */
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            border-left-color: var(--primary);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }
            .sidebar-header h2, .menu-item span {
                display: none;
            }
            .menu-item {
                justify-content: center;
            }
            .menu-item i {
                margin-right: 0;
            }
            .main-content {
                margin-left: 70px;
            }
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            .user-info {
                margin-top: 10px;
            }
        }

        /* Grade Visualization */
        .grade-visualization {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .grade-bar {
            flex: 1;
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-right: 10px;
        }

        .grade-fill {
            height: 100%;
            border-radius: 5px;
        }

        .grade-value {
            font-weight: 600;
            width: 40px;
            text-align: right;
        }

        /* Profile Image */
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid var(--primary);
            margin: 0 auto 20px;
            display: block;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }

        .tab.active {
            border-bottom: 3px solid var(--primary);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Portail Étudiant</h2>
                <p>Université Excellence</p>
            </div>
            <div class="sidebar-menu">
                <div class="menu-item active" data-target="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Tableau de bord</span>
                </div>
                <div class="menu-item" data-target="profile">
                    <i class="fas fa-user"></i>
                    <span>Profil</span>
                </div>
                <div class="menu-item" data-target="grades">
                    <i class="fas fa-chart-bar"></i>
                    <span>Notes</span>
                </div>
                <div class="menu-item" data-target="diplomas">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Diplômes</span>
                </div>
                <div class="menu-item" data-target="password">
                    <i class="fas fa-lock"></i>
                    <span>Changer le mot de passe</span>
                </div>
                <div class="menu-item" id="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Déconnexion</span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Tableau de bord étudiant</h1>
                <div class="user-info">
                    <div class="user-avatar" id="user-avatar">JD</div>
                    <div>
                        <div id="user-name">Jean Dupont</div>
                        <div id="user-id" style="font-size: 0.8rem; color: var(--gray);">ID: 12345</div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Section -->
            <div class="content-section active" id="dashboard">
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(67, 97, 238, 0.1); color: var(--primary);">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="modules-count">12</h3>
                            <p>Modules suivis</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(76, 201, 240, 0.1); color: var(--success);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="average-grade">14.5</h3>
                            <p>Moyenne générale</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(247, 37, 133, 0.1); color: var(--warning);">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="ranking">5</h3>
                            <p>Classement</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: rgba(58, 12, 163, 0.1); color: var(--secondary);">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="academic-year">2023-2024</h3>
                            <p>Année académique</p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Notes récentes</h3>
                        <a href="#" style="color: white; text-decoration: none;" id="view-all-grades">Voir tout</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="recent-grades-table">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th>Note</th>
                                        <th>Date</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Recent grades will be populated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Progression académique</h3>
                    </div>
                    <div class="card-body">
                        <div id="academic-progress">
                            <!-- Progress visualization will be added here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Section -->
            <div class="content-section" id="profile">
                <div class="card">
                    <div class="card-header">
                        <h3>Informations personnelles</h3>
                    </div>
                    <div class="card-body">
                        <div style="text-align: center;">
                            <img src="https://via.placeholder.com/150" alt="Photo de profil" class="profile-image">
                            <h3 id="profile-fullname">Jean Dupont</h3>
                            <p id="profile-id">ID: 12345</p>
                        </div>
                        <div class="form-group">
                            <label for="profile-email">Email</label>
                            <input type="email" id="profile-email" value="jean.dupont@universite.com" readonly>
                        </div>
                        <div class="form-group">
                            <label for="profile-phone">Téléphone</label>
                            <input type="tel" id="profile-phone" value="+33 1 23 45 67 89" readonly>
                        </div>
                        <div class="form-group">
                            <label for="profile-address">Adresse</label>
                            <input type="text" id="profile-address" value="123 Rue de l'Université, 75000 Paris" readonly>
                        </div>
                        <div class="form-group">
                            <label for="profile-birthdate">Date de naissance</label>
                            <input type="text" id="profile-birthdate" value="15/03/2000" readonly>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Informations académiques</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="academic-field">Filière</label>
                            <input type="text" id="academic-field" value="Informatique" readonly>
                        </div>
                        <div class="form-group">
                            <label for="academic-cycle">Cycle</label>
                            <input type="text" id="academic-cycle" value="Licence" readonly>
                        </div>
                        <div class="form-group">
                            <label for="academic-year-info">Année académique</label>
                            <input type="text" id="academic-year-info" value="2023-2024" readonly>
                        </div>
                        <div class="form-group">
                            <label for="academic-status">Statut</label>
                            <input type="text" id="academic-status" value="Inscrit" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grades Section -->
            <div class="content-section" id="grades">
                <div class="tabs">
                    <div class="tab active" data-tab="semester-grades">Notes par semestre</div>
                    <div class="tab" data-tab="annual-grades">Notes annuelles</div>
                    <div class="tab" data-tab="ranking">Classement</div>
                </div>

                <div class="tab-content">
                    <div class="tab-pane active" id="semester-grades">
                        <div class="card">
                            <div class="card-header">
                                <h3>Sélectionnez un semestre</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="semester-select">Semestre</label>
                                    <select id="semester-select">
                                        <option value="">Choisir un semestre</option>
                                        <option value="1">Semestre 1 (2023-2024)</option>
                                        <option value="2">Semestre 2 (2023-2024)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="card" id="grades-details" style="display: none;">
                            <div class="card-header">
                                <h3>Détail des notes</h3>
                            </div>
                            <div class="card-body">
                                <div id="grades-table-container">
                                    <!-- Grades table will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="annual-grades" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <h3>Notes annuelles</h3>
                            </div>
                            <div class="card-body">
                                <div id="annual-grades-container">
                                    <!-- Annual grades will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="ranking" style="display: none;">
                        <div class="card">
                            <div class="card-header">
                                <h3>Classement</h3>
                            </div>
                            <div class="card-body">
                                <div id="ranking-container">
                                    <!-- Ranking information will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diplomas Section -->
            <div class="content-section" id="diplomas">
                <div class="card">
                    <div class="card-header">
                        <h3>Diplômes obtenus</h3>
                    </div>
                    <div class="card-body">
                        <div id="diplomas-container">
                            <!-- Diplomas will be populated here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password Change Section -->
            <div class="content-section" id="password">
                <div class="card">
                    <div class="card-header">
                        <h3>Changer le mot de passe</h3>
                    </div>
                    <div class="card-body">
                        <form id="password-form">
                            <div class="form-group">
                                <label for="current-password">Mot de passe actuel</label>
                                <input type="password" id="current-password" required>
                            </div>
                            <div class="form-group">
                                <label for="new-password">Nouveau mot de passe</label>
                                <input type="password" id="new-password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm-password">Confirmer le nouveau mot de passe</label>
                                <input type="password" id="confirm-password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sample data - in a real application, this would come from API calls
        const studentData = {
            id: 12345,
            name: "Jean Dupont",
            email: "jean.dupont@universite.com",
            phone: "+33 1 23 45 67 89",
            address: "123 Rue de l'Université, 75000 Paris",
            birthdate: "15/03/2000",
            field: "Informatique",
            cycle: "Licence",
            academicYear: "2023-2024",
            status: "Inscrit",
            averageGrade: 14.5,
            ranking: 5,
            modulesCount: 12,
            recentGrades: [
                { module: "Algorithmique", grade: 16, date: "2023-10-15", status: "Validé" },
                { module: "Base de données", grade: 13.5, date: "2023-10-20", status: "Validé" },
                { module: "Réseaux", grade: 15, date: "2023-10-25", status: "Validé" },
                { module: "Programmation Web", grade: 14, date: "2023-11-05", status: "En attente" }
            ],
            semesterGrades: {
                1: [
                    { module: "Algorithmique", tp: 15, td: 14, cc: 16, exam: 17, final: 16, decision: "Validé" },
                    { module: "Base de données", tp: 12, td: 13, cc: 14, exam: 15, final: 13.5, decision: "Validé" },
                    { module: "Réseaux", tp: 16, td: 15, cc: 14, exam: 15, final: 15, decision: "Validé" }
                ],
                2: [
                    { module: "Programmation Web", tp: 14, td: 15, cc: 13, exam: 14, final: 14, decision: "En attente" },
                    { module: "Systèmes d'exploitation", tp: 13, td: 14, cc: 15, exam: 16, final: 14.5, decision: "Validé" },
                    { module: "Intelligence Artificielle", tp: 15, td: 16, cc: 14, exam: 15, final: 15, decision: "Validé" }
                ]
            },
            annualGrades: [
                { year: "2023-2024", grade: 14.5, decision: "Admis", ranking: 5, totalStudents: 120 },
                { year: "2022-2023", grade: 13.8, decision: "Admis", ranking: 8, totalStudents: 115 },
                { year: "2021-2022", grade: 12.5, decision: "Admis", ranking: 15, totalStudents: 110 }
            ],
            diplomas: [
                { name: "Baccalauréat Scientifique", year: 2020, institution: "Lycée Descartes" },
                { name: "Diplôme Universitaire de Technologie", year: 2022, institution: "IUT Paris" }
            ]
        };

        // DOM Elements
        const menuItems = document.querySelectorAll('.menu-item');
        const contentSections = document.querySelectorAll('.content-section');
        const tabs = document.querySelectorAll('.tab');
        const tabPanes = document.querySelectorAll('.tab-pane');
        const semesterSelect = document.getElementById('semester-select');
        const gradesDetails = document.getElementById('grades-details');
        const viewAllGradesBtn = document.getElementById('view-all-grades');
        const logoutBtn = document.getElementById('logout-btn');

        // Initialize the dashboard with data
        document.addEventListener('DOMContentLoaded', function() {
            // Set user info
            document.getElementById('user-avatar').textContent = getInitials(studentData.name);
            document.getElementById('user-name').textContent = studentData.name;
            document.getElementById('user-id').textContent = `ID: ${studentData.id}`;
            
            // Set profile info
            document.getElementById('profile-fullname').textContent = studentData.name;
            document.getElementById('profile-id').textContent = `ID: ${studentData.id}`;
            document.getElementById('profile-email').value = studentData.email;
            document.getElementById('profile-phone').value = studentData.phone;
            document.getElementById('profile-address').value = studentData.address;
            document.getElementById('profile-birthdate').value = studentData.birthdate;
            document.getElementById('academic-field').value = studentData.field;
            document.getElementById('academic-cycle').value = studentData.cycle;
            document.getElementById('academic-year-info').value = studentData.academicYear;
            document.getElementById('academic-status').value = studentData.status;
            
            // Set dashboard stats
            document.getElementById('modules-count').textContent = studentData.modulesCount;
            document.getElementById('average-grade').textContent = studentData.averageGrade;
            document.getElementById('ranking').textContent = studentData.ranking;
            document.getElementById('academic-year').textContent = studentData.academicYear;
            
            // Populate recent grades table
            populateRecentGrades();
            
            // Populate diplomas
            populateDiplomas();
            
            // Initialize academic progress visualization
            initializeAcademicProgress();
        });

        // Menu navigation
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                if (this.id === 'logout-btn') {
                    logout();
                    return;
                }
                
                const target = this.getAttribute('data-target');
                
                // Update active menu item
                menuItems.forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                
                // Show target section
                contentSections.forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(target).classList.add('active');
                
                // If navigating to grades section, show semester grades by default
                if (target === 'grades') {
                    showTab('semester-grades');
                }
            });
        });

        // Tab navigation for grades section
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                showTab(tabId);
            });
        });

        function showTab(tabId) {
            // Update active tab
            tabs.forEach(tab => {
                tab.classList.remove('active');
                if (tab.getAttribute('data-tab') === tabId) {
                    tab.classList.add('active');
                }
            });
            
            // Show active tab content
            tabPanes.forEach(pane => {
                pane.style.display = 'none';
                if (pane.id === tabId) {
                    pane.style.display = 'block';
                }
            });
            
            // Load data for the tab if needed
            if (tabId === 'annual-grades') {
                populateAnnualGrades();
            } else if (tabId === 'ranking') {
                populateRanking();
            }
        }

        // Semester selection for grades
        semesterSelect.addEventListener('change', function() {
            const semester = this.value;
            if (semester) {
                gradesDetails.style.display = 'block';
                populateSemesterGrades(semester);
            } else {
                gradesDetails.style.display = 'none';
            }
        });

        // View all grades button
        viewAllGradesBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Navigate to grades section
            menuItems.forEach(i => i.classList.remove('active'));
            document.querySelector('.menu-item[data-target="grades"]').classList.add('active');
            
            contentSections.forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById('grades').classList.add('active');
            
            // Show semester grades by default
            showTab('semester-grades');
        });

        // Password form submission
        document.getElementById('password-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (newPassword !== confirmPassword) {
                alert('Les mots de passe ne correspondent pas.');
                return;
            }
            
            // In a real application, you would make an API call here
            alert('Mot de passe changé avec succès!');
            this.reset();
        });

        // Helper functions
        function getInitials(name) {
            return name.split(' ').map(n => n[0]).join('').toUpperCase();
        }

        function populateRecentGrades() {
            const tbody = document.querySelector('#recent-grades-table tbody');
            tbody.innerHTML = '';
            
            studentData.recentGrades.forEach(grade => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${grade.module}</td>
                    <td>${grade.grade}</td>
                    <td>${formatDate(grade.date)}</td>
                    <td><span class="badge ${getStatusBadgeClass(grade.status)}">${grade.status}</span></td>
                `;
                tbody.appendChild(row);
            });
        }

        function populateSemesterGrades(semester) {
            const container = document.getElementById('grades-table-container');
            const grades = studentData.semesterGrades[semester];
            
            if (!grades || grades.length === 0) {
                container.innerHTML = '<p>Aucune note disponible pour ce semestre.</p>';
                return;
            }
            
            let html = `
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th>TP</th>
                                <th>TD</th>
                                <th>CC</th>
                                <th>Examen</th>
                                <th>Note finale</th>
                                <th>Décision</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            grades.forEach(grade => {
                html += `
                    <tr>
                        <td>${grade.module}</td>
                        <td>${grade.tp}</td>
                        <td>${grade.td}</td>
                        <td>${grade.cc}</td>
                        <td>${grade.exam}</td>
                        <td>${grade.final}</td>
                        <td><span class="badge ${getStatusBadgeClass(grade.decision)}">${grade.decision}</span></td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            container.innerHTML = html;
        }

        function populateAnnualGrades() {
            const container = document.getElementById('annual-grades-container');
            
            if (!studentData.annualGrades || studentData.annualGrades.length === 0) {
                container.innerHTML = '<p>Aucune note annuelle disponible.</p>';
                return;
            }
            
            let html = `
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Année académique</th>
                                <th>Note annuelle</th>
                                <th>Décision</th>
                                <th>Classement</th>
                                <th>Effectif</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            studentData.annualGrades.forEach(grade => {
                html += `
                    <tr>
                        <td>${grade.year}</td>
                        <td>${grade.grade}</td>
                        <td><span class="badge ${getStatusBadgeClass(grade.decision)}">${grade.decision}</span></td>
                        <td>${grade.ranking}</td>
                        <td>${grade.totalStudents}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            container.innerHTML = html;
        }

        function populateRanking() {
            const container = document.getElementById('ranking-container');
            const currentRanking = studentData.annualGrades[0]; // Current year
            
            if (!currentRanking) {
                container.innerHTML = '<p>Aucune information de classement disponible.</p>';
                return;
            }
            
            const percentage = ((currentRanking.totalStudents - currentRanking.ranking + 1) / currentRanking.totalStudents) * 100;
            
            container.innerHTML = `
                <div style="text-align: center; margin-bottom: 20px;">
                    <h3>Classement pour l'année ${currentRanking.year}</h3>
                    <div style="font-size: 3rem; color: var(--primary); font-weight: bold; margin: 20px 0;">
                        ${currentRanking.ranking}<sup>e</sup> / ${currentRanking.totalStudents}
                    </div>
                    <div style="font-size: 1.2rem; color: var(--gray);">
                        Vous êtes dans le top ${percentage.toFixed(1)}% de votre promotion
                    </div>
                </div>
                
                <div class="grade-visualization">
                    <div class="grade-bar">
                        <div class="grade-fill" style="width: ${percentage}%; background-color: var(--primary);"></div>
                    </div>
                    <div class="grade-value">${percentage.toFixed(1)}%</div>
                </div>
                
                <div style="margin-top: 20px;">
                    <p><strong>Note annuelle:</strong> ${currentRanking.grade}</p>
                    <p><strong>Décision:</strong> <span class="badge ${getStatusBadgeClass(currentRanking.decision)}">${currentRanking.decision}</span></p>
                </div>
            `;
        }

        function populateDiplomas() {
            const container = document.getElementById('diplomas-container');
            
            if (!studentData.diplomas || studentData.diplomas.length === 0) {
                container.innerHTML = '<p>Aucun diplôme obtenu.</p>';
                return;
            }
            
            let html = '';
            
            studentData.diplomas.forEach(diploma => {
                html += `
                    <div class="card" style="margin-bottom: 15px;">
                        <div class="card-body">
                            <h4>${diploma.name}</h4>
                            <p><strong>Année d'obtention:</strong> ${diploma.year}</p>
                            <p><strong>Établissement:</strong> ${diploma.institution}</p>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function initializeAcademicProgress() {
            const container = document.getElementById('academic-progress');
            
            // This would normally come from API data
            const progressData = [
                { year: "2021-2022", average: 12.5, credits: 60 },
                { year: "2022-2023", average: 13.8, credits: 120 },
                { year: "2023-2024", average: 14.5, credits: 180 }
            ];
            
            let html = '';
            
            progressData.forEach((item, index) => {
                const isCurrent = index === progressData.length - 1;
                const progressPercentage = (item.credits / 180) * 100;
                
                html += `
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><strong>${item.year}</strong> ${isCurrent ? '(en cours)' : ''}</span>
                            <span>Moyenne: ${item.average} | Crédits: ${item.credits}/180</span>
                        </div>
                        <div class="grade-bar">
                            <div class="grade-fill" style="width: ${progressPercentage}%; background-color: ${isCurrent ? 'var(--primary)' : 'var(--success)'};"></div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR');
        }

        function getStatusBadgeClass(status) {
            switch(status.toLowerCase()) {
                case 'validé':
                case 'admis':
                    return 'badge-success';
                case 'en attente':
                    return 'badge-warning';
                default:
                    return 'badge-danger';
            }
        }

        function logout() {
            if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                // In a real application, you would make an API call to logout
                alert('Déconnexion réussie. Redirection...');
                // Redirect to login page
                window.location.href = 'login.html';
            }
        }
    </script>
</body>
</html>