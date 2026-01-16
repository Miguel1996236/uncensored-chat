// Configuración
const API_URL = 'api.php';
const MAX_RETRIES = 3;

// Estado
let isProcessing = false;
let currentAbortController = null;

// Elementos DOM
const chatMessages = document.getElementById('chat-messages');
const chatInput = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-btn');
const charCount = document.getElementById('char-count');
const clearBtn = document.getElementById('clear-chat');
const toggleAdvanced = document.getElementById('toggle-advanced');
const advancedOptions = document.getElementById('advanced-options');

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    setupEventListeners();
    setupConfigSliders();
    loadChatHistory();
});

// Event Listeners
function setupEventListeners() {
    sendBtn.addEventListener('click', handleSend);
    chatInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    });
    
    chatInput.addEventListener('input', () => {
        updateCharCount();
        autoResizeTextarea();
    });
    
    clearBtn.addEventListener('click', clearChat);
    toggleAdvanced.addEventListener('click', toggleAdvancedOptions);
}

// Configuración de sliders
function setupConfigSliders() {
    const sliders = {
        'temperature': 'temp-value',
        'top_p': 'top_p-value',
        'top_k': 'top_k-value',
        'num_predict': 'num_predict-value',
        'repeat_penalty': 'repeat_penalty-value'
    };
    
    Object.entries(sliders).forEach(([sliderId, displayId]) => {
        const slider = document.getElementById(sliderId);
        const display = document.getElementById(displayId);
        
        if (slider && display) {
            updateSliderDisplay(slider, display);
            slider.addEventListener('input', () => {
                updateSliderDisplay(slider, display);
            });
        }
    });
}

function updateSliderDisplay(slider, display) {
    if (display) {
        display.textContent = slider.value;
    }
}

// Manejo de envío
async function handleSend() {
    if (isProcessing) return;
    
    const prompt = chatInput.value.trim();
    if (!prompt) return;
    
    // Agregar mensaje del usuario
    addMessage('user', prompt);
    chatInput.value = '';
    updateCharCount();
    autoResizeTextarea();
    
    // Mostrar mensaje de carga
    const loadingId = addLoadingMessage();
    
    isProcessing = true;
    sendBtn.disabled = true;
    
    const startTime = Date.now();
    
    try {
        const config = getConfig(prompt);
        const response = await fetchAPI(config);
        
        const duration = Date.now() - startTime;
        const tokens = response.eval_count || 0;
        
        removeMessage(loadingId);
        addMessage('assistant', response.response || 'Sin respuesta', false, {
            duration,
            tokens,
            model: config.model
        });
        
        // Actualizar indicador de estado
        if (window.chatFeatures) {
            const speed = tokens > 0 ? (tokens / (duration / 1000)).toFixed(1) : 'N/A';
            window.chatFeatures.updateStatusIndicator(
                `✓ Completado en ${(duration/1000).toFixed(1)}s | ${tokens} tokens | ${speed} tok/s`
            );
            
            // Track analytics
            if (window.chatFeatures.trackRequest) {
                window.chatFeatures.trackRequest(duration, tokens, false);
            }
        }
        
        saveChatHistory();
    } catch (error) {
        const duration = Date.now() - startTime;
        removeMessage(loadingId);
        addMessage('assistant', `❌ Error: ${error.message}`, true);
        
        if (window.chatFeatures) {
            window.chatFeatures.updateStatusIndicator(`✗ Error después de ${(duration/1000).toFixed(1)}s`);
            if (window.chatFeatures.trackRequest) {
                window.chatFeatures.trackRequest(duration, 0, true);
            }
        }
        
        console.error('Error:', error);
    } finally {
        isProcessing = false;
        sendBtn.disabled = false;
    }
}

