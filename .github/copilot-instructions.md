<!-- copilot instructions for proyectoRetos -->

# Contexto rápido

Proyecto backend en Laravel (PHP >= 8) que gestiona retos: categorías, retos (challenges), respuestas (answers) y puntuaciones de usuarios. Autenticación con JWT. Algunos endpoints usan la API de OpenAI para generar retos de forma automática (`ChallengeController::generateRandom`).

# Qué debe saber un agente AI antes de editar código

-   Estructura clave: modelos en `app/Models` (Challenge, Category, Answer, UserAnswer, User). Controladores API en `app/Http/Controllers/Api` (p. ej. `ChallengeController.php`).
-   Rutas API en `routes/api.php`. Middlewares en `app/Http/Middleware` y `app/Http/Controllers/Controller.php` como base.
-   Variables de entorno sensibles o necesarias: `DB_*`, `JWT_SECRET`, `OPENAI_API_KEY`, `OPENAI_MODEL`.
-   Dependencias notables en `composer.json`: JWT (tymon/jwt-auth) y cliente OpenAI (se usa `Http::withToken(...)` en controllers).

# Convenciones de implementación encontradas

-   Roles: el campo `role` en `users` controla permisos. Roles conocidos: `admin` y `user`. Muchos endpoints usan `->middleware('role:admin')` o chequeos manuales en controladores (ej. `if ($request->user()->role !== 'admin')`).
-   Respuestas JSON: los controladores devuelven respuestas JSON con claves `message`, `data`, `errors`. Código de estado HTTP coherente (201 para creación, 422 para validación, 500 para errores del servidor).
-   Respuesta de retos: `ChallengeResource` es la Resource para serializar retos; los controladores suelen usar `load(['answers','category'])` para incluir relaciones.
-   Sincronización de respuestas: al actualizar un `Challenge`, el controlador sincroniza `answers` eliminando las que no vienen y actualizando/creando las que sí. Mantiene exactamente una respuesta con `is_correct = true` (si hay >1, deja la primera).

# Flujos críticos y puntos de integración

-   OpenAI: `ChallengeController::generateRandom` hace POST a `https://api.openai.com/v1/chat/completions` con `OPENAI_API_KEY`. Valida y parsea JSON en el texto de la IA (busca primer bloque JSON). Si cambia el formato de respuesta de OpenAI o el modelo, hay que ajustar extracción y límites de tokens/temperature.
-   JWT: autenticación y generación de tokens está configurada; revisar `AuthServiceProvider` y configuración `config/jwt.php` si hay problemas de login.
-   BBDD: migraciones en `database/migrations` y seeders en `database/seeders`. Comandos comunes: `php artisan migrate --seed`.

# Comandos y flujos de desarrollo (local)

-   Instalar dependencias: `composer install`.
-   Generar claves: `php artisan key:generate` y `php artisan jwt:secret`.
-   Migrar y sembrar DB: `php artisan migrate` y `php artisan db:seed`.
-   Levantar servidor local: `php artisan serve --host=127.0.0.1 --port=8000`.

# Qué evitar o revisar cuidadosamente

-   No confiar en que OpenAI siempre devolverá JSON válido; el código actual extrae el primer objeto JSON en el texto y valida que haya exactamente una respuesta correcta y el número de respuestas esperado.
-   Evitar cambios que rompan la contract de `ChallengeResource` (los endpoints devuelven estructuras predecibles usadas por el frontend/mobile).
-   Cuidado con validaciones duplicadas: algunos endpoints usan `Validator::make(...)` manual además de `Request::validate()`.

# Ejemplos concretos para tareas comunes

-   Añadir un nuevo campo a `challenges` (por ejemplo `difficulty`):

    1. Crear migración en `database/migrations`.
    2. Añadir propiedad fillable al modelo `app/Models/Challenge.php`.
    3. Actualizar `store` y `update` en `app/Http/Controllers/Api/ChallengeController.php` para aceptar y persistir el campo.
    4. Actualizar `ChallengeResource` para exponer el campo.

-   Depurar problemas con generación IA:
    1. Revisa `OPENAI_API_KEY` en `.env` y `OPENAI_MODEL` en `config/openai.php` o `config/app.php`.
    2. Reproduce la petición usando `tinker` o un script: ejemplo mínimo usa `Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [...])`.
    3. Loggear el cuerpo devuelto (`Log::debug($resp->body())`) antes de parsear.

# Archivos y ubicaciones clave (rápida referencia)

-   Rutas API: `routes/api.php`
-   Controladores API: `app/Http/Controllers/Api/*.php` (p.ej. `ChallengeController.php`)
-   Modelos: `app/Models/*.php` (p.ej. `Challenge.php`, `Answer.php`, `Category.php`, `UserAnswer.php`)
-   Resources: `app/Http/Resources/ChallengeResource.php`
-   Config: `config/*.php` (buscar `openai.php`, `jwt.php`)
-   Migraciones y seeders: `database/migrations`, `database/seeders`

# Short checklist when changing behavior

1. Ejecutar migraciones locales si cambias esquema: `php artisan migrate`.
2. Añadir/actualizar seeders si requiere datos iniciales: `php artisan db:seed`.
3. Ejecutar pruebas (si las hay) con `./vendor/bin/phpunit`.
4. Asegurarse de que los endpoints devuelven `ChallengeResource` con relaciones cargadas si corresponde.

# Preguntas para el autor si falta contexto

-   ¿Hay documentación o tests para los contracts de API usados por clientes (frontend/mobile)?
-   ¿Desea que el generador IA acepte idiomas distintos a español o plantillas específicas? (actual prompt en `generateRandom` está en español)

---

Si quieres, actualizo esto con ejemplos de `composer.json` o fragmentos de `ChallengeResource` para hacer la guía aún más específica.
