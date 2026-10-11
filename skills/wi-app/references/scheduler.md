# Wonder scheduler

Scheduler and logs share the `dev` backend section. Preserve each resource's
permissions. Backend jobs support PHP scripts with separate argv entries and
HTTPS GET/POST with named parameters via ConfiguredTask and the existing worker.
Never forward the system token automatically. Code-origin schedules are protected
from deletion/conversion; backend-created schedules may be removed without logs.
Use Bootstrap grids and DataTables, keeping metric cards inside grid columns.
The dashboard counts sent mail and successful login/remember events, excluding
duplicate federated-login events. External memory/CPU remain unavailable.

Core lives in `class/App/Scheduler`. Extend `AbstractTask`, implement
`Contracts/TaskInterface`, or use the `Task::make(key, callback)` builder.
Callbacks receive `Context` and return arrays; never invoke an HTTP endpoint
or call `exit`. Reuse application services across API and scheduled tasks.

The existing `Api\Handler::run()` pattern (e.g. backend/alert.php) remains the
HTTP boundary. Scheduler HTTP endpoints authenticate `api_internal_user` and
require the `@system` username; they enqueue requests and return 202. CLI
entrypoints reject HTTP and resolve the enabled system identity internally.
Never put the system token on the cPanel command line.

Sites return tasks from `custom/config/tasks.php`; modules implement optional
`ModuleTasks::tasks()`. Precedence is core, modules, site. Stable keys support
site overrides; duplicate module keys are errors. `Task::parameters()` validates
parameters, `withDefaults()` supplies initial parameters, and `active()` opts
into initial activation. Custom tasks default to suspended.

`forge build` and `forge update` generate site `bin/scheduler.php`; the core
adds `/bin/scheduler.php` to the site's `.gitignore`. Ignore only this generated
file, not custom site scripts or the framework's source `bin/` directory.
Deploy must generate the entrypoint via build or server update.
The framework
contains no site forge executable. One server cron invokes that file every
minute. Use `forge schedule:run` for the equivalent site CLI entrypoint.
`proc_open` is required. Reuse `Support\NamedLock` for tick and task locks.
Workers are sequential isolated processes with bounded output and timeouts.
Do not silently bypass isolation if the hosting disables process creation.

Models/Resources own schedules and run logs; `SchedulerPageSchema` describes
dashboard inputs. Use the existing FormField and Bootstrap Element renderers.
Default insertion is idempotent and does not reset saved schedules or recreate
removed defaults. Disabled modules and soft-deleted schedules must not execute.
History retention is 180 days for every task, with bounded cleanup. Null
metrics mean unavailable; PHP peak memory is not RSS or hosting resource use.

Sitemap is the first default, wrapping the existing licensed crawler without
modifying its code. The legacy site `api/task/sitemap.php` is removed by build;
migrate to the generated CLI entrypoint. Euribor reuses `Euribor::sync()` only
where the site provides it.

`Tasks\GooglePlacesTask` (`wonder.google-places`, off by default, 03:30) syncs
every `SocietyLocation` with its own `google_place_id` through Places API (New)
Place Details and the server key `GCP_API_KEY` (header, never URL). Logic lives
in `Support/GooglePlaces`: pure `PlaceMapper`, `PlaceDiff`, `PlaceSyncPlan`,
`GooglePlaceData`; IO behind `PlaceDetailsClient` and `GooglePlacesStore`, so
tests inject fakes and never call Google. Parameters: `apply_hours` (false =
store differences only) and `language`. Never apply the business status; a
manual special-hours row beats Google's on the same date. Snapshots go to
`society_location_google`, at most five reviews to `society_location_reviews`
(stale ones deleted, everything cleared when the Place ID goes away).
`SocietyLocations` exposes `$location->google` / `$location->reviews`; sites
render them with `GoogleReviews::fromLocation()`, whose Google attributions
are mandatory. Places returns at most five reviews, not the full archive
(that needs Business Profile with OAuth). Storing reviews conflicts with the
Places caching terms (only Place ID may be stored): document it as the site's
choice, never as compliant. Do not activate fake placeholder jobs.

Validate PHP lint, `tests/scheduler.php`, DB/process integration in a disposable
site, and normal site `forge update --local` / `forge start`. Update the core
GitBook cron-job page and AGENTS together with changes to this contract.

Documentation routing: `docs/app/piattaforma/cron-job.md` explains adding,
editing and removing definitions in code, including saved-schedule semantics
and fallback after removing an override. The quick-start guide documents the
single cPanel command and `* * * * *` frequency immediately after GitHub Desktop
setup. Do not document `schedule()` or `active()` as updating existing DB rows.
