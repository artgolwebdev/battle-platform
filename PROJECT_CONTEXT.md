# Project Context

## Stack
- Laravel: 12.62.0
- PHP: 8.2.12
- Key packages:
  - laravel/ui 4.6.3
  - laravel/reverb 1.10.x
  - laravel-echo 2.4.x
  - pusher-js 8.5.x
  - spatie/laravel-permission 6.25.0
  - spatie/laravel-medialibrary 11.23.1
  - spatie/laravel-query-builder 6.4.4
- Frontend approach:
  - Blade templates for server-rendered pages
  - Bootstrap for styling and UI behavior
  - Vanilla JS for interactivity, with Laravel Echo for realtime broadcast subscriptions
  - Vite is used for frontend build/dev assets

## Architecture Rules
- Company scoping is enforced through policies, not by trusting client input.
- The pattern used in CompanyPolicy and EventPolicy is the template for future policies:
  - superadmins bypass company scoping
  - admins may only access resources belonging to their own company
  - the authorization check derives the company from the authenticated user and the target resource, never from a request parameter such as company_id
- Role structure is Spatie-based:
  - superadmin: global platform management
  - admin: company-scoped management for one company via company_id
  - user: basic user role
- Blade forms must use @csrf.
- Vue/axios usage should rely on the existing bootstrap.js setup, which configures Axios and the XSRF token handling automatically.

## Schema
### Core auth / app tables
- users
  - id
  - name
  - email (unique)
  - email_verified_at
  - password
  - remember_token
  - timestamps
  - company_id (nullable foreign key to companies)
- password_reset_tokens
  - email (primary)
  - token
  - created_at
- sessions
  - id (primary)
  - user_id (nullable index)
  - ip_address
  - user_agent
  - payload
  - last_activity
- cache / cache_locks
  - standard Laravel cache tables
- jobs / job_batches / failed_jobs
  - standard Laravel queue tables

### Spatie Permission Tables
- permissions
- roles
- model_has_permissions
- model_has_roles
- role_has_permissions

### Media Library
- media
  - standard Spatie media library columns
  - order_column is present for media ordering

### Domain Tables
- companies
  - id
  - name
  - slug (unique)
  - logo (nullable)
  - owner_admin_id (nullable foreign key to users)
  - status (default active)
  - timestamps
- events
  - id
  - company_id (foreign key to companies, cascade delete)
  - title
  - description (nullable)
  - location (nullable)
  - start_date (nullable datetime)
  - end_date (nullable datetime)
  - programme (nullable json)
  - registration_open (boolean, default true)
  - timestamps
- event_categories
  - id
  - event_id (foreign key to events, cascade delete)
  - name
  - description (nullable)
  - has_prelims (boolean, default false)
  - current_phase (enum: registration, prelims, bracket, complete; default registration)
  - current_prelims_registration_id (nullable foreign key to registrations)
  - timestamps
- registration_fields
  - id
  - event_id (legacy compatibility column retained during the transition)
  - event_category_id (foreign key to event_categories, active owner)
  - field_name
  - field_type
  - required (boolean, default false)
  - options (nullable json)
  - timestamps
- registrations
  - id
  - event_id (foreign key to events, cascade delete)
  - category_id (nullable foreign key to event_categories)
  - name
  - email
  - responses (nullable json)
  - status (default pending; used by the admin review UI)
  - seed (nullable)
  - bracket_position (nullable)
  - order_column (nullable integer used to persist manual prelims order per category)
  - timestamps
- battles
  - id
  - event_id (foreign key to events, cascade delete)
  - category_id (nullable foreign key to event_categories)
  - name
  - status (default active)
  - seed_type (default random)
  - timestamps
- battle_matches
  - id
  - battle_id (foreign key to battles, cascade delete)
  - round (integer)
  - position (integer)
  - registration1_id (nullable foreign key to registrations, null on delete)
  - registration2_id (nullable foreign key to registrations, null on delete)
  - winner_id (nullable foreign key to registrations, null on delete)
  - score1 (nullable integer)
  - score2 (nullable integer)
  - status (default pending)
  - timestamps

