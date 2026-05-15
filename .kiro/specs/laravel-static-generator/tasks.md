# Implementation Plan

- [ ] 1. Set up Laravel project structure and core configuration
  - Initialize Laravel 13 project with required dependencies
  - Configure database connection (MariaDB/PostgreSQL)
  - Set up environment configuration files
  - Install required packages: Intervention Image, League Flysystem SFTP, Laravel Horizon
  - Configure storage disks for sites, generated HTML, and staging
  - _Requirements: All_

- [ ] 2. Create database schema and migrations
  - Create migration for sites table with all fields including SFTP configuration
  - Create migration for pages table with SEO fields and JSON columns
  - Create migration for sections table with JSON content
  - Create migration for media table with image metadata
  - Create migration for deployments table with status tracking
  - Create migration for audit_logs table
  - Add indexes for performance optimization
  - _Requirements: 1.1, 2.1, 3.1, 4.1, 8.4, 12.3, 22.1, 22.2_

- [ ] 3. Implement domain models with relationships
  - Create Site model with fillable fields, casts, relationships, and SFTP methods
  - Create Page model with SEO fields, JSON casts, and relationships
  - Create Section model with type enum and content casting
  - Create Media model with file path and metadata fields
  - Create Deployment model with status tracking
  - Create AuditLog model for security logging
  - Define all Eloquent relationships (HasMany, BelongsTo)
  - Add hidden fields for sensitive SFTP credentials
  - _Requirements: 1.1, 2.1, 3.1, 4.1, 8.4, 12.3, 22.1, 22.2_

- [ ]* 3.1 Write property test for slug uniqueness
  - **Property 1: Slug uniqueness within site**
  - **Validates: Requirements 2.4**

- [ ]* 3.2 Write property test for cascade deletion
  - **Property 2: Cascade deletion**
  - **Validates: Requirements 1.5**

- [ ] 4. Implement repository layer for data access
  - Create SiteRepository with CRUD operations, clone method, and staging clone method
  - Create PageRepository with slug lookup and site filtering
  - Create SectionRepository with ordering operations
  - Create MediaRepository with site filtering
  - Create DeploymentRepository with status queries
  - Create AuditLogRepository with filtering capabilities
  - Bind interfaces to implementations in service provider
  - _Requirements: 1.1, 1.4, 2.1, 3.1, 4.1, 8.4, 12.3, 21.1, 21.2, 21.3_

- [ ]* 4.1 Write property test for site cloning
  - **Property 3: Site cloning preserves structure**
  - **Validates: Requirements 1.4**

- [ ] 5. Implement SEO service with validation
  - Create SeoService with meta title/description validation methods
  - Implement length validation with configurable limits (60/160 chars)
  - Create duplicate slug checker for site scope
  - Implement auto-generation methods for meta fields from content
  - Add OpenGraph data validation
  - Add JSON-LD structure validation
  - _Requirements: 2.2, 2.3, 2.4, 2.5, 2.6_

- [ ]* 5.1 Write property test for SEO field validation
  - **Property 4: SEO field validation**
  - **Validates: Requirements 2.2, 2.3**

- [ ]* 5.2 Write property test for JSON validation
  - **Property 5: JSON validation**
  - **Validates: Requirements 2.6**

- [ ] 6. Implement section management with ordering
  - Create SectionService with add, update, delete, reorder methods
  - Implement automatic order calculation on insert
  - Implement order recalculation on delete
  - Add section type validation (FAQ, hero, text, list, table, gallery, cta)
  - Validate required fields per section type
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ]* 6.1 Write property test for section ordering
  - **Property 6: Section ordering consistency**
  - **Validates: Requirements 3.2, 3.3**

- [ ]* 6.2 Write property test for section type validation
  - **Property 7: Section type validation**
  - **Validates: Requirements 3.4, 3.5**

- [ ] 7. Implement media manager service
  - Create MediaManagerService with upload method
  - Implement image metadata extraction (width, height, size)
  - Add MIME type validation (whitelist: jpeg, png, gif, webp)
  - Implement file size validation (max 10MB with warning at 2MB)
  - Add alt text requirement validation
  - Create unique filename generator
  - Implement storage path organization by site_id
  - _Requirements: 4.1, 4.3, 4.4_

- [ ]* 7.1 Write property test for image metadata extraction
  - **Property 8: Image metadata extraction**
  - **Validates: Requirements 4.1**

