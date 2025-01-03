/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2022 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

function formXSSsanitize(form) {
    $(form).find("input, textarea").not(":submit, :reset, :image, :file, :disabled").not('[data-sanitize-ignore]').each(function() {
        $(this).val(DOMPurify.sanitize($(this).val(), {}));
    });
}

$(function() {
    $('body').on('click', '[type=submit]:not([name])', function(e) {
        var form = $(this).parents('form');
        if (XSSsanitize && !$('[name=submit]', form).length) {
            // Khi không xử lý XSS thì trình submit mặc định sẽ thực hiện
            e.preventDefault();

            // Đưa CKEditor 5 trình soạn thảo vào textarea trước khi submit
            $(form).find("textarea").each(function() {
                if (this.dataset.editorname && window.nveditor && window.nveditor[this.dataset.editorname]) {
                    $(this).val(window.nveditor[this.dataset.editorname].getData());
                }
            });

            formXSSsanitize(form);
            $(form).submit();
        }
    });
})
