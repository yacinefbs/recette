(function () {
    'use strict';

    var link = document.getElementById('recipe-cost-prefill-link');
    var presetElement = document.getElementById('recipe-cost-page-preset');
    if (!link || !presetElement) return;

    link.addEventListener('click', function (event) {
        try {
            var preset = JSON.parse(presetElement.textContent);
            window.sessionStorage.setItem('topictrail_recipe_cost_prefill_v1', JSON.stringify(preset));
        } catch (error) {
            event.preventDefault();
            window.alert('Recipe details could not be transferred. You can still enter them manually in the calculator.');
        }
    });
}());
