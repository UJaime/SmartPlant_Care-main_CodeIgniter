<!-- ═══════════════════════════════════════════════════════════════
     COMPONENTE COMPARTIDO: SMARTPLANT AI ASSISTANT (GEMINI)
     ═══════════════════════════════════════════════════════════════ -->
<button class="ai-chat-btn" id="aiChatBtn" onclick="toggleAIChat()" title="Asistente SmartPlant IA">
    <i data-lucide="sparkles" class="w-6 h-6 text-white"></i>
</button>

<div class="ai-chat-window" id="aiChatWindow">
    <div class="ai-chat-header flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-md">
                <i data-lucide="sparkles" class="w-4 h-4 text-white"></i>
            </div>
            <div>
                <h4 class="font-bold text-xs text-slate-900 dark:text-white leading-tight">SmartPlant AI</h4>
                <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Gemini 2.5 Flash
                </p>
            </div>
        </div>
        <button onclick="toggleAIChat()" class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
    
    <div class="ai-quick-prompts">
        <button class="ai-quick-prompt-btn" onclick="sendQuickPrompt('¿Cómo está mi planta?')">¿Cómo está mi planta?</button>
        <button class="ai-quick-prompt-btn" onclick="sendQuickPrompt('¿Qué especie de planta tengo?')">Identificar especie</button>
        <button class="ai-quick-prompt-btn" onclick="sendQuickPrompt('Dame consejos de riego y luz')">Consejos de riego</button>
        <button class="ai-quick-prompt-btn" onclick="sendQuickPrompt('¿Cómo funciona el sistema SmartPlant?')">Sobre SmartPlant</button>
    </div>

    <div class="ai-chat-messages" id="aiChatMessages">
        <div class="msg-bubble msg-ai">
            ¡Hola! Soy tu asistente botánico de SmartPlant impulsado por IA Gemini. Puedo responder tus dudas sobre cuidados, identificar especies o analizar tus sensores. ¿En qué te ayudo hoy? 🌱
        </div>
    </div>

    <div class="ai-chat-footer">
        <div class="img-upload-preview" id="aiImgPreviewContainer">
            <img id="aiImgPreview" src="" alt="Preview">
            <span class="img-remove" onclick="removeAIImage()">✕</span>
        </div>
        <form id="aiChatForm" class="ai-chat-input-wrapper" onsubmit="handleAIChatSubmit(event)">
            <input type="hidden" id="aiPlantaId" value="<?= isset($planta_id) ? htmlspecialchars($planta_id) : 0 ?>">
            <input type="file" id="aiImageInput" accept="image/jpeg, image/png, image/webp" class="hidden" onchange="previewAIImage(event)">
            <button type="button" class="ai-chat-action-btn" onclick="document.getElementById('aiImageInput').click()" title="Subir foto">
                <i data-lucide="camera" class="w-4 h-4"></i>
            </button>
            <input type="text" id="aiChatInput" class="ai-chat-input" placeholder="Preguntale a Gemini..." autocomplete="off">
            <button type="submit" class="ai-chat-action-btn ai-chat-send" id="aiChatSendBtn">
                <i data-lucide="send" class="w-3.5 h-3.5"></i>
            </button>
        </form>
    </div>
</div>

<script>
    function toggleAIChat() {
        const win = document.getElementById('aiChatWindow');
        if (win) {
            win.classList.toggle('visible');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function sendQuickPrompt(text) {
        const input = document.getElementById('aiChatInput');
        const form = document.getElementById('aiChatForm');
        if (input && form) {
            input.value = text;
            form.dispatchEvent(new Event('submit'));
        }
    }

    function previewAIImage(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(ev) {
                const preview = document.getElementById('aiImgPreview');
                const container = document.getElementById('aiImgPreviewContainer');
                if (preview && container) {
                    preview.src = ev.target.result;
                    container.classList.add('active');
                }
            }
            reader.readAsDataURL(file);
        }
    }

    function removeAIImage() {
        const fileInput = document.getElementById('aiImageInput');
        const container = document.getElementById('aiImgPreviewContainer');
        const preview = document.getElementById('aiImgPreview');
        if (fileInput) fileInput.value = '';
        if (container) container.classList.remove('active');
        if (preview) preview.src = '';
    }

    function addChatMessage(text, type, imgUrl = null) {
        const container = document.getElementById('aiChatMessages');
        if (!container) return;

        const bubble = document.createElement('div');
        bubble.className = `msg-bubble msg-${type}`;
        
        if (imgUrl) {
            const img = document.createElement('img');
            img.src = imgUrl;
            img.className = 'msg-img-preview';
            bubble.appendChild(img);
        }
        
        const textSpan = document.createElement('span');
        textSpan.innerHTML = text.replace(/\n/g, '<br>');
        bubble.appendChild(textSpan);
        
        container.appendChild(bubble);
        container.scrollTop = container.scrollHeight;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function showAITyping() {
        const container = document.getElementById('aiChatMessages');
        if (!container) return;

        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble msg-ai';
        bubble.id = 'aiTypingIndicator';
        bubble.innerHTML = '<div class="ai-typing-indicator"><div class="ai-dot"></div><div class="ai-dot"></div><div class="ai-dot"></div></div>';
        container.appendChild(bubble);
        container.scrollTop = container.scrollHeight;
    }

    function hideAITyping() {
        const ind = document.getElementById('aiTypingIndicator');
        if (ind) ind.remove();
    }

    async function handleAIChatSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('aiChatInput');
        const fileInput = document.getElementById('aiImageInput');
        const plantaInput = document.getElementById('aiPlantaId');
        
        const msg = input ? input.value.trim() : '';
        const file = fileInput && fileInput.files ? fileInput.files[0] : null;
        const plantaId = plantaInput ? plantaInput.value : 0;

        if (!msg && !file) return;

        let imgUrl = null;
        if (file) {
            const preview = document.getElementById('aiImgPreview');
            imgUrl = preview ? preview.src : null;
        }

        addChatMessage(msg || 'Fotografía adjunta', 'user', imgUrl);
        
        if (input) input.value = '';
        removeAIImage();
        showAITyping();

        const formData = new FormData();
        formData.append('mensaje', msg);
        if (plantaId) formData.append('planta_id', plantaId);
        if (file) formData.append('imagen', file);

        try {
            const res = await fetch('/ai-assistant', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            hideAITyping();
            
            if (data.error) {
                addChatMessage('⚠️ ' + data.error, 'ai');
            } else {
                addChatMessage(data.respuesta, 'ai');
            }
        } catch (err) {
            hideAITyping();
            addChatMessage('⚠️ No pude conectarme con el servidor de IA.', 'ai');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
