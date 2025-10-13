@php
$isSuperAdmin = auth()->user()->hasRole('superadmin');
//echo "asasasa".$isSuperAdmin;
// dd(auth()->user()->roles->pluck('name'));


@endphp
<aside class="app-sidebar sticky" id="sidebar">

    <!-- Start::main-sidebar-header -->
    <div class="main-sidebar-header">
        @include('layouts.core.logo')
    </div>
    <!-- End::main-sidebar-header -->

    <!-- Start::main-sidebar -->
    <div class="main-sidebar" id="sidebar-scroll">

        <!-- Start::nav -->
        <nav class="main-menu-container nav nav-pills flex-column sub-open">
            <div class="slide-left" id="slide-left">
                <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path>
                </svg>
            </div>
            <ul class="main-menu">
                <!-- Start::slide__category -->
                <li class="slide__category"><span class="category-name">Menus</span></li>
                <!-- End::slide__category -->

                <!-- Start::slide -->
                <li class="slide {{ request()->routeIs('home') ? 'active' : '' }}">
                    <a href="{{ route('home') }}" class="side-menu__item  {{ request()->routeIs('home') ? 'active' : '' }}">
                        <i class="side-menu__icon fa-solid fa-house-flag "></i>
                        <span class="side-menu__label">Dashboard</span>

                    </a>
                </li>
                <!-- End::slide -->

                <!-- Start::slide -->
                @if($isSuperAdmin || auth()->user()->hasAnyDirectPermission(['view-user', 'view-role', 'list-user', 'list-role']))
                <li class="slide has-sub {{ request()->is('users*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item {{ request()->is('users*') ? 'active' : '' }}">
                        <i class="side-menu__icon fa-solid fa-user-tie "></i>
                        <span class="side-menu__label ">User's</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        @if($isSuperAdmin || auth()->user()->getAllPermissions()->whereIn('name', ['view-user', 'list-user'])->count() > 0)
                        <li class="slide">
                            <a href="{{route('user_index')}}" class="side-menu__item {{ request()->is('users/user*') ? 'active' : '' }}">User</a>
                        </li>
                        @endif
                        @if($isSuperAdmin || auth()->user()->getAllPermissions()->whereIn('name', ['view-role', 'list-role'])->count() > 0)
                        <li class="slide">
                            <a href="{{route('role_index')}}" class="side-menu__item {{ request()->is('users/role*') ? 'active' : '' }}">Role</a>
                        </li>
                        @endif
                    </ul>
                </li>
                @endif
                <!-- End::slide -->

                <li class="slide has-sub {{ request()->is('job_order*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item {{ request()->is('job_order*') ? 'active open' : '' }}">
                        <i class="side-menu__icon fa-solid fa-folder-open"></i>
                        <span class="side-menu__label ">Job Order</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide">
                            <a href="{{ route('job_order_add') }}" class="side-menu__item {{ request()->routeIs('job_order_add') ? 'active' : '' }}">Entry</a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('job_order_master') }}" class="side-menu__item {{ request()->routeIs('job_order_master') ? 'active' : '' }}">Master</a>
                        </li>
                    </ul>
                </li>

                <!-- Start::slide -->
                <li class="slide has-sub {{ request()->is('po*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item {{ request()->is('po*') ? 'active' : '' }}">
                        <i class="side-menu__icon fa-solid fa-file-pdf"></i>
                        <span class="side-menu__label ">Purchase Order</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide">
                            <a href="{{ route('pdf_extract_add') }}" class="side-menu__item {{ request()->routeIs('pdf_extract_add') ? 'active' : '' }}">Upload</a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('pdf_extract_all_master') }}" class="side-menu__item {{ request()->routeIs('pdf_extract_all_master') || request()->routeIs('pdf_extract_amended_master')? 'active' : '' }}">Master</a>
                        </li>
                    </ul>
                </li>
                <!-- End::slide -->

                <li class="slide has-sub {{ request()->is('packing_list*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item {{ request()->is('packing_list*') ? 'active open' : '' }}">
                        <i class="side-menu__icon fa-solid fa-list-check"></i>
                        <span class="side-menu__label ">Packing Lists</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide">
                            <a href="{{ route('packing_list_config') }}" class="side-menu__item {{ request()->routeIs('packing_list_config') ? 'active' : '' }}">Config</a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('packing_list_auto') }}" class="side-menu__item {{ request()->routeIs('packing_list_auto') ? 'active' : '' }}">Auto Packaging</a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('packing_list_add') }}" class="side-menu__item {{ request()->routeIs('packing_list_add') ? 'active' : '' }}">Entry</a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('packing_list_master') }}" class="side-menu__item {{ request()->routeIs('packing_list_master') ? 'active' : '' }}">Master</a>
                        </li>
                    </ul>
                </li>

                <li class="slide has-sub {{ request()->is('invoice*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item {{ request()->is('invoice*') ? 'active open' : '' }}">
                        <i class="side-menu__icon fa-solid fa-file-invoice"></i>
                        <span class="side-menu__label ">Invoice</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide">
                            <a href="{{ route('invoice_genrate') }}" class="side-menu__item {{ request()->routeIs('invoice_genrate') ? 'active' : '' }}">Generate</a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('invoice_master') }}" class="side-menu__item {{ request()->routeIs('invoice_master') ? 'active' : '' }}">Master</a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('grn_entry') }}" class="side-menu__item {{ request()->routeIs('grn_entry') ? 'active' : '' }}">GRN Entry</a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('e_invoice_master') }}" class="side-menu__item {{ request()->routeIs('e_invoice_master') ? 'active' : '' }}">E-invoice Download</a>
                        </li>
                    </ul>
                </li>

                <!-- Start::slide -->
                <li class="slide has-sub {{ request()->is('report*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item {{ request()->is('report*') ? 'active' : '' }}">
                        <i class="side-menu__icon fa-solid fa-chart-line"></i>
                        <span class="side-menu__label ">Report</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        <li class="slide">
                            <a href="{{ route('dispatch_status_report_master') }}" class="side-menu__item {{ request()->routeIs('dispatch_status_report_master') ? 'active' : '' }}">Dispatch Status</a>
                        </li>
                        <li class="slide">
                            <a href="{{ route('daily_packing_report_master') }}" class="side-menu__item {{ request()->routeIs('daily_packing_report_master') ? 'active' : '' }}">Daily Packing</a>
                        </li>
                    </ul>
                </li>
                <!-- End::slide -->

                @if($isSuperAdmin || auth()->user()->hasAnyDirectPermission(['create-vendor', 'list-vendor', 'view-vendor']))
                <li class="slide has-sub {{ request()->is('settings*') ? 'active open' : '' }}">
                    <a href="javascript:void(0);" class="side-menu__item {{ request()->is('settings*') ? 'active' : '' }}">
                        <i class="side-menu__icon fa-solid fa-gears "></i>
                        <span class="side-menu__label ">Settings</span>
                        <i class="fe fe-chevron-right side-menu__angle"></i>
                    </a>
                    <ul class="slide-menu child1">
                        @if($isSuperAdmin || auth()->user()->hasAnyDirectPermission(['create-vendor', 'list-vendor', 'create-vendor-carton', 'list-vendor-carton']))
                        <li class="slide has-sub {{ request()->is('settings/vendor*') ? 'active open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ request()->is('settings/vendor*') ? 'active' : '' }}">
                                Vendor
                                <i class="fe fe-chevron-right side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child2">
                                @if($isSuperAdmin || auth()->user()->hasAnyDirectPermission(['create-vendor', 'list-vendor', 'view-vendor']))
                                <li class="slide">
                                    <a href="{{route('vendor_index')}}" class="side-menu__item {{ request()->routeIs('vendor_index') ? 'active' : '' }}">Master</a>
                                </li>
                                @endif
                                @if($isSuperAdmin || auth()->user()->hasAnyDirectPermission(['create-vendor-carton', 'list-vendor-carton', 'view-vendor-carton']))
                                <li class="slide">
                                    <a href="{{route('carton_jack_master')}}" class="side-menu__item {{ request()->routeIs('carton_jack_master') ? 'active' : '' }}">Carton</a>
                                </li>
                                @endif
                                @if($isSuperAdmin || auth()->user()->hasAnyDirectPermission(['create-vendor', 'list-vendor', 'view-vendor']))
                                <li class="slide">
                                    <a href="{{route('size_chart_master')}}" class="side-menu__item {{ request()->routeIs('size_chart_master') ? 'active' : '' }}">Size Chart</a>
                                </li>
                                @endif
                            </ul>
                        </li>
                        @endif
                        <li class="slide">
                            <a href="{{ route('transport_master') }}" class="side-menu__item {{ request()->routeIs('transport_master') ? 'active' : '' }}">Transport</a>
                        </li>
                    </ul>
                </li>
                @endif
            </ul>
            <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24">
                    <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path>
                </svg></div>
        </nav>
        <!-- End::nav -->

    </div>
    <!-- End::main-sidebar -->

</aside>