// Obtener configuración
function getConfig(prompt) {
    const streamValue = document.getElementById('stream').checked;
    const config = {
        model: document.getElementById('model').value || 'llama2-uncensored',
        prompt: prompt || getLastUserMessage(),
        stream: streamValue, // Asegurar que siempre se envíe (true o false)
    };
    
    const system = document.getElementById('system').value.trim();
    if (system) {
        config.system = system;
    }
    
    const format = document.getElementById('format').value;
    if (format) {
        config.format = format;
    }
    
    // Opciones
    const options = {};
    
    const temperature = parseFloat(document.getElementById('temperature').value);
    if (temperature !== 0.3) {
        options.temperature = temperature;
    }
    
    const topP = document.getElementById('top_p').value;
    if (topP && topP !== '0.95') {
        options.top_p = parseFloat(topP);
    }
    
    const topK = document.getElementById('top_k').value;
    if (topK && topK !== '40') {
        options.top_k = parseInt(topK);
    }
    
    const numPredict = document.getElementById('num_predict').value;
    if (numPredict && numPredict !== '2000') {
        options.num_predict = parseInt(numPredict);
    }
    
    const repeatPenalty = document.getElementById('repeat_penalty').value;
    if (repeatPenalty && repeatPenalty !== '1.15') {
        options.repeat_penalty = parseFloat(repeatPenalty);
    }
    
    const seed = document.getElementById('seed').value;
    if (seed) {
        options.seed = parseInt(seed);
    }
    
    const stop = document.getElementById('stop').value.trim();
    if (stop) {
        try {
            options.stop = JSON.parse(stop);
        } catch (e) {
            console.warn('Error parsing stop sequences:', e);
        }
    }
    
    if (Object.keys(options).length > 0) {
        config.options = options;
    }
    
    return config;
}

function getLastUserMessage() {
    const messages = chatMessages.querySelectorAll('.message.user');
    if (messages.length > 0) {
        return messages[messages.length - 1].querySelector('.message-content').textContent;
    }
    return '';
}

// Llamada a la API
async function fetchAPI(config) {
    currentAbortController = new AbortController();
    
    const response = await fetch(API_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(config),
        signal: currentAbortController.signal
    });
    
    if (!response.ok) {
        // Intentar obtener el error como JSON, si falla obtener como texto
        let errorMessage = `Error HTTP: ${response.status}`;
        try {
            const errorData = await response.json();
            errorMessage = errorData.error || errorMessage;
        } catch (e) {
            const errorText = await response.text();
            errorMessage = errorText || errorMessage;
        }
        throw new Error(errorMessage);
    }
    
    // Obtener la respuesta como texto primero para debug
    const responseText = await response.text();
    
    // Verificar que sea JSON válido
    if (!responseText.trim().startsWith('{') && !responseText.trim().startsWith('[')) {
        console.error('Response is not JSON:', responseText.substring(0, 200));
        throw new Error('La respuesta del servidor no es JSON válido. Respuesta: ' + responseText.substring(0, 200));
    }
    
    try {
        return JSON.parse(responseText);
    } catch (e) {
        console.error('JSON Parse Error:', e);
        console.error('Response text:', responseText.substring(0, 500));
        throw new Error('Error al parsear JSON: ' + e.message + '. Respuesta: ' + responseText.substring(0, 200));
    }
}

