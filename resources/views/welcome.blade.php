<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wijdan Admin Panel</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:700&display=swap" rel="stylesheet" />

    <style>
        /* Basic Reset */
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Figtree', sans-serif;
            overflow: hidden;
        }

        /* Main Container Styling */
        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #f9fafb;
            text-align: center;
            
            /* === YAHAN BADLAAV KIYA GAYA HAI === */
            /* Background image ke upar halki si dark layer taaki text saaf dikhe */
            background-image: 
                linear-gradient(rgba(17, 24, 39, 0.7), rgba(17, 24, 39, 0.7)), 
                url('C7AA8C11-D2D0-4963-8962-4CF427396551.webp');
            
            background-size: cover; /* Image ko poori screen par fit karega */
            background-position: center; /* Image ko center mein rakhega */
            background-repeat: no-repeat; /* Image repeat nahi hogi */
        }
        
        /* Glassmorphism content box */
        .content-box {
            padding: 3rem 4.5rem;
            background: rgba(31, 41, 55, 0.45); /* Semi-transparent dark gray */
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            animation: slide-in 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
        }

        @keyframes slide-in {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Welcome Title */
        h1 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 2.5rem;
            letter-spacing: 1px;
            text-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
        }

        /* Login Button */
        .login-btn {
            display: inline-block;
            padding: 1rem 3rem;
            border: none;
            border-radius: 0.5rem;
            background: #4F46E5; /* Indigo color */
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(79, 70, 229, 0.5);
        }

        .login-btn:hover {
            background: #4338CA; /* Darker Indigo */
            transform: translateY(-4px);
            box-shadow: 0 7px 25px rgba(79, 70, 229, 0.6);
        }

        .login-btn:active {
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="antialiased">
    <div class="main-container">
       <div class="content-box">
            <h1>Welcome to Wijdan Admin</h1>
            
            @if (Route::has('login'))
                <a href="{{ route('login') }}" class="login-btn">Admin Login</a>
            @endif
       </div>
    </div>
</body>
</html>