- [ ]* 7.2 Write property test for required alt text
  - **Property 10: Required alt text**
  - **Validates: Requirements 4.4**

- [ ] 8. Implement WebP generation and image processing
  - Add WebP generation method using Intervention Image
  - Implement automatic WebP creation on JPEG/PNG upload
  - Create image resize method with dimension parameters
  - Add aspect ratio preservation logic
  - Implement queue job for async WebP generation
  - Store both original and WebP paths in database
  - _Requirements: 4.2, 4.5_

- [ ]* 8.1 Write property test for WebP generation
  - **Property 9: WebP generation**
  - **Validates: Requirements 4.2**

- [ ]* 8.2 Write property test for image resize
  - **Property 11: Image resize preserves aspect ratio**
  - **Validates: Requirements 4.5**

- [ ] 9. Implement AI content service with Markdown parsing
  - Create MarkdownParser for extracting document structure
  - Implement AI client integration (OpenAI/Anthropic API)
  - Create method to generate sections from parsed content
  - Implement metadata generation (title, description, keywords)
  - Add JSON-LD generation for different schema types
  - Create section data structure builders
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ]* 9.1 Write property test for Markdown parsing
  - **Property 12: Markdown parsing round trip**
  - **Validates: Requirements 5.1**

- [ ]* 9.2 Write property test for AI section generation
  - **Property 13: AI section generation completeness**
  - **Validates: Requirements 5.3, 5.5**

- [ ] 10. Create Blade template structure
  - Create base template set directory structure (layouts, components, pages)
  - Implement main.blade.php layout with SEO section
  - Create component templates: text, hero, list, table, faq, gallery, cta
  - Add SEO meta tags rendering in layout head
  - Implement OpenGraph tags rendering
  - Add JSON-LD script tag rendering
  - Create hreflang tags rendering for multi-language
  - _Requirements: 2.5, 2.6, 7.1, 7.2, 7.4, 16.1, 16.3_

- [ ] 11. Implement HTML generator service
  - Create HtmlGeneratorService with page generation method
  - Implement Blade template rendering with page data
  - Add section loop rendering with component inclusion
  - Create site-wide generation method
  - Implement file saving to staging storage
  - Add path generation based on page slug
  - Create HTML validation check (well-formed tags)
  - _Requirements: 6.1, 6.2, 6.3, 7.2_

- [ ]* 11.1 Write property test for HTML generation
  - **Property 14: HTML generation produces valid output**
  - **Validates: Requirements 6.1**

- [ ]* 11.2 Write property test for file path matching
  - **Property 15: Generated file path matches slug**
  - **Validates: Requirements 6.2**

- [ ]* 11.3 Write property test for active pages only
  - **Property 16: Active pages only generation**
  - **Validates: Requirements 6.3**

- [ ]* 11.4 Write property test for template component inclusion
  - **Property 18: Template component inclusion**
  - **Validates: Requirements 7.2**

- [ ] 12. Implement sitemap and robots.txt generation
  - Create sitemap generator with XML structure
  - Add all active pages to sitemap with URLs
  - Include lastmod dates from updated_at field
  - Implement sitemap splitting for >50000 URLs
  - Create sitemap index generator
  - Implement robots.txt generator with sitemap reference
  - Add custom directives support for robots.txt
  - _Requirements: 6.4, 15.1, 15.2, 15.3, 15.4, 15.5_

- [ ]* 12.1 Write property test for sitemap generation
  - **Property 17: Sitemap contains all active pages**
  - **Validates: Requirements 6.4, 15.1, 15.2**

- [ ]* 12.2 Write property test for robots.txt
  - **Property 31: Robots.txt includes sitemap reference**
  - **Validates: Requirements 15.4**

- [ ] 13. Implement hreflang support for multi-language
  - Add language_versions relationship to Page model
  - Create method to link pages as language versions
  - Implement hreflang tag generation in HTML generator
  - Add ISO 639-1 language code validation
  - Add ISO 3166-1 region code validation
  - Implement x-default tag for default language
  - Add conditional hreflang rendering (only if versions exist)
  - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5_

- [ ]* 13.1 Write property test for hreflang generation
  - **Property 32: Hreflang generation for language versions**
  - **Validates: Requirements 16.1, 16.3**

- [ ]* 13.2 Write property test for ISO code validation
  - **Property 33: Hreflang uses valid ISO codes**
  - **Validates: Requirements 16.2**

