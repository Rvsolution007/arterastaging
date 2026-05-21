@extends('layouts.app')

@section('extra_css')
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

    .docs-container {
        font-family: 'Inter', sans-serif;
        padding: 2rem;
        background-color: #f8fafc;
        min-height: 100vh;
    }

    .docs-header {
        background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        color: white;
        padding: 3rem 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
    }

    .docs-title {
        font-weight: 800;
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }

    .docs-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        max-width: 800px;
    }

    .phase-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .phase-header {
        background: #f1f5f9;
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .phase-title {
        color: #1e293b;
        font-weight: 700;
        font-size: 1.4rem;
        margin: 0;
    }

    .task-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .task-item {
        padding: 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 1.5rem;
    }

    .task-item:last-child {
        border-bottom: none;
    }

    .task-left h5 {
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
    }

    .task-desc {
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .task-right {
        background: #f8fafc;
        border-radius: 8px;
        padding: 1.25rem;
        border: 1px dashed #cbd5e1;
    }

    .step-badge {
        display: inline-block;
        background: #e0e7ff;
        color: #4338ca;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
    }

    .live-link {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .live-link:hover {
        text-decoration: underline;
    }

    .step-list {
        margin-top: 0.75rem;
        padding-left: 1.2rem;
        font-size: 0.85rem;
        color: #475569;
    }

    .step-list li {
        margin-bottom: 0.25rem;
    }

    code {
        background: #e2e8f0;
        color: #ef4444;
        padding: 0.1rem 0.3rem;
        border-radius: 4px;
        font-size: 0.8rem;
    }
</style>
@endsection

@section('content')
<div class="docs-container">
    <div class="docs-header">
        <h1 class="docs-title">Low-Staff SaaS Automation Architecture</h1>
        <p class="docs-subtitle">Master reference guide for all 40 automated tasks across 8 deployment phases. Use this document to understand what features exist, where to find them, and how to verify their operations.</p>
    </div>

    <!-- Phase 1 -->
    <div class="phase-card">
        <div class="phase-header">
            <h2 class="phase-title">Phase 1: Core Infrastructure & Stability</h2>
        </div>
        <ul class="task-list">
            <li class="task-item">
                <div class="task-left">
                    <h5>Task 1-3: AI Ticketing System</h5>
                    <p class="task-desc">An automated support ticket system to handle customer inquiries efficiently without manual email sorting.</p>
                </div>
                <div class="task-right">
                    <span class="step-badge">Live Example</span>
                    <br>
                    <a href="{{ route('admin.tickets') ?? '#' }}" class="live-link"><i class="fa fa-external-link-alt"></i> /admin/tickets</a>
                    <ul class="step-list">
                        <li>Users create tickets via API/App.</li>
                        <li>Admin responds via the AJAX-powered UI.</li>
                        <li>Logs sentiment score automatically.</li>
                    </ul>
                </div>
            </li>
            <li class="task-item">
                <div class="task-left">
                    <h5>Task 4-5: AI FAQ / Knowledge Base & Bot</h5>
                    <p class="task-desc">Self-serve support features where users can find answers without contacting human agents.</p>
                </div>
                <div class="task-right">
                    <span class="step-badge">Live Example</span>
                    <ul class="step-list">
                        <li>Front-end users chat with the AI Bot in the Flutter App.</li>
                        <li>It queries the FAQ DB before opening a ticket.</li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>

    <!-- Phase 2 -->
    <div class="phase-card">
        <div class="phase-header">
            <h2 class="phase-title">Phase 2: AI Support & Realtime Systems</h2>
        </div>
        <ul class="task-list">
            <li class="task-item">
                <div class="task-left">
                    <h5>Task 6-8: WebSocket & Smart Notifications</h5>
                    <p class="task-desc">Real-time chat for support tickets and an engine that schedules push notifications.</p>
                </div>
                <div class="task-right">
                    <span class="step-badge">Testing Steps</span>
                    <ul class="step-list">
                        <li>Run <code>php artisan websockets:serve</code></li>
                        <li>Open a ticket and send a message. It updates without refreshing.</li>
                        <li>Run <code>php artisan push:smart-optimize</code> to queue notifications based on user active hours.</li>
                    </ul>
                </div>
            </li>
            <li class="task-item">
                <div class="task-left">
                    <h5>Task 9-10: Churn Prediction & Event Automation</h5>
                    <p class="task-desc">System scores users on churn risk (0-100) and triggers backend listeners automatically.</p>
                </div>
                <div class="task-right">
                    <span class="step-badge">Live Example</span>
                    <br>
                    <a href="{{ url('admin/churn-analytics') }}" class="live-link"><i class="fa fa-external-link-alt"></i> /admin/churn-analytics</a>
                </div>
            </li>
        </ul>
    </div>

    <!-- Phase 3 -->
    <div class="phase-card">
        <div class="phase-header">
            <h2 class="phase-title">Phase 3: Retention & Customer Journey</h2>
        </div>
        <ul class="task-list">
            <li class="task-item">
                <div class="task-left">
                    <h5>Task 11-15: AI Journey & Winback Flow</h5>
                    <p class="task-desc">Automatically routes users through tailored experiences, tracks onboarding in Flutter, and requests NPS feedback.</p>
                </div>
                <div class="task-right">
                    <span class="step-badge">Live Example</span>
                    <br>
                    <a href="{{ url('admin/journey') }}" class="live-link"><i class="fa fa-external-link-alt"></i> /admin/journey</a>
                    <ul class="step-list">
                        <li>Check the visual node graph to see how users flow.</li>
                        <li>Features are suggested via API automatically.</li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>

    <!-- Phase 4 -->
    <div class="phase-card">
        <div class="phase-header">
            <h2 class="phase-title">Phase 4: Revenue & Subscription Scaling</h2>
        </div>
        <ul class="task-list">
            <li class="task-item">
                <div class="task-left">
                    <h5>Task 16-20: Dynamic Pricing & Payment Analytics</h5>
                    <p class="task-desc">Offers automated discounts to high-churn risks, and provides advanced MRR/ARR dashboards.</p>
                </div>
                <div class="task-right">
                    <span class="step-badge">Live Example</span>
                    <br>
                    <a href="{{ url('admin/payment-analytics') }}" class="live-link"><i class="fa fa-external-link-alt"></i> /admin/payment-analytics</a>
                    <ul class="step-list">
                        <li>Monitor LTV and monthly recurring revenue here.</li>
                        <li>Dynamic discounts are automatically assigned to users in the API logic.</li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>

    <!-- Phase 5 -->
    <div class="phase-card">
        <div class="phase-header">
            <h2 class="phase-title">Phase 5: Marketing & Lead Gen Automation</h2>
        </div>
        <ul class="task-list">
            <li class="task-item">
                <div class="task-left">
                    <h5>Task 21-25: AI Blog Generator & Campaigns</h5>
                    <p class="task-desc">Generates SEO blog posts via AI, tracks high-intent leads, and automates marketing campaigns.</p>
                </div>
                <div class="task-right">
                    <span class="step-badge">Live Example</span>
                    <br>
                    <a href="{{ url('admin/blogs') }}" class="live-link"><i class="fa fa-external-link-alt"></i> /admin/blogs</a><br>
                    <a href="{{ url('admin/leads') }}" class="live-link"><i class="fa fa-external-link-alt"></i> /admin/leads</a><br>
                    <a href="{{ url('admin/ai-campaigns') }}" class="live-link"><i class="fa fa-external-link-alt"></i> /admin/ai-campaigns</a>
                    <ul class="step-list">
                        <li>Click "Generate AI Blog" to auto-create content.</li>
                        <li>Leads are scored 1-100 automatically based on profile completion.</li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>

    <!-- Phase 6 -->
    <div class="phase-card">
        <div class="phase-header">
            <h2 class="phase-title">Phase 6: Partner/Affiliate Ecosystem</h2>
        </div>
        <ul class="task-list">
            <li class="task-item">
                <div class="task-left">
                    <h5>Task 26-30: AI Toolkit & Fraud Detection</h5>
                    <p class="task-desc">Affiliates can generate AI promos, view leaderboards, while system detects fake signups.</p>
                </div>
                <div class="task-right">
                    <span class="step-badge">Live Example</span>
                    <br>
                    <a href="{{ url('partner/toolkit') }}" class="live-link"><i class="fa fa-external-link-alt"></i> /partner/toolkit</a><br>
                    <a href="{{ url('admin/partner-leaderboard') }}" class="live-link"><i class="fa fa-external-link-alt"></i> /admin/partner-leaderboard</a>
                </div>
            </li>
        </ul>
    </div>

    <!-- Phase 7 -->
    <div class="phase-card">
        <div class="phase-header">
            <h2 class="phase-title">Phase 7: Platform Gamification & Engagement</h2>
        </div>
        <ul class="task-list">
            <li class="task-item">
                <div class="task-left">
                    <h5>Task 31-35: Badges, Streaks & Design Challenges</h5>
                    <p class="task-desc">Automated reward system for daily logins, design contests, and year-in-review emails.</p>
                </div>
                <div class="task-right">
                    <span class="step-badge">Live Example</span>
                    <br>
                    <a href="{{ url('admin/challenges') }}" class="live-link"><i class="fa fa-external-link-alt"></i> /admin/challenges</a>
                    <ul class="step-list">
                        <li>Create a challenge, and users submit via mobile app.</li>
                        <li>Run <code>php artisan emails:milestones</code> to send anniversary emails.</li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>

    <!-- Phase 8 -->
    <div class="phase-card">
        <div class="phase-header">
            <h2 class="phase-title">Phase 8: Advanced Analytics & Monitoring</h2>
        </div>
        <ul class="task-list">
            <li class="task-item">
                <div class="task-left">
                    <h5>Task 36-40: The God View & Auto-Scaling</h5>
                    <p class="task-desc">Ultimate server monitoring, AI anomaly detection (API Token blocking), and competitor web scraping.</p>
                </div>
                <div class="task-right">
                    <span class="step-badge">Live Example</span>
                    <br>
                    <a href="{{ route('admin.god_view') }}" class="live-link"><i class="fa fa-external-link-alt"></i> /admin/god-view</a>
                    <ul class="step-list">
                        <li>Run <code>php artisan system:monitor</code> for server health.</li>
                        <li>Run <code>php artisan ai:detect-anomalies</code> to catch token abuse.</li>
                        <li>Run <code>php artisan competitor:track</code> to check competitor sites.</li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
    
</div>
@endsection
