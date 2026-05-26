# Permissions and Users

## Current Architecture

- Areas and base permissions are defined in `app/config/app/permission.php`.
- That file uses the builder API:
  - `Permissions::reset()`
  - `Area::make(...)`
  - `Permission::make(...)`
  - `Permissions::area(...)->route(...)->function(...)`
- After base definition, the core merges module permissions with `Module\Registry::mergePermissions(...)`.
- After the module merge, the site may extend or customize the registry through `custom/config/permissions.php`.
- The final exported structure is assigned to `$PERMITS = Permissions::toArray();`.
- Shared user management is centered in:
  - `class/App/Resources/Support/UserManagementResource.php`
  - `class/App/Resources/User/BackendUserResource.php`
  - `class/App/Resources/User/ApiUserResource.php`
- These shared resources define form fields, table layout, custom backend routes, authority filtering, and create/edit flow.
- Backend and API user resources specialize the shared base through:
  - `managedArea()`
  - `permissionsFunction()`
  - navigation metadata

## What This Means for Site Work

- Treat backend and API user management as centralized through the shared resource flow.
- Do not build parallel custom user-management pages unless there is a strong framework-level reason.
- When adding a new backend role such as `segretaria`, first register it in the builder-based permission registry, not by inventing ad hoc arrays elsewhere.

## Permission Sources

- Resource route access is controlled by each resource `permissionSchema()`.
- Runtime authority metadata is exported from the builder registry into `$PERMITS`, then resolved through:
  - `permissions()`
  - `permissionsBackend()`
  - `permissionsApi()`
- In a site, custom permission additions should enter through `custom/config/permissions.php` by extending the builder registry that started in `app/config/app/permission.php`.

## Area and Permission Definition Flow

1. Define or confirm the target area in `app/config/app/permission.php` with `Area::make('<area>')`.
2. Register the permission with `Permission::make('<key>', '<area>')`.
3. Add metadata on the permission:
   - `name(...)`
   - `icon(...)`
   - `bg(...)`
   - `tx(...)`
   - `color(...)`
   - `creator([...])`
4. Add area-level routes and callbacks with `Permissions::area('<area>')->route(...)->function(...)` when the area needs shared links or behavior.
5. Let module permissions merge.
6. Apply project-specific additions or overrides in `custom/config/permissions.php`.
7. Reference the permission key from resource `permissionSchema()` and navigation authority gates.

## Practical Rule

When asked to create a new backend permission:

1. Add or update the authority definition in the builder registry rooted at `app/config/app/permission.php`.
2. If the permission is project-specific, extend it from `custom/config/permissions.php` after the base and module definitions are loaded.
3. Use that authority key in the relevant resource `permissionSchema()`.
4. If the navigation entry is restricted, update the resource `navigationSchema()->authority([...])` or equivalent gate.
5. Validate that the shared user-management backend can assign that new authority.

## Risk Notes

- The architecture is only partially migrated: user CRUD is centralized, while runtime permission lookup still passes through helper functions that read the built `$PERMITS` array.
- If a task requires redesigning the permission-definition system itself, that is a framework-level change, not only a site-level change.