- [ ]* 13.3 Write property test for single language pages
  - **Property 34: No hreflang for single language pages**
  - **Validates: Requirements 16.5**

- [ ] 14. Implement SFTP client and connection management
  - Configure SFTP storage disk in filesystems.php
  - Create SftpClient wrapper with connection pooling
  - Implement connection test method
  - Add credential encryption/decryption using Laravel encryption
  - Implement connection timeout handling
  - Add retry logic for failed connections
  - Create connection status checker
  - Support per-site SFTP configuration
  - _Requirements: 8.2, 17.1, 17.2, 22.2, 22.4_

- [ ]* 14.1 Write property test for credential encryption
  - **Property: SFTP credentials are encrypted in database**
  - **Validates: Requirements 22.2**

- [ ] 15. Implement deploy service with rollback
  - Create DeployService with deploy method using site-specific SFTP credentials
  - Implement pre-deploy validation (check staging files exist)
  - Add backup creation before deploy
  - Implement file upload via SFTP using site's remote server configuration
  - Add upload verification
  - Create deployment record with status tracking
  - Implement rollback mechanism on failure
  - Add deployment logging
  - Create SFTP connection test method
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 22.4, 22.5_

- [ ]* 15.1 Write property test for pre-deploy validation
  - **Property 20: Pre-deploy validation**
  - **Validates: Requirements 8.1**

- [ ]* 15.2 Write property test for deploy rollback
  - **Property 21: Deploy rollback on failure**
  - **Validates: Requirements 8.5**

- [ ] 16. Implement site:deploy artisan command
  - Create site:deploy command with site_id argument
  - Implement full deployment cycle: generate → archive → transfer → extract
  - Add progress output to console
  - Create archive with gzip compression
  - Implement SFTP transfer with progress tracking
  - Add remote extraction logic
  - Create deployment log entry
  - Handle errors with clear messages
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [ ]* 16.1 Write property test for deploy command
  - **Property 22: Deploy command triggers full cycle**
  - **Validates: Requirements 10.1, 10.2, 10.3, 10.4, 10.5**

- [ ] 17. Implement Git integration for version control
  - Initialize Git repository in templates directory
  - Create GitService with commit method
  - Implement auto-commit on template file changes
  - Add descriptive commit message generation
  - Create .gitignore with secrets exclusion patterns
  - Implement commit history retrieval
  - Add file restoration from specific commit
  - Configure staging directory as Git-tracked
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

- [ ]* 17.1 Write property test for Git commits
  - **Property 23: Git commit on template change**
  - **Validates: Requirements 11.1**

- [ ]* 17.2 Write property test for gitignore
  - **Property 24: Gitignore excludes secrets**
  - **Validates: Requirements 11.5**

- [ ] 18. Implement authentication with 2FA
  - Install and configure Laravel Fortify
  - Enable two-factor authentication feature
  - Create 2FA setup flow in user settings
  - Implement TOTP verification
  - Add recovery codes generation
  - Create 2FA enforcement for admin users
  - _Requirements: 12.1_

- [ ] 19. Implement rate limiting and security features
  - Configure rate limiter for login attempts (5 per 10 minutes)
  - Add IP-based blocking on failed attempts
  - Implement session timeout (30 minutes inactivity)
  - Add CSRF protection to all forms
  - Configure API rate limiting (60 requests per minute)
  - Create middleware for rate limit enforcement
  - _Requirements: 12.2, 12.5_

- [ ] 20. Implement audit logging system
  - Create AuditLogService with log method
  - Add event listeners for critical actions (create, update, delete, deploy)
  - Implement automatic logging with user_id, IP, user_agent
  - Create audit log viewer in admin panel
  - Add filtering by user, action, date range
  - Implement audit log export functionality
  - _Requirements: 12.3, 12.4_

- [ ]* 20.1 Write property test for audit logging
  - **Property 25: Audit logging for critical actions**
  - **Validates: Requirements 12.3**

- [ ]* 20.2 Write property test for audit filtering
  - **Property 26: Audit log filtering**
  - **Validates: Requirements 12.4**

- [ ] 21. Implement preview functionality
  - Create preview generation method in HtmlGeneratorService
  - Implement temporary storage for preview files
  - Add preview route with authentication
  - Create preview iframe rendering in admin UI
  - Implement preview URL generation
  - Add preview isolation (separate from staging/production)
  - Ensure preview includes all CSS/JS resources
  - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5_

