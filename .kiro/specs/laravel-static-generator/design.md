# Design Document

## Overview

Система представляет собой Laravel-приложение для генерации статических HTML-сайтов с административной панелью, работающее по принципу "генератор-первый". Blade используется исключительно как шаблонизатор для создания статического HTML, который затем публикуется на изолированные production-серверы без PHP и баз данных.

Административная панель построена на Tailwind CSS v4.0.7 с поддержкой темной и светлой тем оформления. Интерфейс обеспечивает полную редактируемость всего контента сайта, включая мета-поля, медиафайлы и структурированные данные. Система поддерживает быстрое клонирование сайтов на основе локальной staging-папки и индивидуальную настройку SFTP-доступа к удаленным серверам для каждого сайта.

Архитектура построена на четком разделении ответственности:
- **Admin Server** - полнофункциональное Laravel-приложение с БД, генератором и админ-панелью на Tailwind CSS
- **Staging** - промежуточное хранилище для тестирования сгенерированного HTML (локальная папка, служит резервной копией)
- **Production Server** - минималистичный сервер с Nginx, обслуживающий только статические файлы

Система поддерживает мультисайты, AI-интеграцию для контента, автоматическую SEO-оптимизацию и безопасный деплой через SFTP с индивидуальными настройками для каждого сайта.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      ADMIN SERVER (Debian)                   │
│                       a.ratel.im                         │
├─────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐   │
│  │   Laravel    │  │   MariaDB    │  │   Supervisor    │   │
│  │   Admin UI   │  │   Database   │  │   (Queues)      │   │
│  └──────┬───────┘  └──────────────┘  └─────────────────┘   │
│         │                                                     │
│  ┌──────▼──────────────────────────────────────────────┐   │
│  │           HTML Generator Service                     │   │
│  │  (Blade → HTML + SEO + Sitemap)                     │   │
│  └──────┬──────────────────────────────────────────────┘   │
│         │                                                     │
│  ┌──────▼──────────────────────────────────────────────┐   │
│  │         Staging Storage (Local/Disk)                 │   │
│  │         /var/www/storage/generated/                  │   │
│  └──────┬──────────────────────────────────────────────┘   │
└─────────┼───────────────────────────────────────────────────┘
          │
          │ SFTP Deploy
          │
┌─────────▼───────────────────────────────────────────────────┐
│                  PRODUCTION SERVER (Debian)                  │
│                       test.ratel.im                               │
├─────────────────────────────────────────────────────────────┤
│  ┌──────────────┐                                           │
│  │    Nginx     │  ← Serves static HTML only                │
│  │   (Static)   │                                           │
│  └──────┬───────┘                                           │
│         │                                                     │
│  ┌──────▼──────────────────────────────────────────────┐   │
│  │         /var/www/test.ratel.im/                            │   │
│  │         ├── index.html                               │   │
│  │         ├── about.html                               │   │
│  │         ├── media/                                   │   │
│  │         ├── sitemap.xml                              │   │
│  │         └── robots.txt                               │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### Multi-Site Architecture

```
Admin Server
├── Site 1 (test.ratel.im)
│   ├── Pages (DB)
│   ├── Sections (DB)
│   ├── Media (/storage/sites/1/media/)
│   └── Generated HTML (/storage/generated/site1/)
│
├── Site 2 (test2.ratel.im)
│   ├── Pages (DB)
│   ├── Sections (DB)
│   ├── Media (/storage/sites/2/media/)
│   └── Generated HTML (/storage/generated/site2/)
│
└── Site N (testN.ratel.im)
    └── ...

Production Servers
├── test.ratel.im → /var/www/test.ratel.im/
├── test2.ratel.im → /var/www/test2.ratel.im/
└── testN.ratel.im → /var/www/testN.ratel.im/
```

### Component Interaction Flow

```
User Action → Admin UI → Controller → Service Layer → Repository
                                           ↓
                                    Domain Models
                                           ↓
                              ┌────────────┴────────────┐
                              ↓                         ↓
                        HTML Generator            Media Manager
                              ↓                         ↓
                        Blade Engine              Image Processing
                              ↓                         ↓
                        Staging Storage           Media Storage
                              ↓                         ↓
                        Deploy Service ←──────────────┘
                              ↓
                        SFTP Client
                              ↓
                        Production Server
```

## Components and Interfaces

### 1. Domain Models

#### Site Model
```php
class Site extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'template_set',
        'output_path',
        'status',
        'locale',
        'default_locale',
        'sftp_host',
        'sftp_port',
        'sftp_username',
        'sftp_password',
        'sftp_private_key',
        'sftp_auth_method',
        'sftp_remote_path'
    ];

    protected $casts = [
        'status' => SiteStatus::class,
        'sftp_port' => 'integer',
        'sftp_auth_method' => SftpAuthMethod::class,
    ];

    protected $hidden = [
        'sftp_password',
        'sftp_private_key',
    ];

    public function pages(): HasMany;
    public function media(): HasMany;
    public function deployments(): HasMany;
    
    public function getSftpCredentials(): array;
    public function testSftpConnection(): bool;
}
```

#### Page Model
```php
class Page extends Model
{
    protected $fillable = [
        'site_id',
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical',
        'og_data',
        'json_ld',
        'status',
        'locale',
        'parent_page_id'
    ];

    protected $casts = [
        'og_data' => 'array',
        'json_ld' => 'array',
        'status' => PageStatus::class,
    ];

    public function site(): BelongsTo;
    public function sections(): HasMany;
    public function languageVersions(): HasMany;
    public function parentPage(): BelongsTo;
}
```

#### Section Model
```php
class Section extends Model
{
    protected $fillable = [
        'page_id',
        'type',
        'content',
        'order'
    ];

    protected $casts = [
        'content' => 'array',
        'type' => SectionType::class,
    ];

    public function page(): BelongsTo;
}
```

#### Media Model
```php
class Media extends Model
{
    protected $fillable = [
        'site_id',
        'path',
        'webp_path',
        'alt',
        'title',
        'width',
        'height',
        'size',
        'mime_type'
    ];

    public function site(): BelongsTo;
}
```

### 2. Service Layer

