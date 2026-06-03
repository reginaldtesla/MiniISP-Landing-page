@php
    use App\Support\PackageValidity;
    $package = $package ?? null;
    $editing = $package !== null;
    $isSpecial = (bool) old('is_special_offer', $package?->is_special_offer ?? request()->boolean('special'));
    $validityType = old('validity_type', $package?->validity_type ?? PackageValidity::TYPE_DAYS);
    $startsAt = old('special_starts_at', $package?->special_starts_at?->format('Y-m-d\TH:i') ?? '');
    $endsAt = old('special_ends_at', $package?->special_ends_at?->format('Y-m-d\TH:i') ?? '');
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="sm:col-span-2">
        <label for="name" class="font-label-sm admin-card-muted block mb-1">Package name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $package?->name ?? '') }}" required maxlength="120"
            class="portal-input min-h-[48px]" placeholder="e.g. Student Choice"/>
    </div>
    <div>
        <label for="data_label" class="font-label-sm admin-card-muted block mb-1">Data label (shown to students)</label>
        <input type="text" name="data_label" id="data_label" value="{{ old('data_label', $package?->data_label ?? '') }}" required maxlength="32"
            class="portal-input min-h-[48px]" placeholder="e.g. 3GB"/>
    </div>
    <div id="data-limit-field">
        <label for="data_limit_mb" class="font-label-sm admin-card-muted block mb-1">Data limit (MB)</label>
        <input type="number" name="data_limit_mb" id="data_limit_mb" value="{{ old('data_limit_mb', $package?->data_limit_mb ?? '') }}" min="1"
            class="portal-input min-h-[48px]" placeholder="3072" @unless($validityType === PackageValidity::TYPE_UNLIMITED) required @endunless/>
        <p id="data-limit-unlimited-hint" class="font-label-sm admin-card-muted mt-1 @unless($validityType === PackageValidity::TYPE_UNLIMITED) hidden @endunless">Not used for unlimited plans — set the label above to e.g. “Unlimited”.</p>
    </div>
    <div>
        <label for="price" class="font-label-sm admin-card-muted block mb-1">Price (GH¢)</label>
        <input type="number" name="price" id="price" value="{{ old('price', $package?->price ?? '') }}" required min="0.5" step="0.01"
            class="portal-input min-h-[48px]" placeholder="9.00"/>
    </div>
    <div>
        <label for="speed_mbps" class="font-label-sm admin-card-muted block mb-1">Speed cap (Mbps, optional)</label>
        <input type="number" name="speed_mbps" id="speed_mbps" value="{{ old('speed_mbps', $package?->speed_mbps ?? '') }}" min="1" max="1000"
            class="portal-input min-h-[48px]" placeholder="10"/>
    </div>
    <div id="validity-fields" class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4 @if($isSpecial) hidden @endif">
        <div>
            <label for="validity_type" class="font-label-sm admin-card-muted block mb-1">Data validity</label>
            <select name="validity_type" id="validity_type" class="portal-input min-h-[48px] w-full">
                @foreach (PackageValidity::typeLabels() as $value => $label)
                    <option value="{{ $value }}" @selected($validityType === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="font-label-sm admin-card-muted mt-1">How long purchased data stays active.</p>
        </div>
        <div id="validity-days-wrap" @unless($validityType === PackageValidity::TYPE_DAYS) class="hidden" @endunless>
            <label for="validity_days" class="font-label-sm admin-card-muted block mb-1">Duration (days)</label>
            <input type="number" name="validity_days" id="validity_days" value="{{ old('validity_days', $package?->validity_days ?? 30) }}" min="1" max="3650"
                class="portal-input min-h-[48px]" placeholder="30" @if($validityType === PackageValidity::TYPE_DAYS) required @endif/>
            <p class="font-label-sm admin-card-muted mt-1">Expires after this period, even if data remains.</p>
        </div>
    </div>
    <div>
        <label for="sort_order" class="font-label-sm admin-card-muted block mb-1">Display order</label>
        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $package?->sort_order ?? 0) }}" required min="0"
            class="portal-input min-h-[48px]"/>
    </div>
    <div class="sm:col-span-2">
        <label for="description" class="font-label-sm admin-card-muted block mb-1">Description (optional)</label>
        <textarea name="description" id="description" rows="2" maxlength="500"
            class="portal-input min-h-[72px] resize-y">{{ old('description', $package?->description ?? '') }}</textarea>
    </div>

    <div class="sm:col-span-2 admin-card rounded-xl p-4 border border-outline-variant/20 space-y-4" id="special-offer-fields">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="is_special_offer" value="1" id="is_special_offer"
                @checked($isSpecial)
                class="rounded border-outline-variant text-primary focus:ring-primary dark:focus:ring-primary-fixed-dim w-5 h-5 mt-0.5"/>
            <span>
                <span class="font-body-md admin-card-strong block">Special day offer</span>
                <span class="font-label-sm admin-card-muted block mt-0.5">Shown only in the “Limited-time offers” section. Purchased data expires when the offer ends (not when data runs out). Does not affect the custom calculator.</span>
            </span>
        </label>

        <div id="special-offer-schedule" class="grid grid-cols-1 sm:grid-cols-2 gap-4 @unless($isSpecial) hidden @endunless">
            <div class="sm:col-span-2">
                <label for="promo_label" class="font-label-sm admin-card-muted block mb-1">Badge label (optional)</label>
                <input type="text" name="promo_label" id="promo_label" value="{{ old('promo_label', $package?->promo_label ?? '') }}" maxlength="64"
                    class="portal-input min-h-[48px]" placeholder="e.g. Weekend Blast · Valentine's Deal"/>
            </div>
            <div>
                <label for="special_starts_at" class="font-label-sm admin-card-muted block mb-1">Show from (optional)</label>
                <input type="datetime-local" name="special_starts_at" id="special_starts_at" value="{{ $startsAt }}"
                    class="portal-input min-h-[48px] w-full"/>
                <p class="font-label-sm admin-card-muted mt-1">Leave empty to show immediately when active.</p>
            </div>
            <div>
                <label for="special_ends_at" class="font-label-sm admin-card-muted block mb-1">Show until (required)</label>
                <input type="datetime-local" name="special_ends_at" id="special_ends_at" value="{{ $endsAt }}"
                    class="portal-input min-h-[48px] w-full"/>
            </div>
        </div>
    </div>

    <div class="sm:col-span-2">
        <label class="flex items-center gap-2 min-h-[44px] cursor-pointer">
            <input type="checkbox" name="is_active" value="1"
                @checked(old('is_active', $package?->is_active ?? true))
                class="rounded border-outline-variant text-primary focus:ring-primary dark:focus:ring-primary-fixed-dim w-5 h-5"/>
            <span class="font-body-md admin-card-strong">Active (available for student purchase when schedule allows)</span>
        </label>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var toggle = document.getElementById('is_special_offer');
    var panel = document.getElementById('special-offer-schedule');
    var validityFields = document.getElementById('validity-fields');
    var validityType = document.getElementById('validity_type');
    var validityDaysWrap = document.getElementById('validity-days-wrap');
    var validityDays = document.getElementById('validity_days');
    var dataLimitField = document.getElementById('data-limit-field');
    var dataLimitInput = document.getElementById('data_limit_mb');
    var dataLimitHint = document.getElementById('data-limit-unlimited-hint');
    var ends = document.getElementById('special_ends_at');
    if (!toggle || !panel) return;

    function syncValidityType() {
        var type = validityType ? validityType.value : 'days';
        var isDays = type === 'days';
        var isUnlimited = type === 'unlimited';
        if (validityDaysWrap) validityDaysWrap.classList.toggle('hidden', !isDays);
        if (validityDays) validityDays.required = isDays;
        if (dataLimitInput) dataLimitInput.required = !isUnlimited;
        if (dataLimitField) dataLimitField.classList.toggle('opacity-60', isUnlimited);
        if (dataLimitHint) dataLimitHint.classList.toggle('hidden', !isUnlimited);
    }

    function sync() {
        var isSpecial = toggle.checked;
        panel.classList.toggle('hidden', !isSpecial);
        if (validityFields) validityFields.classList.toggle('hidden', isSpecial);
        if (ends) ends.required = isSpecial;
        if (!isSpecial) syncValidityType();
    }

    toggle.addEventListener('change', sync);
    if (validityType) validityType.addEventListener('change', syncValidityType);
    sync();
})();
</script>
@endpush
