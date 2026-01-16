<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Validador y sanitizador de inputs
 */
class Validator {
    /**
     * Valida y sanitiza el prompt
     */
    public static function validatePrompt(string $prompt): string {
        // Validar longitud
        if (strlen($prompt) > Config::MAX_PROMPT_LENGTH) {
            throw new InvalidArgumentException('El prompt excede la longitud máxima permitida');
        }
        
        if (empty(trim($prompt))) {
            throw new InvalidArgumentException('El prompt no puede estar vacío');
        }
        
        // Sanitizar: remover caracteres peligrosos pero mantener el texto legible
        $prompt = trim($prompt);
        
        // Remover caracteres de control excepto saltos de línea y tabs
        $prompt = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $prompt);
        
        return $prompt;
    }
    
    /**
     * Valida y sanitiza el system prompt
     */
    public static function validateSystem(?string $system): ?string {
        if ($system === null || $system === '') {
            return null;
        }
        
        if (strlen($system) > Config::MAX_SYSTEM_LENGTH) {
            throw new InvalidArgumentException('El system prompt excede la longitud máxima permitida');
        }
        
        $system = trim($system);
        $system = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $system);
        
        return $system;
    }
    
    /**
     * Valida el modelo
     */
    public static function validateModel(string $model): string {
        // Permitir solo caracteres alfanuméricos, guiones y guiones bajos
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $model)) {
            throw new InvalidArgumentException('Nombre de modelo inválido');
        }
        
        if (strlen($model) > 100) {
            throw new InvalidArgumentException('Nombre de modelo muy largo');
        }
        
        return $model;
    }
    
    /**
     * Valida temperatura (0.0 - 2.0)
     */
    public static function validateTemperature($temperature): float {
        $temp = filter_var($temperature, FILTER_VALIDATE_FLOAT);
        
        if ($temp === false) {
            throw new InvalidArgumentException('Temperatura debe ser un número');
        }
        
        if ($temp < 0.0 || $temp > 2.0) {
            throw new InvalidArgumentException('Temperatura debe estar entre 0.0 y 2.0');
        }
        
        return round($temp, 2);
    }
    
    /**
     * Valida top_p (0.0 - 1.0)
     */
    public static function validateTopP($topP): ?float {
        if ($topP === null || $topP === '') {
            return null;
        }
        
        $value = filter_var($topP, FILTER_VALIDATE_FLOAT);
        
        if ($value === false) {
            throw new InvalidArgumentException('top_p debe ser un número');
        }
        
        if ($value < 0.0 || $value > 1.0) {
            throw new InvalidArgumentException('top_p debe estar entre 0.0 y 1.0');
        }
        
        return round($value, 3);
    }
    
    /**
     * Valida top_k (entero positivo)
     */
    public static function validateTopK($topK): ?int {
        if ($topK === null || $topK === '') {
            return null;
        }
        
        $value = filter_var($topK, FILTER_VALIDATE_INT);
        
        if ($value === false || $value < 1) {
            throw new InvalidArgumentException('top_k debe ser un entero positivo');
        }
        
        return $value;
    }
    
    /**
     * Valida num_predict (entero positivo)
     */
    public static function validateNumPredict($numPredict): ?int {
        if ($numPredict === null || $numPredict === '') {
            return null;
        }
        
        $value = filter_var($numPredict, FILTER_VALIDATE_INT);
        
        if ($value === false || $value < 1) {
            throw new InvalidArgumentException('num_predict debe ser un entero positivo');
        }
        
        if ($value > 100000) {
            throw new InvalidArgumentException('num_predict excede el máximo permitido');
        }
        
        return $value;
    }
    
    /**
     * Valida repeat_penalty
     */
    public static function validateRepeatPenalty($penalty): ?float {
        if ($penalty === null || $penalty === '') {
            return null;
        }
        
        $value = filter_var($penalty, FILTER_VALIDATE_FLOAT);
        
        if ($value === false || $value < 0.0) {
            throw new InvalidArgumentException('repeat_penalty debe ser un número positivo');
        }
        
        return round($value, 2);
    }
    
    /**
     * Valida seed
     */
    public static function validateSeed($seed): ?int {
        if ($seed === null || $seed === '') {
            return null;
        }
        
        $value = filter_var($seed, FILTER_VALIDATE_INT);
        
        if ($value === false) {
            throw new InvalidArgumentException('seed debe ser un número entero');
        }
        
        return $value;
    }
    
    /**
     * Valida stop (array de strings)
     */
    public static function validateStop($stop): ?array {
        if ($stop === null || $stop === '') {
            return null;
        }
        
        if (is_string($stop)) {
            // Si es un string, intentar decodificar JSON
            $decoded = json_decode($stop, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $stop = $decoded;
            } else {
                // Si no es JSON, tratarlo como un solo string
                $stop = [$stop];
            }
        }
        
        if (!is_array($stop)) {
            throw new InvalidArgumentException('stop debe ser un array o string');
        }
        
        // Validar cada elemento
        $validated = [];
        foreach ($stop as $item) {
            if (!is_string($item)) {
                continue;
            }
            
            if (strlen($item) > 100) {
                continue; // Ignorar strings muy largos
            }
            
            $validated[] = $item;
        }
        
        return !empty($validated) ? $validated : null;
    }
    
    /**
     * Valida stream (boolean)
     */
    public static function validateStream($stream): bool {
        if (is_bool($stream)) {
            return $stream;
        }
        
        if (is_string($stream)) {
            $stream = strtolower($stream);
            return in_array($stream, ['true', '1', 'yes', 'on'], true);
        }
        
        return (bool) $stream;
    }
    
    /**
     * Valida format
     */
    public static function validateFormat(?string $format): ?string {
        if ($format === null || $format === '') {
            return null;
        }
        
        $allowed = ['json', 'text'];
        $format = strtolower(trim($format));
        
        if (!in_array($format, $allowed, true)) {
            throw new InvalidArgumentException('Formato no permitido');
        }
        
        return $format;
    }
    
    /**
     * Sanitiza HTML para prevenir XSS
     */
    public static function sanitizeHtml(string $html): string {
        // Permitir solo tags seguros para markdown
        $html = strip_tags($html, Config::ALLOWED_HTML_TAGS);
        
        // Escapar atributos peligrosos
        $html = preg_replace_callback(
            '/<(\w+)([^>]*)>/i',
            function($matches) {
                $tag = $matches[1];
                $attrs = $matches[2];
                
                // Remover atributos peligrosos (onclick, onerror, etc.)
                $attrs = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $attrs);
                $attrs = preg_replace('/\s*javascript:/i', '', $attrs);
                
                return '<' . $tag . $attrs . '>';
            },
            $html
        );
        
        return $html;
    }
    
    /**
     * Valida y construye el array de opciones
     */
    public static function buildOptions(array $input): array {
        $options = [];
        
        if (isset($input['temperature'])) {
            $options['temperature'] = self::validateTemperature($input['temperature']);
        }
        
        if (isset($input['top_p'])) {
            $topP = self::validateTopP($input['top_p']);
            if ($topP !== null) {
                $options['top_p'] = $topP;
            }
        }
        
        if (isset($input['top_k'])) {
            $topK = self::validateTopK($input['top_k']);
            if ($topK !== null) {
                $options['top_k'] = $topK;
            }
        }
        
        if (isset($input['num_predict'])) {
            $numPredict = self::validateNumPredict($input['num_predict']);
            if ($numPredict !== null) {
                $options['num_predict'] = $numPredict;
            }
        }
        
        if (isset($input['repeat_penalty'])) {
            $penalty = self::validateRepeatPenalty($input['repeat_penalty']);
            if ($penalty !== null) {
                $options['repeat_penalty'] = $penalty;
            }
        }
        
        if (isset($input['seed'])) {
            $seed = self::validateSeed($input['seed']);
            if ($seed !== null) {
                $options['seed'] = $seed;
            }
        }
        
        if (isset($input['stop'])) {
            $stop = self::validateStop($input['stop']);
            if ($stop !== null) {
                $options['stop'] = $stop;
            }
        }
        
        // Opciones numéricas adicionales
        $numericOptions = [
            'num_ctx', 'num_batch', 'num_gpu', 'num_thread', 
            'repeat_last_n', 'tfs_z', 'typical_p', 'mirostat',
            'mirostat_eta', 'mirostat_tau', 'presence_penalty', 'frequency_penalty'
        ];
        
        foreach ($numericOptions as $opt) {
            if (isset($input[$opt]) && $input[$opt] !== '') {
                $value = filter_var($input[$opt], FILTER_VALIDATE_FLOAT);
                if ($value !== false) {
                    $options[$opt] = $value;
                }
            }
        }
        
        // Opciones booleanas
        if (isset($input['penalize_newline'])) {
            $options['penalize_newline'] = (bool) $input['penalize_newline'];
        }
        
        return $options;
    }
}
