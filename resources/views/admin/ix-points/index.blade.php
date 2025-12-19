@extends('admin.layout')

@section('title', 'IX Points')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">IX Points</h2>
                    <p class="mb-0">
                        @if($nodeType)
                            {{ ucfirst($nodeType) }} IX Points
                        @else
                            All Active IX Points
                        @endif
                    </p>
                    <div class="accent-line"></div>
                </div>
                <div>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link {{ !$nodeType ? 'active' : '' }}" href="{{ route('admin.ix-points') }}">
                        All Points ({{ $locations->count() }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $nodeType === 'edge' ? 'active' : '' }}" href="{{ route('admin.ix-points', ['node_type' => 'edge']) }}">
                        Edge Points ({{ $locations->where('node_type', 'edge')->count() }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $nodeType === 'metro' ? 'active' : '' }}" href="{{ route('admin.ix-points', ['node_type' => 'metro']) }}">
                        Metro Points ({{ $locations->where('node_type', 'metro')->count() }})
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- IX Points Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    @if($locations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="color: #2c3e50; font-weight: 600;">Name</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Node Type</th>
                                        <th style="color: #2c3e50; font-weight: 600;">State</th>
                                        <th style="color: #2c3e50; font-weight: 600;">City</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Ports</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Nodal Officer</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Zone</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Applications</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($locations as $location)
                                    @php
                                        $stats = $locationStats[$location->id] ?? ['total_applications' => 0, 'approved_applications' => 0, 'pending_applications' => 0];
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $location->name }}</strong>
                                            @if($location->switch_details)
                                                <br><small class="text-muted">Switch: {{ $location->switch_details }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($location->node_type === 'metro')
                                                <span class="badge bg-success">{{ ucfirst($location->node_type) }}</span>
                                            @else
                                                <span class="badge bg-info">{{ ucfirst($location->node_type) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $location->state }}</td>
                                        <td>{{ $location->city ?? 'N/A' }}</td>
                                        <td>{{ $location->ports ?? 'N/A' }}</td>
                                        <td>{{ $location->nodal_officer ?? 'N/A' }}</td>
                                        <td>{{ $location->zone ?? 'N/A' }}</td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <span><strong>Total:</strong> {{ $stats['total_applications'] }}</span>
                                                <span class="text-success"><strong>Approved:</strong> {{ $stats['approved_applications'] }}</span>
                                                <span class="text-warning"><strong>Pending:</strong> {{ $stats['pending_applications'] }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($location->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">No IX points found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
