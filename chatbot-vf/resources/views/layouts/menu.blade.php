<div class="sidebar-nav">
    @can('manage_dashboard')
    <li class="{{ Request::is('/') ? 'active' : '' }}" title="{{ __('messages.menu_dashboard') }}">
        <a href="/">
            <span class="mdi mdi-chart-line"></span>
            <span class="text-menu-item">{{ __('messages.menu_dashboard') }}</span>
        </a>
    </li>
    @endcan
    @can('manage_clients')
    <li class="{{ Request::is('customers*') ? 'active' : '' }}" title="Clientes">
        <a href="customers">
            <span class="mdi mdi-city"></span>
            <span class="text-menu-item">Clientes</span>
        </a>
    </li>
    @endcan
    @can('manage_chatbots')
    <li class="{{ Request::is('chatbots*') || Request::is('scriptTester*') ? 'active' : '' }}" title="{{ __('messages.chatbots') }}">
        <a href="chatbots">
            <span class="mdi mdi-monitor-account"></span>
            <span class="text-menu-item">{{ __('messages.chatbots') }}</span>
        </a>
    </li>
    @endcan
    @can('manage_supervised_training')
    <li class="{{ Request::is('supervised_training*') ? 'active' : '' }}" title="Entrenamiento supervisado">
        <a href="supervised_training">
            <span class="mdi mdi-head-snowflake-outline"></span>
            <span class="text-menu-item sin-abbrev">Entrenamiento supervisado</span>
            <span class="text-menu-abbrev">Entrenam. supervisado</span>
        </a>
    </li>
    @endcan
    @can('manage_manual_training')
    <li class="{{ Request::is('supervised_manual*') ? 'active' : '' }}" title="Entrenamiento manual">
        <a href="supervised_manual">
            <span class="mdi mdi-update"></span>
            <span class="text-menu-item sin-abbrev">Entrenamiento manual</span>
            <span class="text-menu-abbrev">Entrenam. manual</span>
        </a>
    </li>
    @endcan

    @can('manage_conversations')
    <li class="{{ Request::is('conversations*') ? 'active' : '' }}" title="{{ __('messages.conversations') }}">
        <a href="conversations">
            <span class="mdi mdi-wechat"></span>
            <span class="text-menu-item">{{ __('messages.conversations') }}</span>
        </a>
    </li>
    @endcan
    <div class="dropdown-divider"></div>
    @can('manage_settings')
    <li class="{{ Request::is('settings*') ? 'active' : '' }}" title="General">
        <a href="settings">
            <span class="mdi mdi-cog"></span>
            <span class="text-menu-item">General</span>
        </a>
    </li>
    @endcan
    @can('manage_users')
    <li class="{{ Request::is('users*') ? 'active' : '' }}" title="{{ __('messages.users') }}">
        <a href="users">
            <span class="mdi mdi-account-group-outline"></span>
            <span class="text-menu-item">{{ __('messages.users') }}</span>
        </a>
    </li>
    @endcan
    @can('manage_roles')
    <li class="{{ Request::is('roles*') ? 'active' : '' }}" title="Roles">
        <a href="roles">
            <span class="mdi mdi-account-cog-outline"></span>
            <span class="text-menu-item">Roles</span>
        </a>
    </li>
    @endcan
</div>


<style>
.sidebar-nav>li {
    text-indent: 4px;
    line-height: 42px;
}

.sidebar-nav>li a {
    display: block;
    text-decoration: none;
    font-weight: 500;
    color: #9899ac;
    font-size: 16px;
}

.sidebar-nav>li>a:hover {
    text-decoration: none;
    color: #9899ac !important;
    background: #f7f7f7 !important;
}

.sidebar-nav>li.active>a {
    text-decoration: none;
    color: #fff !important;
    background: var(--primary-color) !important;
}

.sidebar-nav>li>a span.mdi {
    font-size: 25px;
    width: 80px;
}

.text-menu-abbrev {
display: none
}


@media (max-width: 822px) {
    .text-menu-item {
        display: none;
    }

    .text-menu-abbrev {
        display: none;
    }


}

@media (min-width: 822px) and (max-width: 992px) {
    .sidebar-nav>li>a{
        font-size: 12.6px!important
    }

    .sidebar-nav>li{
        text-indent: 4px !important
    }

    .sidebar-nav>li>a span.mdi{
        font-size: 20px !important
    }

    .text-menu-abbrev {
        display: inline
    }
    .sin-abbrev {
        display: none;
    }
}
</style>
