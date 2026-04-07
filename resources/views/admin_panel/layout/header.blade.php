<div class="container-scroller">

    <nav class="rt_nav_header horizontal-layout col-lg-12 col-12 p-0">
        <div class="top_nav flex-grow-1">
            <div class="container d-flex flex-row h-100 align-items-center">
                <div class="text-center rt_nav_wrapper d-flex align-items-center">
                    <a class="nav_logo rt_logo" href="index.html">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="logo" /></a>
                </div>
                <div class="nav_wrapper_main d-flex align-items-center justify-content-between flex-grow-1">
                    <ul class="navbar-nav navbar-nav-right mr-0 ml-auto">
                        <li class="nav-item nav-profile dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown"
                                id="profileDropdown">
                                <span class="profile_name">{{ Auth::user()->name }} <i
                                        class="feather ft-chevron-down"></i></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right navbar-dropdown pt-2"
                                aria-labelledby="profileDropdown">
                                <span role="separator" class="divider"></span>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="ti-power-off text-dark mr-3"></i> Logout
                                    </button>
                                </form>
                            </div>
                        </li>
                    </ul>

                    <button class="navbar-toggler align-self-center" type="button" data-toggle="minimize">
                        <span class="feather ft-menu text-white"></span>
                    </button>

                </div>
            </div>
        </div>
        <div class="nav-bottom">
            <div class="container">
                <ul class="nav page-navigation">
                    <li class="nav-item">
                        <a href="{{ url('/home') }}" class="nav-link"><i
                                class="menu_icon feather ft-home"></i><span class="menu-title">Dashboard</span></a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ url('/sale/create') }}" class="nav-link">
                            <i class="menu_icon fas fa-cash-register"></i>
                            <span class="menu-title">Sale</span></a>
                    </li>

                    <li class="nav-item mega-menu">
                        <a href="#" class="nav-link">
                            <i class="menu_icon fas fa-user-shield"></i>
                            <span class="menu-title">Management</span>

                            <i class="menu-arrow"></i>
                        </a>
                        <div class="submenu">
                            <div class="col-group-wrapper row">
                                <!-- Products & Categories -->
                                <div class="col-group col-md-3">
                                    <p class="category-heading">Products & Categories</p>

                                    <ul class="submenu-item">

                                        @can('Products')
                                        <li>
                                            <a href="{{ route('product') }}">
                                                <i class="fas fa-box"></i> Products
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Discount Products')
                                        <li>
                                            <a href="{{ route('discount.index') }}">
                                                <i class="fas fa-tags"></i> Discount Products
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Category')
                                        <li>
                                            <a href="{{ route('Category.home') }}">
                                                <i class="fas fa-list"></i> Category
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Sub Category')
                                        <li>
                                            <a href="{{ route('subcategory.home') }}">
                                                <i class="fas fa-th-list"></i> Sub Category
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Brands')
                                        <li>
                                            <a href="{{ route('Brand.home') }}">
                                                <i class="fas fa-trademark"></i> Brands
                                            </a>
                                        </li>
                                        @endcan

                                    </ul>
                                </div>

                                <!-- Purchase & Inventory -->
                                <div class="col-group col-md-3">
                                    <p class="category-heading">Purchase & Inventory</p>

                                    <ul class="submenu-item">

                                        @can('List Inwards')
                                        <li>
                                            <a href="{{ route('InwardGatepass.home') }}">
                                                <i class="fas fa-shopping-cart"></i> List Inwards
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Create Inward Gatepass')
                                        <li>
                                            <a href="{{ route('add_inwardgatepass') }}">
                                                <i class="fas fa-shopping-cart"></i> Create Inward Gatepass
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Purchase')
                                        <li>
                                            <a href="{{ route('Purchase.home') }}">
                                                <i class="fas fa-shopping-cart"></i> Purchase
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Purchase Return')
                                        <li>
                                            <a href="{{ route('purchase.return.index') }}">
                                                <i class="fas fa-shopping-cart"></i> Purchase Return
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Vendor')
                                        <li>
                                            <a href="{{ route('vendors') }}">
                                                <i class="fas fa-truck"></i> Vendor
                                            </a>
                                        </li>
                                        @endcan

                                    </ul>
                                </div>


                                <!-- Accounts -->
                                <div class="col-group col-md-3">
                                    <p class="category-heading">Accounts</p>

                                    <ul class="submenu-item">

                                        @can('List Warehouse')
                                        <li>
                                            <a href="{{ url('warehouse') }}">
                                                <i class="fas fa-warehouse"></i> List Warehouse
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Warehouse Stock')
                                        <li>
                                            <a href="{{ url('warehouse_stocks') }}">
                                                <i class="fas fa-boxes"></i> Stock Status
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Stock Transfer')
                                        <li>
                                            <a href="{{ url('stock_transfers') }}">
                                                <i class="fas fa-exchange-alt"></i> Stock Transfer
                                            </a>
                                        </li>
                                        @endcan

                                    </ul>
                                </div>
                                <!-- Customers & Sales -->
                                <div class="col-group col-md-3">
                                    <p class="category-heading">Sales & Customers</p>

                                    <ul class="submenu-item">

                                        @can('Sales')
                                        <li>
                                            <a href="{{ url('sale') }}">
                                                <i class="fas fa-receipt"></i> Sales
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Sale Return')
                                        <li>
                                            <a href="{{ url('sale-returns') }}">
                                                <i class="fas fa-receipt"></i> Sale Return
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Bookings')
                                        <li>
                                            <a href="{{ route('bookings.index') }}">
                                                <i class="fas fa-receipt"></i> Bookings
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Customer')
                                        <li>
                                            <a href="{{ url('customers') }}">
                                                <i class="fas fa-user"></i> Customer
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Sales Officer')
                                        <li>
                                            <a href="{{ url('sales-officers') }}">
                                                <i class="fas fa-user-tie"></i> Sales Officer
                                            </a>
                                        </li>
                                        @endcan

                                        @can('Zone')
                                        <li>
                                            <a href="{{ url('zone') }}">
                                                <i class="fas fa-map-marker-alt"></i> Zone
                                            </a>
                                        </li>
                                        @endcan

                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- HR Management Menu -->
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="menu_icon fas fa-users"></i>
                            <span class="menu-title">HR</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="submenu">
                            <ul class="submenu-item">
                                @can('hr.employees.view')
                                <li class="nav-item"><a class="nav-link" href="{{ route('hr.employees.index') }}"><i class="fas fa-user-friends mr-2"></i> <span class="menu-title">Employees</span></a></li>
                                @endcan
                                @can('hr.attendance.view')
                                <li class="nav-item"><a class="nav-link" href="{{ route('hr.attendance.index') }}"><i class="fas fa-calendar-check mr-2"></i> <span class="menu-title">Attendance</span></a></li>
                                @endcan
                                @can('hr.payroll.view')
                                <li class="nav-item"><a class="nav-link" href="{{ route('hr.payroll.index') }}"><i class="fas fa-money-check-alt mr-2"></i> <span class="menu-title">Payroll</span></a></li>
                                @endcan
                                @can('hr.leaves.view')
                                <li class="nav-item"><a class="nav-link" href="{{ route('hr.leaves.index') }}"><i class="fas fa-calendar-minus mr-2"></i> <span class="menu-title">Leaves</span></a></li>
                                @endcan
                                @can('hr.loans.view')
                                <li class="nav-item"><a class="nav-link" href="{{ route('hr.loans.index') }}"><i class="fas fa-hand-holding-usd mr-2"></i> <span class="menu-title">Loans</span></a></li>
                                @endcan
                                @can('hr.departments.view')
                                <li class="nav-item"><a class="nav-link" href="{{ route('hr.departments.index') }}"><i class="fas fa-sitemap mr-2"></i> <span class="menu-title">Departments</span></a></li>
                                @endcan
                                @can('hr.designations.view')
                                <li class="nav-item"><a class="nav-link" href="{{ route('hr.designations.index') }}"><i class="fas fa-user-tag mr-2"></i> <span class="menu-title">Designations</span></a></li>
                                @endcan
                                @can('hr.shifts.view')
                                <li class="nav-item"><a class="nav-link" href="{{ route('hr.shifts.index') }}"><i class="fas fa-clock mr-2"></i> <span class="menu-title">Shifts</span></a></li>
                                @endcan
                                @can('hr.holidays.view')
                                <li class="nav-item"><a class="nav-link" href="{{ route('hr.holidays.index') }}"><i class="fas fa-umbrella-beach mr-2"></i> <span class="menu-title">Holidays</span></a></li>
                                @endcan
                                @can('hr.salary.structure.view')
                                <li class="nav-item"><a class="nav-link" href="{{ route('hr.salary-structure.index') }}"><i class="fas fa-file-invoice-dollar mr-2"></i> <span class="menu-title">Salary Structure</span></a></li>
                                @endcan
                                @can('hr.biometric.devices.view')
                                <li class="nav-item"><a class="nav-link" href="{{ route('hr.biometric-devices.index') }}"><i class="fas fa-fingerprint mr-2"></i> <span class="menu-title">Biometric Devices</span></a></li>
                                @endcan
                                <li class="nav-item"><a class="nav-link" href="{{ route('my-attendance') }}"><i class="fas fa-user-clock mr-2"></i> <span class="menu-title">My Attendance</span></a></li>
                            </ul>
                        </div>
                    </li>



                    <!-- Vouchers Menu -->

                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="menu_icon fas fa-file-invoice-dollar"></i>
                            <span class="menu-title">Vouchers</span>

                            <i class="menu-arrow"></i>
                        </a>

                        <div class="submenu">
                            <ul class="submenu-item">

                                @can('Char Of Accounts')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('view_all') }}">
                                        <i class="fa-solid fa-money-bill-wave mr-2"></i>
                                        <span>Char Of Accounts</span>
                                    </a>
                                </li>
                                @endcan

                                @can('Narrations')
                                <!-- <li class="nav-item">
                                    <a class="nav-link" href="{{ route('narrations.index') }}">
                                        <i class="fa-solid fa-money-bill-wave mr-2"></i>
                                        <span>Narrations</span>
                                    </a>
                                </li> -->
                                @endcan

                                @can('Receipts Voucher')
                                <!-- <li class="nav-item">
                                    <a class="nav-link" href="{{ route('all-recepit-vochers') }}">
                                        <i class="fa-solid fa-wallet mr-2"></i>
                                        <span>Receipts Voucher</span>
                                    </a>
                                </li> -->
                                @endcan

                                @can('Payment Voucher')
                                <!-- <li class="nav-item">
                                    <a class="nav-link" href="{{ route('all-Payment-vochers') }}">
                                        <i class="fa-solid fa-wallet mr-2"></i>
                                        <span>Payment Voucher</span>
                                    </a>
                                </li> -->
                                @endcan

                                @can('Expense Voucher')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('all-expense-vochers') }}">
                                        <i class="fa-solid fa-money-bill-wave mr-2"></i>
                                        <span>Expense Voucher</span>
                                    </a>
                                </li>
                                @endcan

                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="menu_icon feather ft-clipboard"></i>
                            <span class="menu-title">Reports</span>
                            <i class="menu-arrow"></i>
                        </a>

                        <div class="submenu">
                            <ul class="submenu-item">

                                @can('Item Stock Report')
                                <li>
                                    <a href="{{ route('report.item_stock') }}">
                                        <i class="fa-solid fa-users"></i> Item Stock Report
                                    </a>
                                </li>
                                @endcan

                                @can('Purchase Report')
                                <li>
                                    <a href="{{ route('report.purchase') }}">
                                        <i class="fa-solid fa-users"></i> Purchase Report
                                    </a>
                                </li>
                                @endcan

                                @can('Sale Report')
                                <li>
                                    <a href="{{ route('report.sale') }}">
                                        <i class="fa-solid fa-users"></i> Sale Report
                                    </a>
                                </li>

                                @if (auth()->user()->email === 'admin@admin.com')
                                <li>
                                    <a href="{{ route('report.niaz') }}">
                                        <i class="fa-solid fa-users"></i> Niaz Report
                                    </a>
                                </li>

                                <li>
                                    <a href="{{ route('report.sale.bonus') }}">
                                        <i class="fa-solid fa-users"></i> Sale Bonus Report
                                    </a>
                                </li>
                                @endif

                                <li>
                                    <a href="{{ route('report.sale.category') }}">
                                        <i class="fa-solid fa-users"></i> Sale Report Category
                                    </a>
                                </li>

                                @endcan



                                @can('Customer Ledger')
                                <li>
                                    <a href="{{ route('report.customer.ledger') }}">
                                        <i class="fa-solid fa-users"></i> Customer Ledger
                                    </a>
                                </li>
                                @endcan

                                @can('Vendor Ledger')
                                <li>
                                    <a href="{{ route('report.vendor.ledger') }}">
                                        <i class="fa-solid fa-users"></i> Vendor Ledger
                                    </a>
                                </li>
                                @endcan

                                @can('System Reports')
                                <li>
                                    <a href="{{ route('System.Reports') }}">
                                        <i class="fa-solid fa-users"></i> System Reports
                                    </a>
                                </li>
                                @endcan

                                @if (auth()->user()->email === 'admin@admin.com')
                                <li>
                                    <a href="{{ route('expense.vocher') }}">
                                        <i class="fa-solid fa-users"></i> Expense Report
                                    </a>
                                </li>
                                @endif

                            </ul>
                        </div>
                    </li>

                    <!-- User Management Menu -->
                    @if (auth()->user()->email === 'admin@admin.com')
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="menu_icon fas fa-users-cog"></i>
                            <span class="menu-title">User Management</span>

                            <i class="menu-arrow"></i>
                        </a>
                        <div class="submenu">
                            <ul class="submenu-item">
                                <li><a href="{{ route('users.index') }}"><i class="fa-solid fa-users"></i>
                                        Users</a></li>
                                <li><a href="{{ route('roles.index') }}"><i
                                            class="fa-solid fa-user-lock"></i> Roles</a></li>
                                <li><a href="{{ route('permissions.index') }}"><i
                                            class="fa-solid fa-user-lock"></i> Permissions</a></li>
                                <li><a href="{{ route('branch.index') }}"><i
                                            class="fa-solid fa-code-branch"></i> Branches</a></li>
                            </ul>
                        </div>
                    </li>
                    @endif

                    @if (auth()->user()->email === 'admin@admin.com')
                    <li class="nav-item">
                        <a href="{{ route('cashbook') }}" class="nav-link">
                            <i class="menu_icon fas fa-users-cog"></i>
                            <span class="menu-title">CashBook</span>
                        </a>
                    </li>
                    @endif

                </ul>
            </div>
        </div>
    </nav>