#### HtmlGeneratorService
```php
interface HtmlGeneratorInterface
{
    public function generatePage(Page $page): string;
    public function generateSite(Site $site): GenerationResult;
    public function generateSitemap(Site $site): string;
    public function generateRobotsTxt(Site $site): string;
}

class HtmlGeneratorService implements HtmlGeneratorInterface
{
    public function __construct(
        private BladeCompiler $blade,
        private SeoService $seo,
        private MinifierService $minifier,
        private StorageManager $storage
    ) {}

    public function generatePage(Page $page): string
    {
        // 1. Load page with sections
        // 2. Prepare view data
        // 3. Render Blade template
        // 4. Apply SEO optimizations
        // 5. Minify HTML (optional)
        // 6. Return HTML string
    }

    public function generateSite(Site $site): GenerationResult
    {
        // 1. Get all active pages
        // 2. Generate HTML for each page
        // 3. Save to staging storage
        // 4. Generate sitemap.xml
        // 5. Generate robots.txt
        // 6. Copy media files
        // 7. Return result with stats
    }

    public function generatePreview(Page $page): array
    {
        // 1. Load page with sections
        // 2. Generate HTML from Blade template
        // 3. Rewrite absolute asset paths to relative (/assets/... -> assets/...)
        //    Handles: /assets/, /css/, /js/, /images/, /fonts/, /media/, etc.
        // 4. Save HTML to preview storage with unique token
        // 5. Copy all static resource directories from site's generated directory:
        //    - Source: storage/generated/site{id}/{assets,css,js,images,fonts,media,...}/
        //    - Destination: storage/generated/preview/{token}/{assets,css,js,images,fonts,media,...}/
        //    - Maintains full directory structure
        // 6. Return preview URL and expiration time
        // Resources are then served at: /api/preview/{token}/css/..., /api/preview/{token}/assets/..., etc.
    }
}
```

**Preview Feature with Assets:**
When a user clicks the Preview button on a page editing interface, the system:
1. Generates a temporary preview token
2. Renders the page HTML from its Blade template
3. **Rewrites all absolute asset paths to relative paths** so they resolve in preview context:
   - `/assets/...` → `assets/...`
   - `/css/...` → `css/...`
   - `/js/...` → `js/...`
   - `/images/...` → `images/...`
   - `/fonts/...` → `fonts/...`
   - And any other static resource directory
4. **Copies all static directories** from the site's generated folder to the preview folder:
   - Includes: assets/, css/, js/, images/, fonts/, media/, img/, static/, dist/
   - Maintains full directory structure for correct path resolution
5. Stores everything in temporary storage with automatic cleanup after 30 minutes
6. Returns a preview URL that can be opened in a new browser tab

This approach allows users to:
- Preview pages with complete styling and functionality
- Immediately edit assets and styles for testing
- See changes exactly as they would appear on the production site
- Test all media files, CSS, JavaScript, and other resources without needing to deploy

**Asset Path Rewriting:**
The system detects and rewrites all absolute paths to static resources:
```
Original HTML: <link href="/css/style.css" rel="stylesheet">
Preview HTML:  <link href="css/style.css" rel="stylesheet">

Original HTML: <script src="/js/app.js"></script>
Preview HTML:  <script src="js/app.js"></script>

Original HTML: <img src="/assets/images/logo.png" alt="Logo">
Preview HTML:  <img src="assets/images/logo.png" alt="Logo">
```

The relative paths resolve correctly within the preview directory structure, allowing all resources to load without modification to the file system structure.

**Supported Resource Directories:**
The system automatically copies these common static directories if they exist:
- `assets/` - General static assets (CSS, JS, images, fonts)
- `css/` - Stylesheets
- `js/` - JavaScript files
- `images/` or `img/` - Image files
- `fonts/` - Font files
- `media/` - Media files
- `static/` - Static resources
- `dist/` - Distribution files
```

#### MediaManagerService
```php
interface MediaManagerInterface
{
    public function upload(UploadedFile $file, Site $site): Media;
    public function generateWebP(Media $media): string;
    public function resize(Media $media, int $width, int $height): Media;
    public function delete(Media $media): bool;
}

class MediaManagerService implements MediaManagerInterface
{
    public function __construct(
        private ImageProcessor $processor,
        private StorageManager $storage
    ) {}

    public function upload(UploadedFile $file, Site $site): Media
    {
        // 1. Validate file type and size
        // 2. Generate unique filename
        // 3. Extract image dimensions
        // 4. Save original file
        // 5. Generate WebP version
        // 6. Create Media record
        // 7. Return Media model
    }
}
```

#### DeployService
```php
interface DeployServiceInterface
{
    public function deploy(Site $site): DeploymentResult;
    public function checkConnection(Site $site): bool;
    public function rollback(Deployment $deployment): bool;
}

class DeployService implements DeployServiceInterface
{
    public function __construct(
        private SftpClient $sftp,
        private HtmlGeneratorService $generator,
        private DeploymentRepository $deployments
    ) {}

    public function deploy(Site $site): DeploymentResult
    {
        // 1. Verify staging files exist
        // 2. Create deployment record
        // 3. Connect to production via SFTP using site-specific credentials
        // 4. Create backup of current files
        // 5. Upload new files
        // 6. Verify upload success
        // 7. Update deployment status
        // 8. Return result
    }
    
    public function checkConnection(Site $site): bool
    {
        // Test SFTP connection using site's credentials
        // Return true if successful, false otherwise
    }
}
```

#### SiteCloningService
```php
interface SiteCloningServiceInterface
{
    public function cloneFromDatabase(Site $sourceSite, array $overrides): Site;
    public function cloneFromStaging(string $stagingPath, array $siteData): Site;
}

class SiteCloningService implements SiteCloningServiceInterface
{
    public function __construct(
        private SiteRepository $sites,
        private PageRepository $pages,
        private SectionRepository $sections,
        private StorageManager $storage
    ) {}

    public function cloneFromDatabase(Site $sourceSite, array $overrides): Site
    {
        // 1. Create new site record with overrides
        // 2. Clone all pages with sections
        // 3. Copy media files to new site directory
        // 4. Return new site
    }

    public function cloneFromStaging(string $stagingPath, array $siteData): Site
    {
        // 1. Validate staging path exists
        // 2. Create new site record
        // 3. Copy all HTML files from staging to new site's staging
        // 4. Copy media files
        // 5. Parse HTML to extract page structure (optional)
        // 6. Create page records based on HTML files
        // 7. Return new site
    }
}
```

#### SeoService
```php
interface SeoServiceInterface
{
    public function validateMetaTitle(string $title): ValidationResult;
    public function validateMetaDescription(string $description): ValidationResult;
    public function generateMetaTitle(string $content): string;
    public function generateMetaDescription(string $content): string;
    public function checkDuplicateSlugs(Site $site, string $slug, ?int $excludePageId): bool;
}

