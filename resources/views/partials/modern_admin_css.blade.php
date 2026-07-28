<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');
.modern-ui-wrapper {
    font-family: 'Poppins', sans-serif;
    padding: 1.5rem;
    background-color: #f8fafc;
    min-height: 100vh;
}
.modern-ui-wrapper h3 {
    font-weight: 700;
    color: #1e293b;
    font-size: 1.5rem;
    letter-spacing: -0.025em;
}
.modern-ui-wrapper .text-muted {
    color: #64748b !important;
}
.modern-ui-wrapper .card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.modern-ui-wrapper .card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    background: #fff;
    font-size: 1.125rem;
    font-weight: 700;
    color: #1e293b;
}
.modern-ui-wrapper .table {
    width: 100%;
    margin: 0;
}
.modern-ui-wrapper .table thead th {
    background: #f8fafc;
    padding: 1rem 1.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #e2e8f0;
    border-top: none;
}
.modern-ui-wrapper .table tbody td {
    padding: 1rem 1.5rem;
    font-size: 0.875rem;
    color: #334155;
    font-weight: 500;
    border-bottom: 1px solid #f1f5f9;
    border-top: none;
    vertical-align: middle;
}
.modern-ui-wrapper .table tbody tr {
    transition: background-color 0.2s ease;
}
.modern-ui-wrapper .table tbody tr:hover {
    background-color: #f8fafc;
}
.modern-ui-wrapper .btn-primary {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    border: none;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3);
    font-weight: 600;
    padding: 0.5rem 1.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
}
.modern-ui-wrapper .btn-primary i { margin: 0 !important; }
.modern-ui-wrapper .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4);
}
.modern-ui-wrapper .btn-outline-primary {
    border-color: #6366f1;
    color: #6366f1;
    background-color: transparent;
    border-radius: 8px;
    font-weight: 600;
    padding: 0.5rem 1.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    transition: all 0.2s ease;
}
.modern-ui-wrapper .btn-outline-primary i { margin: 0 !important; }
.modern-ui-wrapper .btn-outline-primary:hover {
    background: #6366f1;
    color: #fff;
}
.modern-ui-wrapper .form-control {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.4rem 0.75rem;
    font-size: 0.875rem;
    color: #334155;
    transition: all 0.2s ease;
    background-color: #f8fafc;
}
.modern-ui-wrapper .form-control:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    background-color: #ffffff;
}
.modern-ui-wrapper label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #64748b;
}

/* Action Buttons in a Single Row */
.modern-ui-wrapper .table td.text-right {
    white-space: nowrap;
}
.modern-ui-wrapper form.d-inline {
    display: inline-block;
    margin: 0;
}
.modern-ui-wrapper .table td .btn {
    padding: 0.35rem 0.85rem !important;
    font-size: 0.8rem !important;
    border-radius: 6px !important;
    margin-left: 0.35rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 34px;
}
.modern-ui-wrapper .btn-outline-danger {
    border-color: #ef4444;
    color: #ef4444;
    background: transparent;
    transition: all 0.2s ease;
}
.modern-ui-wrapper .btn-outline-danger:hover {
    background: #ef4444;
    color: white;
}

/* Header actions container */
.modern-ui-wrapper .d-flex.justify-content-between {
    flex-wrap: wrap;
    gap: 1rem;
}
.modern-ui-wrapper .d-flex.justify-content-between > div:not(:first-child) {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}
.modern-ui-wrapper .d-flex.justify-content-between > div:not(:first-child) .btn {
    margin: 0 !important;
}
</style>
