$(document).ready(function () {
    // Настройка marked.js
    marked.setOptions({
        highlight: function (code, lang) {
            if (lang && hljs.getLanguage(lang)) {
                try {
                    return hljs.highlight(code, { language: lang }).value;
                } catch (err) { }
            }
            return hljs.highlightAuto(code).value;
        },
        breaks: true,
        gfm: true
    });

    // Рендеринг Markdown контента
    $('.post-content').each(function () {
        var markdown = $(this).data('markdown');
        if (markdown) {
            var html = marked.parse(markdown);
            $(this).html(html);
            hljs.highlightAll();

            // Добавление кнопок копирования к блокам кода
            $(this).find('pre code').each(function (i) {
                var code = $(this);
                var pre = code.parent();
                var uniqueId = 'code-' + i;
                code.attr('id', uniqueId);

                // Определение языка
                var lang = 'text';
                var classes = code.attr('class');
                if (classes) {
                    var match = classes.match(/language-(\w+)/);
                    if (match) {
                        lang = match[1];
                    }
                }

                // Обертка для блока кода
                var wrapper = $('<div class="code-block"></div>');
                var header = $('<div class="code-header"></div>');
                header.append('<span class="code-lang">' + lang + '</span>');
                header.append('<button class="btn btn-sm btn-secondary copy-code" data-target="' + uniqueId + '">Копировать</button>');

                pre.wrap(wrapper);
                pre.before(header);
            });
        }
    });

    // Копирование кода
    $(document).on('click', '.copy-code', function () {
        var targetId = $(this).data('target');
        var codeBlock = $('#' + targetId);

        if (codeBlock.length) {
            var text = codeBlock.text();
            copyToClipboard(text, $(this));
        }
    });

    // Копирование Markdown для файлов
    $(document).on('click', '.copy-markdown', function () {
        var filename = $(this).data('filename');
        var original = $(this).data('original');
        var isImage = $(this).data('is-image');

        var markdown;
        if (isImage) {
            markdown = '![' + original + '](/uploads/' + encodeURIComponent(filename) + ')';
        } else {
            markdown = '[' + original + '](/uploads/' + encodeURIComponent(filename) + ')';
        }

        copyToClipboard(markdown, $(this));
    });

    // Функция копирования в буфер обмена
    function copyToClipboard(text, button) {
        var tempInput = $('<textarea>');
        $('body').append(tempInput);
        tempInput.val(text).select();
        document.execCommand('copy');
        tempInput.remove();

        // Визуальная обратная связь
        var originalText = button.text();
        button.text('Скопировано!');

        setTimeout(function () {
            button.text(originalText);
        }, 2000);
    }

    // Загрузка файлов на странице создания/редактирования поста
    $('#file-upload').on('change', function () {
        uploadFile(this, '#file-upload-result');
    });

    // Загрузка файлов в админке
    $('#admin-file-upload').on('change', function () {
        uploadFile(this, '#admin-file-upload-result', function () {
            // Перезагрузка страницы после успешной загрузки
            setTimeout(function () {
                location.reload();
            }, 1500);
        });
    });

    // Функция загрузки файла
    function uploadFile(input, resultSelector, callback) {
        var file = input.files[0];
        if (!file) return;

        var formData = new FormData();
        formData.append('file', file);

        $(resultSelector).html('<div class="alert alert-info">Загрузка файла "' + file.name + '"...</div>');

        $.ajax({
            url: '/upload.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    var isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(response.filename);
                    var markdown = isImage ? response.markdown_image : response.markdown_link;

                    var message = '<div class="alert alert-success">' +
                        '<strong>Файл загружен успешно!</strong><br>' +
                        '<strong>Оригинальное имя:</strong> ' + response.original_name + '<br>' +
                        '<strong>Сохранено как:</strong> ' + response.filename + '<br>' +
                        '<a href="' + response.url + '" target="_blank">Открыть файл</a><br><br>' +
                        '<strong>Markdown для вставки:</strong><br>' +
                        '<input type="text" class="form-control mt-2" value="' + escapeHtml(markdown) + '" readonly onclick="this.select()">' +
                        '</div>';

                    $(resultSelector).html(message);

                    // Очистка input
                    $(input).val('');

                    if (callback) {
                        callback(response);
                    }
                } else {
                    var errorMsg = response.error || 'Неизвестная ошибка';
                    $(resultSelector).html('<div class="alert alert-danger"><strong>Ошибка:</strong> ' + errorMsg + '</div>');
                }
            },
            error: function (xhr) {
                var errorMsg = 'Ошибка загрузки файла';

                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMsg = response.error;
                    }
                } catch (e) {
                    if (xhr.responseText) {
                        errorMsg += ': ' + xhr.responseText;
                    }
                }

                $(resultSelector).html('<div class="alert alert-danger"><strong>Ошибка:</strong> ' + errorMsg + '</div>');
            }
        });
    }

    // Функция экранирования HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function (m) { return map[m]; });
    }
});