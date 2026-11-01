<form method="POST" action="{{ route('tools.ingredient-substitution.search') }}#results" class="tt-sub-form" novalidate>
    @csrf
    <h2 class="tt-sub-form__title">Find an ingredient substitute</h2>

    <div class="tt-field">
        <label for="sub-ingredient">What ingredient are you replacing?</label>
        <input id="sub-ingredient" name="ingredient" type="text" list="sub-ingredient-list" value="{{ $input['ingredient'] }}"
               maxlength="80" required autocomplete="off" placeholder="e.g. butter, eggs, milk"
               @error('ingredient') aria-invalid="true" aria-describedby="sub-ingredient-error" @enderror>
        <datalist id="sub-ingredient-list">
            @foreach ($catalog as $i)<option value="{{ $i->name }}">@endforeach
        </datalist>
        @error('ingredient')<p class="tt-error" id="sub-ingredient-error" role="alert">{{ $message }}</p>@enderror
    </div>

    <fieldset class="tt-field">
        <legend>What are you making?</legend>
        <div class="tt-pills">
            @foreach (\App\Services\IngredientSubstitutionService::PURPOSES as $value => $label)
                <label><input type="radio" name="purpose" value="{{ $value }}" @checked($input['purpose'] === $value)><span>{{ $label }}</span></label>
            @endforeach
        </div>
        @error('purpose')<p class="tt-error" role="alert">{{ $message }}</p>@enderror
    </fieldset>

    <fieldset class="tt-field">
        <legend>Dietary preference</legend>
        <div class="tt-pills">
            @foreach (\App\Services\IngredientSubstitutionService::DIETS as $value => $label)
                <label><input type="radio" name="diet" value="{{ $value }}" @checked($input['diet'] === $value)><span>{{ $label }}</span></label>
            @endforeach
        </div>
        @error('diet')<p class="tt-error" role="alert">{{ $message }}</p>@enderror
    </fieldset>

    <fieldset class="tt-field">
        <legend>Quantity you need to replace <small>(optional)</small></legend>
        <input id="sub-quantity" name="quantity" type="number" step="any" min="0" inputmode="decimal" value="{{ $input['quantity'] }}" aria-label="Quantity" placeholder="e.g. 100"
               @error('quantity') aria-invalid="true" @enderror>
        <div class="tt-pills tt-pills--sm" role="radiogroup" aria-label="Unit">
            @foreach (\App\Services\IngredientConversionService::UNITS as $value => $label)
                <label><input type="radio" name="unit" value="{{ $value }}" @checked($input['unit'] === $value)><span>{{ $label }}</span></label>
            @endforeach
        </div>
        @error('quantity')<p class="tt-error" role="alert">{{ $message }}</p>@enderror
        @error('unit')<p class="tt-error" role="alert">{{ $message }}</p>@enderror
    </fieldset>

    <button type="submit" class="tt-btn tt-btn--primary tt-btn--block">Find substitutes</button>
</form>
