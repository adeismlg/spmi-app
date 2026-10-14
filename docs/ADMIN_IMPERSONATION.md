# Admin Impersonation Feature (Login As)

## Overview

The Admin Impersonation feature (often called "Login As" or "Sudo Mode") allows administrators with the `admin_spmi` role to temporarily login as another user for testing, troubleshooting, and task completion purposes.

## Features

- **Seamless User Switching**: Admin can login as any non-admin user without knowing their password
- **Session Preservation**: Original admin identity is stored in the session for easy restoration
- **Visual Feedback**: Clear indicator in the top navigation showing impersonation status
- **One-Click Return**: "Kembali ke Admin" button to instantly return to admin account
- **Security Safeguards**: 
  - Cannot impersonate other admins
  - Cannot impersonate users without email
  - Cannot impersonate yourself
  - Full role-based access scoping applies to impersonated user

## Architecture

### Components

1. **ImpersonationController** (`app/Http/Controllers/ImpersonationController.php`)
   - `impersonate(Request $request, User $user)`: Start impersonating a user
   - `stopImpersonating(Request $request)`: Return to admin account

2. **DetectImpersonation Middleware** (`app/Http/Middleware/DetectImpersonation.php`)
   - Sets `isImpersonating` and `originalAdminId` on request for use in controllers/views
   - Global middleware (registered in `bootstrap/app.php`)

3. **User Model Helper** (`app/Models/User.php`)
   - `canBeImpersonated()`: Returns true if user is eligible for impersonation

4. **UI Components**
   - Top navigation: Masquerade mode badge and "Kembali ke Admin" button
   - Users list: "Masquerade" action button for eligible users
   - Breadcrumb: Shows current route information

5. **Routes** (`routes/web.php`)
   - `POST /users/{user}/impersonate` → `users.impersonate` (admin only)
   - `POST /impersonate/stop` → `impersonate.stop` (when impersonating)

### Session Storage

When impersonating, the session stores:
```php
[
    'impersonated_by' => (int) $originalAdminId,    // Admin's user ID
    'impersonated_user' => (int) $targetUserId,     // Impersonated user's ID
]
```

This allows:
- Bi-directional tracking (who is impersonating whom)
- Easy restoration (fetch original user and re-login)
- Detection in middleware/views via `session('impersonated_by')`

## Usage

### Starting Impersonation

1. Navigate to **Pengaturan → Pengguna** (Users list)
2. Find the user you want to impersonate
3. Click the **Masquerade** button (yellow warning button with icon)
4. Confirm the impersonation in the dialog

**Result**: You are logged in as that user. Dashboard shows their scoped data/units.

### Completing Tasks as Impersonated User

Once impersonating:
- All dashboard data is filtered to the impersonated user's role/unit
- You can complete evaluations, audits, corrective actions, etc., as if you were that user
- All activities are logged against the impersonated user (not the admin)
- Role-based access control applies to the impersonated user's role

### Returning to Admin Account

1. Look at the top-right corner of the navigation header
2. You'll see:
   - A yellow "Masquerade Mode" badge
   - A "Kembali ke Admin" button
3. Click the button to return to your admin account

**Result**: Session returns to admin user, impersonation flags are cleared.

## Security Considerations

### What Is Protected

✅ **Cannot impersonate admins** - Only users without `admin_spmi` role can be impersonated
✅ **Cannot impersonate yourself** - Admin cannot masquerade as themselves
✅ **Cannot impersonate users without email** - Ensures valid user selection
✅ **Role scoping preserved** - Impersonated user's role/unit restrictions apply
✅ **Audit trail potential** - Session stores original_admin_id for logging

### What Is Not Implemented (Consider for Future)

⚠️ **Impersonation audit logging** - Currently no permanent log of who impersonated whom and when
⚠️ **Impersonation time limits** - Uses default Laravel session timeout (no special limits)
⚠️ **Concurrent impersonation limits** - One admin can have multiple tabs impersonating different users
⚠️ **User notification** - Impersonated user is not notified they are being impersonated

### Recommendations

For production environments, consider implementing:

1. **Audit Logging**: Log impersonation events to track compliance
   ```php
   Log::channel('impersonation')->info(
       "Admin {$adminId} impersonated user {$userId}",
       ['admin' => $admin->name, 'user' => $user->name]
   );
   ```

