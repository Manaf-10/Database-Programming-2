$(document).ready(function() {
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
