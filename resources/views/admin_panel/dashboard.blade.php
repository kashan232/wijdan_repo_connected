@extends('admin_panel.layout.app')

@section('content')
<style>
    /* Google Font */
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap');

    .welcome-wrapper{
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Poppins', sans-serif;
    }

    .welcome-box{
        text-align: center;
        padding: 55px 40px;
        background: linear-gradient(180deg, #ffffff, #f9fafb);
        border-radius: 18px;
        box-shadow: 0 18px 45px rgba(0,0,0,0.10);
        max-width: 780px;
        width: 100%;
        border: 1px solid #eef1f5;
    }

    .welcome-divider{
        width: 90px;
        height: 5px;
        background: linear-gradient(90deg, #0d6efd, #6610f2);
        margin: 0 auto 28px;
        border-radius: 6px;
    }

    .welcome-title{
        font-size: 42px;      /* 🔥 Bigger title */
        font-weight: 900;
        color: #0f172a;
        margin-bottom: 14px;
    }

    .welcome-subtitle{
        font-size: 20px;      /* 🔥 Bigger subtitle */
        color: #475569;
        margin-bottom: 30px;
        font-weight: 500;
    }

    .welcome-footer{
        font-size: 15px;
        color: #64748b;
        margin-top: 30px;
        font-weight: 500;
        letter-spacing: .3px;
    }

    .welcome-footer strong{
        color: #0f172a;
        font-weight: 700;
    }

    /* Small screens */
    @media(max-width:768px){
        .welcome-title{
            font-size: 30px;
        }
        .welcome-subtitle{
            font-size: 16px;
        }
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container">

            <div class="welcome-wrapper">
                <div class="welcome-box">

                    <div class="welcome-divider"></div>

                    <h1 class="welcome-title">
                        Welcome to Wijdan Exclusive Store
                    </h1>

                    <p class="welcome-subtitle">
                        Management & Reporting Dashboard
                    </p>

                    <div class="welcome-divider"></div>

                    <p class="welcome-footer">
                        Developed by <strong>ProWave Software Solutions</strong>
                    </p>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