### Key Relationships
- User belongsTo Company (company_id)
- Company hasMany Users, hasMany Events
- Event belongsTo Company, hasMany EventCategories, hasMany Registrations, hasMany Battles, hasManyThrough RegistrationFields via EventCategories
- EventCategory belongsTo Event, hasMany RegistrationFields, hasMany Registrations, hasMany Battles, belongsTo currentPrelimsRegistration
- Registration belongsTo Event, belongsTo EventCategory, hasMany battleMatchesAsPlayer1, hasMany battleMatchesAsPlayer2, hasMany wonMatches
- RegistrationField belongsTo EventCategory
- Battle belongsTo Event, belongsTo EventCategory, hasMany matches (BattleMatch)
- BattleMatch belongsTo Battle, belongsTo registration1, belongsTo registration2, belongsTo winner

## Completed Phases
### Phase 1: roles and companies
Built and verified:
- Models: User, Company
- Policies: CompanyPolicy
- Controllers: CompanyController, AdminDashboardController, SuperAdminDashboardController
- Routes: authenticated company management routes plus dashboard routes
- Views: company management pages and dashboards (Blade)
- Seeders: RolesAndPermissionsSeeder, DatabaseSeeder
- Test coverage:
  - tests/Feature/CompanyManagementTest.php
  - Verified results: 4 tests passed, 5 assertions

### Phase 2: events and registration
Built and verified:
- Models: Event, Registration, RegistrationField
- Policies: EventPolicy, RegistrationPolicy
- Controllers: EventController, PublicEventController, RegistrationController
- Routes: public event page, public registration form, authenticated event CRUD routes
- Views: event list/create/edit/show, public event page, public registration form
- Media: Event uses Spatie Media Library with a banner media collection and a thumb conversion
- Test coverage:
  - tests/Feature/EventManagementTest.php
  - tests/Feature/RegistrationFlowTest.php
  - Verified results: 9 tests passed, 20 assertions across the three feature suites

### Phase 3 remainder: admin registration review
Built and verified:
- Controllers: RegistrationReviewController
- Routes: authenticated registration listing and status update routes under events/{event}/registrations
- Views: registration review list with approval state updates
- Test coverage:
  - tests/Feature/RegistrationReviewTest.php
  - Verified results: 1 test passed, 4 assertions

### Phase 4: bracket generation and progression
Built and verified:
- Schema:
  - `battles`: `id`, `event_id`, `name`, `status`, `seed_type`, `timestamps`
  - `battle_matches`: `id`, `battle_id`, `round`, `position`, `registration1_id`, `registration2_id`, `winner_id`, `score1`, `score2`, `status`, `timestamps`
- Models & Relationships:
  - `Battle` belongsTo `Event`, hasMany `BattleMatch`
  - `BattleMatch` belongsTo `Battle`, belongsTo `registration1` (`Registration`), belongsTo `registration2` (`Registration`), belongsTo `winner` (`Registration`)
- Controllers & Scope:
  - `BracketController` handles: `show` (renders bracket UI), `store` (generates bracket ensuring no duplicate active brackets), `updateMatch` (records scores and recursively propagates winners, ensuring scores are locked once subsequent rounds are completed), and `destroy` (resets/deletes bracket)
  - `PublicEventController` eager loads active battle relationships to display read-only brackets publicly (names only, emails hidden)
- Seeding: random (default) or manual (respecting admin-defined seed numbers in registrations view)
- Test coverage:
  - tests/Feature/BracketManagementTest.php
  - Verified results: 22 tests passed, 80 assertions

## Phase 5 Status
### Step 1 and Step 2
Built and verified:
- Registration fields are now category-owned (`registration_fields.event_category_id`) with legacy `event_id` retained for compatibility.
- Category admin UI polish is done, including safer delete behavior and clearer category editing.
- Prelims queue is implemented with manual drag-and-drop ordering, persisted `registrations.order_column`, next/jump actions, and category phase transitions.
- `EventCategory` now tracks `has_prelims`, `current_phase`, and `current_prelims_registration_id`.

