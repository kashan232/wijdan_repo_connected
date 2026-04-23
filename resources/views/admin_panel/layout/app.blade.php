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
 
     /* Global Button Spacing Fix */
     .btn + .btn, 
     .btn + a, 
     a + .btn,
     button + a,
     a + button {
         margin-left: 0.5rem !important;
     }
 
     /* Ensure flex gaps work everywhere */
     .gap-1 { gap: 0.25rem !important; }
     .gap-2 { gap: 0.5rem !important; }
     .gap-3 { gap: 1rem !important; }
 
     /* Fix card header layout consistency */
     .card-header {
         padding: 1rem 1.25rem !important;
         display: flex !important;
         justify-content: space-between !important;
         align-items: center !important;
         background-color: #f8f9fa !important;
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
