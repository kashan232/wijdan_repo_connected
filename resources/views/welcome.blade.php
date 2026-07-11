<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to Wijdan</title>

    <!-- Google Fonts for Premium Look -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #1a1a1a;
            --accent-color: #d4af37; /* Gold */
            --text-light: #fdfdfd;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-light);
            height: 100vh;
            overflow: hidden; /* No scroll */
            background-image: url('{{ asset("wijdan_fabric_bg.png") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        /* Overlay to darken background */
        .overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 100%);
            z-index: 1;
        }

        h1, h2, h3, h4 {
            font-family: 'Playfair Display', serif;
        }

        /* --- Main Content Container --- */
        .main-content {
            position: relative;
            z-index: 5;
            max-width: 800px;
            padding: 2rem;
            animation: fadeIn 1.5s ease-out;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .welcome-title {
            font-size: 1.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 6px;
            margin-bottom: 1rem;
            color: var(--accent-color);
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .hero-title {
            font-size: 5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            letter-spacing: 2px;
            line-height: 1.1;
        }

        .hero-desc {
            font-size: 1.15rem;
            font-weight: 300;
            line-height: 1.8;
            color: #e0e0e0;
            margin-bottom: 3.5rem;
            max-width: 650px;
        }

        .admin-login-btn {
            display: inline-block;
            padding: 1rem 3rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
            font-size: 1rem;
            text-decoration: none;
            letter-spacing: 2px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            border-radius: 4px;
        }

        .admin-login-btn:hover {
            background: var(--accent-color);
            border-color: var(--accent-color);
            color: #fff;
            transform: translateY(-2px);
        }

        /* --- Footer --- */
        .footer-fixed {
            position: absolute;
            bottom: 2rem;
            left: 0;
            width: 100%;
            z-index: 10;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.4);
            letter-spacing: 1px;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 3.5rem;
            }
            .welcome-title {
                font-size: 1.2rem;
            }
            .hero-desc {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>

    <div class="overlay"></div>

    <!-- Main Layout -->
    <div class="main-content">
        
        <div class="welcome-title">Welcome to Wijdan</div>
        <h1 class="hero-title">Colors of East</h1>
        <p class="hero-desc">Wijdan was established in 1986, set up in Hyderabad. Our aim is to bring the best quality fabrics to satisfy our customer needs. We are a company which offers a wide variety of fabric qualities, flexible to withstand any weather or occasion.</p>
        
        @if (Route::has('login'))
            @auth
                <a href="{{ url('/home') }}" class="admin-login-btn"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="admin-login-btn"><i class="fas fa-lock"></i> Admin Login</a>
            @endauth
        @endif

    </div>

    <!-- Footer -->
    <div class="footer-fixed">
        &copy; {{ date('Y') }} Wijdan Stores. All Rights Reserved.
    </div>

</body>
</html>