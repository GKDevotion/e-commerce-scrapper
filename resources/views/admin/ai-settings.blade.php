@extends('layouts.app')
@section('title', 'AI Settings')
@section('page-title', 'AI Settings')

@section('content')
<div class="row g-4">
    <!-- OpenAI Config -->
    <div class="col-lg-6 fade-in-up">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-cpu me-2" style="color:#E31837;"></i>OpenAI Configuration</h3>
            </div>
            <form method="POST" action="{{ route('admin.ai-settings.update') }}">
                @csrf
                <div class="alb-form-group">
                    <label class="alb-label">OpenAI API Key</label>
                    <div style="position:relative;">
                        <input type="password" name="openai_api_key" class="alb-input" id="apiKey"
                            placeholder="sk-..." value="{{ config('services.openai.key') ? '••••••••••••••••' : '' }}">
                        <button type="button" onclick="toggleKey()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#9CA3AF;cursor:pointer;">
                            <i class="bi bi-eye" id="keyEye"></i>
                        </button>
                    </div>
                    <div style="font-size:12px;color:#9CA3AF;margin-top:4px;">Your OpenAI API key from platform.openai.com</div>
                </div>
                <div class="alb-form-group">
                    <label class="alb-label">AI Model</label>
                    <select name="openai_model" class="alb-input">
                        @foreach(['gpt-4o','gpt-4o-mini','gpt-4-turbo','gpt-3.5-turbo'] as $m)
                        <option value="{{ $m }}" {{ config('services.openai.model') === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                    <div style="font-size:12px;color:#9CA3AF;margin-top:4px;">GPT-4o recommended for best listing quality</div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="alb-form-group">
                            <label class="alb-label">Max Tokens</label>
                            <input type="number" name="max_tokens" class="alb-input" value="4000" min="500" max="8000">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="alb-form-group">
                            <label class="alb-label">Temperature (0–2)</label>
                            <input type="number" name="temperature" class="alb-input" value="0.7" min="0" max="2" step="0.1">
                            <div style="font-size:12px;color:#9CA3AF;margin-top:4px;">0.7 = balanced creativity</div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-alb-primary btn">
                    <i class="bi bi-save me-2"></i>Save AI Settings
                </button>
            </form>
        </div>
    </div>

    <!-- Prompt Templates -->
    <div class="col-lg-6 fade-in-up fade-in-up-delay-1">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-file-text me-2" style="color:#8B5CF6;"></i>Prompt Templates</h3>
                <span style="font-size:12px;color:#9CA3AF;">{{ $templates->count() }} templates</span>
            </div>
            @if($templates->isEmpty())
            <div style="text-align:center;padding:32px;color:#9CA3AF;font-size:13px;">
                <i class="bi bi-file-plus" style="font-size:28px;display:block;margin-bottom:10px;"></i>
                No prompt templates. Using default built-in prompts.
            </div>
            @else
            @foreach($templates as $tpl)
            <div style="border:1.5px solid #E5E7EB;border-radius:10px;padding:14px;margin-bottom:10px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                    <div style="font-size:13.5px;font-weight:700;color:#111827;">{{ $tpl->name }}</div>
                    <div style="display:flex;gap:6px;align-items:center;">
                        @if($tpl->is_default)
                        <span style="background:#D1FAE5;color:#065F46;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px;">Default</span>
                        @endif
                        @if($tpl->is_active)
                        <span style="background:#DBEAFE;color:#1E40AF;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px;">Active</span>
                        @endif
                    </div>
                </div>
                <div style="font-size:12px;color:#9CA3AF;">{{ $tpl->description }}</div>
                <div style="font-size:11.5px;color:#6B7280;margin-top:6px;background:#F9FAFB;border-radius:6px;padding:8px;font-family:monospace;">
                    Model: {{ $tpl->ai_model }} | Tokens: {{ $tpl->max_tokens }} | Temp: {{ $tpl->temperature }}
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>

    <!-- API Usage Stats -->
    <div class="col-12 fade-in-up fade-in-up-delay-2">
        <div class="alb-card">
            <div class="alb-card-header">
                <h3 class="alb-card-title"><i class="bi bi-graph-up me-2" style="color:#10B981;"></i>AI Usage Overview</h3>
            </div>
            <div class="row g-3">
                @php
                $aiStats = [
                    ['Total Generations', \App\Models\AiGeneration::count(), 'bi-cpu', '#E31837'],
                    ['Completed', \App\Models\AiGeneration::where('status','completed')->count(), 'bi-check-circle', '#10B981'],
                    ['Failed', \App\Models\AiGeneration::where('status','failed')->count(), 'bi-x-circle', '#EF4444'],
                    ['Total Tokens', number_format(\App\Models\AiGeneration::sum('total_tokens')), 'bi-hash', '#8B5CF6'],
                    ['Total AI Cost', '$'.number_format(\App\Models\AiGeneration::sum('ai_cost'),4), 'bi-currency-dollar', '#F59E0B'],
                    ['Avg Tokens/Gen', number_format(\App\Models\AiGeneration::where('status','completed')->avg('total_tokens') ?? 0), 'bi-bar-chart', '#3B82F6'],
                ];
                @endphp
                @foreach($aiStats as [$label, $value, $icon, $color])
                <div class="col-6 col-md-2">
                    <div style="text-align:center;padding:16px;background:#F9FAFB;border-radius:12px;">
                        <i class="bi {{ $icon }}" style="font-size:24px;color:{{ $color }};display:block;margin-bottom:8px;"></i>
                        <div style="font-family:'Sora',sans-serif;font-size:18px;font-weight:800;color:#111827;">{{ $value }}</div>
                        <div style="font-size:11.5px;color:#9CA3AF;">{{ $label }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleKey() {
    const input = document.getElementById('apiKey');
    const eye = document.getElementById('keyEye');
    if (input.type === 'password') { input.type = 'text'; eye.className = 'bi bi-eye-slash'; }
    else { input.type = 'password'; eye.className = 'bi bi-eye'; }
}
</script>
@endpush
@endsection
