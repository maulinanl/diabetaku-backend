<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @yield('title', 'Admin diabetAku')
    </title>
    <link rel="icon" type="image/png"
        href="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/logo.png'))) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {

            --primary: #3A86D1;
            --primary-dark: #2F73B7;
            --primary-soft: #EFF6FF;
            --primary-soft-2: #DCEEFF;
            --bg: #F8FBFF;
            --white: #FFFFFF;
            --dark: #3A3A3C;
            --dark-2: #6B7588;
            --line: #DDE5E9;
            --green: #10B981;
            --green-soft: #EAFBF3;
            --orange: #F59E0B;
            --orange-soft: #FFF8E8;
            --red: #FF3B3B;
            --red-soft: #FEF2F2;
            --radius-card: 22px;
            --radius-control: 14px;
            --shadow-soft:
                0 14px 36px rgba(58, 134, 209, .08);
        }

        * {
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--dark);
            font-size: 14px;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        .admin-wrapper {
            min-height: 100vh;
            background:
                radial-gradient(circle at 88% 4%,
                    rgba(220, 238, 255, .8) 0,
                    rgba(220, 238, 255, 0) 250px),
                var(--bg);
        }

        .topbar {
            height: 78px;
            background: rgba(248, 251, 255, .92);
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 34px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 20;
            backdrop-filter: blur(12px);
        }

        .topbar-logo img {
            height: 44px;
            width: auto;
            display: block;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 14px 8px 8px;
            border: 1px solid var(--primary-soft-2);
            border-radius: 999px;
            background: white;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .admin-name {
            font-size: 13px;
            font-weight: 700;
        }

        .admin-role {
            font-size: 11px;
            color: var(--dark-2);
        }

        .sidebar {
            width: 270px;
            background: var(--primary);
            color: white;
            position: fixed;
            top: 78px;
            bottom: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            padding-top: 38px;
            border-top-right-radius: 34px;
            box-shadow:
                12px 0 34px rgba(58, 134, 209, .13);
        }

        .nav-menu {
            padding-left: 30px;
        }

        .nav-link {
            width: 100%;
            min-height: 56px;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 24px;
            margin-bottom: 8px;
            border-radius: 999px 0 0 999px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
        }

        .nav-link svg {
            width: 20px;
            height: 20px;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--primary-soft);
            color: var(--primary);
        }

        .logout-area {
            margin-top: auto;
            padding: 0 18px 24px;
        }

        .logout-button {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 15px 18px;
            border: none;
            border-radius: 999px;
            background: rgba(255, 255, 255, .16);
            color: white;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .logout-button:hover {
            background: white;
            color: var(--primary);
        }

        .main {
            margin-left: 270px;
            width: calc(100% - 270px);
            min-height: 100vh;
            padding-top: 78px;
        }

        .content {
            padding: 28px;
            max-width: 1480px;
        }

        .page-header {
            margin-bottom: 20px;
        }

        .page-title {
            margin: 0;
            color: var(--dark);
            font-size: 24px;
            font-weight: 800;
        }

        .page-subtitle {
            margin: 7px 0 0;
            color: var(--dark-2);
            font-size: 14px;
        }

        .card {
            background: white;
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 22px;
            margin-bottom: 22px;
            box-shadow: var(--shadow-soft);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .card-title {
            margin: 0;
            color: var(--primary);
            font-size: 16px;
            font-weight: 800;
        }

        .card-desc {
            margin: 5px 0 0;
            color: var(--dark-2);
            font-size: 12px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 18px;
        }

        .dashboard-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            min-height: 150px;
        }

        .stat-card::after {
            content: "";
            position: absolute;
            right: -26px;
            top: -26px;
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: rgba(58, 134, 209, .09);
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: var(--primary-soft);
            color: var(--primary);
            margin-bottom: 18px;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 28px;
            line-height: 1.1;
            font-weight: 800;
        }

        .stat-card p {
            margin: 7px 0 0;
            color: var(--dark-2);
            font-size: 14px;
            font-weight: 600;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 18px;
            border: 1px solid var(--line);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            font-size: 12px;
            color: var(--primary);
            background: var(--primary-soft);
            padding: 14px 16px;
            font-weight: 800;
            white-space: nowrap;
        }

        td {
            padding: 13px 16px;
            border-bottom: 1px solid var(--line);
            font-size: 13px;
            font-weight: 400;
            color: #4B5563;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #FAFCFF;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .mini-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--primary-soft);
            color: var(--primary);
            display: grid;
            place-items: center;
            font-weight: 800;
        }

        .cell-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
        }

        .cell-subtitle {
            margin-top: 3px;
            font-size: 12px;
            color: #7A8494;
            font-weight: 400;
        }

        .status-text {
            font-size: 13px;
            font-weight: 400;
            color: var(--dark);
        }

        .status-success {
            color: var(--primary);
            font-weight: 500;
        }

        .status-warning {
            color: var(--red);
            font-weight: 500;
        }

        .status-danger {
            color: var(--red);
            font-weight: 500;
        }

        .btn {
            border: none;
            border-radius: var(--radius-control);
            padding: 10px 16px;
            min-height: 42px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-outline {
            background: white;
            color: var(--primary);
            border: 1px solid var(--primary-soft-2);
        }

        .btn-danger {
            background: var(--red);
            color: white;
        }

        .btn-master-action {
            height: 38px;
            min-height: 38px;
            padding: 0 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .btn-master-edit {
            background: var(--primary);
            color: white;
        }

        .btn-master-edit:hover {
            background: var(--primary-dark);
        }

        .btn-master-delete {
            background: var(--red);
            color: white;
        }

        .btn-master-delete:hover {
            background: #dc2626;
        }

        .btn-action {
            min-height: 34px;
            height: 34px;
            padding: 0 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
        }

        .btn-edit {
            background: var(--primary);
            color: white;
        }

        .btn-edit:hover {
            background: var(--primary-dark);
        }

        .btn-delete {
            background: var(--red);
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .action-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
        }


        .action-form {
            margin: 0;
            display: flex;
            align-items: center;
        }

        .form-control {
            width: 100%;
            min-height: 46px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: var(--radius-control);
            background: white;
            color: var(--dark);
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(58, 134, 209, .10);
        }

        .inline-form {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .filter-form {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) 190px 190px auto;
            gap: 12px;
            align-items: end;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 16px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert-success {
            background: var(--green-soft);
            color: #087D5B;
        }

        .alert-error {
            background: var(--red-soft);
            color: #B91C1C;
        }

        .empty-state {
            padding: 34px 20px;
            text-align: center;
            color: var(--dark-2);
        }

        .text-muted {
            color: var(--dark-2);
        }

        @media(max-width:1200px) {

            .grid,
            .dashboard-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filter-form {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media(max-width:900px) {
            .sidebar {
                width: 230px;
            }

            .main {
                margin-left: 230px;
                width: calc(100% - 230px);
            }
        }

        @media(max-width:768px) {
            .topbar {
                position: relative;
            }

            .sidebar {
                position: relative;
                top: 0;
                width: 100%;
                height: auto;
                border-radius: 0;
            }

            .main {
                margin-left: 0;
                width: 100%;
                padding-top: 0;
            }

            .content {
                padding: 18px;
            }

            .grid,
            .dashboard-grid,
            .filter-form {
                grid-template-columns: 1fr;
            }
        }

        .btn-master-edit,
        .btn-master-delete {
            min-height: 38px;
            padding: 0 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .btn-master-edit {
            background: #3A86D1;
            color: white;
        }

        .btn-master-edit:hover {
            background: #2F73B7;
        }

        .btn-master-delete {
            background: #FF3B3B;
            color: white;
        }

        .btn-master-delete:hover {
            background: #dc2626;
        }
    </style>
</head>

<body>
    <div class="admin-wrapper">
        <header class="topbar">
            <div class="topbar-logo">
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/images/logo.png'))) }}"
                    alt="Logo diabetAku">
            </div>
            <div class="topbar-actions">
                <div class="admin-profile">
                    <div class="admin-avatar">
                        {{ strtoupper(substr(session('admin_name', 'A'), 0, 1)) }}
                    </div>
                    <div>
                        <div class="admin-name">
                            {{ session('admin_name', 'Administrator') }}
                        </div>
                        <div class="admin-role">
                            Administrator Sistem
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <aside class="sidebar">
            <nav class="nav-menu">
                <a href="{{ route('admin.web.dashboard') }}"
                    class="nav-link {{ request()->routeIs('admin.web.dashboard') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M3 11L12 4l9 7"></path>
                        <path d="M5 10v10h5v-6h4v6h5V10"></path>
                    </svg>
                    <span>
                        Dashboard
                    </span>
                </a>
                <a href="{{ route('admin.web.doctors.pending') }}"
                    class="nav-link {{ request()->routeIs('admin.web.doctors.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3l8 4-8 4-8-4 8-4z"></path>
                        <path d="M4 11l8 4 8-4"></path>
                        <path d="M4 16l8 4 8-4"></path>
                    </svg>
                    <span>
                        Verifikasi Dokter
                    </span>
                </a>
                <a href="{{ route('admin.web.users.index') }}"
                    class="nav-link {{ request()->routeIs('admin.web.users.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M16 11a4 4 0 10-8 0"></path>
                        <path d="M4 21a8 8 0 0116 0"></path>
                    </svg>
                    <span>
                        Manajemen User
                    </span>
                </a>
                <a href="{{ route('admin.web.master.index', 'specializations') }}"
                    class="nav-link {{ request()->routeIs('admin.web.master.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="5" y="4" width="14" height="16" rx="2"></rect>
                        <path d="M9 8h6"></path>
                        <path d="M9 12h6"></path>
                        <path d="M9 16h4"></path>
                    </svg>
                    <span>
                        Data Master
                    </span>
                </a>
            </nav>

            <div class="logout-area">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="logout-button">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M10 17l5-5-5-5"></path>
                            <path d="M15 12H3"></path>
                            <path d="M21 4v16"></path>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="main">
            <section class="content">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">
                            @yield('title', 'Dashboard Admin')
                        </h1>
                        @hasSection('subtitle')
                            <p class="page-subtitle">
                                @yield('subtitle')
                            </p>
                        @endif
                    </div>
                </div>
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-error">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error">
                        {{ $errors->first() }}
                    </div>
                @endif

                @yield('content')

            </section>
        </main>
    </div>
</body>

</html>