- [ ]* 21.1 Write property test for preview isolation
  - **Property 27: Preview isolation**
  - **Validates: Requirements 13.3**

- [ ]* 21.2 Write property test for preview resources
  - **Property 28: Preview includes all resources**
  - **Validates: Requirements 13.4**

- [ ] 22. Implement queue system for large site generation
  - Configure Laravel Horizon for queue management
  - Create GenerateSiteJob with progress tracking
  - Implement job dispatching for sites with >100 pages
  - Add progress percentage calculation and updates
  - Create DeploySiteJob for async deployment
  - Implement job completion notifications
  - Add error handling and logging in jobs
  - Configure Supervisor for queue workers
  - _Requirements: 14.1, 14.2, 14.3, 14.4, 14.5_

- [ ]* 22.1 Write property test for queue threshold
  - **Property 29: Queue job creation threshold**
  - **Validates: Requirements 14.1**

- [ ]* 22.2 Write property test for progress tracking
  - **Property 30: Queue progress tracking**
  - **Validates: Requirements 14.3**

- [ ] 23. Implement template set management
  - Create TemplateSet model and migration
  - Implement template set creation with directory structure
  - Add template set cloning functionality
  - Create template file upload/edit interface
  - Implement template set selection for sites
  - Add template validation (check required files exist)
  - _Requirements: 1.2, 7.4, 7.5_

- [ ]* 23.1 Write property test for template cloning
  - **Property 19: Template Set cloning completeness**
  - **Validates: Requirements 7.5**

- [ ] 24. Implement site cloning service
  - Create SiteCloningService with database cloning method
  - Implement staging folder cloning method
  - Add file copying from staging to new site directory
  - Create page records from cloned HTML files
  - Implement media file copying
  - Add validation for staging path existence
  - _Requirements: 1.4, 21.1, 21.2, 21.3, 21.4, 21.5_

- [ ]* 24.1 Write property test for staging cloning
  - **Property: Staging clone preserves all files**
  - **Validates: Requirements 21.2, 21.4**

- [ ] 25. Create site management API endpoints
  - Create SiteController with index, store, update, destroy methods
  - Implement site listing with status, domain, last generation date
  - Add site creation endpoint with validation and SFTP configuration
  - Implement site cloning endpoint (database clone)
  - Add site cloning from staging endpoint
  - Create site deletion with cascade
  - Create site generation trigger endpoint
  - Implement site deployment trigger endpoint
  - Add SFTP connection test endpoint
  - Add proper authorization checks
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 21.1, 21.2, 22.1, 22.3, 22.4_

- [ ] 25. Create page management API endpoints
  - Create PageController with CRUD operations
  - Implement page creation with SEO fields validation
  - Add page update endpoint with full content editability
  - Create page deletion endpoint
  - Implement slug uniqueness validation
  - Add page listing with filtering by site and status
  - Create preview endpoint
  - Ensure all meta fields are editable (title, meta_title, meta_description, meta_keywords, canonical, og_data, json_ld)
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 13.1, 13.2, 20.1, 20.4, 20.5_

- [ ] 26. Create section management API endpoints
  - Create SectionController with CRUD operations
  - Implement section creation with type validation
  - Add section update endpoint with full content editability
  - Create section deletion with reordering
  - Implement section reordering endpoint
  - Add section type-specific validation
  - Ensure all section content is editable from admin panel
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 20.2_

- [ ] 27. Create media management API endpoints
  - Create MediaController with upload, delete, resize methods
  - Implement file upload endpoint with validation
  - Add metadata extraction on upload
  - Create WebP generation trigger
  - Implement image resize endpoint
  - Add media listing by site
  - Create media deletion endpoint
  - Implement media metadata editing (alt, title)
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 20.3_

- [ ] 28. Create AI content processing endpoint
  - Create AiContentController with process method
  - Implement Markdown file upload endpoint
  - Add AI processing trigger
  - Create section generation from AI response
  - Implement metadata auto-fill
  - Add progress tracking for AI processing
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 29. Create deployment management endpoints
  - Create DeploymentController with deploy, history, status methods
  - Implement deployment trigger endpoint
  - Add deployment history listing
  - Create deployment status check endpoint
  - Implement deployment log retrieval
  - Add rollback endpoint
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 17.3, 17.5_