2. **Session Invalidation Warning**: Show warning if user is impersonated in another session
   ```php
   if (session('impersonated_by') && Cache::get("user.{$userId}.impersonating")) {
       // Warning: User is being impersonated in another session
   }
   ```

3. **Time Limit**: Set specific timeout for impersonation sessions
   ```php
   session(['impersonate_expires_at' => now()->addHours(1)]);
   ```

4. **Notification**: Alert original user when their account is accessed via impersonation

## Troubleshooting

### Issue: "Masquerade" button not showing in users list

**Possible Causes**:
- User has `admin_spmi` role (cannot impersonate admins)
- User does not have an email address
- You are not logged in as an admin

**Solution**: Check user's role and email in database, or verify your admin permissions.

### Issue: Unable to access user's scoped data after impersonating

**Possible Cause**: Cached queries or view cache not cleared

**Solution**:
```bash
php artisan view:clear
php artisan cache:clear
php artisan config:clear
```

### Issue: Impersonation session not cleared after logout

**Possible Cause**: Session middleware not running properly

**Solution**: Check that `DetectImpersonation` middleware is registered in `bootstrap/app.php`:
```php
$middleware->append(DetectImpersonation::class);
```

## Code Examples

### Programmatically Check if Request is Impersonated

```php
// In controller
if ($request->attributes->get('isImpersonating')) {
    $originalAdminId = $request->attributes->get('originalAdminId');
    // Handle impersonation-specific logic
}

// In view
@if (session('impersonated_by'))
    You are impersonating user #{{ session('impersonated_user') }}
@endif
```

### Scope Queries to Impersonated User

The DetectImpersonation middleware sets request attributes, so in your controllers:

```php
public function index(Request $request)
{
    $user = $request->user(); // This is already the impersonated user
    $units = $user->seesAllUnits() 
        ? Unit::all() 
        : $user->unit()->get();
}
```

### Log Impersonation Events (Future Implementation)

```php
// In ImpersonationController@impersonate
Log::channel('security')->info('User impersonation started', [
    'admin_id' => $adminUser->id,
    'admin_name' => $adminUser->name,
    'user_id' => $user->id,
    'user_name' => $user->name,
    'ip' => $request->ip(),
    'timestamp' => now(),
]);
```

## Files Modified/Created

### Created
- `app/Http/Controllers/ImpersonationController.php` - Impersonation logic
- `app/Http/Middleware/DetectImpersonation.php` - Middleware to detect impersonation state

### Modified
- `app/Models/User.php` - Added `canBeImpersonated()` helper method
- `app/Http/Controllers/UserController.php` - Added `rowActions()` with masquerade button
- `routes/web.php` - Added impersonation routes
- `bootstrap/app.php` - Registered DetectImpersonation middleware
- `resources/views/layouts/app.blade.php` - Added impersonation status banner and return button

## Testing Checklist

- [ ] Login as admin_spmi user
- [ ] Navigate to Users list
- [ ] Verify "Masquerade" button appears only for non-admin users
- [ ] Click "Masquerade" button
- [ ] Confirm impersonation dialog
- [ ] Verify dashboard shows impersonated user's scoped data
- [ ] Check header shows "Masquerade Mode" badge and "Kembali ke Admin" button
- [ ] Create/edit/delete records as impersonated user
- [ ] Click "Kembali ke Admin" button
- [ ] Verify return to admin account
- [ ] Verify session is cleared
- [ ] Check impersonate button is not available for:
  - Other admin users
  - Users without email addresses
  - Current admin user (yourself)

## Related Features

- **Role-Based Access Control**: `Spatie\Permission\Models\HasRoles` trait
- **Unit Hierarchy**: `Unit::parent_id` for organizational structure
- **Session Management**: Laravel's built-in session handling via `session()` helper
- **Authentication**: `Auth::login()` for programmatic login without password

## Changelog

### Version 1.0.0 (Current)
- Initial release with basic impersonation support
- Session-based impersonation tracking
- Top navigation status badge and return button
- User list action button integration
- Security safeguards (no admin-to-admin impersonation)

### Future Enhancements
- Impersonation audit logging
- Time-limited impersonation sessions
- Concurrent impersonation detection
- User notification system
- Impersonation activity history dashboard
