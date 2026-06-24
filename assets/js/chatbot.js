document.addEventListener('DOMContentLoaded', function () {
    const chatToggle = document.getElementById('chat-toggle');
    const chatBox = document.getElementById('chat-box');
    const chatClose = document.getElementById('chat-close');
    const chatSend = document.getElementById('chat-send');
    const chatInput = document.getElementById('chat-message');
    const chatMessages = document.getElementById('chat-messages');

    if (!chatToggle || !chatBox) {
        console.error('Không tìm thấy nút Foodie AI hoặc khung chat.');
        return;
    }

    chatToggle.addEventListener('click', function () {
        chatBox.classList.remove('hidden');
    });

    if (chatClose) {
        chatClose.addEventListener('click', function () {
            chatBox.classList.add('hidden');
        });
    }

    if (chatSend) {
        chatSend.addEventListener('click', sendMessage);
    }

    if (chatInput) {
        chatInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }

    function appendMessage(sender, text) {
        const div = document.createElement('div');
        div.className = sender === 'user' ? 'msg user' : 'msg ai';
        div.innerText = text;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function sendMessage() {
        const message = chatInput.value.trim();

        if (!message) {
            return;
        }

        appendMessage('user', message);
        chatInput.value = '';

        const formData = new FormData();
        formData.append('message', message);

        fetch('/api/ai_chat.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.need_table) {
                appendMessage('ai', res.message);
                if (typeof openTablePopup === 'function') {
                    openTablePopup();
                }
                return;
            }

            if (!res.success) {
                appendMessage('ai', res.message || 'Foodie AI đang lỗi.');
                return;
            }

            const data = res.data;

            appendMessage('ai', data.message || data.ly_do || 'Mình đã gợi ý cho bạn.');

            if (data.type === 'recommend_set' && data.set_mon && data.set_mon.length > 0) {
                renderRecommendSet(data);
            }
        })
        .catch(() => {
            appendMessage('ai', 'Không thể kết nối Foodie AI.');
        });
    }

    function renderRecommendSet(data) {
        const wrapper = document.createElement('div');
        wrapper.className = 'ai-set-box';

        let html = '<strong>Set gợi ý:</strong><br>';

        data.set_mon.forEach(item => {
            html += `${escapeHtml(item.ten_mon)} x ${item.so_luong} - ${Number(item.gia).toLocaleString('vi-VN')}đ<br>`;
        });

        html += `<b>Tổng: ${Number(data.tong_tien).toLocaleString('vi-VN')}đ</b><br>`;

        if (data.ly_do) {
            html += `<small>${escapeHtml(data.ly_do)}</small><br>`;
        }

        html += `<button type="button" id="add-ai-set-btn">Thêm toàn bộ set vào giỏ</button>`;

        wrapper.innerHTML = html;

        chatMessages.appendChild(wrapper);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        document.getElementById('add-ai-set-btn').addEventListener('click', function () {
            addSetToCart(data.set_mon);
        });
    }

    function addSetToCart(items) {
        const formData = new FormData();
        formData.append('items', JSON.stringify(items));

        fetch('/api/cart_add_set.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.need_table) {
                if (typeof openTablePopup === 'function') {
                    openTablePopup();
                }
                return;
            }

            appendMessage('ai', res.message);
        });
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function (m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[m];
        });
    }
});