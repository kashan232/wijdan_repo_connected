{{-- @include('admin_panel.layout.header') --}}

{{-- @yield('content')
@include('admin_panel.layout.footer') --}}



<!DOCTYPE html>
<html class="no-js" lang="zxx">

<head>
    @include('admin_panel.layout.head')
</head>
<style>
    .main-content {
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        body {
            padding-top: 10px;
        }
    }
</style>

<body>

    @include('admin_panel.layout.header')

    <div class="main-content">
        @yield('content')
    </div>

    @include('admin_panel.layout.footer')
    @yield('scripts')

</body>

</html>