### Step 3: realtime broadcasting
Built and verified:
- Laravel Reverb is installed and configured.
- Echo and the Reverb JS client are wired into `resources/js/bootstrap.js`.
- `config/broadcasting.php` now includes the Reverb connection and `.env` / `.env.example` include Reverb variables.
- Two broadcast events exist:
  - `App\Events\PrelimsDancerChanged`
  - `App\Events\BracketMatchUpdated`
- Public and admin Blade views subscribe to Echo channels and update in place.
- Full test suite passed after wiring realtime updates: 36 tests, 145 assertions.

### Step 4: demo seeders and smoke pass
Built and verified:
- Comprehensive demo seeder (`database/seeders/DemoSeeder.php`) creates realistic data:
  - 3 companies (2 approved, 1 pending) to demonstrate approval gate
  - 2 events with multiple categories each
  - Categories mix of solo/crew, with/without prelims
  - 8-16 registrations per category for clean brackets
  - One category partway through prelims phase
  - One bracket with scored matches to demonstrate live features
- Smoke pass test suite (`tests/Feature/SmokePassTest.php`) validates all user journeys:
  - Superadmin: view pending companies, approve/reject
  - Company admin (pending): event/category creation blocked
  - Company admin (approved): create events/categories, manage prelims, score brackets
  - Public visitor: view events, see "now performing", view live brackets, register with category-specific fields
- All smoke pass tests pass (9 tests, 53 assertions)

### Step 4 files added
- `database/seeders/DemoSeeder.php`
- `tests/Feature/SmokePassTest.php`
- `database/seeders/DatabaseSeeder.php` (updated to call DemoSeeder)

### Step 3 files touched
- `.env`
- `.env.example`
- `composer.json`
- `composer.lock`
- `config/broadcasting.php`
- `routes/channels.php`
- `package.json`
- `package-lock.json`
- `resources/js/bootstrap.js`
- `app/Events/PrelimsDancerChanged.php`
- `app/Events/BracketMatchUpdated.php`
- `app/Http/Controllers/PrelimsController.php`
- `app/Http/Controllers/BracketController.php`
- `app/Http/Controllers/PublicEventController.php`
- `resources/views/events/public/show.blade.php`
- `resources/views/events/prelims/show.blade.php`
- `resources/views/events/bracket/show.blade.php`
- `tests/Feature/PrelimsManagementTest.php`
- `tests/Feature/BracketManagementTest.php`
- `PROJECT_CONTEXT.md`

## Known Gaps / Remaining Work
- None. Phase 5 is fully complete.
- `registration_fields.event_id` remains for migration compatibility, but the active ownership model is now `event_category_id`.

## How to Resume
- Local dev commands:
  - `php artisan serve`
  - `php artisan reverb:start`
  - `npm run dev`
- DB connection:
  - `DB_CONNECTION=mysql`
  - `DB_DATABASE=battle_platform`
  - `DB_USERNAME=root`
  - `DB_PASSWORD=`
- Tests:
  - `php artisan test`
  - `php artisan test tests/Feature/CompanyManagementTest.php tests/Feature/EventCategoryManagementTest.php tests/Feature/EventManagementTest.php tests/Feature/PrelimsManagementTest.php tests/Feature/RegistrationFlowTest.php tests/Feature/RegistrationReviewTest.php tests/Feature/BracketManagementTest.php`
- Seeded test accounts:
  - `superadmin@example.com / password` (if the app is seeded via DatabaseSeeder, the seeded superadmin account is created with the superadmin role)
  - `admin@example.com / password` (seeded company admin)
  - `user@example.com / password` (seeded regular user)

## Rule For Future Work
- At the end of every phase, update `PROJECT_CONTEXT.md` before moving to the next phase.
