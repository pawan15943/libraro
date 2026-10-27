@include('master.custom-management._toolbar', [
    'heading' => 'Show / Hide Options',
    'hint' => 'Turn a switch on to hide that field, button or menu for this branch. Changes save instantly.',
])

@if(!$branchId)
    @include('master.custom-management._no-branch', ['text' => 'Show / hide options are saved per branch.'])
@else
    {{-- One section per category; its options sit below as a grid of switch cards. --}}
    <div class="cm-toggle-sections" id="cmToggleGroups">
        @foreach($toggleFeatures as $category => $features)
            @php $hiddenInCategory = $features->filter(fn ($feature) => in_array($feature->id, $hiddenFields))->count(); @endphp
            <section class="cm-toggle-section">
                <header class="cm-toggle-section-head">
                    <p class="cm-toggle-category">{{ $category ?: 'Other' }}</p>
                    <span class="cm-toggle-count">{{ $features->count() }} options · {{ $hiddenInCategory }} hidden</span>
                </header>
                <div class="cm-toggle-grid">
                    @foreach($features as $feature)
                        @php $isHidden = in_array($feature->id, $hiddenFields); @endphp
                        <label class="cm-toggle-card {{ $isHidden ? 'is-hidden' : '' }}" for="cmToggle{{ $feature->id }}">
                            <span class="cm-toggle-card-top">
                                <span class="cm-toggle-name">{{ $feature->name }}</span>
                                <span class="form-check form-switch m-0">
                                    <input class="form-check-input cm-toggle-input" type="checkbox" role="switch"
                                           id="cmToggle{{ $feature->id }}" value="{{ $feature->id }}" @checked($isHidden)>
                                </span>
                            </span>
                            @if($feature->description)
                                <span class="cm-toggle-desc">{{ $feature->description }}</span>
                            @endif
                            <span class="cm-toggle-state">
                                <i class="fa-solid {{ $isHidden ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                <span>{{ $isHidden ? 'Hidden' : 'Visible' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endif