class SeoService implements SeoServiceInterface
{
    private const MAX_TITLE_LENGTH = 60;
    private const MAX_DESCRIPTION_LENGTH = 160;

    public function validateMetaTitle(string $title): ValidationResult
    {
        // Check length and return warnings
    }
}
```

#### AiContentService
```php
interface AiContentServiceInterface
{
    public function parseMarkdown(string $markdown): ParsedContent;
    public function generateSections(ParsedContent $content): array;
    public function generateMetadata(string $content): array;
    public function generateJsonLd(string $content, string $type): array;
}

class AiContentService implements AiContentServiceInterface
{
    public function __construct(
        private MarkdownParser $parser,
        private AiClient $aiClient
    ) {}

    public function parseMarkdown(string $markdown): ParsedContent
    {
        // Parse markdown structure
    }

    public function generateSections(ParsedContent $content): array
    {
        // Send to AI service
        // Parse response
        // Create section data structures
    }
}
```

### 3. Repository Layer

```php
interface SiteRepositoryInterface
{
    public function create(array $data): Site;
    public function update(Site $site, array $data): Site;
    public function delete(Site $site): bool;
    public function findById(int $id): ?Site;
    public function getAll(): Collection;
    public function clone(Site $site, array $overrides): Site;
}

interface PageRepositoryInterface
{
    public function create(array $data): Page;
    public function update(Page $page, array $data): Page;
    public function delete(Page $page): bool;
    public function findBySlug(Site $site, string $slug): ?Page;
    public function getActiveBySite(Site $site): Collection;
}
```

### 4. Queue Jobs

```php
class GenerateSiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Site $site,
        private bool $shouldDeploy = false
    ) {}

    public function handle(HtmlGeneratorService $generator): void
    {
        // Generate HTML for all pages
        // Update progress
        // Optionally trigger deploy
    }
}

class DeploySiteJob implements ShouldQueue
{
    public function __construct(private Site $site) {}

    public function handle(DeployService $deploy): void
    {
        // Deploy to production
        // Handle errors
        // Send notifications
    }
}
```

### 5. API/Controller Layer

```php
class SiteController extends Controller
{
    public function __construct(
        private SiteRepositoryInterface $sites,
        private HtmlGeneratorService $generator
    ) {}

    public function index(): JsonResponse;
    public function store(StoreSiteRequest $request): JsonResponse;
    public function update(UpdateSiteRequest $request, Site $site): JsonResponse;
    public function destroy(Site $site): JsonResponse;
    public function clone(Site $site, CloneSiteRequest $request): JsonResponse;
    public function cloneFromStaging(Site $site, CloneStagingRequest $request): JsonResponse;
    public function generate(Site $site): JsonResponse;
    public function deploy(Site $site): JsonResponse;
    public function testSftpConnection(Site $site): JsonResponse;
}

class PageController extends Controller
{
    public function store(StorePageRequest $request): JsonResponse;
    public function update(UpdatePageRequest $request, Page $page): JsonResponse;
    public function preview(Page $page): Response;
}
```

### 6. Admin Panel UI Layer

#### Frontend Stack
- **CSS Framework**: Tailwind CSS v4.0.7
- **Build Tool**: Vite
- **JavaScript Framework**: Vue.js 3 or React (to be determined)
- **Theme Management**: CSS variables + localStorage/database persistence

#### Theme System
```javascript
// Theme configuration
const themes = {
  light: {
    '--bg-primary': '#ffffff',
    '--bg-secondary': '#f3f4f6',
    '--text-primary': '#111827',
    '--text-secondary': '#6b7280',
    '--border': '#e5e7eb',
    '--accent': '#3b82f6'
  },
  dark: {
    '--bg-primary': '#111827',
    '--bg-secondary': '#1f2937',
    '--text-primary': '#f9fafb',
    '--text-secondary': '#9ca3af',
    '--border': '#374151',
    '--accent': '#60a5fa'
  }
};

// Theme switcher component
class ThemeSwitcher {
  constructor() {
    this.currentTheme = this.loadTheme();
    this.applyTheme(this.currentTheme);
  }

  loadTheme(): string {
    return localStorage.getItem('theme') || 'light';
  }

  applyTheme(theme: string): void {
    const root = document.documentElement;
    Object.entries(themes[theme]).forEach(([key, value]) => {
      root.style.setProperty(key, value);
    });
    localStorage.setItem('theme', theme);
  }

  toggle(): void {
    this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
    this.applyTheme(this.currentTheme);
  }
}
```

#### Tailwind Configuration
```javascript
// tailwind.config.js
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: 'var(--bg-primary)',
        secondary: 'var(--bg-secondary)',
        accent: 'var(--accent)',
      }
    }
  },
  plugins: []
}
```

#### UI Components Structure
```
resources/js/components/
├── layout/
│   ├── Sidebar.vue
│   ├── Header.vue
│   ├── ThemeSwitcher.vue
│   └── Breadcrumbs.vue
├── sites/
│   ├── SiteList.vue
│   ├── SiteForm.vue
│   ├── SiteCloner.vue
│   └── RemoteServerConfig.vue
├── pages/
│   ├── PageEditor.vue
│   ├── SectionBuilder.vue
│   └── MetaFieldsEditor.vue
├── media/
│   ├── MediaLibrary.vue
│   ├── MediaUploader.vue
│   └── MediaEditor.vue
└── common/
    ├── Button.vue
    ├── Input.vue
    ├── Modal.vue
    └── Toast.vue
