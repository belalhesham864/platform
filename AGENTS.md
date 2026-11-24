# AGENTS.md – Central Development Guidelines & Project Standards

> **Notice to all AI Agents & Developers:**  
> This document defines the permanent, mandatory architecture, coding standards, and development rules for this Laravel 12 API project.  
> **You MUST read and follow this document before modifying or writing any code.**  
> Do not introduce conflicting design patterns, generic conventions, or duplicate abstractions.

---

## 1. Project Overview & Architecture Philosophy

- **Framework:** Laravel 12 API-only platform (Headless, no Blade front-end views except transactional email templates).
- **Core Pattern:** Controller $\rightarrow$ Form Request $\rightarrow$ Service Layer $\rightarrow$ Eloquent Model $\rightarrow$ Database.
- **Authentication:** Laravel Sanctum Bearer tokens across three distinct authenticatable entities.
- **Languages / Localization:** Standard API response messages and validation feedback are localized in **Arabic** via organized translation files (`lang/ar/api.php`, `lang/ar/auth.php`, `lang/ar/validation.php`). Hardcoded strings are strictly forbidden in controllers, services, form requests, and exception handlers. Default locale is set to `ar`.
- **Golden Rule:** **Preserve existing database data and code patterns at all times.** Never restart, delete, or rewrite working functionality.

---

## 2. Directory & Namespace Structure

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── Admin/         # Admin API Controllers
│   │   │   ├── Instructor/    # Instructor API Controllers
│   │   │   └── Student/       # Student API Controllers
│   │   └── Controller.php
│   ├── Middleware/
│   │   └── SetLocale.php      # Enforces Arabic locale on API requests
│   ├── Requests/
│   │   ├── BaseLoginRequest.php            # Abstract base request
│   │   ├── BaseForgotPasswordRequest.php   # Abstract base request
│   │   ├── BaseResetPasswordRequest.php    # Abstract base request
│   │   ├── Admin/             # Admin Form Requests
│   │   ├── Instructor/        # Instructor Form Requests
│   │   └── Student/           # Student Form Requests
│   └── Resources/             # API Resources (AdminResource, InstructorResource, StudentResource, etc.)
├── Mail/                      # Transactional Mailables (SendOtpMail, etc.)
├── Models/                    # Eloquent Models (User, admins, instructors, courses, etc.)
├── Providers/
│   └── AppServiceProvider.php # Global configurations (JsonResource withoutWrapping, auth rebinding)
├── Services/                  # Business Logic Services (AdminAuthService, InstructorAuthService, StudentAuthService)
├── Support/
│   └── ApiResponse.php        # Static API response helper (for exception handling)
├── Traits/
│   └── ApiResponse.php        # Controller API response trait (single unified apiResponse function)
└── utils/                     # Utility classes (ImageManger for image upload & deletion)
bootstrap/
└── app.php                    # Routing, middleware aliases, and unified JSON exception rendering
database/
├── factories/                 # Eloquent model factories
├── migrations/                # Database migrations
└── seeders/                   # Idempotent database seeders
lang/
├── ar/                        # Arabic localization catalog
│   ├── api.php                # API response messages
│   ├── auth.php               # Authentication feedback
│   ├── passwords.php          # Password broker feedback
│   └── validation.php         # Validation rules, attributes, & custom messages
└── en/
routes/
├── admin.php                  # Admin domain routes
├── api.php                    # API route aggregator (/api/admin, /api/instructor, /api/student & /api/v1/*)
├── instructor.php             # Instructor domain routes
├── student.php                # Student domain routes
└── console.php                # Scheduled commands & tasks
tests/
└── Feature/
    └── Auth/                  # Automated feature tests
