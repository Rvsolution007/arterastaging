@extends("layouts.app")

@section('extra_css')
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

.ticket-container {
    font-family: 'Poppins', sans-serif;
    padding: 1.5rem;
    background-color: #f8fafc;
    min-height: 100vh;
}

.page-title {
    font-weight: 700;
    color: #1e293b;
    font-size: 1.5rem;
    letter-spacing: -0.025em;
}

/* KPI Cards Styling (from AI Analytics) */
.kpi-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid rgba(226, 232, 240, 0.8);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
}

.kpi-card.requests::before { background: linear-gradient(to right, #fbbf24, #f59e0b); }
.kpi-card.tokens::before { background: linear-gradient(to right, #38bdf8, #0ea5e9); }
.kpi-card.cost::before { background: linear-gradient(to right, #34d399, #10b981); }

.kpi-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.03);
}

.kpi-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.kpi-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    font-size: 1rem;
}

.requests .kpi-icon { background: #fef3c7; color: #d97706; }
.tokens .kpi-icon { background: #e0f2fe; color: #0284c7; }
.cost .kpi-icon { background: #d1fae5; color: #059669; }

.kpi-value {
    font-size: 1.875rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    line-height: 1.2;
}

.font-math {
    font-variant-numeric: tabular-nums;
    font-family: 'Poppins', sans-serif;
}

/* Panels Styling */
.table-panel {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    margin-bottom: 1.5rem;
    display: flex;
    flex-direction: column;
}

.table-panel-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: #f8fafc;
}

.table-panel-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.table-icon-wrapper {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.icon-feature { background: #fee2e2; color: #e11d48; }
.icon-model { background: #e0e7ff; color: #4338ca; }

/* Tickets Layout */
.ticket-wrapper { display: flex; height: 600px; gap: 20px; }
.ticket-sidebar { width: 350px; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.03); }
.ticket-sidebar-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; gap: 0.75rem; }
.ticket-list { overflow-y: auto; flex: 1; }
.ticket-item { padding: 15px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.2s; }
.ticket-item:hover { background: #f8fafc; }
.ticket-item.active { background: #eff6ff; border-left: 3px solid #4f46e5; }
.ticket-title { font-weight: 600; font-size: 14px; color: #1e293b; margin-bottom: 5px; }
.ticket-meta { font-size: 12px; color: #64748b; display: flex; justify-content: space-between; align-items: center;}

.badge-soft { padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
.badge-open { background: #fef3c7; color: #d97706; }
.badge-in_progress { background: #dbeafe; color: #2563eb; }
.badge-ai_resolved { background: #dcfce7; color: #16a34a; }
.badge-closed { background: #f1f5f9; color: #64748b; }

.ticket-main { flex: 1; display: flex; flex-direction: column; }
.ticket-main-header { display: flex; justify-content: space-between; align-items: center; }
.ticket-main-body { flex: 1; padding: 20px; overflow-y: auto; background: #ffffff; display: flex; flex-direction: column; gap: 15px; }

/* Chat Bubbles */
.chat-bubble { max-width: 75%; padding: 12px 16px; border-radius: 16px; font-size: 14px; line-height: 1.5; position: relative; font-family: 'Poppins', sans-serif; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
.chat-user { align-self: flex-start; background: #f8fafc; border: 1px solid #e2e8f0; color: #334155; border-bottom-left-radius: 4px; }
.chat-admin { align-self: flex-end; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #fff; border-bottom-right-radius: 4px; }
.chat-ai { align-self: flex-start; background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; border-bottom-left-radius: 4px; }
.chat-internal { align-self: flex-end; background: #fef3c7; color: #b45309; border: 1px solid #fde68a; border-bottom-right-radius: 4px; }

.chat-meta { font-size: 10px; margin-top: 5px; opacity: 0.7; text-align: right; }

.ticket-main-footer { padding: 15px; border-top: 1px solid #e2e8f0; background: #f8fafc; }
.reply-box { display: flex; gap: 10px; align-items: flex-end; }
.reply-input { flex: 1; min-height: 80px; padding: 12px; border: 1px solid #cbd5e1; border-radius: 12px; resize: none; font-family: 'Poppins', sans-serif; font-size: 14px; background: #fff; }
.reply-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
.reply-actions { display: flex; flex-direction: column; gap: 8px; }
.btn-reply { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #fff; border: none; padding: 10px 16px; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.85rem; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3); transition: all 0.2s ease; }
.btn-reply:hover { transform: translateY(-1px); box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4); }
.btn-internal { background: #f59e0b; color: #fff; border: none; padding: 8px 16px; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.75rem; transition: all 0.2s ease; }

/* AI Helper Panel */
.ai-helper { margin-bottom: 12px; padding: 12px; background: #eff6ff; border: 1px dashed #93c5fd; border-radius: 12px; display: none; }
.ai-helper-title { font-size: 11px; font-weight: 700; color: #1d4ed8; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em; }
.ai-suggestion { font-size: 13px; color: #1e3a8a; font-style: italic; cursor: pointer; font-weight: 500; }
.ai-suggestion:hover { text-decoration: underline; }

.empty-state { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #94a3b8; }
.empty-state i { font-size: 48px; margin-bottom: 10px; color: #cbd5e1; }
</style>
@endsection

@section("content")
<div class="analytics-container">
    <!-- Header Section -->
    <div class="row align-items-center mb-4">
        <div class="col-md-5">
            <h4 class="page-title mb-0"><i class="fa-solid fa-headset mr-2 text-primary"></i> AI Support Tickets</h4>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-4 mb-4 mb-md-0">
            <div class="kpi-card requests">
                <div class="kpi-title">
                    <div class="kpi-icon"><i class="fa-solid fa-envelope-open"></i></div>
                    Open Tickets
                </div>
                <h3 class="kpi-value font-math">{{ $stats['open'] }}</h3>
            </div>
        </div>
        <div class="col-xl-4 col-md-4 mb-4 mb-md-0">
            <div class="kpi-card tokens">
                <div class="kpi-title">
                    <div class="kpi-icon"><i class="fa-solid fa-spinner"></i></div>
                    In Progress
                </div>
                <h3 class="kpi-value font-math">{{ $stats['in_progress'] }}</h3>
            </div>
        </div>
        <div class="col-xl-4 col-md-4">
            <div class="kpi-card cost">
                <div class="kpi-title">
                    <div class="kpi-icon"><i class="fa-solid fa-check-circle"></i></div>
                    AI Resolved
                </div>
                <h3 class="kpi-value font-math text-success">{{ $stats['ai_resolved'] }}</h3>
            </div>
        </div>
    </div>

    <div class="ticket-wrapper">
        <!-- Sidebar List -->
        <div class="ticket-sidebar">
            <div class="ticket-sidebar-header">
                <div class="table-icon-wrapper icon-feature">
                    <i class="fa-solid fa-list-ul"></i>
                </div>
                <h5 class="table-panel-title" style="font-size: 1rem;">Recent Tickets</h5>
            </div>
            <div class="ticket-list">
                @foreach($tickets as $t)
                <div class="ticket-item" onclick="loadTicket({{ $t->id }}, this)">
                    <div class="ticket-title">
                        @if($t->ai_rating && $t->ai_rating <= 3)
                            <span class="badge badge-danger mr-1" style="font-size: 0.65rem;" title="Generated from In-App Feedback Loop"><i class="fa fa-star text-white"></i> NPS Feedback</span>
                        @endif
                        #{{ $t->id }} - {{ Str::limit($t->subject, 30) }}
                    </div>
                    <div class="ticket-meta">
                        <span class="font-weight-bold" style="color: #475569;">{{ $t->user->name ?? 'Unknown User' }}</span>
                        <span class="badge-soft badge-{{ $t->status }}">{{ str_replace('_', ' ', $t->status) }}</span>
                    </div>
                    <div style="font-size: 11px; margin-top: 6px; color: #94a3b8; display: flex; justify-content: space-between;">
                        <span><i class="fa-regular fa-clock"></i> {{ $t->updated_at->diffForHumans() }}</span>
                        <span>
                            @if($t->ai_rating)
                                <span style="color: #f59e0b; font-weight: 700;"><i class="fa-solid fa-star"></i> {{ $t->ai_rating }}/5 Rated</span>
                            @else
                                Sentiment: <strong style="color: {{ $t->sentiment_score < 4 ? '#ef4444' : '#10b981' }}">{{ $t->sentiment_score }}/10</strong>
                            @endif
                        </span>
                    </div>
                </div>
                @endforeach
                
                @if($tickets->isEmpty())
                <div class="p-4 text-center text-muted">No tickets found.</div>
                @endif
            </div>
        </div>

        <!-- Main View -->
        <div class="table-panel ticket-main" id="ticket-main">
            <div class="empty-state">
                <i class="fa-solid fa-comments"></i>
                <h5>Select a ticket to view conversation</h5>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
let currentTicketId = null;

function loadTicket(id, element) {
    $('.ticket-item').removeClass('active');
    $(element).addClass('active');
    currentTicketId = id;
    
    $('#ticket-main').html('<div class="empty-state"><i class="fa-solid fa-spinner fa-spin text-primary"></i><h5>Loading...</h5></div>');

    // FIX: Using absolute URL to prevent 404 in subdirectory installations like XAMPP /Artera/
    $.get('{{ url("admin/tickets") }}/' + id, function(data) {
        let t = data.ticket;
        let msgs = data.messages;
        let suggestion = data.suggested_reply;

        let html = `
            <div class="table-panel-header ticket-main-header">
                <div class="d-flex align-items-center gap-3" style="gap: 15px;">
                    <div class="table-icon-wrapper icon-model">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h5 class="table-panel-title">#${t.id} - ${t.subject}</h5>
                        <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">User: <strong class="text-dark">${t.user ? t.user.name : 'Unknown'}</strong>
                        ${t.ai_rating ? `<span class="ml-3" style="color: #f59e0b; font-weight: bold;"><i class="fa-solid fa-star"></i> ${t.ai_rating}/5 User Rating</span>` : ''}
                        </div>
                    </div>
                </div>
                <div>
                    <select class="form-control" style="border-radius: 8px; font-size: 0.85rem; font-weight: 500;" onchange="updateStatus(${t.id}, this.value)">
                        <option value="open" ${t.status == 'open' ? 'selected' : ''}>Open</option>
                        <option value="in_progress" ${t.status == 'in_progress' ? 'selected' : ''}>In Progress</option>
                        <option value="closed" ${t.status == 'closed' ? 'selected' : ''}>Closed</option>
                        <option value="ai_resolved" ${t.status == 'ai_resolved' ? 'selected' : ''}>AI Resolved</option>
                    </select>
                </div>
            </div>
            <div class="ticket-main-body" id="chat-body">
        `;

        msgs.forEach(m => {
            let cls = 'chat-user';
            let sender = 'User';
            if (m.sender_type == 'admin') { cls = 'chat-admin'; sender = 'Admin'; }
            else if (m.sender_type == 'ai') { cls = 'chat-ai'; sender = 'AI Assistant'; }
            if (m.is_internal_note) { cls = 'chat-internal'; sender = 'Internal Note'; }

            html += `
                <div class="chat-bubble ${cls}">
                    <strong>${sender}</strong><br>
                    ${m.message}
                    <div class="chat-meta">${new Date(m.created_at).toLocaleString()}</div>
                </div>
            `;
        });

        html += `
            </div>
            <div class="ticket-main-footer">
                <div class="ai-helper" id="ai-helper" style="display: block;">
                    <div class="ai-helper-title"><i class="fa-solid fa-wand-magic-sparkles"></i> AI Suggested Reply</div>
                    <div class="ai-suggestion" onclick="useSuggestion('${suggestion.replace(/'/g, "\\'")}')">"${suggestion}"</div>
                </div>
                <div class="reply-box">
                    <textarea class="reply-input" id="reply-text" placeholder="Type a message..."></textarea>
                    <div class="reply-actions">
                        <button class="btn-reply" onclick="sendReply(false)"><i class="fa-solid fa-paper-plane"></i> Send Reply</button>
                        <button class="btn-internal" onclick="sendReply(true)"><i class="fa-solid fa-lock"></i> Add Internal Note</button>
                    </div>
                </div>
            </div>
        `;

        $('#ticket-main').html(html);
        scrollToBottom();
    }).fail(function(jqXHR, textStatus, errorThrown) {
        $('#ticket-main').html('<div class="empty-state text-danger"><i class="fa-solid fa-triangle-exclamation"></i><h5>Error Loading Ticket</h5><p>'+errorThrown+'</p></div>');
    });
}

function useSuggestion(text) {
    $('#reply-text').val(text);
}

function scrollToBottom() {
    let cb = document.getElementById('chat-body');
    if(cb) cb.scrollTop = cb.scrollHeight;
}

function sendReply(isInternal) {
    if(!currentTicketId) return;
    let txt = $('#reply-text').val().trim();
    if(!txt) return;

    let btn = isInternal ? '.btn-internal' : '.btn-reply';
    $(btn).prop('disabled', true);

    $.post('{{ url("admin/tickets/reply") }}/' + currentTicketId, {
        _token: '{{ csrf_token() }}',
        message: txt,
        is_internal: isInternal ? 1 : 0
    }, function(res) {
        $(btn).prop('disabled', false);
        $('#reply-text').val('');
        
        let m = res.message;
        let cls = m.is_internal_note ? 'chat-internal' : 'chat-admin';
        let sender = m.is_internal_note ? 'Internal Note' : 'Admin';

        let mHtml = `
            <div class="chat-bubble ${cls}">
                <strong>${sender}</strong><br>
                ${m.message}
                <div class="chat-meta">Just now</div>
            </div>
        `;
        $('#chat-body').append(mHtml);
        scrollToBottom();
    }).fail(function() {
        $(btn).prop('disabled', false);
        toastr.error('Failed to send reply');
    });
}

function updateStatus(id, status) {
    $.post('{{ url("admin/tickets/status") }}/' + id, {
        _token: '{{ csrf_token() }}',
        status: status
    }, function() {
        toastr.success('Status updated');
    });
}
</script>
@endsection
