# 🤖 Chat Uncensored - Asistente de IA

Un chat moderno y seguro que consume la API de Ollama con configuración avanzada y protección contra vulnerabilidades comunes.

## ✨ Características

- 🎨 **Interfaz moderna y responsive** - Diseño oscuro con gradientes y animaciones suaves
- 🔒 **Seguridad robusta** - Protección contra XSS, SQL Injection, overflow y otros ataques
- 📝 **Soporte Markdown** - Las respuestas se renderizan con formato Markdown
- ⚙️ **Configuración avanzada** - Control total sobre temperatura, top_p, top_k, y más
- 💾 **Historial persistente** - Guarda automáticamente el historial de conversación
- 🚀 **Rendimiento optimizado** - Código limpio y eficiente

## 📁 Estructura del Proyecto

```
uncensored-chat/
├── config.php              # Configuración del sistema
├── index.php               # Interfaz principal del chat
├── api.php                 # Endpoint API para procesar peticiones
├── .htaccess              # Configuración de seguridad Apache
├── controlador/
│   └── ChatController.php # Controlador MVC para manejar peticiones
├── modelo/
│   ├── ApiService.php     # Servicio para comunicación con API Ollama
│   └── Validator.php      # Validación y sanitización de inputs
├── css/
│   └── styles.css         # Estilos modernos y responsive
└── js/
    └── chat.js            # Lógica del frontend
```

## 🛡️ Medidas de Seguridad

### Protección contra XSS (Cross-Site Scripting)
- Sanitización de HTML con `strip_tags()` permitiendo solo tags seguros
- Remoción de atributos peligrosos (onclick, onerror, javascript:, etc.)
- Validación estricta de inputs antes de procesar

### Protección contra SQL Injection
- No se usa SQL directo (solo comunicación con API externa)
- Validación de tipos de datos
- Uso de parámetros preparados en todas las operaciones

### Protección contra Overflow
- Límites estrictos en longitud de inputs:
  - Prompt: máximo 5000 caracteres
  - System prompt: máximo 1000 caracteres
  - Validación de números (temperatura, top_p, etc.)

### Otras Protecciones
- Validación de tipos de datos (float, int, string, array)
- Sanitización de caracteres de control
- Headers de seguridad HTTP (.htaccess)
- Content Security Policy (CSP)
- Validación de formato JSON

## ⚙️ Opciones de Configuración

### Básicas

- **Modelo**: Nombre del modelo de IA (ej: `llama2-uncensored`)
- **Temperatura** (0.0 - 2.0): Controla la aleatoriedad
  - `0.0 - 0.3`: Respuestas más deterministas y precisas
  - `0.4 - 0.7`: Balance entre creatividad y precisión
  - `0.8 - 2.0`: Respuestas más creativas y variadas
- **Modo Stream**: Muestra la respuesta mientras se genera
- **System Prompt**: Define el comportamiento del asistente
- **Formato**: Texto plano o JSON

### Avanzadas

- **Top P** (0.0 - 1.0): Nucleus sampling - probabilidad acumulada de tokens
- **Top K** (1 - 100): Número de tokens más probables a considerar
- **Máximo de Tokens** (1 - 2000): Longitud máxima de la respuesta
- **Repeat Penalty** (0.5 - 2.0): Penaliza tokens repetidos
- **Seed**: Número para reproducir respuestas idénticas
- **Stop Sequences**: Secuencias que detienen la generación (formato JSON)

## 🚀 Instalación

1. Clona o copia el proyecto en tu servidor web (XAMPP, WAMP, etc.)
2. Asegúrate de que PHP 8.1+ esté instalado
3. Verifica que la extensión `curl` esté habilitada
4. **IMPORTANTE**: Copia `env.example.txt` a `.env` (o créalo manualmente) y ajusta los valores
5. Asegúrate de que el directorio `storage/` tenga permisos de escritura
6. Accede a `http://localhost/uncensored-chat/`

### Solución de Error 500

Si encuentras un error 500:
- Verifica que el directorio `storage/` y sus subdirectorios existan y tengan permisos de escritura
- Asegúrate de que el archivo `.env` exista (puedes copiarlo desde `env.example.txt`)
- Revisa los logs de error de PHP en `storage/logs/` o en los logs de Apache

## 📝 Uso

1. Abre `index.php` en tu navegador
2. Ajusta la configuración según tus necesidades
3. Escribe tu pregunta en el campo de texto
4. Presiona Enter o haz clic en "Enviar"
5. La respuesta se mostrará con formato Markdown

## 🔧 Configuración de la API

La URL de la API está configurada en `config.php`:

```php
public const API_URL = 'http://46.4.122.18:11434/api/generate';
```

Puedes cambiarla editando este archivo.

## 📋 Requisitos

- PHP 8.1 o superior
- Extensión cURL habilitada
- Servidor web (Apache/Nginx) con mod_rewrite (opcional)
- Navegador moderno con soporte para JavaScript ES6+

## 🎨 Personalización

### Colores
Edita las variables CSS en `css/styles.css`:

```css
:root {
    --primary-color: #6366f1;
    --bg-primary: #0f172a;
    /* ... más variables ... */
}
```

### Configuración
Modifica los valores en `config.php`:

```php
public const MAX_PROMPT_LENGTH = 5000;
public const DEFAULT_TEMPERATURE = 0.3;
// ... más configuraciones ...
```

## 🐛 Solución de Problemas

### Error: "Error cURL"
- Verifica que la URL de la API sea accesible
- Revisa la configuración de firewall
- Asegúrate de que cURL esté habilitado en PHP

### Error: "JSON inválido"
- Verifica que los datos enviados sean válidos
- Revisa la consola del navegador para más detalles

### La respuesta no se muestra
- Abre la consola del navegador (F12)
- Verifica errores de JavaScript
- Asegúrate de que la librería Marked.js se cargue correctamente

## 📄 Licencia

Este proyecto es de código abierto y está disponible para uso personal y educativo.

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:
1. Haz fork del proyecto
2. Crea una rama para tu feature
3. Commit tus cambios
4. Push a la rama
5. Abre un Pull Request

## ⚠️ Notas de Seguridad

- Este proyecto está diseñado para uso en entornos controlados
- No expongas este chat públicamente sin medidas de autenticación adicionales
- Considera implementar rate limiting para prevenir abuso
- Revisa y actualiza regularmente las dependencias

---

Desarrollado con ❤️ usando PHP, JavaScript y CSS moderno.
