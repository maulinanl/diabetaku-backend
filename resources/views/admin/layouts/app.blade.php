<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin diabetAku')</title>

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
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: var(--primary);
            color: var(--white);
            padding: 24px 18px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            display: flex;
            flex-direction: column;
        }

        .brand {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 34px;
        }

        .brand span {
            display: block;
            font-size: 12px;
            font-weight: 400;
            opacity: .9;
            margin-top: 4px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 8px;
            font-size: 14px;
            transition: .2s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, .18);
        }

        .logout-area {
            margin-top: auto;
        }

        .logout-button {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border: none;
            border-radius: 10px;
            background: transparent;
            color: white;
            font-size: 14px;
            cursor: pointer;
            transition: .2s;
        }

        .logout-button:hover {
            background: rgba(255, 255, 255, .18);
        }

        .main {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 24px;
        }

        .topbar {
            background: white;
            border: 1px solid var(--light);
            border-radius: 14px;
            padding: 18px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        .page-title {
            margin: 0;
            color: var(--primary);
            font-size: 22px;
            font-weight: 700;
        }

        .admin-name {
            font-size: 13px;
            color: var(--gray);
            font-weight: 600;
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
                width: 210px;
            }

            .main {
                margin-left: 210px;
                width: calc(100% - 210px);
            }

            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                width: 100%;
            }

            .admin-wrapper {
                flex-direction: column;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

    <div class="admin-wrapper">

        <aside class="sidebar">

            <div class="brand">
                diabetAku
                <span>Admin Panel</span>
            </div>

            <a href="{{ route('admin.web.dashboard') }}"
                class="nav-link {{ request()->routeIs('admin.web.dashboard') ? 'active' : '' }}">
                🏠 Dashboard
            </a>

            <a href="{{ route('admin.web.doctors.pending') }}"
                class="nav-link {{ request()->routeIs('admin.web.doctors.*') ? 'active' : '' }}">
                🩺 Verifikasi Dokter
            </a>

            <a href="{{ route('admin.web.users.index') }}"
                class="nav-link {{ request()->routeIs('admin.web.users.*') ? 'active' : '' }}">
                👥 Manajemen User
            </a>

            <a href="{{ route('admin.web.master.index', 'specializations') }}"
                class="nav-link {{ request()->routeIs('admin.web.master.*') ? 'active' : '' }}">
                🗂️ Data Master
            </a>

            <div class="logout-area">

                <hr style="margin:20px 0;border:none;border-top:1px solid rgba(255,255,255,.2);">

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf

                    <button type="submit" class="logout-button">
                        🚪 Logout
                    </button>
                </form>

            </div>

        </aside>

        <main class="main">

            <div class="topbar">

                <h1 class="page-title">
                    @yield('title', 'Dashboard Admin')
                </h1>

                <div class="admin-name">
                    {{ session('admin_name', 'Administrator') }}
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

            @yield('content')

        </main>

    </div>

</body>

</html>
