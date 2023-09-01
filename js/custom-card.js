jQuery(document).ready(function($) {
    // 当主卡片被点击时
    $('.main-card[data-card-id]').on('click', function(event) {
        event.preventDefault();

        // 获取被点击的主卡片的卡片ID
        var cardId = $(this).data('card-id');

        // 找到具有相同卡片ID的所有子卡片容器
        var subCards = $('.sub-cards-container[data-card-id="' + cardId + '"]');

        // 隐藏其他所有子卡片容器
        $('.sub-cards-container').not(subCards).slideUp();

        // Hide all sub card descriptions
        $('.sub-card-description').hide();

        // 切换被点击的主卡片关联的子卡片容器的可见性
        subCards.slideToggle();

        // 如果子卡片容器被展开，滚动页面以使其可见
        if (subCards.is(':visible')) {
            $('html, body').animate({
                scrollTop: subCards.offset().top
            }, 800);
        }
    });

    $('.sub-card').click(function() {
        var cardId = $(this).data('card-id');

        // Hide all sub card descriptions
        $('.sub-card-description').hide();

        // Show the description of the clicked sub card
        $('.sub-card-description[data-card-id="' + cardId + '"]').show();
    });
});