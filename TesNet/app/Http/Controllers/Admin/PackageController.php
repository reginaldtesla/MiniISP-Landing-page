<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataPackage;
use App\Models\PackagePurchase;
use App\Support\PackageValidity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(): View
    {
        $packages = DataPackage::query()->ordered()->get();

        return view('admin.packages.index', compact('packages'));
    }

    public function create(): View
    {
        return view('admin.packages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        DataPackage::query()->create([
            ...$this->packageAttributes($request, $validated),
            'slug' => DataPackage::uniqueSlug($validated['name']),
        ]);

        return redirect()->route('admin.packages.index')
            ->with('status', 'Package created and listed for student purchase.');
    }

    public function edit(DataPackage $package): View
    {
        return view('admin.packages.edit', compact('package'));
    }

    public function update(Request $request, DataPackage $package): RedirectResponse
    {
        $validated = $this->validated($request, $package);

        $package->fill($this->packageAttributes($request, $validated));

        if ($package->isDirty('name')) {
            $package->slug = DataPackage::uniqueSlug($validated['name'], $package->id);
        }

        $package->save();

        return redirect()->route('admin.packages.index')
            ->with('status', 'Package "'.$package->name.'" updated.');
    }

    public function destroy(DataPackage $package): RedirectResponse
    {
        $hasPurchases = PackagePurchase::query()
            ->where('package_slug', $package->slug)
            ->exists();

        if ($hasPurchases) {
            $package->update(['is_active' => false]);

            return redirect()->route('admin.packages.index')
                ->with('status', 'Package hidden from the store (existing purchases kept).');
        }

        $package->delete();

        return redirect()->route('admin.packages.index')
            ->with('status', 'Package deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?DataPackage $package = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'data_label' => ['required', 'string', 'max:32'],
            'data_limit_mb' => [
                Rule::requiredIf(fn () => ! $request->boolean('is_special_offer') && $request->input('validity_type') !== PackageValidity::TYPE_UNLIMITED),
                'nullable',
                'integer',
                'min:1',
                'max:1048576',
            ],
            'price' => ['required', 'numeric', 'min:0.5', 'max:10000'],
            'speed_mbps' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'validity_type' => [
                Rule::requiredIf(fn () => ! $request->boolean('is_special_offer')),
                'nullable',
                Rule::in([
                    PackageValidity::TYPE_DAYS,
                    PackageValidity::TYPE_UNTIL_FINISHED,
                    PackageValidity::TYPE_UNLIMITED,
                ]),
            ],
            'validity_days' => [
                Rule::requiredIf(fn () => ! $request->boolean('is_special_offer') && $request->input('validity_type') === PackageValidity::TYPE_DAYS),
                'nullable',
                'integer',
                'min:1',
                'max:3650',
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'is_special_offer' => ['sometimes', 'boolean'],
            'promo_label' => ['nullable', 'string', 'max:64'],
            'special_starts_at' => ['nullable', 'date'],
            'special_ends_at' => ['nullable', 'date', 'after:special_starts_at'],
        ];

        if ($request->boolean('is_special_offer')) {
            $rules['special_ends_at'] = ['required', 'date'];
            if ($request->filled('special_starts_at')) {
                $rules['special_ends_at'][] = 'after:special_starts_at';
            }
        }

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function packageAttributes(Request $request, array $validated): array
    {
        $isSpecial = $request->boolean('is_special_offer');
        $validityType = $isSpecial
            ? PackageValidity::TYPE_DAYS
            : ($validated['validity_type'] ?? PackageValidity::TYPE_DAYS);
        $isUnlimited = $validityType === PackageValidity::TYPE_UNLIMITED;

        return [
            'name' => $validated['name'],
            'data_label' => $validated['data_label'],
            'data_limit_mb' => $isUnlimited ? 0 : (int) $validated['data_limit_mb'],
            'price' => $validated['price'],
            'speed_mbps' => $validated['speed_mbps'] ?? null,
            'validity_type' => $validityType,
            'validity_days' => ($isSpecial || $validityType !== PackageValidity::TYPE_DAYS)
                ? null
                : (int) ($validated['validity_days'] ?? 30),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'],
            'is_active' => $request->boolean('is_active'),
            'is_special_offer' => $isSpecial,
            'promo_label' => $isSpecial ? ($validated['promo_label'] ?? null) : null,
            'special_starts_at' => $isSpecial ? ($validated['special_starts_at'] ?? null) : null,
            'special_ends_at' => $isSpecial ? ($validated['special_ends_at'] ?? null) : null,
        ];
    }
}
