@extends('layouts.app')
@section('title','Audit Logs')
@section('page-title','Audit Logs')
@section('content')
<div class="alb-card mb-4 fade-in-up" style="padding:14px 20px;">
  <form method="GET" class="d-flex gap-3 flex-wrap align-items-center">
    <input type="text" name="action" class="alb-input" style="max-width:220px;padding:8px 14px;" placeholder="Filter by action..." value="{{ request('action') }}">
    <input type="number" name="user_id" class="alb-input" style="max-width:130px;padding:8px 14px;" placeholder="User ID..." value="{{ request('user_id') }}">
    <button type="submit" class="btn-alb-primary btn" style="padding:9px 20px;">Filter</button>
    <a href="{{ route('admin.logs.audit') }}" class="btn-alb-outline btn" style="padding:9px 16px;">Clear</a>
    <span style="font-size:13px;color:#9CA3AF;margin-left:auto;">{{ $logs->total() }} records</span>
  </form>
</div>
<div class="alb-card fade-in-up" style="padding:0;overflow:hidden;">
  <div style="overflow-x:auto;">
    <table class="alb-table" style="min-width:700px;">
      <thead><tr><th>User</th><th>Action</th><th>Model</th><th>IP</th><th>When</th></tr></thead>
      <tbody>
        @forelse($logs as $log)
        <tr>
          <td>
            <div style="font-size:13px;font-weight:600;color:#111827;">{{ $log->user?->name ?? 'System' }}</div>
            <div style="font-size:11px;color:#9CA3AF;">{{ $log->user?->email }}</div>
          </td>
          <td>
            <span style="background:#DBEAFE;color:#1E40AF;font-size:12px;font-weight:600;padding:3px 9px;border-radius:6px;">{{ $log->action }}</span>
          </td>
          <td style="font-size:12.5px;color:#6B7280;">
            @if($log->model_type)
              {{ class_basename($log->model_type) }}{{ $log->model_id ? ' #'.$log->model_id : '' }}
            @else —
            @endif
          </td>
          <td style="font-size:12px;color:#9CA3AF;font-family:monospace;">{{ $log->ip_address ?? '—' }}</td>
          <td style="font-size:12px;color:#9CA3AF;white-space:nowrap;">{{ $log->created_at->diffForHumans() }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:40px;color:#9CA3AF;">No audit logs found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($logs->hasPages())
  <div style="padding:14px 20px;border-top:1px solid #F3F4F6;">{{ $logs->links('pagination::bootstrap-5') }}</div>
  @endif
</div>
@endsection
