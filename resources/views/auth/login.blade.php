<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wijdan - Admin Login</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <style>
        :root {
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
            background-image: url('{{ asset("wijdan_fabric_bg.png") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
        }

        .overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.4) 100%);
            z-index: 1;
        }

        .login-container {
            position: relative;
            z-index: 5;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            background: rgba(25, 25, 25, 0.65);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            animation: fadeIn 1s ease-out;
            text-align: center;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 5px;
            letter-spacing: 2px;
        }

        .subtitle {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #ccc;
            margin-bottom: 30px;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            text-align: left;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
            text-align: left;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.5);
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 6px;
            color: #fff;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: all 0.3s ease;
        }

        .input-group input::placeholder {
            color: rgba(255,255,255,0.4);
        }

        .input-group input:focus {
            border-color: var(--accent-color);
            background: rgba(0, 0, 0, 0.5);
        }

        .input-group input:focus + i,
        .input-group input:valid + i {
            color: var(--accent-color);
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: var(--accent-color);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            margin-top: 10px;
        }

        .login-btn:hover {
            background: #b5952f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
        }

        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--accent-color);
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
            .brand-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

    <div class="overlay"></div>

    <div class="login-container">
        <h1 class="brand-title">Wijdan</h1>
        <div class="subtitle">Admin Portal</div>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            
            @if ($errors->any())
            <div class="alert-danger">
                @foreach ($errors->all() as $error)
                    <p><i class="fas fa-exclamation-circle"></i> {{ $error }}</p>
                @endforeach
            </div>
            @endif

            <div class="input-group">
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Email Address" required autofocus autocomplete="username">
                <i class="fas fa-envelope"></i>
            </div>

            <div class="input-group">
                <input id="password" type="password" name="password" placeholder="Password" required autocomplete="current-password">
                <i class="fas fa-lock"></i>
            </div>

            <button type="submit" class="login-btn">Secure Login</button>
        </form>

        <a href="{{ url('/') }}" class="back-link"><i class="fas fa-arrow-left"></i> Back to Main Site</a>
    </div>

</body>
</html>