<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Uncensored - Asistente IA</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-content">
                <div class="robot-logo">
                    <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Fondo gris modo incógnito -->
                        <rect width="60" height="60" rx="8" fill="#5f6368"/>
                        <!-- Cabeza del robot (blanca) -->
                        <rect x="15" y="22" width="30" height="24" rx="3" fill="#ffffff"/>
                        <!-- Ojos grises -->
                        <circle cx="24" cy="32" r="2.5" fill="#5f6368"/>
                        <circle cx="36" cy="32" r="2.5" fill="#5f6368"/>
                        <!-- Boca negra -->
                        <rect x="26" y="38" width="8" height="2" rx="1" fill="#202124"/>
                        <!-- Sombrero negro -->
                        <ellipse cx="30" cy="18" rx="12" ry="3" fill="#1e293b"/>
                        <rect x="20" y="18" width="20" height="4" rx="2" fill="#1e293b"/>
                        
                    </svg>
                </div>
                <div class="header-text">
                    <h1>Chat Uncensored</h1>
                    <p class="subtitle">Asistente de IA con configuración avanzada</p>
                </div>
            </div>
        </header>

        <div class="main-content">
            <aside class="sidebar">
                <div class="config-panel">
                    <h2>⚙️ Configuración</h2>
                    
                    <div class="config-group">
                        <label for="model">Modelo</label>
                        <input type="text" id="model" value="llama2-uncensored" placeholder="llama2-uncensored">
                        <small>Modelo de IA a utilizar</small>
                    </div>

                    <div class="config-group">
                        <label for="temperature">
                            Temperatura
                            <span class="tooltip" data-tooltip="Controla la aleatoriedad de las respuestas. Valores bajos (0.1-0.3) = más determinista, valores altos (0.7-1.0) = más creativo">ℹ️</span>
                            <span class="value-display" id="temp-value">0.3</span>
                        </label>
                        <input type="range" id="temperature" min="0" max="2" step="0.1" value="0.3">
                        <small>0.0 (determinista) - 2.0 (creativo)</small>
                    </div>

                    <div class="config-group">
                        <label for="stream">
                            <input type="checkbox" id="stream">
                            Modo Stream
                            <span class="tooltip" data-tooltip="Muestra la respuesta mientras se genera (más rápido visualmente)">ℹ️</span>
                        </label>
                    </div>

                    <div class="config-group">
                        <label for="system">System Prompt (Opcional)</label>
                        <textarea id="system" rows="3" placeholder="Eres un asistente experto..."></textarea>
                        <small>Define el comportamiento del asistente</small>
                    </div>

                    <div class="config-group">
                        <label for="format">Formato de Respuesta</label>
                        <select id="format">
                            <option value="">Texto plano</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>

                    <div class="config-group">
                        <label for="num_predict">
                            Máximo de Tokens de Salida
                            <span class="tooltip" data-tooltip="Longitud máxima de la respuesta generada. Valores más altos = respuestas más largas pero más lentas. ⚠️ Valores muy altos pueden ser lentos">ℹ️</span>
                            <span class="value-display" id="num_predict-value">4000</span>
                        </label>
                        <input type="number" id="num_predict" min="100" max="32000" value="4000" step="100">
                        <small>100-32000 tokens (recomendado: 2000-8000 para respuestas largas, 4000 por defecto)</small>
                    </div>

                    <div class="config-advanced">
                        <button class="toggle-advanced" id="toggle-advanced">
                            🔧 Opciones Avanzadas
                        </button>
                        
                        <div class="advanced-options" id="advanced-options" style="display: none;">
                            <div class="config-group">
                                <label for="top_p">
                                    Top P
                                    <span class="tooltip" data-tooltip="Nucleus sampling: probabilidad acumulada de tokens a considerar">ℹ️</span>
                                    <span class="value-display" id="top_p-value"></span>
                                </label>
                                <input type="range" id="top_p" min="0" max="1" step="0.05" value="0.95">
                                <small>0.0 - 1.0 (recomendado: 0.9-0.95)</small>
                            </div>

                            <div class="config-group">
                                <label for="top_k">
                                    Top K
                                    <span class="tooltip" data-tooltip="Número de tokens más probables a considerar">ℹ️</span>
                                    <span class="value-display" id="top_k-value"></span>
                                </label>
                                <input type="number" id="top_k" min="1" max="100" value="40">
                                <small>1-100 (recomendado: 40)</small>
                            </div>

                            <div class="config-group">
                                <label for="repeat_penalty">
                                    Repeat Penalty
                                    <span class="tooltip" data-tooltip="Penaliza tokens repetidos. Valores > 1.0 reducen repeticiones">ℹ️</span>
                                    <span class="value-display" id="repeat_penalty-value">1.15</span>
                                </label>
                                <input type="range" id="repeat_penalty" min="0.5" max="2.0" step="0.05" value="1.15">
                                <small>0.5 - 2.0 (recomendado: 1.1-1.2)</small>
                            </div>

                            <div class="config-group">
                                <label for="seed">
                                    Seed (Semilla)
                                    <span class="tooltip" data-tooltip="Número para reproducir respuestas idénticas">ℹ️</span>
                                </label>
                                <input type="number" id="seed" placeholder="Opcional">
                                <small>Dejar vacío para aleatorio</small>
                            </div>

                            <div class="config-group">
                                <label for="stop">Stop Sequences (JSON)</label>
                                <textarea id="stop" rows="2" placeholder='["\\n\\n\\n", "FIN"]'></textarea>
                                <small>Secuencias que detienen la generación</small>
                            </div>
                        </div>
                    </div>

                    <button class="btn-clear" id="clear-chat">🗑️ Limpiar Chat</button>
                    
                    <div class="config-group" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                        <label>Plantillas de Prompts</label>
                        <select id="prompt-template" onchange="if(this.value) window.chatFeatures.loadPromptTemplate(this.value); this.value='';">
                            <option value="">Seleccionar plantilla...</option>
                            <option value="Científico">Científico</option>
                            <option value="Programador">Programador</option>
                            <option value="Escritor">Escritor</option>
                            <option value="Traductor">Traductor</option>
                            <option value="Analista">Analista</option>
                        </select>
                    </div>
                    
                    <div class="config-group">
                        <button class="btn-export" onclick="window.chatFeatures.exportChat('json')" style="width: 100%; margin-bottom: 8px; padding: 10px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-primary); cursor: pointer;">📥 Exportar JSON</button>
                        <button class="btn-export" onclick="window.chatFeatures.exportChat('txt')" style="width: 100%; margin-bottom: 8px; padding: 10px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-primary); cursor: pointer;">📄 Exportar TXT</button>
                        <button class="btn-export" onclick="window.chatFeatures.exportChat('md')" style="width: 100%; margin-bottom: 8px; padding: 10px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-primary); cursor: pointer;">📝 Exportar Markdown</button>
                    </div>
                    
                    <div class="config-group">
                        <button class="btn-theme" onclick="window.chatFeatures.toggleTheme()" style="width: 100%; padding: 10px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; color: var(--text-primary); cursor: pointer;">🌓 Cambiar Tema</button>
                    </div>
                </div>
            </aside>

            <main class="chat-container">
                <div class="chat-messages" id="chat-messages">
                    <div class="welcome-message">
                        <h2>👋 ¡Bienvenido!</h2>
                        <p>Escribe tu pregunta o consulta en el campo de abajo y el asistente te responderá.</p>
                        <p class="tip">💡 Tip: Ajusta la temperatura para controlar qué tan creativas son las respuestas.</p>
                    </div>
                </div>

                <div class="chat-input-container">
                    <div class="input-wrapper">
                        <textarea 
                            id="chat-input" 
                            placeholder="Escribe tu mensaje aquí..." 
                            rows="3"
                            maxlength="50000"
                        ></textarea>
                        <div class="input-footer">
                            <span class="char-count"><span id="char-count">0</span>/50000</span>
                            <button class="btn-send" id="send-btn">
                                <span>Enviar</span>
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="js/features.js"></script>
    <script src="js/chat.js"></script>
    <script>
        // Inicializar funcionalidades adicionales
        document.addEventListener('DOMContentLoaded', () => {
            if (window.chatFeatures) {
                window.chatFeatures.initFeatures();
            }
        });
    </script>
</body>
</html>
