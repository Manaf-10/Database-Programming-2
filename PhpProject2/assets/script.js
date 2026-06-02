$(document).ready(function() {
    // Save user collapse preference to localStorage when manually toggled
    $(document).on('shown.bs.collapse', '#latestRecipesCollapse', function() {
        localStorage.setItem('latestRecipesCollapsed', 'false');
    });

    $(document).on('hidden.bs.collapse', '#latestRecipesCollapse', function() {
        localStorage.setItem('latestRecipesCollapsed', 'true');
    });

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
            // Respect the user's saved preference when on page 1
            const isCollapsed = localStorage.getItem('latestRecipesCollapsed') === 'true';
            if (isCollapsed) {
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
    }

    // Dynamic dropdown label behavior for checked creators
    function updateCreatorDropdownLabel() {
        const $checked = $('.creator-checkbox:checked');
        const $label = $('#creator-dropdown-label');
        if (!$label.length) return;

        if ($checked.length === 0) {
            $label.text('All Creators');
        } else if ($checked.length === 1) {
            $label.text($checked.first().parent().find('label').text().trim());
        } else {
            $label.text($checked.length + ' selected');
        }
    }

    // Call update on page load (in case of browser refresh with inputs saved)
    updateCreatorDropdownLabel();

    $(document).on('change', '.creator-checkbox', function() {
        updateCreatorDropdownLabel();
    });

    // Client-side image upload live preview & 1.5MB validation handler
    $(document).on('change', '#recipe-image-input', function() {
        const file = this.files[0];
        if (file) {
            const maxSize = 1.5 * 1024 * 1024; // 1.5 Megabytes in bytes

            if (file.size > maxSize) {
                alert('The selected image is too large. Please choose an image smaller than 1.5MB.');
                $(this).val(''); // Reset file input

                // Revert UI preview back to database state if a new selection was rejected
                const currentSrc = $('#image-preview').attr('src');
                if (!currentSrc || currentSrc.startsWith('data:')) {
                    $('#image-preview-container').addClass('d-none');
                    $('#image-preview').attr('src', '');
                    $('#upload-placeholder-icon').removeClass('d-none');
                    $('#upload-text-label').text('Upload cover image');
                }
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                $('#image-preview').attr('src', e.target.result);
                $('#image-preview-container').removeClass('d-none');
                $('#upload-placeholder-icon').addClass('d-none');
                $('#upload-text-label').text('Replace cover image');
            };
            reader.readAsDataURL(file);
        }
    });

    // Apply the saved preference immediately on initial page load if on page 1
    const initialPage = parseInt($('#all-recipes-container').attr('data-current-page'), 10) || 1;
    if (initialPage === 1) {
        const savedCollapsedState = localStorage.getItem('latestRecipesCollapsed');
        if (savedCollapsedState === 'true') {
            const latestCollapse = document.getElementById('latestRecipesCollapse');
            const latestToggle = document.querySelector('.latest-toggle');
            if (latestCollapse && window.bootstrap) {
                const collapse = bootstrap.Collapse.getOrCreateInstance(latestCollapse, { toggle: false });
                collapse.hide();
                if (latestToggle) {
                    latestToggle.setAttribute('aria-expanded', 'false');
                }
            }
        }
    }

    function loadRecipes(page, pushState = true) {
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
                if (pushState) {
                    const newUrl = 'index.php?' + $.param(query);
                    window.history.pushState({ page: page }, '', newUrl);
                }
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

        // If the clicked button is already active, clear it (unselect). Otherwise, set it.
        const targetSort = (currentSort === sort) ? '' : sort;

        $('#sort-input').val(targetSort);
        updateSortButtons(targetSort);
        loadRecipes(1);
    });

    let creatorSearchTimer = null;

    function loadCreatorRecipes(creatorId) {
        const $input = $('#creator-search-input');
        const recipesUrl = $input.data('recipes-url');
        const $body = $('#creator-recipes-body');

        if (!recipesUrl || !$body.length) {
            return;
        }

        if (!creatorId) {
            $body.html('<tr><td colspan="6" class="text-muted fst-italic text-center">Select a creator to view recipes.</td></tr>');
            return;
        }

        $body.html('<tr><td colspan="6" class="text-muted fst-italic text-center">Loading recipes...</td></tr>');

        $.ajax({
            url: recipesUrl,
            type: 'GET',
            data: { creator_id: creatorId },
            success: function(html) {
                $body.html(html);
            },
            error: function() {
                $body.html('<tr><td colspan="6" class="text-danger text-center">Unable to load creator recipes.</td></tr>');
            }
        });
    }

    $('#creator-search-input').on('input', function() {
        const $input = $(this);
        const term = $input.val().trim();
        const searchUrl = $input.data('search-url');
        const $results = $('#creator-search-results');

        $('#creator-id-input').val('');
        clearTimeout(creatorSearchTimer);

        if (!searchUrl || term.length < 1) {
            $results.addClass('d-none').empty();
            return;
        }

        creatorSearchTimer = setTimeout(function() {
            $.ajax({
                url: searchUrl,
                type: 'GET',
                dataType: 'json',
                data: { term: term },
                success: function(creators) {
                    $results.empty();

                    if (!Array.isArray(creators) || creators.length === 0) {
                        $results
                            .removeClass('d-none')
                            .html('<div class="creator-search-empty">No creators found</div>');
                        return;
                    }

                    creators.forEach(function(creator) {
                        $('<button>', {
                            type: 'button',
                            class: 'creator-search-item',
                            text: creator.username
                        })
                            .attr('data-id', creator.id)
                            .attr('data-username', creator.username)
                            .appendTo($results);
                    });

                    $results.removeClass('d-none');
                },
                error: function() {
                    $results.addClass('d-none').empty();
                }
            });
        }, 180);
    });

    $(document).on('click', '.creator-search-item', function() {
        $('#creator-search-input').val($(this).data('username'));
        $('#creator-id-input').val($(this).data('id'));
        $('#creator-search-results').addClass('d-none').empty();
        loadCreatorRecipes($(this).data('id'));
    });

    $('.creator-search-form').on('submit', function(event) {
        const creatorId = $('#creator-id-input').val();

        // Only prevent default and load via AJAX if a valid autocomplete ID is populated.
        // Otherwise, allow standard HTML submission so PHP can resolve the username lookup safely.
        if (creatorId) {
            event.preventDefault();
            loadCreatorRecipes(creatorId);
        }
    });

    $(document).on('click', function(event) {
        if (!$(event.target).closest('.creator-search-form').length) {
            $('#creator-search-results').addClass('d-none');
        }
    });

    window.addEventListener('popstate', function() {
        const params = new URLSearchParams(window.location.search);
        const $form = $('#recipe-search-form');

        if ($form.length) {
            $form.find('input[name="search"]').val(params.get('search') || '');
            $form.find('input[name="creator"]').val(params.get('creator') || '');
            $form.find('select[name="category"]').val(params.get('category') || '');
            $form.find('input[name="date_from"]').val(params.get('date_from') || '');
            $form.find('input[name="date_to"]').val(params.get('date_to') || '');

            // Restore creator dropdown checkboxes
            $form.find('.creator-checkbox').prop('checked', false);
            const creators = params.getAll('creator[]');
            creators.forEach(function(val) {
                $form.find('.creator-checkbox[value="' + val + '"]').prop('checked', true);
            });
            updateCreatorDropdownLabel();

            const sort = params.get('sort') || '';
            $('#sort-input').val(sort);
            updateSortButtons(sort);
        }

        const page = parseInt(params.get('page'), 10) || 1;
        loadRecipes(page, false);
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
                $('#rating-msg').html('<div class="alert ' + cssClass + ' mt-2 mb-0">' + response.message + '</div>');
                if (response.success) {
                    const $container = $('#rating-container');
                    $container.data('user-rating', response.rating);
                    paintStars($container, parseInt(response.rating, 10) || 0);
                }
            },
            error: function() {
                $('#rating-msg').html('<div class="alert alert-danger mt-2 mb-0">Error connecting to server.</div>');
            }
        });
    });
});