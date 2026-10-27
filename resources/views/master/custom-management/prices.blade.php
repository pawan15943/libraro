@include('master.custom-management._toolbar', [
    'heading' => 'Plan Prices',
    'hint' => 'Price of every plan for every shift, suggested from your 1 Month price.',
    'listable' => $branchId && $cmPlanPrices->isNotEmpty(),
    'modal' => $branchId ? 'price' : null,
    'canAdd' => auth()->user()->can('has-permission', 'Add Plan Price Master'),
    'addLabel' => 'Add Price',
])

@if(!$branchId)
    @include('master.custom-management._no-branch', ['text' => 'Plan prices are branch-specific.'])
@elseif($cmPlanPrices->isEmpty())
    @include('master.custom-management._empty', ['icon' => 'fa-indian-rupee-sign', 'heading' => 'No prices added yet', 'text' => 'Add a price for each plan and shift combination.'])
@else
    <div class="cm-items">
        @foreach($cmPlanPrices as $price)
            @include('master.custom-management._item', [
                'icon' => 'fa-indian-rupee-sign', 'tone' => 'green',
                'heading' => $price->plan->name ?? 'Plan removed',
                'subheading' => $price->planType->name ?? 'Shift removed',
                'meta' => ['Price' => '₹' . number_format((float) $price->price, 2)],
                'inactive' => (bool) $price->deleted_at, 'locked' => false,
                'modal' => 'price', 'table' => 'PlanPrice', 'id' => $price->id,
                'record' => $price->only(['id', 'plan_id', 'plan_type_id', 'price']),
            ])
        @endforeach
    </div>
    @include('master.custom-management._no-results')
@endif
