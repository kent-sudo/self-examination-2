jQuery(document).ready(function($) {
    $('#summernote_kent').summernote({
        height:350,
        minHeight:null,    // 定义编辑框最低的高度
        maxHeight:null,    // 定义编辑框最高的高度
        disableDragAndDrop:true,   // 禁止拖拽
        lang:'zh-CN'
    });
});