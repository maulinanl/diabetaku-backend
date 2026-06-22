<!DOCTYPE html>
<html>
<head>
    <title>Admin diabetAku</title>

    <style>

        body{
            margin:0;
            background:#F5F8FC;
            font-family:Arial;
        }

        .container{
            height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
        }

        .card{
            width:420px;
            background:white;
            padding:32px;
            border-radius:20px;
            box-shadow:0 5px 20px rgba(0,0,0,.08);
        }

        h1{
            color:#3A8DDE;
            text-align:center;
            margin-bottom:8px;
        }

        p{
            text-align:center;
            color:#666;
            margin-bottom:24px;
        }

        input{
            width:100%;
            padding:14px;
            margin-bottom:14px;
            border:1px solid #DDE7F3;
            border-radius:8px;
        }

        button{
            width:100%;
            padding:14px;
            background:#3A8DDE;
            color:white;
            border:none;
            border-radius:8px;
            cursor:pointer;
            font-weight:600;
        }

        .error{
            background:#FFEAEA;
            color:red;
            padding:12px;
            border-radius:8px;
            margin-bottom:16px;
        }

    </style>
</head>
<body>

<div class="container">

    <div class="card">

        <h1>diabetAku</h1>

        <p>Admin Dashboard</p>

        @if(session('error'))
            <div class="error">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="/admin/login">

            @csrf

            <input
                type="email"
                name="email"
                placeholder="Email Admin"
            >

            <input
                type="password"
                name="password"
                placeholder="Password"
            >

            <button type="submit">
                Masuk
            </button>

        </form>

    </div>

</div>

</body>
</html>
