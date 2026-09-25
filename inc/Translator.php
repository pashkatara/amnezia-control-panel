<?php
/**
 * Translator class for multi-language support
 * Supports automatic translation using external services
 */
class Translator {
    private static ?string $currentLanguage = 'ru';
    private static array $translations = [];
    private static array $supportedLanguages = [
        ['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский']
    ];
    
    /**
     * Initialize translator
     */
    public static function init(): void {
        self::$currentLanguage = 'ru';
        self::loadSupportedLanguages();
        self::loadTranslations('ru');
    }
    
    /**
     * Load supported languages from database
     */
    private static function loadSupportedLanguages(): void {
        try {
            $pdo = DB::conn();
            $stmt = $pdo->query("SELECT code, name, native_name FROM languages WHERE code = 'ru' AND is_active = 1");
            $langs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($langs)) {
                self::$supportedLanguages = $langs;
            }
        } catch (Throwable $e) {
            // Use default fallback
        }
    }
    
    /**
     * Detect user's preferred language (Russian only)
     */
    private static function detectLanguage(): void {
        self::$currentLanguage = 'ru';
        $_SESSION['language'] = 'ru';
    }
    
    /**
     * Check if language is supported
     */
    public static function isSupported(string $code): bool {
        return $code === 'ru';
    }
    
    /**
     * Load translations for specific language
     */
    private static function loadTranslations(string $languageCode): void {
        try {
            $pdo = DB::conn();
            // Try new schema first (locale, category, key_name, translation)
            $stmt = $pdo->prepare('SELECT CONCAT(category, ".", key_name) as trans_key, translation FROM translations WHERE locale = ?');
            $stmt->execute([$languageCode]);
            $translations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            if (!empty($translations)) {
                self::$translations = $translations;
                return;
            }

            // Try legacy schema (language_code, translation_key, translation_value)
            $stmt = $pdo->prepare('SELECT translation_key, translation_value FROM translations WHERE language_code = ?');
            $stmt->execute([$languageCode]);
            $legacyTranslations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            if (!empty($legacyTranslations)) {
                self::$translations = $legacyTranslations;
                return;
            }
        } catch (Throwable $e) {
            // Will fallback to default dictionary
        }

        self::$translations = [];
    }
    
    /**
     * Fallback Russian dictionary
     */
    private static array $defaultRu = [
        'auth.email' => 'Email',
        'auth.login' => 'Вход',
        'auth.name' => 'Имя',
        'auth.password' => 'Пароль',
        'auth.register' => 'Регистрация',
        'auth.sign_in' => 'Войти',
        'auth.sign_in_desc' => 'Войдите для управления VPN серверами',
        'auth.dont_have_account' => 'Нет аккаунта? Зарегистрироваться',
        'auth.already_have_account' => 'Уже есть аккаунт? Войти',
        'auth.logout' => 'Выход',
        'backups.create' => 'Создать резервную копию',
        'backups.create_confirm' => 'Создать резервную копию всех клиентов на этом сервере?',
        'backups.created_success' => 'Резервная копия успешно создана',
        'backups.delete_confirm' => 'Удалить эту резервную копию навсегда?',
        'backups.deleted_success' => 'Резервная копия успешно удалена',
        'backups.login_required' => 'Пожалуйста, войдите через API для управления резервными копиями',
        'backups.no_backups' => 'Пока нет резервных копий',
        'backups.restore' => 'Восстановить',
        'backups.restore_confirm' => 'Восстановить клиентов из этой резервной копии? Существующие клиенты не будут затронуты.',
        'backups.restored_success' => 'Восстановлено',
        'backups.title' => 'Резервные копии сервера',
        'clients.actions' => 'Действия',
        'clients.add' => 'Добавить клиента',
        'clients.create' => 'Создать клиента',
        'clients.delete' => 'Удалить',
        'clients.delete_confirm' => 'Удалить этого клиента навсегда?',
        'clients.download_config' => 'Скачать конфигурацию',
        'clients.expiration' => 'Срок действия',
        'clients.expired' => 'Истек',
        'clients.ip' => 'IP-адрес',
        'clients.last_handshake' => 'Последнее соединение',
        'clients.name' => 'Имя клиента',
        'clients.never' => 'Никогда',
        'clients.never_expires' => 'Бессрочно',
        'clients.no_clients' => 'Пока нет клиентов',
        'clients.overlimit' => 'Превышен лимит',
        'clients.qr_code' => 'QR-код',
        'clients.received' => 'Получено',
        'clients.restore' => 'Восстановить',
        'clients.revoke' => 'Отозвать',
        'clients.revoke_confirm' => 'Отозвать доступ для этого клиента?',
        'clients.sent' => 'Отправлено',
        'clients.server' => 'Сервер',
        'clients.status' => 'Статус',
        'clients.sync_stats' => 'Синхронизировать статистику',
        'clients.title' => 'Клиенты',
        'clients.traffic' => 'Трафик',
        'clients.traffic_limit' => 'Лимит трафика',
        'clients.unlimited' => 'Безлимитно',
        'clients.custom_seconds' => 'Своё значение (секунды)',
        'clients.custom_mb' => 'Своё значение (МБ)',
        'clients.enter_seconds' => 'Введите секунды',
        'clients.enter_megabytes' => 'Введите мегабайты',
        'common.days' => 'дней',
        'common.speed' => 'Скорость',
        'dashboard.active_clients' => 'Активные клиенты',
        'dashboard.add_first_server' => 'Добавить первый сервер',
        'dashboard.get_started' => 'Начните с добавления вашего первого VPN-сервера',
        'dashboard.no_servers' => 'Пока нет серверов',
        'dashboard.quick_actions' => 'Быстрые действия',
        'dashboard.recent_servers' => 'Недавние серверы',
        'dashboard.title' => 'Панель управления',
        'dashboard.total_clients' => 'Всего клиентов',
        'dashboard.total_servers' => 'Всего серверов',
        'dashboard.total_traffic' => 'Общий трафик',
        'dashboard.online_now' => 'Онлайн сейчас',
        'dashboard.welcome' => 'Добро пожаловать в панель управления Amnezia VPN',
        'form.cancel' => 'Отмена',
        'form.close' => 'Закрыть',
        'form.create' => 'Создать',
        'form.loading' => 'Загрузка...',
        'form.processing' => 'Обработка...',
        'form.save' => 'Сохранить',
        'form.submit' => 'Отправить',
        'form.update' => 'Обновить',
        'menu.clients' => 'Клиенты',
        'menu.dashboard' => 'Панель управления',
        'menu.logout' => 'Выход',
        'menu.servers' => 'Серверы',
        'menu.settings' => 'Настройки',
        'menu.users' => 'Пользователи',
        'message.confirm' => 'Вы уверены?',
        'message.deleted' => 'Успешно удалено',
        'message.deployed' => 'Успешно развернуто',
        'message.error' => 'Произошла ошибка',
        'message.saved' => 'Успешно сохранено',
        'message.success' => 'Операция успешно завершена',
        'servers.actions' => 'Действия',
        'servers.add' => 'Добавить сервер',
        'servers.clients' => 'Клиенты',
        'servers.delete' => 'Удалить',
        'servers.deploy' => 'Развернуть',
        'servers.edit' => 'Редактировать',
        'servers.host' => 'Хост',
        'servers.name' => 'Имя',
        'servers.port' => 'Порт',
        'servers.status' => 'Статус',
        'servers.title' => 'Серверы',
        'servers.view' => 'Просмотр',
        'servers.import_from_panel' => 'Импорт из другой панели',
        'servers.select_panel_type' => 'Выберите тип панели',
        'servers.panel_type_wgeasy' => 'wg-easy',
        'servers.panel_type_3xui' => '3x-ui',
        'servers.upload_backup_file' => 'Загрузите файл резервной копии (JSON)',
        'servers.import_in_progress' => 'Импорт выполняется...',
        'servers.import_success' => 'Успешно импортировано клиентов: %s',
        'servers.import_failed' => 'Ошибка импорта',
        'servers.import_partial' => 'Импортировано %s из %s клиентов',
        'servers.import_history' => 'История импорта',
        'settings.actions' => 'Действия',
        'settings.api_key_configured' => 'API-ключ настроен',
        'settings.api_keys' => 'API-ключи',
        'settings.api_keys_desc' => 'Настройка API-ключей для внешних сервисов',
        'settings.auto_translate' => 'Автоперевод',
        'settings.change_password' => 'Изменить пароль',
        'settings.confirm_password' => 'Подтвердите пароль',
        'settings.confirm_translate' => 'Начать автоматический перевод?',
        'settings.current_password' => 'Текущий пароль',
        'settings.description' => 'Управление конфигурацией панели и настройками',
        'settings.error_empty_key' => 'API-ключ не может быть пустым',
        'settings.error_invalid_key' => 'Неверный формат API-ключа',
        'settings.error_key_test' => 'Тест API-ключа не удался',
        'settings.for_translation' => 'для автоперевода',
        'settings.get_key_at' => 'Получите ваш API-ключ на',
        'settings.key_saved' => 'API-ключ успешно сохранен',
        'settings.keys' => 'ключи',
        'settings.language' => 'Язык',
        'settings.min_6_chars' => 'Минимум 6 символов',
        'settings.new_password' => 'Новый пароль',
        'settings.no_api_key' => 'API-ключ не настроен.',
        'settings.profile' => 'Профиль',
        'settings.progress' => 'Прогресс',
        'settings.skip_validation' => 'Пропустить проверку',
        'settings.translation_complete' => 'Перевод завершен',
        'settings.translation_status' => 'Статус перевода',
        'settings.translations' => 'Переводы',
        'settings.users' => 'Пользователи',
        'settings.protocol_management' => 'Управление протоколами',
        'status.active' => 'Активен',
        'status.deploying' => 'Развертывание',
        'status.disabled' => 'Отключен',
        'status.error' => 'Ошибка',
        'status.inactive' => 'Неактивен',
        'users.add_user' => 'Добавить пользователя',
        'users.administrator' => 'Администратор',
        'users.all_users' => 'Все пользователи',
        'users.created' => 'Создан',
        'users.delete_confirm' => 'Удалить пользователя %s?',
        'users.role' => 'Роль',
        'users.role_admin' => 'Администратор',
        'users.role_user' => 'Пользователь',
        'protocols.management' => 'Управление протоколами',
        'protocols.management_description' => 'Настройка, добавление и управление протоколами VPN',
        'protocols.add_protocol' => 'Добавить протокол',
        'protocols.create_protocol' => 'Создать протокол',
        'protocols.create_protocol_description' => 'Добавление нового шаблона протокола VPN в систему',
        'protocols.edit_protocol' => 'Редактировать протокол',
        'protocols.edit_protocol_description' => 'Изменение конфигурации и параметров протокола VPN',
        'protocols.back_to_protocols' => 'Назад к списку протоколов',
        'protocols.template_editor' => 'Редактор шаблонов протокола',
        'protocols.template_editor_description' => 'Настройка формата генерации конфигурационных файлов для клиентов',
        'protocols.output_template' => 'Шаблон вывода',
        'protocols.template_editor_help' => 'Используйте переменные в фигурных скобках для динамической подстановки параметров клиента',
        'protocols.template_content' => 'Содержимое шаблона',
        'protocols.save_template' => 'Сохранить шаблон',
        'common.format' => 'Форматировать',
        'common.clear' => 'Очистить',
        'protocols.available_protocols' => 'Доступные протоколы',
        'protocols.search_protocols' => 'Поиск протоколов...',
        'protocols.all_protocols' => 'Все протоколы',
        'protocols.active_only' => 'Только активные',
        'protocols.ubuntu_compatible' => 'Совместимые с Ubuntu',
        'protocols.with_ai_generations' => 'С AI генерациями',
        'protocols.no_protocols' => 'Нет протоколов',
        'protocols.no_protocols_description' => 'В системе еще не создано ни одного протокола',
        'protocols.create_first_protocol' => 'Создать первый протокол',
        'protocols.name' => 'Название протокола',
        'protocols.slug' => 'Идентификатор (slug)',
        'protocols.basic_information' => 'Основная информация',
        'protocols.name_label' => 'Название',
        'protocols.name_help' => 'Отображаемое имя протокола (например: AmneziaWG 3.1)',
        'protocols.slug_label' => 'Идентификатор (slug)',
        'protocols.slug_help' => 'Уникальный системный идентификатор (только латиница, цифры и дефис)',
        'protocols.description_help' => 'Краткое описание протокола и его назначения',
        'common.description' => 'Описание',
        'protocols.edit_template' => 'Редактировать шаблон',
        'ai.assistant' => 'AI Ассистент',
        'ai.protocol_type' => 'Протокол',
        'ai.select_model' => 'Выберите модель AI',
        'ai.model_gpt35_turbo' => 'OpenAI GPT-3.5 Turbo',
        'ai.model_gpt4' => 'OpenAI GPT-4',
        'ai.model_claude3_haiku' => 'Anthropic Claude 3 Haiku',
        'ai.model_claude3_sonnet' => 'Anthropic Claude 3 Sonnet',
        'ai.custom_model_placeholder' => 'Своя модель (например: meta-llama/llama-3-70b-instruct)',
        'ai.check_availability' => 'Проверить',
        'ai.general_vpn' => 'Общий VPN',
        'ai.describe_requirements' => 'Опишите требования к протоколу',
        'ai.prompt_placeholder' => 'Опишите особенности протокола, порты, параметры маскировки...',
        'ai.generate_script' => 'Сгенерировать установочный скрипт',
        'ai.generating_script' => 'Генерация скрипта с помощью AI...',
        'ai.generated_script' => 'Сгенерированный скрипт',
        'ai.suggestions' => 'Рекомендации AI',
        'ai.create_new_protocol' => 'Создать новый протокол',
        'ai.generate_with_ai' => 'Сгенерировать с AI',
        'ai.generation_preview' => 'Предпросмотр генерации AI',
        'ai.generation_preview_description' => 'Проверка сгенерированного скрипта установки перед применением',
        'ai.apply_to_protocol' => 'Применить к протоколу',
        'ai.generation_details' => 'Детали генерации',
        'ai.model_used' => 'Использованная модель',
        'ai.generated_at' => 'Дата генерации',
        'settings.enter_api_key' => 'Ввести ключ',
        'common.active' => 'Активен',
        'common.inactive' => 'Неактивен',
        'common.slug' => 'Идентификатор',
        'common.servers' => 'Серверов',
        'common.templates' => 'Шаблонов',
        'common.variables' => 'Переменных',
        'common.compatibility' => 'Совместимость',
        'common.edit' => 'Редактировать',
        'common.delete' => 'Удалить',
        'common.ip_address' => 'IP-адрес',
        'common.status' => 'Статус',
        'common.created' => 'Создан',
        'common.uploaded' => 'Отправлено',
        'common.downloaded' => 'Получено',
        'common.total' => 'Всего',
        'common.save' => 'Сохранить',
        'common.cancel' => 'Отмена',
        'ldap.settings' => 'Настройки LDAP',
        'ldap.test_connection' => 'Проверить соединение',
        'ldap.enable_ldap_auth' => 'Включить аутентификацию через LDAP',
        'ldap.enable_description' => 'Позволяет входить с учетными записями корпоративного каталога',
        'ldap.host' => 'Хост LDAP',
        'ldap.port' => 'Порт',
        'ldap.use_tls' => 'Использовать TLS/SSL',
        'ldap.base_dn' => 'Базовый DN',
        'ldap.base_dn_description' => 'Например: dc=example,dc=com',
        'ldap.bind_dn' => 'DN для привязки',
        'ldap.bind_dn_description' => 'Например: cn=admin,dc=example,dc=com',
        'ldap.bind_password' => 'Пароль для привязки',
        'ldap.user_search_filter' => 'Фильтр поиска пользователей',
        'ldap.user_search_filter_description' => 'Например: (uid=%s) или (sAMAccountName=%s)',
        'ldap.group_search_filter' => 'Фильтр поиска групп',
        'ldap.sync_interval' => 'Интервал синхронизации (минут)',
        'ldap.sync_interval_description' => 'Частота фонового обновления пользователей',
        'ldap.group_mappings' => 'Сопоставление групп LDAP',
        'ldap.group' => 'Группа LDAP',
        'ldap.role' => 'Роль в панели',
        'ldap.description' => 'Описание',
        'ldap.testing' => 'Проверка соединения',
        'ldap.connection_test_failed' => 'Ошибка проверки соединения'
    ];

    /**
     * Fix any mojibake (double-encoded UTF-8)
     */
    public static function fixMojibake(?string $str): string {
        if ($str === null || $str === '') {
            return '';
        }
        // Double-encoded UTF-8 as Latin-1 (e.g. ÐЈÐ¿Ñ€Ð°Ð²Ð»ÐµÐ½ÐёÐµ)
        if (preg_match('/(?:[\xC2\xC3][\x80-\xBF]){2,}/', $str)) {
            $fixed = @mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
            if ($fixed && mb_check_encoding($fixed, 'UTF-8') && !preg_match('/[\xC2\xC3][\x80-\xBF]/', $fixed)) {
                return $fixed;
            }
        }
        // Double-encoded UTF-8 as Windows-1251 (e.g. РЈРїСЂР°РІР»РµРЅРёРµ)
        if (preg_match('/[Р|С][\x80-\xFF]/u', $str)) {
            $fixed = @iconv('UTF-8', 'Windows-1251//IGNORE', $str);
            if ($fixed && mb_check_encoding($fixed, 'UTF-8')) {
                return $fixed;
            }
        }
        return $str;
    }

    /**
     * Translate a key
     * 
     * @param string $key Translation key
     * @param array $params Parameters for sprintf
     * @return string Translated text
     */
    public static function translate(string $key, array $params = []): string {
        $translation = self::$defaultRu[$key] ?? self::$translations[$key] ?? $key;
        $translation = self::fixMojibake($translation);
        
        if (!empty($params)) {
            return sprintf($translation, ...$params);
        }
        
        return $translation;
    }
    
    /**
     * Short alias for translate()
     */
    public static function t(string $key, array $params = []): string {
        return self::translate($key, $params);
    }
    
    /**
     * Get current language code
     */
    public static function getCurrentLanguage(): string {
        return self::$currentLanguage ?? 'en';
    }
    
    /**
     * Set current language
     */
    public static function setLanguage(string $code): bool {
        if (!self::isSupported($code)) {
            return false;
        }
        
        self::$currentLanguage = $code;
        $_SESSION['language'] = $code;
        setcookie('language', $code, time() + 31536000, '/'); // 1 year
        
        // Reload translations
        self::loadTranslations($code);
        
        return true;
    }
    
    /**
     * Get all supported languages
     */
    public static function getSupportedLanguages(): array {
        return self::$supportedLanguages;
    }
    
    /**
     * Auto-translate missing keys using AI (OpenRouter API)
     * 
     * @param string $targetLang Target language code
     * @param string $key Translation key
     * @param string $sourceText Source text (English)
     * @return bool Success status
     */
    public static function autoTranslate(string $targetLang, string $key, string $sourceText): bool {
        if ($targetLang === 'en') {
            return false; // English is source language
        }
        
        try {
            // Language mapping
            $langNames = [
                'ru' => 'Russian',
                'es' => 'Spanish',
                'de' => 'German',
                'fr' => 'French',
                'zh' => 'Chinese'
            ];
            
            $targetLanguage = $langNames[$targetLang] ?? 'English';
            
            // Use OpenRouter API with multiple free model candidates
            $translatedText = self::translateWithAI($sourceText, $targetLanguage);
            
            if (!$translatedText || $translatedText === $sourceText) {
                error_log("Translation failed for '{$sourceText}' to {$targetLang}");
                return false;
            }
            
            // Save to database
            $pdo = DB::conn();
            // Split key into category and key_name (e.g., "common.speed" -> "common" + "speed")
            $parts = explode('.', $key, 2);
            $category = $parts[0] ?? 'common';
            $keyName = $parts[1] ?? $key;
            
            $stmt = $pdo->prepare('
                INSERT INTO translations (locale, category, key_name, translation)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE translation = VALUES(translation)
            ');
            
            return $stmt->execute([$targetLang, $category, $keyName, $translatedText]);
            
        } catch (Exception $e) {
            error_log("Auto-translation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Translate text using AI with model fallback
     */
    private static function translateWithAI(string $text, string $targetLanguage): ?string {
        // Use reliable paid models with fallback
        $models = [
            'anthropic/claude-3.5-sonnet',
            'openai/gpt-4o-mini',
            'google/gemini-pro-1.5'
        ];
        
        foreach ($models as $model) {
            try {
                $result = self::callOpenRouter($model, $text, $targetLanguage);
                if ($result && $result !== $text) {
                    return $result;
                }
            } catch (Exception $e) {
                error_log("Model {$model} failed: " . $e->getMessage());
                continue;
            }
        }
        
        return null;
    }
    
    /**
     * Get OpenRouter API key from database
     */
    private static function getOpenRouterKey(): ?string {
        try {
            $pdo = DB::conn();
            $stmt = $pdo->prepare("SELECT api_key FROM api_keys WHERE service_name = 'openrouter' AND is_active = 1 LIMIT 1");
            $stmt->execute();
            return $stmt->fetchColumn() ?: null;
        } catch (Exception $e) {
            error_log('Failed to get OpenRouter API key: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Call OpenRouter API
     */
    private static function callOpenRouter(string $model, string $text, string $targetLanguage): ?string {
        $apiKey = self::getOpenRouterKey();
        
        if (!$apiKey) {
            error_log('OpenRouter API key not configured');
            return null;
        }
        
        $messages = [
            [
                'role' => 'system',
                'content' => "You are a professional translator. Translate the given English text to {$targetLanguage}. Return ONLY the translation, no explanations or additional text. Keep the same tone and style. If there are parameters in curly braces like {param}, keep them unchanged."
            ],
            [
                'role' => 'user',
                'content' => "Translate to {$targetLanguage}: {$text}"
            ]
        ];
        
        $data = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => 200,
            'temperature' => 0.1
        ];
        
        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'HTTP-Referer: https://amnez.ia',
            'X-Title: Amnezia VPN Panel'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("OpenRouter API error: HTTP {$httpCode} - Model: {$model}");
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            error_log("OpenRouter API error: No content in response - Model: {$model}");
            return null;
        }
        
        return trim($result['choices'][0]['message']['content']);
    }
    
    /**
     * Translate all missing keys for a language
     * 
     * @param string $targetLang Target language code
     * @return array Statistics (total, translated, failed)
     */
    public static function translateMissingKeys(string $targetLang): array {
        if ($targetLang === 'en') {
            return ['total' => 0, 'translated' => 0, 'failed' => 0];
        }
        
        $pdo = DB::conn();
        
        // Get all English keys
        $stmt = $pdo->query("SELECT CONCAT(category, '.', key_name) as trans_key, translation FROM translations WHERE locale = 'en'");
        $englishKeys = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Get existing translations for target language
        $stmt = $pdo->prepare("SELECT CONCAT(category, '.', key_name) FROM translations WHERE locale = ?");
        $stmt->execute([$targetLang]);
        $existingKeys = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $stats = [
            'total' => count($englishKeys),
            'translated' => count($existingKeys),
            'failed' => 0
        ];
        
        // Find missing keys
        $missingKeys = [];
        foreach ($englishKeys as $key => $value) {
            if (!in_array($key, $existingKeys)) {
                $missingKeys[$key] = $value;
            }
        }
        
        if (empty($missingKeys)) {
            return $stats;
        }
        
        // Try batch translation first
        $batchResult = self::translateBatch($missingKeys, $targetLang);
        
        if ($batchResult) {
            foreach ($batchResult as $key => $translatedText) {
                if (isset($missingKeys[$key]) && $translatedText) {
                    self::setTranslation($targetLang, $key, $translatedText);
                    $stats['translated']++;
                }
            }
            return $stats;
        }
        
        // Fallback to individual translation
        foreach ($missingKeys as $key => $value) {
            if (self::autoTranslate($targetLang, $key, $value)) {
                $stats['translated']++;
                sleep(3); // 3 second delay between requests to avoid rate limits
            } else {
                $stats['failed']++;
                sleep(2); // Also delay on failure
            }
        }
        
        return $stats;
    }
    
    /**
     * Batch translate multiple texts at once (more efficient)
     */
    private static function translateBatch(array $texts, string $targetLang): ?array {
        if (empty($texts) || !is_array($texts)) {
            return null;
        }
        
        try {
            $langNames = [
                'ru' => 'Russian',
                'es' => 'Spanish',
                'de' => 'German',
                'fr' => 'French',
                'zh' => 'Chinese'
            ];
            
            $targetLanguage = $langNames[$targetLang] ?? 'English';
            
            // Prepare texts for JSON
            $textsForJson = [];
            foreach ($texts as $key => $text) {
                $textsForJson[] = [
                    'key' => $key,
                    'text' => $text
                ];
            }
            
            $jsonTexts = json_encode($textsForJson, JSON_UNESCAPED_UNICODE);
            
            $models = [
                'anthropic/claude-3.5-sonnet',
                'openai/gpt-4o-mini',
                'google/gemini-pro-1.5'
            ];
            
            foreach ($models as $model) {
                try {
                    $result = self::callOpenRouterBatch($model, $jsonTexts, $targetLanguage);
                    
                    if ($result && is_array($result)) {
                        // Validate results
                        $translations = [];
                        foreach ($result as $item) {
                            if (isset($item['key']) && isset($item['text']) && isset($texts[$item['key']])) {
                                $translations[$item['key']] = $item['text'];
                            }
                        }
                        
                        if (count($translations) > 0) {
                            error_log("Batch translation successful: " . count($translations) . " texts to {$targetLang}");
                            return $translations;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Batch translation with {$model} failed: " . $e->getMessage());
                    continue;
                }
            }
            
            return null;
        } catch (Exception $e) {
            error_log('Batch translation error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Call OpenRouter API for batch translation
     */
    private static function callOpenRouterBatch(string $model, string $jsonTexts, string $targetLanguage): ?array {
        $apiKey = self::getOpenRouterKey();
        
        if (!$apiKey) {
            error_log('OpenRouter API key not configured');
            return null;
        }
        
        $messages = [
            [
                'role' => 'system',
                'content' => "You are a professional translator. Translate the given English texts to {$targetLanguage}. Return ONLY a JSON array with objects containing 'key' and 'text' fields. Each 'text' should contain only the translated text. Keep the same tone and style. If there are parameters in curly braces like {param}, keep them unchanged. Do not add any explanations or additional text outside the JSON."
            ],
            [
                'role' => 'user',
                'content' => "Translate these English texts to {$targetLanguage}:\n{$jsonTexts}"
            ]
        ];
        
        $data = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => 4000,
            'temperature' => 0.1
        ];
        
        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'HTTP-Referer: https://amnez.ia',
            'X-Title: Amnezia VPN Panel'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("OpenRouter batch API error: HTTP {$httpCode}");
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            return null;
        }
        
        $responseText = trim($result['choices'][0]['message']['content']);
        
        // Remove markdown code blocks if present
        if (strpos($responseText, '```json') !== false) {
            $responseText = preg_replace('/```json\s*/', '', $responseText);
            $responseText = preg_replace('/\s*```/', '', $responseText);
            $responseText = trim($responseText);
        }
        
        $translatedJson = json_decode($responseText, true);
        
        if (!is_array($translatedJson)) {
            error_log("Batch translation: Invalid JSON response");
            return null;
        }
        
        return $translatedJson;
    }
    
    /**
     * Get translation statistics
     */
    public static function getStatistics(): array {
        $pdo = DB::conn();
        
        $stmt = $pdo->query("
            SELECT 
                l.code,
                l.name,
                l.native_name,
                COUNT(t.id) as translated_count,
                (SELECT COUNT(*) FROM translations WHERE locale = 'en') as total_count
            FROM languages l
            LEFT JOIN translations t ON l.code = t.locale
            WHERE l.is_active = 1
            GROUP BY l.code, l.name, l.native_name
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add or update translation
     */
    public static function setTranslation(string $languageCode, string $key, string $value): bool {
        $pdo = DB::conn();
        // Split key into category and key_name
        $parts = explode('.', $key, 2);
        $category = $parts[0] ?? 'common';
        $keyName = $parts[1] ?? $key;
        
        $stmt = $pdo->prepare('
            INSERT INTO translations (locale, category, key_name, translation)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE translation = VALUES(translation)
        ');
        
        return $stmt->execute([$languageCode, $category, $keyName, $value]);
    }
    
    /**
     * Export translations to JSON file
     */
    public static function exportToJson(string $languageCode): string {
        $pdo = DB::conn();
        $stmt = $pdo->prepare('SELECT CONCAT(category, ".", key_name) as trans_key, translation FROM translations WHERE locale = ?');
        $stmt->execute([$languageCode]);
        
        $translations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Import translations from JSON file
     */
    public static function importFromJson(string $languageCode, string $json): bool {
        $translations = json_decode($json, true);
        
        if (!is_array($translations)) {
            return false;
        }
        
        $pdo = DB::conn();
        $pdo->beginTransaction();
        
        try {
            foreach ($translations as $key => $value) {
                self::setTranslation($languageCode, $key, $value);
            }
            
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }
    
    /**
     * Save API key for translation service
     */
    public static function saveApiKey(string $serviceName, string $apiKey): bool {
        try {
            $pdo = DB::conn();
            $stmt = $pdo->prepare('
                INSERT INTO api_keys (service_name, api_key, is_active)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE api_key = VALUES(api_key), updated_at = NOW()
            ');
            return $stmt->execute([$serviceName, $apiKey]);
        } catch (Exception $e) {
            error_log('Failed to save API key: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get API key for service
     */
    public static function getApiKey(string $serviceName): ?string {
        try {
            $pdo = DB::conn();
            $stmt = $pdo->prepare("SELECT api_key FROM api_keys WHERE service_name = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$serviceName]);
            return $stmt->fetchColumn() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
}
