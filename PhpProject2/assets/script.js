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
        const latestCollapse = document.getElementById('latestRecipesCollapse');
        const latestToggle = document.querySelector('.latest-toggle');

        if (!latestCollapse || !window.bootstrap) {
            return;
        }

        const collapse = bootstrap.Collapse.getOrCreateInstance(latestCollapse, { toggle: false });

        if (page > 1) {
            collapse.hide();
            if (latestToggle) {
                latestToggle.setAttribute('aria-expanded', 'false');
            }
        } else {
            collapse.show();
            if (latestToggle) {
                latestToggle.setAttribute('aria-expanded', 'true');
            }
        }
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

        $('#sort-input').val(sort);
        updateSortButtons(sort);
        loadRecipes(1);
    });

    window.addEventListener('popstate', function() {
        window.location.reload();
    });

    function paintStars($container, rating) {
        $container.find('.star').each(function() {
            $(this).toggleClass('filled', parseInt($(this).data('value'), 10) <= rating);
        });
    }

    $('.star-rating').each(function() {
        const savedRating = parseInt($(this).data('user-rating'), 10) || 0;
        paintStars($(this), savedRating);
    });

    $('.star').hover(function() {
        const $container = $(this).closest('.star-rating');
        paintStars($container, parseInt($(this).data('value'), 10) || 0);
    }, function() {
        const $container = $(this).closest('.star-rating');
        const savedRating = parseInt($container.data('user-rating'), 10) || 0;
        paintStars($container, savedRating);
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
                if (response.success) {
                    const $container = $('#rating-container');
                    $container.data('user-rating', response.rating);
                    paintStars($container, parseInt(response.rating, 10) || 0);
                }
            },
            error: function() {
                $('#rating-msg').html('<div class="alert alert-danger">Error connecting to server.</div>');
            }
        });
    });
});
