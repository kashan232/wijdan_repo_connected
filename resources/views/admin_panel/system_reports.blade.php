@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container">
            
            {{-- Filter Section --}}
            <div class="row mb-3">
                <div class="col-12">
                    <form action="{{ route('System.Reports') }}" method="GET" class="card shadow-sm border-0 rounded-3 p-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Start Month</label>
                                <input type="month" name="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">End Month</label>
                                <input type="month" name="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-search me-1"></i> Filter
                                </button>
                                <button type="button" id="btnExportCsv" class="btn btn-danger">
                                    <i class="fas fa-file-csv me-1"></i> Export
                                </button>
                                <a href="{{ route('System.Reports') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo me-1"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3">
                <!-- Categories -->
                <div class="col-md-3 mt-2">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 text-muted">Categories</h6>
                                <h3 class="mb-0 fw-bold">{{ $categoryCount }}</h3>
                            </div>
                            <div class="icon text-primary">
                                <i class="fas fa-layer-group fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subcategories -->
                <div class="col-md-3 mt-2">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 text-muted">Subcategories</h6>
                                <h3 class="mb-0 fw-bold">{{ $subcategoryCount }}</h3>
                            </div>
                            <div class="icon text-success">
                                <i class="fas fa-sitemap fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products -->
                <div class="col-md-3 mt-2">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 text-muted">Products</h6>
                                <h3 class="mb-0 fw-bold">{{ $productCount }}</h3>
                            </div>
                            <div class="icon text-danger">
                                <i class="fas fa-box-open fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Example for future (e.g. Orders) -->
                <div class="col-md-3 mt-2">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 text-muted">Customers</h6>
                                <h3 class="mb-0 fw-bold">{{ $customerscount }}</h3>
                            </div>
                            <div class="icon text-warning">
                                <i class="fas fa-shopping-cart fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Total Purchases -->
                <div class="col-md-3 mt-2">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 text-muted">Total Purchases</h6>
                                <h5 class="mb-0 fw-bold">Rs {{ number_format($totalPurchases, 2) }}</h5>
                            </div>
                            <div class="icon text-primary">
                                <i class="fas fa-file-invoice-dollar fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Purchase Returns -->
                <div class="col-md-3 mt-2">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 text-muted">Purchase Returns</h6>
                                <h5 class="mb-0 fw-bold">Rs {{ number_format($totalPurchaseReturns, 2) }}</h5>
                            </div>
                            <div class="icon text-danger">
                                <i class="fas fa-undo-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Sales -->
                <div class="col-md-3 mt-2">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 text-muted">Total Sales</h6>
                                <h5 class="mb-0 fw-bold">Rs {{ number_format($totalSales, 2) }}</h5>
                            </div>
                            <div class="icon text-success">
                                <i class="fas fa-shopping-cart fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Cash Sales -->
                <div class="col-md-3 mt-2">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 text-muted">Total Cash Sales</h6>
                                <h5 class="mb-0 fw-bold">Rs {{ number_format($totalCashSales, 2) }}</h5>
                            </div>
                            <div class="icon text-info">
                                <i class="fas fa-money-bill-wave fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Card Sales -->
                <div class="col-md-3 mt-2">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 text-muted">Total Card Sales</h6>
                                <h5 class="mb-0 fw-bold">Rs {{ number_format($totalCardSales, 2) }}</h5>
                            </div>
                            <div class="icon text-primary">
                                <i class="fas fa-credit-card fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Returns -->
                <div class="col-md-3 mt-2">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 text-muted">Sales Returns</h6>
                                <h5 class="mb-0 fw-bold">Rs {{ number_format($totalSalesReturns, 2) }}</h5>
                            </div>
                            <div class="icon text-warning">
                                <i class="fas fa-undo fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Expenses -->
                <div class="col-md-3 mt-2">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="mb-1 text-muted">Total Expenses</h6>
                                <h5 class="mb-0 fw-bold">Rs {{ number_format($totalExpenses, 2) }}</h5>
                            </div>
                            <div class="icon text-danger">
                                <i class="fas fa-wallet fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="row mt-4">
                <div class="col-md-12 mb-4">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Expense Report</h6>
                            <label for="expenseFilter" class="form-label fw-bold">Expense Report Filter:</label>
                            <select id="expenseFilter" class="form-select w-auto">
                                <option value="daily" selected>Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="card-body">
                            <div id="expenseReportChart" style="height: 400px;" class="bg-white rounded shadow-sm"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12 mb-4">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Sales Report</h6>
                            <label for="salesFilter" class="form-label fw-bold">Sales Report Filter:</label>
                            <select id="salesFilter" class="form-select w-auto">
                                <option value="daily" selected>Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="card-body">
                            <div id="salesReportChart" style="height: 400px;" class="bg-white rounded shadow-sm"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12 mb-4">
                    <div class="card shadow-sm border-0 rounded-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Purchase Report</h6>
                            <label for="purchaseFilter" class="form-label fw-bold">Purchase Report Filter:</label>
                            <select id="purchaseFilter" class="form-select w-auto">
                                <option value="daily" selected>Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="card-body">
                            <div id="purchaseReportChart" style="height: 400px;" class="bg-white rounded shadow-sm"></div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">
                        Category Wise Product Stock
                    </h6>
                    <small class="text-muted">
                        Stock summary of all products by category
                    </small>
                </div>

                <div class="card-body">
                    <div id="categoryStockChart"></div>
                </div>
            </div>


            <div class="modal fade" id="categoryProductsModal" tabindex="-1">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">

                        <div class="modal-header">
                            <h5 id="modalTitle"></h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">

                            <!-- 🔍 SEARCH -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <input type="text"
                                        id="productSearch"
                                        class="form-control"
                                        placeholder="Search product..."
                                        onkeyup="searchProducts()">
                                </div>
                            </div>

                            <!-- TABLE -->
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Product Name</th>
                                        <th>Stock</th>
                                    </tr>
                                </thead>
                                <tbody id="productsTableBody"></tbody>
                            </table>

                            <!-- PAGINATION -->
                            <nav>
                                <ul class="pagination justify-content-center" id="pagination"></ul>
                            </nav>

                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">Account Wise Expense</h6>
                    <small class="text-muted">
                        Expense distribution by accounts
                    </small>
                </div>

                <div class="card-body">
                    <select id="expenseHeadSelect" class="form-select form-select-sm mb-3">
                        <option value="">Select Account Head</option>
                        @foreach($expenseChartData as $hid => $head)
                        <option value="{{ $hid }}">{{ $head['head_name'] }}</option>
                        @endforeach
                    </select>

                    <div id="expenseAccountChart"></div>
                </div>
            </div>




        </div>
    </div>
