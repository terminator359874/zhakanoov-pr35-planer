# CHANGELOG

## [1.0.0] - 2025-05-01
### Добавлено
- REST API: четыре endpoint (read, create, update, delete)
- Валидация всех форм на сервере
- Система ролей: owner, manager, member
- Техническая документация в YouTrack Knowledge Base
- README.md с инструкцией запуска

### Исправлено
- Исправлена ошибка кодировки в JSON (добавлен JSON_UNESCAPED_UNICODE)

### Безопасность
- Добавлена серверная валидация всех форм
- Защита страниц через requireLogin() и requireRole()
