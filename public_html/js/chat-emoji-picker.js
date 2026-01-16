/**
 * Selector de Emojis para Chat
 * Integración ligera con emojis más usados
 */

const EMOJI_CATEGORIES = {
    'frecuentes': ['😀', '😂', '❤️', '👍', '😊', '😍', '🙏', '😎', '🔥', '💯'],
    'caras': ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓'],
    'gestos': ['👋', '🤚', '🖐', '✋', '🖖', '👌', '🤌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎', '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🤝', '🙏'],
    'objetos': ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎', '♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐', '♑', '♒', '♓', '🆔', '⚛️', '🉑', '☢️', '☣️'],
    'símbolos': ['✅', '❌', '❓', '❔', '❕', '❗', '💯', '🔞', '⚡', '🔥', '💧', '⭐', '🌟', '✨', '💫', '💥', '💢', '💤', '💨', '👁️', '👁️‍🗨️', '🧠', '🗣️', '👤', '👥', '🫂', '👶', '🧒', '👦', '👧', '🧑', '👱', '👨', '🧔', '👩', '🧓', '👴', '👵', '🙍', '🙎', '🙅', '🙆', '💁', '🙋', '🧏', '🙇', '🤦', '🤷', '👮', '🕵️', '💂', '🥷', '👷', '🤴', '👸', '👳', '👲', '🧕', '🤵', '👰', '🤰', '🤱', '👼', '🎅', '🤶', '🦸', '🦹', '🧙', '🧚', '🧛', '🧜', '🧝', '🧞', '🧟', '💆', '💇', '🚶', '🧍', '🧎', '🏃', '💃', '🕺', '🕴️', '👯', '🧘', '🧗', '🤺', '🏇', '⛷️', '🏂', '🏌️', '🏄', '🚣', '🏊', '⛹️', '🏋️', '🚴', '🚵', '🤸', '🤼', '🤽', '🤾', '🤹', '🧗', '🛀', '🛌', '👭', '👫', '👬', '💏', '💑', '👪', '👨‍👩‍👧', '👨‍👩‍👧‍👦', '👨‍👩‍👦‍👦', '👨‍👩‍👧‍👧', '👩‍👩‍👦', '👩‍👩‍👧', '👩‍👩‍👧‍👦', '👩‍👩‍👦‍👦', '👩‍👩‍👧‍👧', '👨‍👨‍👦', '👨‍👨‍👧', '👨‍👨‍👧‍👦', '👨‍👨‍👦‍👦', '👨‍👨‍👧‍👧', '👩‍👦', '👩‍👧', '👩‍👧‍👦', '👩‍👦‍👦', '👩‍👧‍👧', '👨‍👦', '👨‍👧', '👨‍👧‍👦', '👨‍👦‍👦', '👨‍👧‍👧']
};

let emojiPickerVisible = false;
let emojiPickerContainer = null;

$(document).ready(function() {
    initEmojiPicker();
    
    $('#emojiPickerBtn').on('click', function(e) {
        e.preventDefault();
        toggleEmojiPicker();
    });
    
    // Cerrar al hacer click fuera
    $(document).on('click', function(e) {
        if (emojiPickerVisible && !$(e.target).closest('#emojiPickerContainer, #emojiPickerBtn').length) {
            hideEmojiPicker();
        }
    });
});

function initEmojiPicker() {
    emojiPickerContainer = $('#emojiPickerContainer');
    
    if (emojiPickerContainer.length === 0) {
        emojiPickerContainer = $('<div id="emojiPickerContainer" class="emoji-picker-container"></div>');
        $('body').append(emojiPickerContainer);
    }
    
    buildEmojiPicker();
}

function buildEmojiPicker() {
    let html = '<div class="emoji-picker">';
    html += '<div class="emoji-picker-header">';
    html += '<div class="emoji-tabs">';
    
    // Tabs por categoría
    Object.keys(EMOJI_CATEGORIES).forEach(function(category, index) {
        const active = index === 0 ? 'active' : '';
        html += `<button class="emoji-tab ${active}" data-category="${category}">${getCategoryIcon(category)}</button>`;
    });
    
    html += '</div>';
    html += '<button class="emoji-picker-close" onclick="hideEmojiPicker()"><i class="fas fa-times"></i></button>';
    html += '</div>';
    html += '<div class="emoji-picker-content">';
    
    // Contenido por categoría
    Object.keys(EMOJI_CATEGORIES).forEach(function(category, index) {
        const active = index === 0 ? 'active' : '';
        html += `<div class="emoji-category ${active}" data-category="${category}">`;
        EMOJI_CATEGORIES[category].forEach(function(emoji) {
            html += `<span class="emoji-item" data-emoji="${emoji}">${emoji}</span>`;
        });
        html += '</div>';
    });
    
    html += '</div>';
    html += '</div>';
    
    emojiPickerContainer.html(html);
    
    // Event handlers
    $('.emoji-tab').on('click', function() {
        const category = $(this).data('category');
        switchEmojiCategory(category);
    });
    
    $('.emoji-item').on('click', function() {
        const emoji = $(this).data('emoji');
        insertEmoji(emoji);
    });
}

function getCategoryIcon(category) {
    const icons = {
        'frecuentes': '⭐',
        'caras': '😀',
        'gestos': '👍',
        'objetos': '❤️',
        'símbolos': '✨'
    };
    return icons[category] || '😀';
}

function switchEmojiCategory(category) {
    $('.emoji-tab').removeClass('active');
    $(`.emoji-tab[data-category="${category}"]`).addClass('active');
    
    $('.emoji-category').removeClass('active');
    $(`.emoji-category[data-category="${category}"]`).addClass('active');
}

function toggleEmojiPicker() {
    if (emojiPickerVisible) {
        hideEmojiPicker();
    } else {
        showEmojiPicker();
    }
}

function showEmojiPicker() {
    if (!emojiPickerContainer) {
        initEmojiPicker();
    }
    
    const btn = $('#emojiPickerBtn');
    const btnOffset = btn.offset();
    const btnHeight = btn.outerHeight();
    
    emojiPickerContainer.css({
        position: 'absolute',
        bottom: '60px',
        left: btnOffset.left + 'px',
        display: 'block'
    });
    
    emojiPickerVisible = true;
}

function hideEmojiPicker() {
    if (emojiPickerContainer) {
        emojiPickerContainer.hide();
    }
    emojiPickerVisible = false;
}

function insertEmoji(emoji) {
    const input = $('#messageInput');
    const currentValue = input.val();
    const cursorPos = input[0].selectionStart;
    const newValue = currentValue.substring(0, cursorPos) + emoji + currentValue.substring(cursorPos);
    input.val(newValue);
    input.focus();
    
    // Mover cursor después del emoji
    input[0].setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
    
    // Trigger input event para indicador de typing
    input.trigger('input');
}

