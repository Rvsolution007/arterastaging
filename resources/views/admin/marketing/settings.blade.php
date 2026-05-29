@extends('layouts.app')

@section('extra_css')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
    .analytics-container { font-family: 'Poppins', sans-serif; padding: 1.5rem; background-color: #f8fafc; min-height: 100vh; }
    .page-title { font-weight: 700; color: #1e293b; font-size: 1.5rem; letter-spacing: -0.025em; }
    .table-panel { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03); overflow: hidden; margin-bottom: 1.5rem; height: calc(100% - 1.5rem); }
    .table-panel-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 0.75rem; justify-content: space-between; }
    .table-panel-title { font-size: 1.125rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 0.75rem; }
    .table-icon-wrapper { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .icon-campaign { background: #d1fae5; color: #059669; }
    .icon-social { background: #e0e7ff; color: #4338ca; }
    .custom-table { width: 100%; border-collapse: separate; border-spacing: 0; margin: 0; }
    .custom-table th { background: #f8fafc; padding: 1rem 1.5rem; font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; text-align: left; border-bottom: 1px solid #e2e8f0; }
    .custom-table td { padding: 1rem 1.5rem; font-size: 0.875rem; color: #334155; font-weight: 500; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .custom-table tbody tr:hover { background-color: #f8fafc; }
    .badge-soft { padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.25rem; }
    .badge-soft-warning { background: #fef3c7; color: #d97706; }
    .btn-ai { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; border: none; padding: 0.4rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.8rem; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3); }
    .btn-ai:hover { transform: translateY(-1px); box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4); color: white; }
    .log-list { list-style: none; padding: 0; margin: 0; }
    .log-item { padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
    .log-item:last-child { border-bottom: none; }
    .log-time { font-size: 0.75rem; color: #64748b; font-weight: 600; margin-bottom: 0.25rem; }
    .log-content { font-size: 0.875rem; color: #1e293b; line-height: 1.5; }
</style>
@endsection

@section('content')
<div class="analytics-container">
    <div class="row align-items-center mb-4">
        <div class="col-12">
            <h4 class="page-title mb-0"><i class="fa-solid fa-bullhorn mr-2 text-primary"></i> Marketing Automation Hub</h4>
            <p class="text-muted mt-2" style="font-size: 0.875rem;">Control your automated seasonal pushes and social media bots.</p>
        </div>
    </div>
        <!-- Seasonal Campaigns Calendar View -->
    <div class="row">
        <!-- Seasonal Campaigns Calendar View -->
        <div class="col-md-7 mb-4">
            <div class="table-panel">
                <div class="table-panel-header">
                    <div class="table-panel-title">
                        <div class="table-icon-wrapper icon-campaign">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        Seasonal Push Campaigns
                    </div>
                </div>
                <div class="table-responsive">
                    <div class="px-4 py-3 bg-light border-bottom text-muted" style="font-size: 0.85rem;">
                        <i class="fa-solid fa-circle-info text-info mr-1"></i> The AI Engine checks your existing <strong>Festivals</strong> calendar. Exactly 3 days before the "Activation Date", the AI will write a custom Push Notification and send it to all users.
                    </div>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Festival Name</th>
                                <th>Activation Date</th>
                                <th>AI Trigger Date (3 Days Prior)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($upcomingFestivals as $fest)
                            @php
                                $triggerDate = \Carbon\Carbon::parse($fest->activation_date)->subDays(3);
                            @endphp
                            <tr>
                                <td class="font-weight-bold text-dark">{{ $fest->title }}</td>
                                <td>{{ \Carbon\Carbon::parse($fest->activation_date)->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge-soft badge-soft-warning">
                                        <i class="fa fa-bell"></i> {{ $triggerDate->format('M d, Y') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted border-0">No upcoming festivals found. AI campaigns are idle.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Social Media Poster Logs -->
        <div class="col-md-5 mb-4">
            <div class="table-panel">
                <div class="table-panel-header">
                    <div class="table-panel-title">
                        <div class="table-icon-wrapper icon-social">
                            <i class="fa-brands fa-twitter"></i>
                        </div>
                        Social Media Bot Log
                    </div>
                </div>
                <div>
                    <div class="px-4 py-3 bg-light border-bottom text-muted" style="font-size: 0.85rem;">
                        Recent AI-generated posts sent to your connected accounts.
                    </div>
                    <ul class="log-list">
                        <!-- Mock data for demonstration -->
                        <li class="log-item">
                            <div class="log-time"><i class="fa-regular fa-clock mr-1"></i> Today, 2:00 PM</div>
                            <div class="log-content">"Turn your ideas into stunning designs with Artera's new AI features! 🚀🎨 #SaaS #DesignTools"</div>
                        </li>
                        <li class="log-item">
                            <div class="log-time"><i class="fa-regular fa-clock mr-1"></i> Yesterday, 2:00 PM</div>
                            <div class="log-content">"Tired of manual templates? Let our AI generate your next ad in 5 seconds. 🔥 #Marketing #AI"</div>
                        </li>
                    </ul>
                    <div class="p-4 border-top text-center">
                        <button class="btn btn-outline-primary btn-block" style="border-radius: 8px; font-weight: 600;"><i class="fa-solid fa-link mr-1"></i> Connect Social Accounts</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
