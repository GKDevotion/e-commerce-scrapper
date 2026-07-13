@extends('layouts.app')
@section('title','API Logs')
@section('page-title','API Logs')
@section('content')
<div class="alb-card mb-4 fade-in-up" style="padding:14px 20px;">
  <form method="GET" class="d-flex gap-3 flex-wrap align-items-center">
    <select name="service" class="alb-input" style="width:auto;padding:8px 14px;">
      <option value="">All Services</option>
      @foreach($services as $s)
      <option value="{{ $s }}" {{ request('service')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
      @endforeach
    </select>
    <select name="success" class="alb-input" style="width:auto;padding:8px 14px;">
      <option value="">All</option>
      <option value="1" {{ request('success')==='1'?'selected':'' }}>Success</option>
      <option value="0" {{ request('success')==='0'?'selected':'' }}>Failed</option>
    </select>
    <button type="submit" class="btn-alb-primary btn" style="padding:9px 20px;">Filter</button>
    <a href="{{ route('admin.logs.api') }}" class="btn-alb-outline btn" style="padding:9px 16px;">Clear</a>
    <span style="font-size:13px;color:#9CA3AF;margin-left:auto;">{{ $logs->total() }} records</span>
  </form>
</div>
<div class="alb-card fade-in-up" style="padding:0;overflow:hidden;">
  <div style="overflow-x:auto;">
    <table class="alb-table" style="min-width:860px;">
      <thead><tr><th>Service</th><th>User</th><th>Endpoint</th><th>HTTP</th><th>Time</th><th>Result</th><th>When</th></tr></thead>
      <tbody>
        @forelse($logs as $log)
        <tr>
          <td><span style="background:#F3F4F6;padding:3px 9px;border-radius:6px;font-size:12px;font-weight:600;">{{ $log->service }}</span></td>
          <td style="font-size:12.5px;">{{ $log->user?->name ?? 'Guest' }}</td>
          <td style="font-size:12px;color:#6B7280;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            <span style="background:#EFF6FF;color:#3B82F6;padding:2px 5px;border-radius:4px;font-size:10.5px;margin-right:4px;">{{ $log->method ?? 'GET' }}</span>{{ $log->endpoint }}
          </td>
          <td>
            @php $code = $log->status_code ?? 0; @endphp
            <span style="background:{{ $code < 400 ? '#D1FAE5' : '#FEE2E2' }};color:{{ $code < 400 ? '#065F46' : '#991B1B' }};font-size:12px;font-weight:700;padding:3px 8px;border-radius:6px;">{{ $code ?: '—' }}</span>
          </td>
          <td style="font-size:12px;color:#6B7280;">{{ $log->response_time_ms ? $log->response_time_ms.'ms' : '—' }}</td>
          <td>
            @if($log->success)
            <i class="bi bi-check-circle-fill" style="color:#10B981;font-size:15px;"></i>
            @else
            <i class="bi bi-x-circle-fill" style="color:#EF4444;font-size:15px;" title="{{ $log->error_message }}"></i>
            @endif
          </td>
          <td style="font-size:12px;color:#9CA3AF;white-space:nowrap;">{{ $log->created_at->diffForHumans() }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:40px;color:#9CA3AF;">No API logs found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($logs->hasPages())
  <div style="padding:14px 20px;border-top:1px solid #F3F4F6;">{{ $logs->links('pagination::bootstrap-5') }}</div>
  @endif
</div>
@endsection
