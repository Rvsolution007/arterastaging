<div class="mb-4">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/payment-analytics*') ? 'active font-weight-bold' : 'text-muted' }}" href="{{ url('admin/payment-analytics') }}">
                <i class="fa fa-chart-pie mr-1"></i> Payment Analytics
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/invoices-history*') ? 'active font-weight-bold' : 'text-muted' }}" href="{{ route('admin.invoices.index') }}">
                <i class="fa fa-file-invoice-dollar mr-1"></i> Invoices History
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/retention/discounts*') ? 'active font-weight-bold' : 'text-muted' }}" href="{{ route('admin.retention.discounts') }}">
                <i class="fa fa-tags mr-1"></i> Dynamic Discounts
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/retention/quotas*') ? 'active font-weight-bold' : 'text-muted' }}" href="{{ route('admin.retention.quotas') }}">
                <i class="fa fa-bell mr-1"></i> Quota Alerts
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/retention/winbacks*') ? 'active font-weight-bold' : 'text-muted' }}" href="{{ route('admin.retention.winbacks') }}">
                <i class="fa fa-undo mr-1"></i> Winbacks
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/retention/settings*') ? 'active font-weight-bold' : 'text-muted' }}" href="{{ route('admin.retention.settings') }}">
                <i class="fa fa-cogs mr-1"></i> AI Settings
            </a>
        </li>
    </ul>
</div>
