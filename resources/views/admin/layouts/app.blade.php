<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin diabetAku')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/logo.png') }}">

    <style>
        :root {
            --primary: #3A8DDE;
            --primary-dark: #2476C7;
            --bg: #F5F8FC;
            --white: #FFFFFF;
            --dark: #1F2937;
            --gray: #6B7280;
            --light: #DDE7F3;
            --soft-blue: #EAF4FF;
            --red: #EF4444;
            --green: #10B981;
            --orange: #F59E0B;
        }

        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--dark);
        }

        .admin-wrapper {
            min-height: 100vh;
        }

        .topbar {
            height: 78px;
            background: var(--soft-blue);
            border-bottom: 1px solid rgba(58, 141, 222, .18);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 38px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 20;
        }

        .topbar-logo {
            display: flex;
            align-items: center;
        }

        .topbar-logo img {
            height: 48px;
            object-fit: contain;
            display: block;
        }

        .topbar-logo-text {
            color: var(--primary);
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
        }

        .topbar-logo-text span {
            display: block;
            font-size: 10px;
            font-weight: 600;
            margin-top: 3px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 205px;
            padding: 9px 16px 9px 10px;
            border: 1.5px solid rgba(58, 141, 222, .45);
            border-radius: 999px;
            background: rgba(255, 255, 255, .24);
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            flex-shrink: 0;
        }

        .admin-info {
            line-height: 1.15;
        }

        .admin-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
        }

        .admin-role {
            margin-top: 3px;
            font-size: 11px;
            color: var(--gray);
            font-weight: 500;
        }

        .admin-arrow {
            margin-left: auto;
            color: var(--primary);
            font-size: 22px;
            font-weight: 700;
        }

        .sidebar {
            width: 270px;
            background: var(--primary);
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
            background: var(--primary-dark);
            border-bottom-left-radius: 38px;
        }

        .nav-menu {
            position: relative;
            z-index: 1;
            padding-left: 36px;
            padding-right: 0;
        }

        .nav-link {
            width: 100%;
            min-height: 54px;
            display: flex;
            align-items: center;
            gap: 16px;
            color: rgba(255, 255, 255, .92);
            text-decoration: none;
            padding: 15px 24px 15px 26px;
            margin-bottom: 8px;
            border-radius: 999px 0 0 999px;
            font-size: 15px;
            font-weight: 600;
            transition: .2s ease;
        }

        .nav-link svg {
            width: 20px;
            height: 20px;
            stroke-width: 2.2;
            flex-shrink: 0;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--soft-blue);
            color: var(--primary);
        }

        .nav-link.active svg,
        .nav-link:hover svg {
            stroke: var(--primary);
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
            font-weight: 600;
            cursor: pointer;
            transition: .2s;
        }

        .logout-button:hover {
            background: var(--soft-blue);
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
            padding: 24px;
        }

        .page-title {
            margin: 0 0 18px;
            color: var(--primary);
            font-size: 22px;
            font-weight: 700;
        }

        .card {
            background: white;
            border: 1px solid var(--light);
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .04);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 18px;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 26px;
            color: var(--primary);
        }

        .stat-card p {
            margin: 6px 0 0;
            color: var(--gray);
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            font-size: 13px;
            color: var(--primary);
            background: var(--soft-blue);
            padding: 12px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid var(--light);
            font-size: 13px;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
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

        .btn {
            border: none;
            border-radius: 8px;
            padding: 9px 13px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
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
            border: 1px solid var(--primary);
        }

        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        .alert-success {
            background: #EAFBF3;
            color: var(--green);
        }

        .alert-error {
            background: #FFEAEA;
            color: var(--red);
        }

        .table-responsive {
            overflow-x: auto;
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 220px;
            }

            .main {
                margin-left: 220px;
                width: calc(100% - 220px);
            }

            .grid {
                grid-template-columns: repeat(2, 1fr);
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
                justify-content: space-between;
                gap: 12px;
            }

            .admin-profile {
                flex: 1;
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
                padding: 16px;
            }

            .grid {
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
                    <div class="admin-avatar"></div>

                    <div class="admin-info">
                        <div class="admin-name">
                            {{ session('admin_name', 'Administrator') }}
                        </div>

                        <div class="admin-role">
                            Super Admin
                        </div>
                    </div>

                    <div class="admin-arrow">⌄</div>
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

                <h1 class="page-title">
                    @yield('title', 'Dashboard Admin')
                </h1>

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

                @yield('content')

            </section>
        </main>

    </div>

</body>

</html>
