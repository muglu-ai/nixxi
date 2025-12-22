@extends('admin.layout')

@section('title', 'IX Point Details')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ $location->name }}</h2>
                    <p class="mb-0">IX Point Details</p>
                    <div class="accent-line"></div>
                </div>
                <div>
                    <a href="{{ route('admin.ix-points') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Location Details -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Location Details</h5>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="40%" class="text-muted">Name:</th>
                            <td><strong>{{ $location->name }}</strong></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Node Type:</th>
                            <td>
                                @if($location->node_type === 'metro')
                                    <span class="badge bg-success">{{ ucfirst($location->node_type) }}</span>
                                @else
                                    <span class="badge bg-info">{{ ucfirst($location->node_type) }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">State:</th>
                            <td>{{ $location->state }}</td>
                        </tr>
                        @if($location->city)
                        <tr>
                            <th class="text-muted">City:</th>
                            <td>{{ $location->city }}</td>
                        </tr>
                        @endif
                        @if($location->ports)
                        <tr>
                            <th class="text-muted">Ports:</th>
                            <td>{{ $location->ports }}</td>
                        </tr>
                        @endif
                        @if($location->switch_details)
                        <tr>
                            <th class="text-muted">Switch Details:</th>
                            <td>{{ $location->switch_details }}</td>
                        </tr>
                        @endif
                        @if($location->nodal_officer)
                        <tr>
                            <th class="text-muted">Nodal Officer:</th>
                            <td>{{ $location->nodal_officer }}</td>
                        </tr>
                        @endif
                        @if($location->zone)
                        <tr>
                            <th class="text-muted">Zone:</th>
                            <td>{{ $location->zone }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th class="text-muted">Status:</th>
                            <td>
                                @if($location->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Admin-Only Details -->
            <div class="card border-0 shadow-sm mt-4" style="border-radius: 16px;">
                <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Admin-Only Details</h5>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless mb-0">
                        @if($location->p2p_capacity)
                        <tr>
                            <th width="40%" class="text-muted">P2P Capacity:</th>
                            <td><strong>{{ $location->p2p_capacity }}</strong></td>
                        </tr>
                        @endif
                        @if($location->p2p_provider)
                        <tr>
                            <th class="text-muted">P2P Provider:</th>
                            <td>{{ $location->p2p_provider }}</td>
                        </tr>
                        @endif
                        @if($location->connected_main_node)
                        <tr>
                            <th class="text-muted">Connected Main Node:</th>
                            <td>{{ $location->connected_main_node }}</td>
                        </tr>
                        @endif
                        @if($location->p2p_arc)
                        <tr>
                            <th class="text-muted">P2P ARC:</th>
                            <td><strong>₹{{ number_format($location->p2p_arc, 2) }}</strong></td>
                        </tr>
                        @endif
                        @if($location->colocation_provider)
                        <tr>
                            <th class="text-muted">Colocation Provider:</th>
                            <td>{{ $location->colocation_provider }}</td>
                        </tr>
                        @endif
                        @if($location->colocation_arc)
                        <tr>
                            <th class="text-muted">Colocation ARC:</th>
                            <td><strong>₹{{ number_format($location->colocation_arc, 2) }}</strong></td>
                        </tr>
                        @endif
                        @if(!$location->p2p_capacity && !$location->p2p_provider && !$location->connected_main_node && !$location->p2p_arc && !$location->colocation_provider && !$location->colocation_arc)
                        <tr>
                            <td colspan="2" class="text-muted text-center">No admin-only details available.</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- Application Statistics -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0">Application Statistics</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                                <h3 class="mb-0">{{ $locationStats['total_applications'] ?? 0 }}</h3>
                                <small class="text-muted">Total Applications</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                                <h3 class="mb-0">{{ $locationStats['approved_applications'] ?? 0 }}</h3>
                                <small class="text-muted">Approved</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                                <h3 class="mb-0">{{ $locationStats['pending_applications'] ?? 0 }}</h3>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center p-3 bg-danger bg-opacity-10 rounded">
                                <h3 class="mb-0">{{ $locationStats['rejected_applications'] ?? 0 }}</h3>
                                <small class="text-muted">Rejected</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
