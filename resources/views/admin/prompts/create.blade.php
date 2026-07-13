@extends('layouts.app')
@section('title', isset($template) ? 'Edit Prompt' : 'New Prompt Template')
@section('page-title', isset($template) ? 'Edit Prompt Template' : 'New Prompt Template')
@section('topbar-actions')
<a href="{{ route('admin.prompts') }}" class="topbar-btn" style="background:#374151;"><i class="bi bi-arrow-left"></i> Back</a>
@endsection
@section('content')
<div class="row justify-content-center">
  <div class="col-lg-9">
    <div class="alb-card fade-in-up">
      <form method="POST" action="{{ isset($template) ? route('admin.prompts.update',$template->id) : route('admin.prompts.store') }}" data-warn-unsaved>
        @csrf
        @if(isset($template)) @method('PUT') @endif

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="alb-label">Name *</label>
            <input type="text" name="name" class="alb-input" value="{{ old('name',$template->name??'') }}" required>
          </div>
          <div class="col-md-6">
            <label class="alb-label">Slug * {{ isset($template)?'(read-only)':'' }}</label>
            <input type="text" name="slug" class="alb-input" value="{{ old('slug',$template->slug??'') }}"
              {{ isset($template)?'readonly style=background:#F9FAFB':'required' }}>
          </div>
          <div class="col-12">
            <label class="alb-label">Description</label>
            <input type="text" name="description" class="alb-input" value="{{ old('description',$template->description??'') }}" placeholder="Short description...">
          </div>
        </div>

        <div class="alb-form-group">
          <label class="alb-label">System Prompt * <span style="color:#9CA3AF;font-size:11px;font-weight:400;">(sent as "system" role to the AI)</span></label>
          <textarea name="system_prompt" class="alb-input" style="min-height:200px;font-family:monospace;font-size:12.5px;" required>{{ old('system_prompt',$template->system_prompt??'') }}</textarea>
        </div>

        <div class="alb-form-group">
          <label class="alb-label">User Prompt Template <span style="color:#9CA3AF;font-size:11px;font-weight:400;">(optional — leave blank to use built-in default)</span></label>
          <textarea name="user_prompt_template" class="alb-input" style="min-height:100px;font-family:monospace;font-size:12.5px;" placeholder="Use {brand}, {original_title} etc as placeholders...">{{ old('user_prompt_template',$template->user_prompt_template??'') }}</textarea>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="alb-label">AI Model *</label>
            <select name="ai_model" class="alb-input">
              @foreach(['gpt-4o','gpt-4o-mini','gpt-4-turbo','gpt-3.5-turbo'] as $m)
              <option value="{{ $m }}" {{ old('ai_model',$template->ai_model??'gpt-4o')===$m?'selected':'' }}>{{ $m }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="alb-label">Max Tokens *</label>
            <input type="number" name="max_tokens" class="alb-input" value="{{ old('max_tokens',$template->max_tokens??4000) }}" min="100" max="8000" required>
          </div>
          <div class="col-md-4">
            <label class="alb-label">Temperature (0–2) *</label>
            <input type="number" name="temperature" class="alb-input" value="{{ old('temperature',$template->temperature??0.7) }}" min="0" max="2" step="0.1" required>
          </div>
        </div>

        <div class="row g-3 mb-4">
          @foreach([['is_active','Active','Make this template available'],['is_default','Default','Use for all new generations']] as [$f,$l,$d])
          <div class="col-md-6">
            <label style="display:flex;align-items:center;gap:12px;padding:14px 16px;background:#F9FAFB;border-radius:10px;cursor:pointer;border:1.5px solid #E5E7EB;">
              <input type="checkbox" name="{{ $f }}" value="1"
                {{ old($f,$template?->$f??($f==='is_active')) ? 'checked' : '' }}
                style="width:18px;height:18px;accent-color:#E31837;flex-shrink:0;">
              <div>
                <div style="font-size:13.5px;font-weight:600;color:#111827;">{{ $l }}</div>
                <div style="font-size:12px;color:#9CA3AF;">{{ $d }}</div>
              </div>
            </label>
          </div>
          @endforeach
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn-alb-primary btn">
            <i class="bi bi-save me-2"></i>{{ isset($template)?'Update Template':'Create Template' }}
          </button>
          <a href="{{ route('admin.prompts') }}" class="btn-alb-outline btn">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