</div>
@endsection
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

@section('scripts')
<script>
    // PHP se JS me data pass kar rahe
    const expenseTrendChartStats = @json($expenseTrendChartStats);
    let expenseTrendChart;

    function renderExpenseTrendChart(type = 'daily') {
        const options = {
            chart: {
                type: 'bar',
                height: 400,
                toolbar: { show: true },
            },
            series: expenseTrendChartStats[type].series,
            xaxis: {
                categories: expenseTrendChartStats[type].categories
            },
            dataLabels: {
                enabled: true
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '50%'
                }
            },
            tooltip: {
                y: {
                    formatter: val => 'PKR ' + parseFloat(val).toLocaleString()
                }
            },
            colors: ['#dc3545'] // Red color for expense
        };

        if (expenseTrendChart) expenseTrendChart.destroy();
        expenseTrendChart = new ApexCharts(document.querySelector("#expenseReportChart"), options);
        expenseTrendChart.render();
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderExpenseTrendChart();
        const filter = document.getElementById('expenseFilter');
        if (filter) {
            filter.addEventListener('change', function() {
                renderExpenseTrendChart(this.value);
            });
        }
    });

    const salesChartStats = @json($salesChartStats);

    let chart;

    function renderSalesChart(type = 'daily') {
        const options = {
            chart: {
                type: 'bar',
                height: 400,
                toolbar: {
                    show: true
                },
            },
            series: salesChartStats[type].series,
            xaxis: {
                categories: salesChartStats[type].categories
            },
            dataLabels: {
                enabled: true
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '50%'
                }
            },
            tooltip: {
                y: {
                    formatter: val => 'Rs ' + val.toLocaleString()
                }
            },
            colors: ['#0d6efd']
        };

        if (chart) chart.destroy();
        chart = new ApexCharts(document.querySelector("#salesReportChart"), options);
        chart.render();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initial render
        renderSalesChart();

        // Handle filter change
        const filter = document.getElementById('salesFilter');
        if (filter) {
            filter.addEventListener('change', function() {
                renderSalesChart(this.value);
            });
        }
    });

    // PHP data to JS
    const purchaseChartStats = @json($purchaseChartStats);

    let purchaseChart;

    function renderPurchaseChart(type = 'daily') {
        const options = {
            chart: {
                type: 'bar',
                height: 400,
                toolbar: {
                    show: true
                },
            },
            series: purchaseChartStats[type].series,
            xaxis: {
                categories: purchaseChartStats[type].categories
            },
            dataLabels: {
                enabled: true
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '50%'
                }
            },
            tooltip: {
                y: {
                    formatter: val => 'PKR' + parseFloat(val).toLocaleString()
                }
            },
            colors: ['#198754'] // green color for purchase
        };

        if (purchaseChart) purchaseChart.destroy();
        purchaseChart = new ApexCharts(document.querySelector("#purchaseReportChart"), options);
        purchaseChart.render();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initial render
        renderPurchaseChart();

        // Handle filter change
        const filter = document.getElementById('purchaseFilter');
        if (filter) {
            filter.addEventListener('change', function() {
                renderPurchaseChart(this.value);
            });
        }
    });


    const categoryStockData = @json($categoryProductChart);

    document.addEventListener('DOMContentLoaded', function() {

        const options = {
            chart: {
                type: 'bar',
                height: 420,
                toolbar: {
                    show: false
                },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        const categoryId = categoryStockData.category_ids[config.dataPointIndex];
                        const categoryName = categoryStockData.categories[config.dataPointIndex];
                        loadCategoryProducts(categoryId, categoryName);
                    }
                }
            },

            series: categoryStockData.series,

            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '50%',
                    borderRadius: 6
                }
            },

            xaxis: {
                categories: categoryStockData.categories,
                labels: {
                    style: {
                        fontSize: '13px',
                        fontWeight: 600
                    }
                }
            },

            yaxis: {
                labels: {
                    formatter: val => val.toLocaleString()
                }
            },

            tooltip: {
                y: {
                    formatter: val => `${val.toLocaleString()} Products`
                }
            },

            colors: ['#0d6efd'],

            grid: {
                borderColor: '#eee'
            }
        };

        new ApexCharts(
            document.querySelector("#categoryStockChart"),
            options
        ).render();
    });

    let activeCategoryId = null;
    let activeCategoryName = '';

    function loadCategoryProducts(categoryId, categoryName, page = 1) {

        activeCategoryId = categoryId;
        activeCategoryName = categoryName;

        $('#modalTitle').text(categoryName + ' – Products');
        $('#categoryProductsModal').modal('show');

        let search = $('#productSearch').val();

        $.get(`/category-products/${categoryId}`, {
            page: page,
            search: search
        }, function(res) {

            let rows = '';
            res.data.forEach((item, index) => {
                rows += `
                <tr>
                    <td>${((res.current_page - 1) * 100) + index + 1}</td>
                    <td>${item.item_name}</td>
                    <td>${item.stock}</td>
                </tr>
            `;
            });

            $('#productsTableBody').html(rows);

            // Pagination
            let pagination = '';
            for (let i = 1; i <= res.last_page; i++) {
                pagination += `
                <li class="page-item ${i === res.current_page ? 'active' : ''}">
                    <a class="page-link"
                       href="#"
                       onclick="loadCategoryProducts(${categoryId}, '${categoryName}', ${i})">
                       ${i}
                    </a>
                </li>
            `;
            }

            $('#pagination').html(pagination);
        });
    }

    // 🔍 SEARCH HANDLER
    function searchProducts() {
        loadCategoryProducts(activeCategoryId, activeCategoryName, 1);
    }

    let expenseChart;

    const expenseData = @json($expenseChartData);

    document.getElementById('expenseHeadSelect').addEventListener('change', function() {

        const headId = this.value;
        if (!headId) return;

        const data = expenseData[headId];

        const options = {
            chart: {
                type: 'bar',
                height: 350
            },
            series: data.series,
            xaxis: {
                categories: data.categories
            },
            dataLabels: {
                enabled: true
            },
            tooltip: {
                y: {
                    formatter: val => 'Rs ' + val.toLocaleString()
                }
            }
        };

        if (expenseChart) {
            expenseChart.destroy();
        }

        expenseChart = new ApexCharts(
            document.querySelector("#expenseAccountChart"),
            options
        );

        expenseChart.render();
    });

    // --- CSV EXPORT LOGIC ---
    document.getElementById('btnExportCsv').addEventListener('click', function() {
        let csvContent = "data:text/csv;charset=utf-8,";
        
        // 1. Summary Counts
        csvContent += "SUMMARY COUNTS\n";
        csvContent += "Categories,{{ $categoryCount }}\n";
        csvContent += "Subcategories,{{ $subcategoryCount }}\n";
        csvContent += "Products,{{ $productCount }}\n";
        csvContent += "Customers,{{ $customerscount }}\n\n";

        // 2. Financial Totals
        csvContent += "FINANCIAL TOTALS (Selected Period)\n";
        csvContent += "Total Purchases,Rs {{ $totalPurchases }}\n";
        csvContent += "Purchase Returns,Rs {{ $totalPurchaseReturns }}\n";
        csvContent += "Total Sales,Rs {{ $totalSales }}\n";
        csvContent += "Total Cash Sales,Rs {{ $totalCashSales }}\n";
        csvContent += "Total Card Sales,Rs {{ $totalCardSales }}\n";
        csvContent += "Sales Returns,Rs {{ $totalSalesReturns }}\n";
        csvContent += "Total Expenses,Rs {{ $totalExpenses }}\n\n";

        // 3. Periodic Data (From Charts)
        const salesType = document.getElementById('salesFilter').value;
        const purchaseType = document.getElementById('purchaseFilter').value;
        
        // We'll use the sales chart structure as the primary timeline
        const labels = salesChartStats[salesType].categories;
        const salesData = salesChartStats[salesType].series[0].data;
        // Purchase might have different labels if filter types differ, so we'll just check if types match
        const pLabels = purchaseChartStats[purchaseType].categories;
        const pData = purchaseChartStats[purchaseType].series[0].data;

        csvContent += "PERIODIC BREAKDOWN (" + salesType.toUpperCase() + ")\n";
        csvContent += "Period,Sales,Purchases\n";
        
        labels.forEach((label, index) => {
            let row = [
                '"' + label + '"',
                salesData[index] || 0,
                (purchaseType === salesType) ? (pData[index] || 0) : "N/A (Filter Mismatch)"
            ];
            csvContent += row.join(",") + "\n";
        });

        // Download Trigger
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        const dateStr = new Date().toISOString().slice(0, 10);
        link.setAttribute("download", "System_Report_Summary_" + dateStr + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
</script>
@endsection