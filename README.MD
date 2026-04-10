# Test Task

## Запуск после клонирования репозитория

Требования:
- Docker
- Docker Compose v2, команда `docker compose`

Клонируйте репозиторий и перейдите в каталог проекта:
```bash
git clone <repo-url>
cd test-task
```

Соберите и запустите контейнеры:
```bash
docker compose up --build
```

Приложение будет доступно на `http://localhost:8000 и http://localhost:8000/api/docs`

```bash
Новый дамп бд new_dump.sql и проблемы dump.sql
```