```

---

## 3. Authentication & Authorization Rules

### Three-Table Segregation (NO `role` Column)
The project separates user types by database table, **NOT** by a role column:
1. `admins` table $\rightarrow$ Authenticates Admins ONLY.
   - Initial account created via `AdminSeeder`.
   - No public registration route.
2. `instructors` table $\rightarrow$ Authenticates Instructors ONLY.
   - Created by Admin accounts; no public registration route.
   - Has an `is_active` boolean flag. Inactive accounts must be rejected with HTTP 403.
3. `users` table $\rightarrow$ Authenticates Students ONLY.
   - Public registration permitted via `/api/student/register`.

### Authentication Rules
- **Guard:** Sanctum (`auth:sanctum`).
- **Token Configuration:** Configured in `config/sanctum.php` with `SANCTUM_TOKEN_EXPIRATION` (default: 10080 minutes / 7 days).
- **Security / Enumeration Prevention:** On login, if the email does not exist **OR** the password is wrong, return HTTP 401 with `__('api.invalid_credentials')` (`"البريد الإلكتروني أو كلمة المرور غير صحيحة"`). Never reveal whether an email exists in the database.
- **Token Invalidation:**
  - `logout()`: Revoke the current access token (`$user->currentAccessToken()?->delete()`).
  - `resetPassword()`: Revoke **all** tokens for the account (`$user->tokens()->delete()`).

---

## 4. Unified API Response Standards

All controllers MUST use the `App\Traits\ApiResponse` trait.  
**Requirement:** Exactly **ONE** function handles both success and error responses. Do not create separate `success()` or `error()` methods in controllers or traits.

### Response Function Signature
```php
public function apiResponse(
    mixed $data = null,
    string $message = '',
    int $statusCode = 200,
    mixed $errors = null,
    ?bool $status = null
): JsonResponse
```

### JSON Format Specification
#### Success Response (`HTTP 200/201`)
```json
{
  "status": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": { ... },
  "errors": null
}
```

#### Error Response (`HTTP 400/401/403/404/422/429/500`)
```json
{
  "status": false,
  "message": "البريد الإلكتروني أو كلمة المرور غير صحيحة",
  "data": {},
  "errors": null
}
```

#### Validation Error Response (`HTTP 422`)
```json
{
  "status": false,
  "message": "بيانات الطلب غير صالحة",
  "data": {},
  "errors": {
    "email": [
      "حقل البريد الإلكتروني مطلوب."
    ]
  }
}
```

---

## 5. Controller Standards

1. **Thin Controllers:** Controllers must contain **zero** business logic, **zero** direct Eloquent queries, and **zero** raw token or password manipulation.
2. **Standard Workflow:**
   - Inject the dedicated Service via the constructor.
   - Type-hint the specific Form Request.
   - Delegate the action to the Service.
   - Pass the returned array into `$this->apiResponse(...)`.
3. **Template:**
```php
namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentLoginRequest;
use App\Services\StudentAuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected StudentAuthService $studentAuthService,
    ) {}

    public function login(StudentLoginRequest $request): JsonResponse
    {
        $result = $this->studentAuthService->login($request->validated());

        return $this->apiResponse(
            data: $result['data'] ?? null,
            message: $result['message'],
            statusCode: $result['code'],
            errors: $result['errors'] ?? null,
            status: $result['status']
        );
    }
}
```

---

## 6. Service Layer Standards

1. **Responsibilities:**
   - Coordinate model queries, password hashing, Sanctum token generation, OTP workflows, and transactional mail.
   - Perform all business checks (e.g. instructor `is_active` check).
2. **Querying Rules (CRITICAL):**
   - **NEVER use `findOrFail()`** in services.
   - Always use `find()` or `where(...)->first()`.
   - If a record is missing, check `if (! $model)` and return a controlled array response. **Do not throw unhandled database exceptions.**
3. **Return Format:** Services return associative arrays consumed by controllers:
   ```php
   return [
       'status' => true|false,
       'message' => 'Arabic or specified security message',
       'code' => 200|201|400|401|403|404|422,
       'data' => $resourceOrArray, // optional on error
       'errors' => $errorsArray,    // optional
   ];
   ```

---

## 7. Form Request Validation Standards

1. **Hierarchical Separation:**
   - Common validation rules and Arabic error messages live in abstract base requests (`BaseLoginRequest`, `BaseForgotPasswordRequest`, `BaseResetPasswordRequest`).
   - User-specific requests extend the base request and define their target table statically.
2. **NO Segment Usage (CRITICAL):**
   - **NEVER use `request()->segment()` or dynamic URL inspection** to determine user type or database table.
   - Each request statically declares its table via an abstract method:
     ```php
     abstract protected function table(): string;
     ```
3. **Arabic Error Messages:** All Form Requests must override `messages()` with clear Arabic feedback:
   ```php
   public function messages(): array
   {
       return [
           'email.required' => 'حقل البريد الإلكتروني مطلوب.',
           'email.email' => 'يجب إدخال بريد إلكتروني صالح.',
           'email.exists' => 'البريد الإلكتروني غير مسجل لدينا.',
           'password.required' => 'حقل كلمة المرور مطلوب.',
           'password.min' => 'يجب ألا تقل كلمة المرور عن 8 أحرف.',
           'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
       ];
   }
   ```

---

## 8. API Resource Standards

1. **Always Transform:** Never return raw Eloquent models directly to the client.
2. **Data Privacy:** Strictly omit sensitive attributes (`password`, `remember_token`).
3. **No Wrapping:** `JsonResource::withoutWrapping()` is enabled in `AppServiceProvider`. Ensure data nesting conforms cleanly to the unified API response format.
4. **Resources Inventory:**
   - `AdminResource`: `id`, `name`, `email`, `phone`, `image`, `created_at`
   - `InstructorResource`: `id`, `name`, `slug`, `email`, `Bio`, `phone`, `portfolio_picture`, `small_description`, `gender`, `birthday`, `education`, `certifications`, `skills`, `experience`, `specializations`, `achievements`, `years_of_experience`, `linkedin_url`, `facebook_url`, `twitter_url`, `instagram_url`, `youtube_url`, `is_active`, `rank_id`, `created_at`
   - `StudentResource`: `id`, `name`, `email`, `email_verified_at`, `created_at`

---

## 9. OTP & Password Reset Workflow

- **Package:** `ichtrojan/laravel-otp` (`Ichtrojan\Otp\Otp`).
- **Generation Settings:** Numeric, 6 digits, 10-minute lifetime:
  ```php
  $otpResult = (new Otp)->generate($email, 'numeric', 6, 10);
  ```
- **Validation:**
  ```php
  $otpValidation = (new Otp)->validate($email, $otp);
  if (! $otpValidation->status) {
      return [
          'status' => false,
          'message' => 'رمز التحقق غير صحيح أو منتهي الصلاحية',
          'code' => 400,
      ];
  }
  ```
- **Email Delivery:** Send OTPs via `App\Mail\SendOtpMail` using the responsive template `resources/views/emails/otp.blade.php`.
- **Mailtrap Configuration:** In `.env` (SMTP sandbox credentials). Automated tests must always use `MAIL_MAILER=array`.

---

## 10. Database & Migration Standards (SAFETY RULES)

1. **Zero Data Loss:**
   - **NEVER run `migrate:fresh`.**
   - **NEVER run `db:wipe`.**
   - **NEVER drop tables or truncate records.**
2. **Schema Alterations:**
   - Always create a new migration for schema changes (e.g. `add_columns_to_table`).
   - Use `Schema::hasColumn('table', 'column')` checks in `up()` and `down()` to guarantee idempotent executions across environments.
   - Use `->after('column')` to maintain logical column ordering in MySQL.
3. **Model Conventions:**
   - Respect existing model naming (`admins.php`, `instructors.php`, `courses.php`, `User.php`).
   - Always define `$fillable`, `$hidden`, and `$casts`.
   - Authenticatable models must have `'password' => 'hashed'` cast.
4. **Seeders:**
   - Must be strictly idempotent (use `firstOrCreate` or `updateOrCreate`).
   - Read credentials from `.env` with secure fallbacks.

---

## 11. Global Error Handling (`bootstrap/app.php`)

All exceptions occurring on API routes (`api/*` or `expectsJson()`) are caught and rendered using `App\Support\ApiResponse::apiResponse()`:
- **`ValidationException` (422):** Message: `"بيانات الطلب غير صالحة"`, includes `errors` dictionary.
- **`AuthenticationException` (401):** Message: `"يجب تسجيل الدخول أولاً للوصول إلى هذا المصدر"`.
- **`HttpException` (403):** Message: `"غير مصرح لك بالقيام بهذا الإجراء"`.
- **`HttpException` (404):** Message: `"المصدر المطلوب غير موجود"`.
- **`HttpException` (429):** Message: `"تم تجاوز عدد المحاولات المسموح بها، يرجى المحاولة لاحقاً"`.
- **Unhandled Exceptions (500):** Message: `"حدث خطأ أثناء معالجة الطلب"`.

---

## 12. Testing & Code Quality Standards

1. **Framework:** Pest PHP extending `Tests\TestCase`.
2. **Database:** SQLite `:memory:` for fast, isolated test runs.
3. **Request Guard Refresh:** Handled automatically in `AppServiceProvider::boot()` on request rebinding:
   ```php
   $this->app->rebinding('request', function ($app, $request) {
       if ($app->resolved('auth')) {
           $app['auth']->forgetGuards();
       }
   });
   ```
4. **Test Coverage Requirements:**
   - Successful and failed authentication flows.
   - Role boundaries and unauthorized access checks.
   - OTP generation and invalid/expired attempts.
   - Token revocation on logout and password reset.
5. **Code Formatting:** Laravel Pint must be run on all modified files:
   ```bash
   ./vendor/bin/pint app tests
   ```

---

## 13. Caching Guidelines

Laravel Cache is allowed and encouraged to improve application performance and reduce repeated database queries, especially for data that is frequently requested and does not change often.

### Required Rules

1. **Use Laravel Cache Facade:** Use the `Illuminate\Support\Facades\Cache` facade whenever caching is needed, following the existing coding standards and service pattern conventions of the project.
2. **Cache Frequently Requested Public Data:** Use caching for frequently requested data, such as Settings and public data that is repeatedly returned by the API (e.g. `Cache::remember(...)`).
3. **Define Explicit TTL:** Define an appropriate Time To Live (TTL) for each type of cached data (e.g. `now()->addHours(24)` or `now()->addDays(7)` for system settings).
4. **Invalidation & Refresh on Mutation:** Whenever an Admin creates, updates, or deletes a Setting (or any cached resource), the related cache must be invalidated or refreshed (e.g. `Cache::forget(...)`) immediately to ensure the latest data is returned.
5. **Clear & Structured Cache Keys:** Use clear and well-organized cache keys (e.g. `settings.all`, `settings.{key}`) to avoid naming conflicts.
6. **Data Privacy & Isolation:** Do not cache personal, sensitive, or user-specific data unless a proper and secure data isolation strategy is implemented.
7. **Avoid Over-Caching:** Avoid caching frequently changing data without a clear reason.
8. **No Additional Packages:** Do not install additional packages just for caching purposes. Use Laravel's existing native Cache system.
9. **Balance Optimization & Data Consistency:** Maintain a balance between performance optimization and data consistency to ensure that cached data is always accurate and up to date.

---

## 14. Image & File Upload Standards (`App\utils\ImageManger`)

Whenever dealing with image uploads, replacements, or deletions across any domain (Admin, Instructor, Student, Courses, etc.), you MUST use the centralized `App\utils\ImageManger` utility class.

### Required Rules

1. **Mandatory Utility:** Always use `App\utils\ImageManger` (`new ImageManger()` or dependency injection). Do not implement raw `$request->file('...')->store(...)` or custom upload logic inside controllers or services.
2. **Filesystem Disks:** Store images using the disks configured in `config/filesystems.php`:
   - Courses: `'courses'` disk (`public_path('uploads/courses')`).
   - General public assets: `'public'` disk.
3. **Single Image Upload:**
   - Use `uploadSingeImage($path, $image, $disk)`: generates a unique filename (`Str::uuid() . time() . '.' . $extension`) and stores the file on the specified disk.
   - Example:
     ```php
     $imageManger = new ImageManger();
     $fileName = $imageManger->uploadSingeImage('/', $request->file('image'), 'courses');
     ```
4. **Multiple Images:**
   - Use `uploadImages($images, $model, $disk)` for models that have a `hasMany` images relationship.
5. **Image Deletion & Cleanup:**
   - When updating or deleting an entity with an image, ensure the old image is deleted using `deleteImageFromLocal($oldImagePath)`.
6. **Database Persistence:**
   - Store the generated file name or relative path in the database as required by the schema.

---

## 15. Mandatory Checklist for Future Agents

Before marking any task as complete, verify:
- [ ] No `migrate:fresh` or destructive commands were used.
- [ ] No `findOrFail()` calls were added in the service layer.
- [ ] No `request()->segment()` or dynamic URL parsing was used.
- [ ] All API responses use the unified `apiResponse()` function.
- [ ] All responses and validation messages adhere to the project's language specifications (`lang/ar/`).
- [ ] Form requests inherit from appropriate base classes and statically define their tables.
- [ ] Caching rules are strictly respected: Cache facade used, explicit TTL configured, cache keys structured, and cache invalidated upon create/update/delete.
- [ ] Any image handling strictly uses `App\utils\ImageManger` and configured filesystem disks.
- [ ] All automated tests pass (`php artisan test`).
- [ ] Code is formatted with Laravel Pint (`./vendor/bin/pint --test app tests`).
- [ ] Every new endpoint has a Pest feature test and is added to the Postman collection (Section 16).


---
