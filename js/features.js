// Funcionalidades adicionales del chat

// Exportar conversaciones
function exportChat(format = 'json') {
    const messages = Array.from(chatMessages.querySelectorAll('.message')).map(msg => {
        const type = msg.classList.contains('user') ? 'user' : 'assistant';
        const content = msg.querySelector('.message-content').textContent;
        const timestamp = msg.dataset.timestamp || '';
        return { type, content, timestamp };
    });
    
    const data = {
        export_date: new Date().toISOString(),
        model: document.getElementById('model').value,
        messages: messages
    };
    
    let content, mimeType, filename;
    
    switch(format) {
        case 'json':
            content = JSON.stringify(data, null, 2);
            mimeType = 'application/json';
            filename = `chat-${Date.now()}.json`;
            break;
        case 'txt':
            content = messages.map(m => 
                `[${m.type.toUpperCase()}] ${m.timestamp}\n${m.content}\n\n`
            ).join('---\n\n');
            mimeType = 'text/plain';
            filename = `chat-${Date.now()}.txt`;
            break;
        case 'md':
            content = `# Chat Export\n\n**Fecha:** ${new Date().toLocaleString()}\n**Modelo:** ${data.model}\n\n---\n\n` +
                messages.map(m => 
                    `## ${m.type === 'user' ? 'Usuario' : 'Asistente'}\n\n${m.content}\n\n`
                ).join('---\n\n');
            mimeType = 'text/markdown';
            filename = `chat-${Date.now()}.md`;
            break;
        default:
            return;
    }
    
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
}

// Copiar respuesta al portapapeles
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('✓ Copiado al portapapeles');
        });
    } else {
        // Fallback para navegadores antiguos
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showNotification('✓ Copiado al portapapeles');
    }
}

// Mostrar notificación
function showNotification(message, duration = 2000) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--primary-color);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

// Búsqueda en historial
function searchInHistory(query) {
    const messages = Array.from(chatMessages.querySelectorAll('.message'));
    const lowerQuery = query.toLowerCase();
    
    messages.forEach(msg => {
        const content = msg.querySelector('.message-content').textContent.toLowerCase();
        if (content.includes(lowerQuery)) {
            msg.style.backgroundColor = 'rgba(59, 130, 246, 0.2)';
            msg.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            msg.style.backgroundColor = '';
        }
    });
}

// Plantillas de prompts
const promptTemplates = {
    'Científico': 'Eres un asistente científico experto. Responde de manera clara y precisa, usando terminología científica cuando sea apropiado.',
    'Programador': 'Eres un programador experto. Proporciona código limpio, bien comentado y siguiendo las mejores prácticas.',
    'Escritor': 'Eres un escritor creativo y profesional. Ayuda a crear contenido atractivo y bien estructurado.',
    'Traductor': 'Eres un traductor profesional. Traduce textos manteniendo el tono y contexto original.',
    'Analista': 'Eres un analista experto. Proporciona análisis detallados y fundamentados con datos.'
};

function loadPromptTemplate(name) {
    if (promptTemplates[name]) {
        document.getElementById('system').value = promptTemplates[name];
        showNotification(`Plantilla "${name}" cargada`);
    }
}

function savePromptTemplate(name, content) {
    promptTemplates[name] = content;
    localStorage.setItem('promptTemplates', JSON.stringify(promptTemplates));
    showNotification(`Plantilla "${name}" guardada`);
}

function loadSavedTemplates() {
    const saved = localStorage.getItem('promptTemplates');
    if (saved) {
        Object.assign(promptTemplates, JSON.parse(saved));
    }
}

// Atajos de teclado
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
        // Ctrl+K o Cmd+K: Nuevo chat
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (confirm('¿Iniciar un nuevo chat?')) {
                clearChat();
            }
        }
        
        // Ctrl+L o Cmd+L: Limpiar chat
        if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
            e.preventDefault();
            clearChat();
        }
        
        // Ctrl+S o Cmd+S: Exportar
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            exportChat('json');
        }
        
        // Ctrl+F o Cmd+F: Buscar
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            const query = prompt('Buscar en el historial:');
            if (query) {
                searchInHistory(query);
            }
        }
        
        // Esc: Cancelar/cerrar
        if (e.key === 'Escape') {
            // Cerrar modales o cancelar acciones
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => modal.style.display = 'none');
        }
    });
}

// Modo claro/oscuro
function toggleTheme() {
    const body = document.body;
    const currentTheme = localStorage.getItem('theme') || 'dark';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    body.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeColors(newTheme);
}

function updateThemeColors(theme) {
    const root = document.documentElement;
    if (theme === 'light') {
        root.style.setProperty('--bg-primary', '#ffffff');
        root.style.setProperty('--bg-secondary', '#f5f5f5');
        root.style.setProperty('--bg-tertiary', '#e5e5e5');
        root.style.setProperty('--text-primary', '#1e293b');
        root.style.setProperty('--text-secondary', '#475569');
        root.style.setProperty('--text-muted', '#64748b');
        root.style.setProperty('--border-color', '#cbd5e1');
    } else {
        root.style.setProperty('--bg-primary', '#0f172a');
        root.style.setProperty('--bg-secondary', '#1e293b');
        root.style.setProperty('--bg-tertiary', '#334155');
        root.style.setProperty('--text-primary', '#f1f5f9');
        root.style.setProperty('--text-secondary', '#cbd5e1');
        root.style.setProperty('--text-muted', '#94a3b8');
        root.style.setProperty('--border-color', '#475569');
    }
}

function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.body.setAttribute('data-theme', savedTheme);
    updateThemeColors(savedTheme);
}

// Indicadores de estado
function updateStatusIndicator(status) {
    let indicator = document.getElementById('status-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'status-indicator';
        indicator.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 8px 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.85rem;
            z-index: 1000;
        `;
        document.body.appendChild(indicator);
    }
    
    indicator.textContent = status;
}

// Analytics básico
const analytics = {
    requests: 0,
    totalTokens: 0,
    totalDuration: 0,
    errors: 0,
    startTime: Date.now()
};

function trackRequest(duration, tokens, error = false) {
    analytics.requests++;
    analytics.totalDuration += duration;
    if (tokens) analytics.totalTokens += tokens;
    if (error) analytics.errors++;
    
    localStorage.setItem('analytics', JSON.stringify(analytics));
}

function getAnalytics() {
    const uptime = Math.floor((Date.now() - analytics.startTime) / 1000);
    return {
        ...analytics,
        uptime,
        avgDuration: analytics.requests > 0 ? (analytics.totalDuration / analytics.requests).toFixed(2) : 0,
        avgTokens: analytics.requests > 0 ? Math.floor(analytics.totalTokens / analytics.requests) : 0
    };
}

// Inicializar funcionalidades
function initFeatures() {
    loadSavedTemplates();
    initTheme();
    setupKeyboardShortcuts();
    
    // Cargar analytics guardados
    const savedAnalytics = localStorage.getItem('analytics');
    if (savedAnalytics) {
        Object.assign(analytics, JSON.parse(savedAnalytics));
    }
}

// Agregar animaciones CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Exportar funciones globales
window.chatFeatures = {
    exportChat,
    copyToClipboard,
    searchInHistory,
    loadPromptTemplate,
    savePromptTemplate,
    toggleTheme,
    getAnalytics,
    updateStatusIndicator,
    initFeatures,
    trackRequest: (duration, tokens, error) => trackRequest(duration, tokens, error)
};
