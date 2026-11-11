@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800">Dashboard</h1>
            <p class="text-muted mb-0">Overview of Security Master List & Auction Results</p>
        </div>
        <div class="d-none d-sm-inline-block">
             <a href="{{ route('securities.index') }}" class="btn btn-sm btn-primary shadow-sm me-2">
                <i class="bi bi-list-ul me-1"></i>View Securities
            </a>
            <a href="{{ route('auction-results.index') }}" class="btn btn-sm btn-outline-primary shadow-sm">
                <i class="bi bi-graph-up me-1"></i>View Auctions
            </a>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row g-3 mb-4">
        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 border-start border-4 border-primary shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                Total Securities</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalSecurities }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-file-earmark-text fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings (Monthly) Card Example -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 border-start border-4 border-success shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                Total Auctions</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $totalAuctions }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-graph-up fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Requests Card Example -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 border-start border-4 border-warning shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                Pending Approvals</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ $pendingApprovals }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clipboard-check fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User Info Card -->
         <div class="col-xl-3 col-md-6">
            <div class="card border-0 border-start border-4 border-info shadow-sm h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                Your Department</div>
                            <div class="h5 mb-0 fw-bold text-gray-800">{{ Auth::user()->department ?? 'N/A' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-person-badge fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Area Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                    <h6 class="m-0 fw-bold text-primary">Recent Auction Sales (N'bn)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="position: relative; height: 300px;">
                        <canvas id="myAreaChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                    <h6 class="m-0 fw-bold text-primary">Security Composition</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2" style="position: relative; height: 300px;">
                         <canvas id="myPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 fw-bold text-primary">Recent System Activity</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                         <table class="table table-sm table-hover">
                             <thead>
                                 <tr>
                                     <th>User</th>
                                     <th>Action</th>
                                     <th>Subject</th>
                                     <th>Time</th>
                                 </tr>
                             </thead>
                             <tbody>
                                 @forelse($recentActivities as $activity)
                                 <tr>
                                     <td>{{ $activity->causer->full_name ?? 'System' }}</td>
                                     <td>{{ ucfirst($activity->description) }}</td>
                                     <td>
                                         @if($activity->subject_type)
                                            {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                                         @else
                                            -
                                         @endif
                                     </td>
                                     <td class="text-muted">{{ $activity->created_at->diffForHumans() }}</td>
                                 </tr>
                                 @empty
                                 <tr>
                                     <td colspan="4" class="text-center">No recent activity.</td>
                                 </tr>
                                 @endforelse
                             </tbody>
                         </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pie Chart
    var ctxPie = document.getElementById("myPieChart");
    var myPieChart = new Chart(ctxPie, {
      type: 'doughnut',
      data: {
        labels: {!! json_encode($portfolioLabels) !!},
        datasets: [{
          data: {!! json_encode($portfolioValues) !!},
          backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
          hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a'],
          hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
      },
      options: {
        maintainAspectRatio: false,
        tooltips: {
          backgroundColor: "rgb(255,255,255)",
          bodyFontColor: "#858796",
          borderColor: '#dddfeb',
          borderWidth: 1,
          xPadding: 15,
          yPadding: 15,
          displayColors: false,
          caretPadding: 10,
        },
        legend: {
          display: true,
          position: 'bottom'
        },
        cutout: '70%',
      },
    });

    // Bar Chart
    var ctxBar = document.getElementById("myAreaChart");
    var myBarChart = new Chart(ctxBar, {
      type: 'bar',
      data: {
        labels: {!! json_encode($auctionLabels) !!},
        datasets: [{
          label: "Amount Sold (N'bn)",
          backgroundColor: "#4e73df",
          hoverBackgroundColor: "#2e59d9",
          borderColor: "#4e73df",
          data: {!! json_encode($auctionValues) !!},
        }],
      },
      options: {
        maintainAspectRatio: false,
        layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
        scales: {
          x: { grid: { display: false, drawBorder: false }, ticks: { maxTicksLimit: 6 } },
          y: { ticks: { maxTicksLimit: 5, padding: 10, callback: function(value) { return 'N' + value + 'bn'; } }, grid: { color: "rgb(234, 236, 244)", zeroLineColor: "rgb(234, 236, 244)", drawBorder: false, borderDash: [2], zeroLineBorderDash: [2] } },
        },
        legend: { display: false },
        tooltips: {
          backgroundColor: "rgb(255,255,255)",
          bodyFontColor: "#858796",
          titleMarginBottom: 10,
          titleFontColor: '#6e707e',
          titleFontSize: 14,
          borderColor: '#dddfeb',
          borderWidth: 1,
          xPadding: 15,
          yPadding: 15,
          displayColors: false,
          intersect: false,
          mode: 'index',
          caretPadding: 10,
          callbacks: {
            label: function(tooltipItem, chart) {
              var datasetLabel = chart.datasets[tooltipItem.datasetIndex].label || '';
              return datasetLabel + ': N' + (tooltipItem.yLabel) + 'bn';
            }
          }
        }
      }
    });
});
</script>
@endsection
