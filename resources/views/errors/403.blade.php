<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>403 | Access Denied</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #f5f7fb, #eef1f6);
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .error-card {
            max-width: 560px;
            width: 100%;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 45px rgba(0,0,0,.12);
            padding: 55px 45px;
            text-align: center;
        }

        .error-code {
            font-size: 140px;
            font-weight: 800;
            line-height: 1;
            color: #dc3545;
            letter-spacing: -6px;
        }

        .error-title {
            font-size: 26px;
            font-weight: 700;
            margin-top: 10px;
            margin-bottom: 12px;
            color: #212529;
        }

        .error-text {
            font-size: 15px;
            color: #6c757d;
            margin-bottom: 35px;
            line-height: 1.6;
        }

        .error-actions .btn {
            min-width: 140px;
            padding: 10px 18px;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="error-card">
    <div class="error-code">403</div>

    <div class="error-title">Access Denied</div>

    <p class="error-text">
        You don’t have permission to access this page.<br>
        Please contact the administrator if you believe this is an error.
    </p>

    <div class="error-actions">
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary me-2">
            Go Back
        </a>
    </div>
</div>

</body>
</html>
