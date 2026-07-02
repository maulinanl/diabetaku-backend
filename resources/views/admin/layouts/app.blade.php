<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin diabetAku')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #3A8DDE;
            --primary-dark: #2476C7;
            --primary-soft: #EAF4FF;
            --bg: #F5F9FC;
            --white: #FFFFFF;
            --dark: #1F2937;
            --dark-2: #374151;
            --gray: #6B7280;
            --gray-2: #9CA3AF;
            --line: #DDE7F3;
            --red: #EF4444;
            --green: #10B981;
            --orange: #F59E0B;
            --purple: #8B5CF6;
            --shadow: 0 14px 36px rgba(31, 41, 55, .06);
        }

        * {
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--dark);
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        .admin-wrapper {
            min-height: 100vh;
        }

        .topbar {
            height: 78px;
            background: rgba(234, 244, 255, .96);
            border-bottom: 1px solid rgba(58, 141, 222, .16);
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

        .topbar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-logo img {
            height: 48px;
            object-fit: contain;
            display: block;
        }

        .topbar-logo-text {
            color: var(--primary);
            font-size: 27px;
            font-weight: 800;
            line-height: 1;
        }

        .topbar-logo-text span {
            display: block;
            font-size: 10px;
            font-weight: 600;
            margin-top: 4px;
            color: var(--gray);
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 220px;
            padding: 9px 16px 9px 10px;
            border: 1.5px solid rgba(58, 141, 222, .35);
            border-radius: 999px;
            background: rgba(255, 255, 255, .62);
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            display: grid;
            place-items: center;
            font-weight: 800;
            flex-shrink: 0;
        }

        .admin-info {
            line-height: 1.15;
            min-width: 0;
        }

        .admin-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }

        .admin-role {
            margin-top: 4px;
            font-size: 11px;
            color: var(--gray);
            font-weight: 600;
        }

        .sidebar {
            width: 270px;
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            color: var(--white);
            position: fixed;
            top: 78px;
            bottom: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-top-right-radius: 28px;
            padding-top: 38px;
            z-index: 10;
        }

        .sidebar::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 118px;
            height: 38px;
            background: rgba(255, 255, 255, .13);
            border-bottom-left-radius: 38px;
        }

        .nav-menu {
            position: relative;
            z-index: 1;
            padding-left: 30px;
            padding-right: 0;
        }

        .nav-link {
            width: 100%;
            min-height: 54px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: rgba(255, 255, 255, .9);
            text-decoration: none;
            padding: 15px 24px 15px 24px;
            margin-bottom: 8px;
            border-radius: 999px 0 0 999px;
            font-size: 14px;
            font-weight: 700;
            transition: .18s ease;
        }

        .nav-link svg {
            width: 20px;
            height: 20px;
            stroke-width: 2.2;
            flex-shrink: 0;
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
            background: rgba(255, 255, 255, .14);
            color: white;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: .18s;
        }

        .logout-button:hover {
            background: var(--primary-soft);
            color: var(--primary);
        }

        .main {
            margin-left: 270px;
            width: calc(100% - 270px);
            min-height: 100vh;
            padding-top: 78px;
            background: var(--bg);
        }

        .content {
            padding: 28px;
        }

        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
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
            color: var(--gray);
            font-size: 13px;
            line-height: 1.6;
        }

        .card {
            background: white;
            border: 1px solid rgba(221, 231, 243, .95);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow);
        }

        .content > .card + .card,
        .content > .grid + .card {
            margin-top: 18px;
        }

        .grid .card {
            margin-top: 0;
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
            color: var(--gray);
            font-size: 12px;
            line-height: 1.5;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 18px;
            align-items: stretch;
        }

        .dashboard-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            min-height: 150px;
            height: 100%;
        }

        .stat-card::after {
            content: "";
            position: absolute;
            right: -26px;
            top: -26px;
            width: 88px;
            height: 88px;
            border-radius: 999px;
            background: rgba(58, 141, 222, .11);
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: var(--primary-soft);
            color: var(--primary);
            margin-bottom: 14px;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 28px;
            color: var(--dark);
            font-weight: 800;
            line-height: 1.1;
        }

        .stat-card p {
            margin: 7px 0 0;
            color: var(--gray);
            font-size: 13px;
            font-weight: 600;
            line-height: 1.4;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 14px;
            border: 1px solid var(--line);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            text-align: left;
            font-size: 12px;
            color: var(--primary-dark);
            background: var(--primary-soft);
            padding: 13px 14px;
            white-space: nowrap;
            font-weight: 800;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid var(--line);
            font-size: 13px;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 190px;
        }

        .mini-avatar {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            display: grid;
            place-items: center;
            font-weight: 800;
            flex-shrink: 0;
        }

        .cell-title {
            font-weight: 800;
            color: var(--dark);
        }

        .cell-subtitle {
            margin-top: 3px;
            font-size: 11px;
            color: var(--gray);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .badge-green {
            background: #EAFBF3;
            color: var(--green);
        }

        .badge-orange {
            background: #FFF4DA;
            color: var(--orange);
        }

        .badge-red {
            background: #FFEAEA;
            color: var(--red);
        }

        .badge-blue {
            background: var(--primary-soft);
            color: var(--primary);
        }

        .badge-purple {
            background: #F3EFFF;
            color: var(--purple);
        }

        .btn {
            border: none;
            border-radius: 12px;
            padding: 9px 13px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 36px;
            transition: .16s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-danger {
            background: var(--red);
            color: white;
        }

        .btn-outline {
            background: white;
            color: var(--primary);
            border: 1px solid rgba(58, 141, 222, .35);
        }

        .btn-soft {
            background: var(--primary-soft);
            color: var(--primary);
        }

        .btn-soft-warning {
            background: #FFF4DA;
            color: #B7791F;
            border: 1px solid rgba(245, 158, 11, .22);
        }

        .btn-soft-warning:hover {
            background: #FFEBC2;
        }

        .btn-sm {
            min-height: 32px;
            padding: 7px 11px;
            font-size: 11px;
            border-radius: 10px;
        }

        .action-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .alert {
            padding: 13px 15px;
            border-radius: 14px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert-success {
            background: #EAFBF3;
            color: #087D5B;
            border: 1px solid rgba(16, 185, 129, .18);
        }

        .alert-error {
            background: #FFEAEA;
            color: #B91C1C;
            border: 1px solid rgba(239, 68, 68, .18);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            color: var(--dark-2);
            font-size: 12px;
            font-weight: 800;
        }

        .form-control {
            width: 100%;
            min-height: 42px;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #FBFDFF;
            color: var(--dark);
            outline: none;
            font-size: 13px;
            transition: .16s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(58, 141, 222, .1);
        }

        .filter-form {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) 190px 190px auto;
            gap: 10px;
            align-items: end;
        }

        .empty-state {
            padding: 34px 20px;
            text-align: center;
            color: var(--gray);
            font-size: 13px;
        }

        .empty-state strong {
            display: block;
            color: var(--dark);
            font-size: 15px;
            margin-bottom: 6px;
        }

        .menu-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .inline-form {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .stacked-action {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
            min-width: 150px;
        }

        .text-muted {
            color: var(--gray);
        }

        .nowrap {
            white-space: nowrap;
        }

        .btn-reset-doctor {
            background: #FFF8E8;
            color: #B7791F;
            border: 1px solid #F1D08A;
            min-height: 34px;
            padding: 8px 14px;
            font-size: 11px;
            font-weight: 800;
            border-radius: 12px;
            white-space: nowrap;
            box-shadow: 0 6px 14px rgba(245, 158, 11, 0.10);
        }

        .btn-reset-doctor:hover {
            background: #FFEFC7;
            color: #9A670F;
            transform: translateY(-1px);
        }

        .doctor-verification-td {
            position: relative;
            vertical-align: middle;
            min-width: 170px;
        }

        .doctor-reset-form {
            position: absolute;
            left: 14px;
            top: calc(50% + 18px);
            margin: 0;
        }

        .btn-reset-doctor {
            background: #FFF8E8;
            color: #B7791F;
            border: 1px solid #F1D08A;
            min-height: 34px;
            padding: 8px 14px;
            font-size: 11px;
            font-weight: 800;
            border-radius: 12px;
            white-space: nowrap;
            box-shadow: 0 6px 14px rgba(245, 158, 11, 0.10);
        }

        .btn-reset-doctor:hover {
            background: #FFEFC7;
            color: #9A670F;
            transform: translateY(-1px);
        }

        .btn-reset-doctor svg {
            flex-shrink: 0;
        }

        @media (max-width: 1100px) {
            .grid,
            .dashboard-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filter-form {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 230px;
            }

            .main {
                margin-left: 230px;
                width: calc(100% - 230px);
            }
        }

        @media (max-width: 768px) {
            .topbar {
                height: auto;
                padding: 16px;
                gap: 14px;
                flex-direction: column;
                align-items: flex-start;
                position: relative;
            }

            .topbar-actions {
                width: 100%;
            }

            .admin-profile {
                width: 100%;
                min-width: 0;
            }

            .sidebar {
                position: relative;
                top: 0;
                width: 100%;
                height: auto;
                padding-top: 32px;
                border-top-right-radius: 0;
            }

            .nav-menu {
                padding-left: 16px;
                padding-right: 16px;
            }

            .nav-link {
                border-radius: 999px;
            }

            .main {
                margin-left: 0;
                width: 100%;
                padding-top: 0;
            }

            .content {
                padding: 18px;
            }

            .page-header,
            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .grid,
            .dashboard-grid,
            .form-grid,
            .filter-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="admin-wrapper">

        <header class="topbar">
            <div class="topbar-logo">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Logo diabetAku"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">

                <div class="topbar-logo-text" style="display: none;">
                    diabetAku
                    <span>Diabetes Self Management System</span>
                </div>
            </div>

            <div class="topbar-actions">
                <div class="admin-profile">
                    <div class="admin-avatar">
                        {{ strtoupper(substr(session('admin_name', 'A'), 0, 1)) }}
                    </div>

                    <div class="admin-info">
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
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('admin.web.doctors.pending') }}"
                    class="nav-link {{ request()->routeIs('admin.web.doctors.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 3l8 4-8 4-8-4 8-4z"></path>
                        <path d="M4 11l8 4 8-4"></path>
                        <path d="M4 16l8 4 8-4"></path>
                    </svg>
                    <span>Verifikasi Dokter</span>
                </a>

                <a href="{{ route('admin.web.users.index') }}"
                    class="nav-link {{ request()->routeIs('admin.web.users.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M16 11a4 4 0 10-8 0"></path>
                        <path d="M4 21a8 8 0 0116 0"></path>
                    </svg>
                    <span>Manajemen User</span>
                </a>

                <a href="{{ route('admin.web.master.index', 'specializations') }}"
                    class="nav-link {{ request()->routeIs('admin.web.master.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="5" y="4" width="14" height="16" rx="2"></rect>
                        <path d="M9 8h6"></path>
                        <path d="M9 12h6"></path>
                        <path d="M9 16h4"></path>
                    </svg>
                    <span>Data Master</span>
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
                            <p class="page-subtitle">@yield('subtitle')</p>
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