- [ ] 30. Create monitoring dashboard API
  - Create DashboardController with metrics methods
  - Implement production server status endpoint
  - Add deployment history summary
  - Create disk space monitoring endpoint
  - Implement queue status endpoint
  - Add generation statistics endpoint
  - _Requirements: 17.1, 17.2, 17.3_

- [ ]* 30.1 Write property test for dashboard data
  - **Property 35: Dashboard shows all servers**
  - **Validates: Requirements 17.1**

- [ ]* 30.2 Write property test for deployment history
  - **Property 36: Deployment history completeness**
  - **Validates: Requirements 17.3**

- [ ] 31. Create admin UI with Vue.js/React and Tailwind CSS v4.0.7
  - Set up frontend build system (Vite) with Tailwind CSS v4.0.7
  - Configure Tailwind with custom theme and dark mode support
  - Create theme switcher component with localStorage persistence
  - Implement CSS variables for light and dark themes
  - Create site management interface (list, create, edit, delete, clone, clone from staging)
  - Implement remote server configuration form for each site
  - Create page editor with full SEO fields editability
  - Implement section builder with drag-and-drop reordering
  - Create media library with upload, editing (alt, title), and management
  - Implement template set selector and editor
  - Add preview modal/iframe
  - Create deployment interface with status tracking
  - Implement dashboard with server status and metrics
  - Add audit log viewer
  - Ensure all content is editable from admin panel
  - _Requirements: All UI-related, 18.1, 18.2, 18.3, 18.4, 18.5, 19.1, 19.2, 19.3, 19.4, 19.5, 20.1, 20.2, 20.3, 20.4, 20.5, 21.1, 22.1, 22.3_

- [ ] 32. Implement notification system
  - Configure notification channels (database, email, Slack)
  - Create deployment success/failure notifications
  - Implement generation completion notifications
  - Add error alert notifications
  - Create notification preferences per user
  - Implement real-time notifications with WebSockets (optional)
  - _Requirements: 14.4, 17.4_

- [ ] 33. Create site:generate artisan command
  - Create site:generate command with site_id argument
  - Implement HTML generation for all pages
  - Add sitemap and robots.txt generation
  - Create progress output
  - Implement error handling and reporting
  - Add option for specific page generation
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [ ] 38. Implement AI agent configuration management
  - Create AiAgentConfig model and migration with encrypted api_key field
  - Implement AiAgentConfigRepository with CRUD operations
  - Create AiAgentService with generateBladeTemplate, validateAccess, saveGeneratedFile methods
  - Add API key encryption using Laravel encryption
  - Implement access control validation for paths and sites
  - Create audit logging for AI agent operations
  - Add support for multiple AI providers (OpenAI, Anthropic)
  - _Requirements: 23.1, 23.2, 23.3, 23.5, 23.7_

- [ ]* 38.1 Write property test for AI agent access control
  - **Property 37: AI agent access control**
  - **Validates: Requirements 23.3, 23.7**

- [ ]* 38.2 Write property test for API key encryption
  - **Property 38: AI agent API key encryption**
  - **Validates: Requirements 23.2**

- [ ]* 38.3 Write property test for AI agent audit logging
  - **Property 39: AI agent audit logging**
  - **Validates: Requirements 23.5**

- [ ] 39. Implement AI agent Blade template generation
  - Create AiClient wrapper for OpenAI and Anthropic APIs
  - Implement prompt construction with MD template context
  - Add response parsing to extract Blade code
  - Create model switching functionality
  - Implement session context preservation
  - Add error handling for API failures
  - Create rate limiting for AI requests
  - _Requirements: 23.4, 23.6_

- [ ] 40. Create AI agent API endpoints
  - Create AiAgentController with CRUD operations for configs
  - Implement config listing endpoint with user filtering
  - Add config creation endpoint with API key encryption
  - Create config update endpoint
  - Implement Blade generation endpoint with prompt and MD template
  - Add model switching endpoint
  - Create access validation middleware
  - _Requirements: 23.1, 23.2, 23.4, 23.6_

- [ ] 41. Create AI agent admin UI
  - Create AiAgentConfig.vue component for settings page
  - Implement API provider selection (OpenAI, Anthropic, Other)
  - Add API key input with secure handling
  - Create model selector dropdown
  - Implement allowed paths configuration interface
  - Add allowed sites multi-select
  - Create AiAgentGenerator.vue for template generation
  - Implement ModelSwitcher.vue for quick model switching
  - Add AccessControl.vue for permissions management
  - _Requirements: 23.1, 23.2, 23.3, 23.6_

