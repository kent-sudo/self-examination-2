jQuery(document).ready(function($) {
    // 当主卡片被点击时
    $('.kent-main-card[data-card-id]').on('click', function(event) {
        event.preventDefault();

        // 获取被点击的主卡片的卡片ID
        var cardId = $(this).data('kent-card-id');

        // 找到具有相同卡片ID的所有子卡片容器
        var subCards = $('.kent-sub-cards-container[data-card-id="' + cardId + '"]');

        // 隐藏其他所有子卡片容器
        $('.kent-sub-cards-container').not(subCards).slideUp();

        // Hide all sub card descriptions
        $('.kent-sub-card-description').hide();

        // 切换被点击的主卡片关联的子卡片容器的可见性
        subCards.slideToggle();

        // 如果子卡片容器被展开，滚动页面以使其可见
        if (subCards.is(':visible')) {
            $('html, body').animate({
                scrollTop: subCards.offset().top-150
            }, 800);
        }
    });

    $('.kent-main-card').on('click', function() {

        $('.kent-main-card').removeClass('kent-main-card-clicked');

        $(this).addClass('kent-main-card-clicked');
    });

    $('.sub-card').click(function() {
        var cardId = $(this).data('card-id');

        // Hide all sub card descriptions
        $('.kent-sub-card-description').hide();

        // Show the description of the clicked sub card
        $('.kent-sub-card-description[data-card-id="' + cardId + '"]').show();
    });

    $('.kent-sub-cards-container').on('click', function() {
        // Remove the 'sub-card-clicked' class from all '.sub-cards-container' elements
        $('.kent-sub-cards-container').removeClass('kent-sub-cards-container-clicked');

        // Add the 'sub-card-clicked' class to the clicked '.sub-cards-container' element
        $(this).addClass('kent-sub-cards-container-clicked');
    });
});