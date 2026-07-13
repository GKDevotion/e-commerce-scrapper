@extends('layouts.app')
@section('title','Prompt Templates')
@section('page-title','Prompt Templates')
@section('topbar-actions')
<a href="{{ route('admin.prompts.create') }}" class="topbar-btn"><i class="bi bi-plus-lg"></i> New Template</a>
@endsection
@section('content')
<div class="row g-4 fade-in-up">
  @forelse($templates as $tpl)
  <div class="col-lg-6">
    <div class="alb-card" style="border:{{ $tpl->is_default?'2px solid #E31837':'1.5px solid #E5E7EB' }};">
      <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
          <div style="font-family:'Sora',sans-serif;font-size:16px;font-weight:700;color:#111827;margin-bottom:4px;">{{ $tpl->name }}</div>
          <div style="font-size:12.5px;color:#9CA3AF;">{{ $tpl->description }}</div>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
          @if($tpl->is_default)<span style="background:#FEE2E8;color:#E31837;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">Default</span>@endif
          @if($tpl->is_active)<span style="background:#D1FAE5;color:#065F46;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">Active</span>
          @else<span style="background:#F3F4F6;color:#9CA3AF;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;">Inactive</span>@endif
        </div>
      </div>
      <div style="background:#F9FAFB;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:12px;font-family:monospace;color:#6B7280;">
        Model: {{ $tpl->ai_model }} &nbsp;|&nbsp; Tokens: {{ $tpl->max_tokens }} &nbsp;|&nbsp; Temp: {{ $tpl->temperature }}
      </div>
      <div style="background:#F9FAFB;border-radius:8px;padding:12px 14px;margin-bottom:16px;max-height:70px;overflow:hidden;position:relative;">
        <div style="font-size:11.5px;color:#6B7280;line-height:1.6;">{{ Str::limit($tpl->system_prompt,200) }}</div>
        <div style="position:absolute;bottom:0;left:0;right:0;height:28px;background:linear-gradient(transparent,#F9FAFB);"></div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('admin.prompts.edit',$tpl->id) }}" class="btn-alb-outline btn" style="font-size:12.5px;padding:8px 16px;">
          <i class="bi bi-pencil me-1"></i>Edit
        </a>
        @if(!$tpl->is_default)
        <form method="POST" action="{{ route('admin.prompts.destroy',$tpl->id) }}" onsubmit="return confirm('Delete this template?')">
          @csrf @method('DELETE')
          <button type="submit" class="btn" style="border:1.5px solid #FCA5A5;background:white;color:#EF4444;font-size:12.5px;padding:8px 16px;border-radius:9px;cursor:pointer;">
            <i class="bi bi-trash me-1"></i>Delete
          </button>
        </form>
        @endif
      </div>
    </div>
  </div>
  @empty
  <div class="col-12">
    <div class="alb-card text-center" style="padding:40px;">
      <i class="bi bi-file-text" style="font-size:32px;color:#D1D5DB;display:block;margin-bottom:10px;"></i>
      <p style="color:#9CA3AF;">No templates yet. <a href="{{ route('admin.prompts.create') }}" style="color:#E31837;">Create one →</a></p>
    </div>
  </div>
  @endforelse
</div>
@endsection