- [ ] 42. Implement site redirect management
  - Create SiteRedirect model and migration
  - Implement SiteRedirectRepository with CRUD operations
  - Add redirect validation (valid paths, status codes)
  - Create redirect listing by site
  - Implement redirect activation/deactivation
  - Add duplicate source path detection
  - _Requirements: 24.3, 24.8_

- [ ]* 42.1 Write property test for redirect persistence
  - **Property 41: Redirect rule persistence**
  - **Validates: Requirements 24.3**

- [ ] 43. Implement Nginx configuration service
  - Create NginxConfigService with generateConfig method
  - Implement Nginx server block generation
  - Add redirect rules generation (rewrite/return directives)
  - Create SSL configuration inclusion
  - Implement config backup before update
  - Add remote config update via SSH
  - Create nginx -t validation before reload
  - Implement nginx reload via SSH
  - Add rollback mechanism on failure
  - _Requirements: 24.4, 24.5, 24.6, 24.7_

- [ ]* 43.1 Write property test for Nginx config generation
  - **Property 42: Nginx config generation includes redirects**
  - **Validates: Requirements 24.4**

- [ ]* 43.2 Write property test for Nginx reload rollback
  - **Property 43: Nginx reload rollback on failure**
  - **Validates: Requirements 24.7**

- [ ]* 43.3 Write property test for SSH command logging
  - **Property 44: SSH command execution logging**
  - **Validates: Requirements 24.5, 24.6**

- [ ] 44. Implement SSH client for remote operations
  - Create SshClient wrapper with connection pooling
  - Implement command execution with output capture
  - Add connection timeout handling
  - Create retry logic for failed connections
  - Implement command logging to deployment log
  - Add exit code validation
  - Create connection status checker
  - _Requirements: 24.5, 24.6_

- [ ] 45. Implement bulk site editing service
  - Create BulkEditService with bulkUpdateSites method
  - Implement transaction-based bulk updates
  - Add validation for bulk operations
  - Create rollback mechanism on partial failure
  - Implement bulkUpdateRedirects method
  - Add site grouping by affected changes
  - Create applyNginxChanges method for multiple sites
  - Implement progress tracking for bulk operations
  - _Requirements: 24.1, 24.2, 24.4, 24.5_

- [ ]* 45.1 Write property test for bulk update atomicity
  - **Property 40: Bulk site update atomicity**
  - **Validates: Requirements 24.2**

- [ ] 46. Create bulk edit API endpoints
  - Create BulkEditController with bulk operations
  - Implement bulk site update endpoint with site selection
  - Add redirect listing endpoint per site
  - Create redirect CRUD endpoints
  - Implement Nginx config apply endpoint
  - Add validation for bulk operations
  - Create progress tracking endpoint
  - _Requirements: 24.1, 24.2, 24.3, 24.8_

- [ ] 47. Create bulk edit admin UI
  - Create BulkEditSites.vue component for site selection
  - Implement multi-select site list with checkboxes
  - Add bulk text content editing form
  - Create RedirectList.vue for site redirects
  - Implement RedirectForm.vue for redirect CRUD
  - Add NginxConfigPreview.vue for config preview
  - Create bulk operation progress indicator
  - Implement error handling and rollback notification
  - Add success/failure summary display
  - _Requirements: 24.1, 24.2, 24.3, 24.8_

- [ ] 34. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 35. Write comprehensive documentation
  - Create installation guide for Admin Server
  - Write Production Server setup guide
  - Document API endpoints with examples
  - Create user manual for admin panel
  - Write template development guide
  - Document deployment process
  - Add troubleshooting section
  - Document AI agent configuration and usage
  - Add bulk editing guide
  - Document Nginx redirect management
  - _Requirements: All_

- [ ] 36. Configure production environment
  - Set up production .env file
  - Configure database credentials
  - Set up SFTP credentials for production servers
  - Configure queue workers in Supervisor
  - Set up SSL certificates with Certbot
  - Configure Nginx virtual hosts
  - Set up log rotation
  - Configure backup scripts
  - Set up SSH keys for remote operations
  - _Requirements: All infrastructure_

- [ ] 37. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
