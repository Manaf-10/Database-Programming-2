$(document).ready(function() {
    function updateSortButtons(activeSort) {
        $('.sort-btn').each(function() {
            const isActive = $(this).data('sort') === activeSort;
            $(this)
                .toggleClass('btn-success', isActive)
                .toggleClass('btn-outline-success', !isActive);
        });
    }

    function updateLatestVisibility(page) {
        $('#latest-recipes-section').toggleClass('d-none', page > 1);
    }

    function loadRecipes(page) {
        const $form = $('#recipe-search-form');
        const $container = $('#all-recipes-container');

        if (!$form.length || !$container.length) {
            return;
        }

        const query = $form.serializeArray();
        query.push({ name: 'page', value: page });

        $container.addClass('is-loading');

        $.ajax({
            url: 'actions/fetch_recipes.php',
            type: 'GET',
            data: $.param(query),
            success: function(html) {
                $container.html(html).attr('data-current-page', page);
                updateLatestVisibility(page);
                const newUrl = 'index.php?' + $.param(query);
                window.history.pushState({ page: page }, '', newUrl);
            },
            error: function() {
                $container.html('<div class="alert alert-danger">Unable to load recipes.</div>');
            },
            complete: function() {
                $container.removeClass('is-loading');
            }
        });
    }

    $('#recipe-search-form').on('submit', function(event) {
        event.preventDefault();
        loadRecipes(1);
    });

    $(document).on('click', '.recipe-page-link', function(event) {
        event.preventDefault();
        loadRecipes(parseInt($(this).data('page'), 10) || 1);
    });

    $('.sort-btn').on('click', function() {
        const sort = $(this).data('sort');
        const currentSort = $('#sort-input').val();
        const nextSort = currentSort === sort ? '' : sort;

        $('#sort-input').val(nextSort);
        updateSortButtons(nextSort);
        loadRecipes(1);
    });

    window.addEventListener('popstate', function() {
        window.location.reload();
    });

    $('.star').hover(function() {
        $(this).prevAll().addBack().css('color', 'gold');
        $(this).nextAll().css('color', 'gray');
    }, function() {
        $('.star').css('color', 'gray');
    });

    $('.star').click(function() {
        const rating = $(this).data('value');
        const recipeId = $('#recipe_id').val();

        $.ajax({
            url: 'actions/rate_recipe.php',
            type: 'POST',
            data: { recipe_id: recipeId, rating: rating },
            dataType: 'json',
            success: function(response) {
                const cssClass = response.success ? 'alert-success' : 'alert-danger';
                $('#rating-msg').html('<div class="alert ' + cssClass + '">' + response.message + '</div>');
            },
            error: function() {
                $('#rating-msg').html('<div class="alert alert-danger">Error connecting to server.</div>');
            }
        });
    });
});