```

#### Remote Server Configuration Component
```vue
<template>
  <div class="remote-server-config">
    <h3>Remote Server Configuration</h3>
    
    <form @submit.prevent="saveConfig">
      <div class="form-group">
        <label>SFTP Host</label>
        <input v-model="config.sftp_host" type="text" required />
      </div>
      
      <div class="form-group">
        <label>SFTP Port</label>
        <input v-model="config.sftp_port" type="number" value="22" />
      </div>
      
      <div class="form-group">
        <label>Username</label>
        <input v-model="config.sftp_username" type="text" required />
      </div>
      
      <div class="form-group">
        <label>Authentication Method</label>
        <select v-model="config.sftp_auth_method">
          <option value="key">SSH Key</option>
          <option value="password">Password</option>
        </select>
      </div>
      
      <div v-if="config.sftp_auth_method === 'password'" class="form-group">
        <label>Password</label>
        <input v-model="config.sftp_password" type="password" />
      </div>
      
      <div v-if="config.sftp_auth_method === 'key'" class="form-group">
        <label>Private Key</label>
        <textarea v-model="config.sftp_private_key" rows="10"></textarea>
      </div>
      
      <div class="form-group">
        <label>Remote Path</label>
        <input v-model="config.sftp_remote_path" type="text" placeholder="/var/www/site.su" />
      </div>
      
      <div class="form-actions">
        <button type="button" @click="testConnection" class="btn-secondary">
          Test Connection
        </button>
        <button type="submit" class="btn-primary">
          Save Configuration
        </button>
      </div>
    </form>
    
    <div v-if="connectionStatus" class="connection-status" :class="connectionStatus.success ? 'success' : 'error'">
      {{ connectionStatus.message }}
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      config: {
        sftp_host: '',
        sftp_port: 22,
        sftp_username: '',
        sftp_password: '',
        sftp_private_key: '',
        sftp_auth_method: 'key',
        sftp_remote_path: ''
      },
      connectionStatus: null
    };
  },
  methods: {
    async testConnection() {
      const response = await fetch(`/api/sites/${this.siteId}/test-sftp`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(this.config)
      });
      this.connectionStatus = await response.json();
    },
    async saveConfig() {
      await fetch(`/api/sites/${this.siteId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(this.config)
      });
    }
  }
};
</script>
```

### 5. Gutenberg-like Page Builder (Target UX)

#### Editor Goal
Редактор страницы должен работать как блочный конструктор (по аналогии с Gutenberg):
- На этапе создания страницы пользователь сначала выбирает `Тип страницы`.
- Поддерживаются типы: `blank`, `1win`, `app-copy`, `app`, `authors`, `bonuses`, `comparison`, `contact-us`, `cookie-policy`, `demo`, `index`, `privacy-policy`, `reviews`, `sitemap`, `terms-and-conditions`, `tips`.
- После выбора типа страницы система подгружает стартовый набор модулей (для `blank` набор пустой).
- Пользователь может добавлять, удалять, дублировать, переупорядочивать и редактировать модули.
- Для контента модуля доступны:
`визуальные поля` + `медиа` + `HTML source mode`.

#### Editor Layout
- Верхняя панель: `slug/title/status/locale`, `Save`, `Preview`.
- Левая колонка: библиотека модулей.
- Центральная колонка: canvas страницы (drag-and-drop порядок модулей).
- Правая колонка: настройки выбранного модуля:
`Content`, `Media`, `Advanced`, `HTML`.

#### Module Catalog (minimum)
Базовые модули должны быть созданы на основе SCSS-блоков:
`authors`, `background`, `benefits`, `bonuses`, `breadcrumbs`, `button`, `card`, `casino`, `characteristics`, `comparison`, `conclusion`, `demo`, `download`, `errors`, `faq`, `feature`, `feedback`, `footer`, `form`, `game`, `gameplay`, `header`, `hero`, `installation`, `level`, `lightbox`, `list`, `logo`, `menu`, `other-reviews`, `payments`, `promo`, `pros`, `review`, `rtp`, `screenshots`, `scrollbar`, `sitemap`, `steps`, `strategies`, `symbols`, `table`, `text`, `tips`.

#### Data Contract (Section Content JSON)
Каждый блок секции хранится в JSON `sections.content`:
```json
{
  "module": "hero",
  "variant": "default",
  "id": "hero-main",
  "class": "hero hero-main",
  "anchor": "top",
  "content": {
    "heading": "Title",
    "text": "Description"
  },
  "media": {
    "image": "/media/hero.webp"
  },
  "settings": {
    "theme": "light",
    "container": "wide"
  }
}
```

#### Template Bootstrap
- Для каждого типа страницы хранится `preset` (стартовый массив секций).
- При создании страницы выбранный preset копируется в секции новой страницы.
- Пользователь может изменить любой модуль после автозагрузки.

#### HTML Editing Mode
- Режим `HTML source` должен быть доступен минимум на уровне одного модуля.
- Дополнительно (опционально, этап 2): режим `Edit page HTML` для всего контента страницы.
- Laravel поддерживает этот сценарий через JS-редактор кода (например CodeMirror/Monaco) и сохранение результата обратно в `sections.content`.

#### Backend/API Requirements
- `GET /api/page-templates` - список типов страниц и доступных preset.
- `POST /api/pages` - создание страницы с параметром `template_key`.
- `POST /api/pages/{id}/sections/bootstrap` - пересоздать секции из выбранного шаблона (с подтверждением).
- `POST /api/pages/{id}/sections/reorder` - массовое обновление `order`.
- `PUT /api/sections/{id}` - сохранение контента выбранного модуля (включая HTML mode).

#### Validation Rules
- `module` обязателен и должен быть из каталога модулей.
- `content` и `settings` должны быть объектами JSON.
- `order` должен оставаться последовательным (0..N без пропусков).
- Для критичных модулей (например `faq`, `hero`, `table`) задаются schema-правила обязательных полей.

#### Rendering Priority
При генерации HTML модуль резолвится в порядке:
1. `templates/base/modules/{module}/{variant}.blade.php`
2. `templates/base/modules/{module}.blade.php`
3. fallback по `type` через `components/{type}.blade.php`

## Data Models

### Database Schema

```sql
-- Sites table
CREATE TABLE sites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NOT NULL UNIQUE,
    template_set VARCHAR(100) NOT NULL,
    output_path VARCHAR(500) NOT NULL,
    status ENUM('active', 'inactive', 'draft') DEFAULT 'draft',
    locale VARCHAR(10) DEFAULT 'en',
    default_locale VARCHAR(10) DEFAULT 'en',
    sftp_host VARCHAR(255),
    sftp_port INT UNSIGNED DEFAULT 22,
    sftp_username VARCHAR(255),
    sftp_password TEXT,
    sftp_private_key TEXT,
    sftp_auth_method ENUM('password', 'key') DEFAULT 'key',
    sftp_remote_path VARCHAR(500),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_domain (domain)
);

-- Pages table
CREATE TABLE pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    slug VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords TEXT,
    canonical VARCHAR(500),
    og_data JSON,
    json_ld JSON,
    status ENUM('published', 'draft', 'archived') DEFAULT 'draft',
    locale VARCHAR(10) DEFAULT 'en',
    parent_page_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_page_id) REFERENCES pages(id) ON DELETE SET NULL,
    UNIQUE KEY unique_site_slug_locale (site_id, slug, locale),
    INDEX idx_site_status (site_id, status),
    INDEX idx_locale (locale)
);

-- Sections table
CREATE TABLE sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    content JSON NOT NULL,
    order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    INDEX idx_page_order (page_id, order)
);

-- Media table
CREATE TABLE media (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    path VARCHAR(500) NOT NULL,
    webp_path VARCHAR(500),
    alt VARCHAR(255) NOT NULL,
    title VARCHAR(255),
    width INT UNSIGNED,
    height INT UNSIGNED,
    size INT UNSIGNED,
    mime_type VARCHAR(100),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_site (site_id)
);

-- Deployments table
CREATE TABLE deployments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'failed', 'rolled_back') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    duration INT UNSIGNED,
    files_count INT UNSIGNED,
    log TEXT,
    error_message TEXT,
    deployed_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_site_status (site_id, status),
    INDEX idx_created (created_at)
);

-- Audit logs table
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    auditable_type VARCHAR(100),
    auditable_id BIGINT UNSIGNED,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP NULL,
    INDEX idx_user (user_id),
    INDEX idx_auditable (auditable_type, auditable_id),
    INDEX idx_created (created_at)
);
```

### Template Structure

```
resources/views/templates/
├── base/
│   ├── layouts/
│   │   ├── main.blade.php
│   │   ├── minimal.blade.php
│   │   └── full-width.blade.php
│   ├── components/
│   │   ├── text.blade.php
│   │   ├── hero.blade.php
│   │   ├── list.blade.php
│   │   ├── table.blade.php
│   │   ├── faq.blade.php
│   │   ├── gallery.blade.php
│   │   └── cta.blade.php
│   └── pages/
│       ├── home.blade.php
│       ├── about.blade.php
│       └── contact.blade.php
├── business/
│   └── [same structure]
└── blog/
    └── [same structure]
```

### Storage Structure

```
storage/
├── app/
│   ├── sites/
│   │   ├── 1/
│   │   │   └── media/
│   │   │       ├── images/
│   │   │       └── documents/
│   │   └── 2/
│   │       └── media/
│   └── generated/
│       ├── site1/
│       │   ├── index.html
│       │   ├── about.html
│       │   ├── media/
│       │   ├── sitemap.xml
│       │   └── robots.txt
│       └── site2/
│           └── [same structure]
└── logs/
    ├── deployments/
    └── generation/
```

## Error Handling

### Error Categories

1. **Validation Errors** - User input validation failures
2. **Generation Errors** - Blade compilation or rendering failures
3. **Deploy Errors** - SFTP connection or file transfer failures
4. **Storage Errors** - File system or disk space issues
5. **External Service Errors** - AI service or third-party API failures

### Error Handling Strategy

```php
class GenerationException extends Exception
{
    public function __construct(
        string $message,
        public readonly Page $page,
        public readonly ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}

class DeployException extends Exception
{
    public function __construct(
        string $message,
        public readonly Site $site,
        public readonly ?string $step = null,
        public readonly ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}

// Global exception handler
class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->reportable(function (GenerationException $e) {
            Log::error('HTML generation failed', [
                'page_id' => $e->page->id,
                'site_id' => $e->page->site_id,
                'error' => $e->getMessage(),
            ]);
        });

        $this->reportable(function (DeployException $e) {
            Log::error('Deployment failed', [
                'site_id' => $e->site->id,
                'step' => $e->step,
                'error' => $e->getMessage(),
            ]);
            
            // Notify administrators
            Notification::send(
                User::admins()->get(),
                new DeploymentFailedNotification($e->site, $e->getMessage())
            );
        });
    }
}
```

### Retry Logic

```php
class DeployService
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 5; // seconds

    public function deploy(Site $site): DeploymentResult
    {
        return retry(
            self::MAX_RETRIES,
            fn() => $this->performDeploy($site),
            self::RETRY_DELAY * 1000,
            function (Exception $e) {
                return $e instanceof ConnectionException;
            }
        );
    }
}
```

### Rollback Mechanism

```php
class DeployService
{
    public function deploy(Site $site): DeploymentResult
    {
        $deployment = $this->deployments->create([
            'site_id' => $site->id,
            'status' => 'in_progress',
        ]);

        try {
            // Create backup
            $backup = $this->createBackup($site);
            
            // Upload files
            $this->uploadFiles($site);
            
            // Verify
            $this->verifyDeployment($site);
            
            $deployment->update(['status' => 'completed']);
            
        } catch (Exception $e) {
            // Rollback to backup
            $this->restoreBackup($site, $backup);
            
            $deployment->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            
            throw new DeployException(
                'Deployment failed and was rolled back',
                $site,
                previous: $e
            );
        }
    }
}
```

## Testing Strategy

### Unit Testing

Используем PHPUnit для unit-тестирования отдельных компонентов:

**Тестируемые компоненты:**
- Service layer methods (HtmlGeneratorService, MediaManagerService, SeoService)
- Repository operations
- Model methods and relationships
- Validation rules
- Helper functions

**Примеры unit-тестов:**
```php
class SeoServiceTest extends TestCase
{
    public function test_validates_meta_title_length()
    {
        $seo = new SeoService();
        $result = $seo->validateMetaTitle(str_repeat('a', 70));
        
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('exceeds', $result->getMessage());
    }

    public function test_detects_duplicate_slugs()
    {
        $site = Site::factory()->create();
        Page::factory()->create(['site_id' => $site->id, 'slug' => 'about']);
        
        $seo = new SeoService();
        $hasDuplicate = $seo->checkDuplicateSlugs($site, 'about', null);
        
        $this->assertTrue($hasDuplicate);
    }
}
```

### Property-Based Testing

Используем **Pest PHP** с плагином **pest-plugin-faker** для property-based тестирования.

**Конфигурация:**
```php
// tests/Pest.php
uses(Tests\TestCase::class)->in('Feature', 'Unit');

// Минимум 100 итераций для каждого property-теста
function property(): PendingCalls
{
    return test()->repeat(100);
}
```

**Каждый property-based тест должен:**
- Запускаться минимум 100 раз с разными входными данными
- Иметь комментарий с явной ссылкой на correctness property из design document
- Использовать формат: `// Feature: laravel-static-generator, Property {N}: {property_text}`

**Примеры property-based тестов:**
```php
// Feature: laravel-static-generator, Property 1: Slug uniqueness
property('site cannot have duplicate slugs')
    ->repeat(100)
    ->with(function () {
        return [
            'slug' => fake()->slug(),
            'site_id' => Site::factory()->create()->id,
        ];
    })
    ->expect(function ($slug, $siteId) {
        Page::factory()->create(['site_id' => $siteId, 'slug' => $slug]);
        
        $this->expectException(QueryException::class);
        Page::factory()->create(['site_id' => $siteId, 'slug' => $slug]);
    });
```

### Integration Testing

**Тестируемые сценарии:**
- Полный цикл генерации сайта (создание → генерация → проверка файлов)
- Деплой на staging SFTP
- AI-интеграция с mock-сервисом
- Queue jobs execution

### End-to-End Testing

**Инструменты:** Laravel Dusk для браузерного тестирования админ-панели

**Сценарии:**
- Создание сайта через UI
- Добавление страниц и секций
- Загрузка медиа
- Preview страницы
- Публикация на production

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

После анализа всех acceptance criteria, выявлены следующие группы свойств, которые можно объединить или упростить:

**Группа валидации длины (2.2, 2.3):** Оба свойства проверяют валидацию длины строк. Можно объединить в одно свойство о валидации SEO-полей.

**Группа сохранения данных (1.1, 2.1, 3.1):** Все проверяют сохранение полей модели. Можно объединить в общее свойство о персистентности данных.

**Группа генерации служебных файлов (6.4, 15.1-15.5):** Свойства о sitemap и robots.txt частично пересекаются. Объединим в свойства о корректной генерации SEO-файлов.

**Группа логирования (8.4, 10.5, 12.3, 14.5, 17.5):** Все проверяют создание записей в логах. Можно объединить в общее свойство о аудите.

**Группа SFTP-операций (8.2, 8.3, 10.3):** Все проверяют SFTP-взаимодействие. Объединим в свойства о деплое.

После рефлексии оставляем уникальные, неизбыточные свойства:

### Core Properties

**Property 1: Slug uniqueness within site**
*For any* site and any two pages within that site, if both pages have the same slug and locale, then the system should prevent the second page from being saved and return a validation error.
**Validates: Requirements 2.4**

**Property 2: Cascade deletion**
*For any* site with associated pages, sections, and media files, when the site is deleted, all related entities should be removed from both database and file storage.
**Validates: Requirements 1.5**

**Property 3: Site cloning preserves structure**
*For any* site with pages and sections, when cloned, the new site should have identical page structure and section content, but different domain and no sitemap reference.
**Validates: Requirements 1.4**

**Property 4: SEO field validation**
*For any* page, when meta_title exceeds 60 characters or meta_description exceeds 160 characters, the system should return a validation warning without preventing save.
**Validates: Requirements 2.2, 2.3**

**Property 5: JSON validation**
*For any* page with JSON-LD data, the system should validate JSON structure before saving, rejecting invalid JSON with a clear error message.
**Validates: Requirements 2.6**

**Property 6: Section ordering consistency**
*For any* page with multiple sections, when a section is deleted or reordered, all remaining sections should have sequential order values starting from 0 with no gaps.
**Validates: Requirements 3.2, 3.3**

**Property 7: Section type validation**
*For any* section of type FAQ, the content JSON must contain both "question" and "answer" fields; for type hero, it must contain "heading" and "image" fields.
**Validates: Requirements 3.4, 3.5**

**Property 8: Image metadata extraction**
*For any* uploaded image file, the system should automatically extract and save width, height, and size metadata to the database.
**Validates: Requirements 4.1**

**Property 9: WebP generation**
*For any* uploaded image in JPEG or PNG format, the system should automatically create a WebP version and save its path to the database.
**Validates: Requirements 4.2**

**Property 10: Required alt text**
*For any* media upload attempt without an alt text, the system should prevent saving and return a validation error requiring the alt field.
**Validates: Requirements 4.4**

**Property 11: Image resize preserves aspect ratio**
*For any* image and target dimensions, when resized, the resulting image should maintain the original aspect ratio unless explicitly overridden.
**Validates: Requirements 4.5**

**Property 12: Markdown parsing round trip**
*For any* valid Markdown document, parsing it to extract structure and then regenerating Markdown should produce semantically equivalent content.
**Validates: Requirements 5.1**

**Property 13: AI section generation completeness**
*For any* AI-processed content, all generated sections should be saved with sequential order values starting from 0, and all sections should have valid type and content fields.
**Validates: Requirements 5.3, 5.5**

**Property 14: HTML generation produces valid output**
*For any* page with sections, the generated HTML should be well-formed (balanced tags, valid structure) and contain all section content.
**Validates: Requirements 6.1**

**Property 15: Generated file path matches slug**
*For any* page with slug "about-us", the generated HTML file should be saved to staging storage at path matching the slug (e.g., "about-us.html" or "about-us/index.html").
**Validates: Requirements 6.2**

**Property 16: Active pages only generation**
*For any* site with both active and inactive pages, HTML generation should produce files only for pages with status "published" or "active".
**Validates: Requirements 6.3**

**Property 17: Sitemap contains all active pages**
*For any* site, the generated sitemap.xml should contain entries for all and only active pages, with valid lastmod dates where available.
**Validates: Requirements 6.4, 15.1, 15.2**

**Property 18: Template component inclusion**
*For any* page with a section of type T, the generated HTML should include the component template corresponding to type T from the Template Set.
**Validates: Requirements 7.2**

**Property 19: Template Set cloning completeness**
*For any* Template Set, when cloned, all files from layouts, components, and pages directories should be copied to the new Template Set directory.
**Validates: Requirements 7.5**

**Property 20: Pre-deploy validation**
*For any* deploy request, if staging storage does not contain generated HTML files for the site, the deploy should fail with a validation error before attempting SFTP connection.
**Validates: Requirements 8.1**

**Property 21: Deploy rollback on failure**
*For any* deployment that fails during file transfer, the system should restore the previous version of files on Production Server and mark deployment as "failed".
**Validates: Requirements 8.5**

**Property 22: Deploy command triggers full cycle**
*For any* site, when the site:deploy command is executed, it should sequentially: generate HTML, create archive, transfer via SFTP, extract archive, and log the deployment.
**Validates: Requirements 10.1, 10.2, 10.3, 10.4, 10.5**

**Property 23: Git commit on template change**
*For any* template file modification, the system should create a Git commit with a descriptive message containing the file name and change type.
**Validates: Requirements 11.1**

**Property 24: Gitignore excludes secrets**
*For any* Git synchronization, files matching patterns for secrets (*.key, *.env, *credentials*) should not be included in the repository.
**Validates: Requirements 11.5**

**Property 25: Audit logging for critical actions**
*For any* critical action (site deletion, deployment, user modification), the system should create an audit log entry with timestamp, user_id, action type, and affected entity.
**Validates: Requirements 12.3**

**Property 26: Audit log filtering**
*For any* audit log query with filters (user, action, date range), the returned results should contain only entries matching all specified filters.
**Validates: Requirements 12.4**

**Property 27: Preview isolation**
*For any* page, generating a preview should create HTML in temporary storage without modifying the published version in production or staging storage.
**Validates: Requirements 13.3**

**Property 28: Preview includes all resources**
*For any* preview page, the generated HTML should include all CSS link tags and JavaScript script tags from the Template Set.
**Validates: Requirements 13.4**

**Property 29: Queue job creation threshold**
*For any* site with more than 100 pages, triggering generation should create a queued job rather than executing synchronously.
**Validates: Requirements 14.1**

**Property 30: Queue progress tracking**
*For any* queued generation job, the progress percentage should be updated at least once per 10% of completion and reach 100% upon successful completion.
**Validates: Requirements 14.3**

**Property 31: Robots.txt includes sitemap reference**
*For any* site, the generated robots.txt should contain a "Sitemap:" directive pointing to the sitemap.xml URL.
**Validates: Requirements 15.4**

**Property 32: Hreflang generation for language versions**
*For any* page with linked language versions, the generated HTML should include hreflang link tags for all versions including x-default for the default language.
**Validates: Requirements 16.1, 16.3**

**Property 33: Hreflang uses valid ISO codes**
*For any* generated hreflang tag, the language code should match ISO 639-1 format (e.g., "en", "ru") and optional region should match ISO 3166-1 (e.g., "en-US").
**Validates: Requirements 16.2**

**Property 34: No hreflang for single language pages**
*For any* page without linked language versions, the generated HTML should not contain any hreflang link tags.
**Validates: Requirements 16.5**

**Property 35: Dashboard shows all servers**
*For any* dashboard request, the response should include status information for all configured Production Servers with availability indicators.
**Validates: Requirements 17.1**

**Property 36: Deployment history completeness**
*For any* deployment history request, the response should include all deployments with date, status, duration, and file count for each entry.
**Validates: Requirements 17.3**



## Security Considerations

### Admin Server Security

**Authentication & Authorization:**
- Two-factor authentication (2FA) using TOTP (Time-based One-Time Password)
- Role-based access control (RBAC) with roles: Super Admin, Admin, Content Manager, Viewer
- Session timeout after 30 minutes of inactivity
- Rate limiting: max 5 failed login attempts per 10 minutes per IP

**API Security:**
- CSRF protection on all state-changing requests
- API rate limiting: 60 requests per minute per user
- Input validation and sanitization on all endpoints
- SQL injection prevention via Eloquent ORM and prepared statements

**File Upload Security:**
- Whitelist allowed MIME types (images: jpeg, png, gif, webp)
- Maximum file size: 10MB
- Virus scanning on upload (ClamAV integration)
- Stored outside web root with controlled access

**Audit Trail:**
- Log all critical actions (create, update, delete, deploy)
- Store IP address, user agent, timestamp
- Immutable audit logs (append-only)

### Production Server Security

**Minimal Attack Surface:**
- No PHP interpreter installed
- No database server
- No Git client
- Only Nginx, Certbot, Fail2ban, UFW

**SSH Hardening:**
- Deploy user with restricted shell (rbash)
- SSH key-only authentication (no passwords)
- IP whitelist: only Admin Server IP allowed
- Chroot jail for deploy user to site directories

**Nginx Configuration:**
- Disable directory listing
- Block access to hidden files (.git, .env)
- Return 404 for .php files
- Security headers (X-Frame-Options, X-Content-Type-Options, CSP)

**Firewall Rules:**
- UFW: allow only ports 22 (SSH from Admin IP), 80 (HTTP), 443 (HTTPS)
- Fail2ban: auto-ban IPs with suspicious activity

### SFTP Security

**Connection Security:**
- SFTP over SSH (not FTP)
- Private key authentication
- Key rotation every 90 days
- Connection timeout: 30 seconds

**Credentials Management:**
- Store SFTP credentials encrypted in database
- Use Laravel's encryption (AES-256-CBC)
- Never log credentials in plain text
- Separate credentials per Production Server

## Performance Optimization

### HTML Generation Performance

**Caching Strategy:**
- Cache compiled Blade templates (Laravel's default)
- Cache database queries for template data
- Cache media file metadata

**Batch Processing:**
- Generate pages in batches of 50
- Use Laravel queues for sites with >100 pages
- Parallel processing: up to 4 workers

**Optimization Techniques:**
- Lazy load page sections
- Use database indexes on frequently queried fields
- Minimize Blade template complexity
- Optional HTML minification (configurable)

**Expected Performance:**
- Single page generation: <100ms
- 100 pages: ~10 seconds
- 1000 pages: ~2-3 minutes
- 10000 pages: ~20-30 minutes

### Media Processing Performance

**Image Optimization:**
- Resize images on upload (not on-demand)
- Generate WebP asynchronously via queue
- Use Intervention Image library with GD driver
- Cache processed images

**Storage Optimization:**
- Store media on separate disk/partition
- Use symbolic links for shared media
- Implement CDN integration (optional)

### Deploy Performance

**Transfer Optimization:**
- Compress files before transfer (gzip)
- Use rsync-style incremental updates
- Parallel file uploads (up to 5 concurrent)
- Resume interrupted transfers

**Network Optimization:**
- Keep-alive SFTP connections
- Connection pooling for multiple deploys
- Timeout handling and retry logic

## Monitoring and Logging

### Application Logging

**Log Levels:**
- ERROR: Generation failures, deploy errors, exceptions
- WARNING: Validation warnings, slow queries, large files
- INFO: Successful deploys, generation completion, user actions
- DEBUG: Detailed execution flow (development only)

**Log Storage:**
- Daily log rotation
- Retain logs for 30 days
- Separate log files per concern (generation, deploy, audit)

**Log Format:**
```json
{
  "timestamp": "2026-02-26T10:30:45Z",
  "level": "INFO",
  "message": "Site generated successfully",
  "context": {
    "site_id": 123,
    "pages_count": 45,
    "duration_ms": 2340,
    "user_id": 5
  }
}
```

### Performance Monitoring

**Metrics to Track:**
- HTML generation time per page
- Deploy duration and success rate
- Queue job processing time
- Database query performance
- Disk space usage

**Monitoring Tools:**
- Laravel Telescope (development)
- Laravel Horizon (queue monitoring)
- Custom dashboard with metrics

### Health Checks

**Admin Server:**
- Database connectivity
- Disk space availability (alert at 80%)
- Queue worker status
- PHP-FPM status

**Production Servers:**
- SFTP connectivity check every 5 minutes
- HTTP response check (200 OK)
- Disk space monitoring
- Nginx process status

### Alerting

**Alert Conditions:**
- Deploy failure
- Generation failure for >10% of pages
- Disk space >90% full
- Production Server unreachable for >5 minutes
- Queue jobs failing repeatedly

**Alert Channels:**
- Email to administrators
- Slack/Discord webhook (optional)
- SMS for critical alerts (optional)

## Deployment and Infrastructure

### Admin Server Setup

**System Requirements:**
- Debian 11 or 12
- 4 CPU cores minimum
- 8GB RAM minimum
- 100GB SSD storage
- PHP 8.3+
- MariaDB 10.6+ or PostgreSQL 14+

**Installation Steps:**
1. Install system packages: `apt install nginx php8.3-fpm php8.3-mysql composer nodejs npm`
2. Install Supervisor: `apt install supervisor`
3. Configure Nginx virtual host for admin.site.su
4. Clone Laravel application
5. Run `composer install --optimize-autoloader --no-dev`
6. Configure `.env` file with database credentials
7. Run migrations: `php artisan migrate`
8. Configure queue workers in Supervisor
9. Set up SSL with Certbot

**Directory Permissions:**
```bash
chown -R www-data:www-data /var/www/admin.site.su
chmod -R 755 /var/www/admin.site.su
chmod -R 775 /var/www/admin.site.su/storage
chmod -R 775 /var/www/admin.site.su/bootstrap/cache
```

### Production Server Setup

**System Requirements:**
- Debian 11 or 12
- 2 CPU cores minimum
- 2GB RAM minimum
- 50GB SSD storage per site

**Installation Steps:**
1. Install Nginx: `apt install nginx`
2. Install Certbot: `apt install certbot python3-certbot-nginx`
3. Install Fail2ban: `apt install fail2ban`
4. Configure UFW firewall
5. Create deploy user with restricted permissions
6. Configure SSH key authentication
7. Set up Nginx virtual hosts per site
8. Obtain SSL certificates

**Deploy User Setup:**
```bash
useradd -m -s /bin/rbash deploy
mkdir -p /home/deploy/.ssh
# Add Admin Server public key to authorized_keys
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys

# Restrict to site directories
mkdir -p /var/www/site1.su /var/www/site2.su
chown deploy:deploy /var/www/site*.su
```

### Nginx Configuration

**Admin Server (admin.site.su):**
```nginx
server {
    listen 80;
    server_name admin.site.su;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name admin.site.su;
    root /var/www/admin.site.su/public;

    ssl_certificate /etc/letsencrypt/live/admin.site.su/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/admin.site.su/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Production Server (site.su):**
```nginx
server {
    listen 80;
    server_name site.su www.site.su;
    return 301 https://site.su$request_uri;
}

server {
    listen 443 ssl http2;
    server_name www.site.su;
    return 301 https://site.su$request_uri;
}

server {
    listen 443 ssl http2;
    server_name site.su;
    root /var/www/site.su;

    ssl_certificate /etc/letsencrypt/live/site.su/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/site.su/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.html;

    location / {
        try_files $uri $uri/ $uri.html =404;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Block PHP execution
    location ~ \.php$ {
        return 404;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|webp)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Supervisor Configuration

**Queue Worker:**
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/admin.site.su/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/admin.site.su/storage/logs/worker.log
stopwaitsecs=3600
```

### Backup Strategy

**Admin Server Backups:**
- Database: Daily full backup, retain 30 days
- Application files: Weekly backup
- Media files: Daily incremental backup
- Backup storage: Off-site (S3, Backblaze, etc.)

**Production Server Backups:**
- Full site backup before each deploy
- Retain last 5 deployments
- Weekly full backup to off-site storage

**Backup Script Example:**
```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -p laravel_db > /backups/db_$DATE.sql
tar -czf /backups/media_$DATE.tar.gz /var/www/storage/sites/
# Upload to S3 or other storage
```

## Future Enhancements

### Phase 2 Features

1. **Multi-language Content Management**
   - UI for managing translations
   - Automatic translation via AI
   - Language fallback mechanism

2. **Advanced SEO Tools**
   - SEO score calculator
   - Keyword density analyzer
   - Broken link checker
   - Schema.org validator

3. **Content Scheduling**
   - Schedule page publication
   - Auto-publish at specified time
   - Content expiration dates

4. **A/B Testing**
   - Multiple page variants
   - Traffic splitting
   - Analytics integration

5. **CDN Integration**
   - Automatic CDN deployment
   - Cache invalidation
   - Edge location management

### Phase 3 Features

1. **Headless CMS API**
   - RESTful API for content
   - GraphQL endpoint
   - Webhook notifications

2. **Advanced Media Management**
   - Image editing in browser
   - Video upload and processing
   - Asset library with tagging

3. **Collaboration Features**
   - Multi-user editing
   - Comment system
   - Approval workflows

4. **Analytics Dashboard**
   - Page view statistics
   - User behavior tracking
   - Conversion tracking

## Conclusion

This design provides a comprehensive architecture for a Laravel-based static site generator with multi-site support, AI integration, and secure deployment to isolated production servers. The system prioritizes security, performance, and maintainability while providing a flexible foundation for future enhancements.

Key architectural decisions:
- **Generator-first approach**: Blade as template engine, not runtime renderer
- **Isolation**: Complete separation of admin and production environments
- **Security**: Minimal attack surface on production, comprehensive security on admin
- **Scalability**: Queue-based processing, batch operations, multi-site support
- **Testability**: Clear separation of concerns, comprehensive property-based testing strategy