// Agregar mensaje
function addMessage(type, content, isError = false, metadata = {}) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}${isError ? ' error' : ''}`;
    messageDiv.dataset.timestamp = new Date().toISOString();
    
    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';
    
    if (type === 'assistant' && !isError) {
        // Renderizar markdown
        if (typeof marked !== 'undefined') {
            contentDiv.innerHTML = marked.parse(content);
        } else {
            contentDiv.textContent = content;
        }
        
        // Agregar botón copiar
        const copyBtn = document.createElement('button');
        copyBtn.className = 'btn-copy';
        copyBtn.innerHTML = '📋';
        copyBtn.title = 'Copiar respuesta';
        copyBtn.style.cssText = `
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(0,0,0,0.1);
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s;
        `;
        copyBtn.onclick = (e) => {
            e.stopPropagation();
            if (window.chatFeatures) {
                window.chatFeatures.copyToClipboard(content);
            }
        };
        messageDiv.style.position = 'relative';
        messageDiv.appendChild(copyBtn);
        
        messageDiv.onmouseenter = () => copyBtn.style.opacity = '1';
        messageDiv.onmouseleave = () => copyBtn.style.opacity = '0';
    } else {
        contentDiv.textContent = content;
    }
    
    // Agregar timestamp
    const timestamp = document.createElement('div');
    timestamp.className = 'message-timestamp';
    timestamp.textContent = new Date().toLocaleTimeString();
    timestamp.style.cssText = `
        font-size: 0.75rem;
        color: var(--text-muted);
        margin-top: 8px;
        opacity: 0.7;
    `;
    
    messageDiv.appendChild(contentDiv);
    if (type === 'assistant' || type === 'user') {
        messageDiv.appendChild(timestamp);
    }
    chatMessages.appendChild(messageDiv);
    
    // Remover mensaje de bienvenida si existe
    const welcomeMsg = chatMessages.querySelector('.welcome-message');
    if (welcomeMsg) {
        welcomeMsg.remove();
    }
    
    scrollToBottom();
    
    return messageDiv;
}

// Agregar mensaje de carga
function addLoadingMessage() {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message assistant loading';
    messageDiv.id = 'loading-' + Date.now();
    
    const dots = document.createElement('div');
    dots.className = 'loading-dots';
    dots.innerHTML = '<span></span><span></span><span></span>';
    
    messageDiv.appendChild(dots);
    chatMessages.appendChild(messageDiv);
    scrollToBottom();
    
    return messageDiv.id;
}

// Remover mensaje
function removeMessage(id) {
    const message = document.getElementById(id);
    if (message) {
        message.remove();
    }
}

// Scroll al final
function scrollToBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Actualizar contador de caracteres
function updateCharCount() {
    const count = chatInput.value.length;
    charCount.textContent = count;
    
    if (count > 4500) {
        charCount.style.color = '#ef4444';
    } else if (count > 4000) {
        charCount.style.color = '#f59e0b';
    } else {
        charCount.style.color = '';
    }
}

// Auto-resize textarea
function autoResizeTextarea() {
    chatInput.style.height = 'auto';
    chatInput.style.height = Math.min(chatInput.scrollHeight, 200) + 'px';
}

// Limpiar chat
function clearChat() {
    if (!confirm('¿Estás seguro de que quieres limpiar todo el chat?')) {
        return;
    }
    
    chatMessages.innerHTML = `
        <div class="welcome-message">
            <h2>👋 ¡Bienvenido!</h2>
            <p>Escribe tu pregunta o consulta en el campo de abajo y el asistente te responderá.</p>
            <p class="tip">💡 Tip: Ajusta la temperatura para controlar qué tan creativas son las respuestas.</p>
        </div>
    `;
    
    localStorage.removeItem('chatHistory');
}

// Toggle opciones avanzadas
function toggleAdvancedOptions() {
    const isVisible = advancedOptions.style.display !== 'none';
    advancedOptions.style.display = isVisible ? 'none' : 'block';
    toggleAdvanced.textContent = isVisible 
        ? '🔧 Opciones Avanzadas' 
        : '🔽 Ocultar Opciones';
}

// Guardar historial
function saveChatHistory() {
    const messages = Array.from(chatMessages.querySelectorAll('.message')).map(msg => {
        const type = msg.classList.contains('user') ? 'user' : 'assistant';
        const content = msg.querySelector('.message-content').textContent;
        return { type, content };
    });
    
    localStorage.setItem('chatHistory', JSON.stringify(messages));
}

// Cargar historial
function loadChatHistory() {
    try {
        const history = localStorage.getItem('chatHistory');
        if (history) {
            const messages = JSON.parse(history);
            if (messages.length > 0) {
                chatMessages.innerHTML = '';
                messages.forEach(msg => {
                    addMessage(msg.type, msg.content);
                });
            }
        }
    } catch (e) {
        console.error('Error loading chat history:', e);
    }
}
