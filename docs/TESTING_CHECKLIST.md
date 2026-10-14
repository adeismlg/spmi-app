# Testing Checklist - Lab Audit & Admin Impersonation

## Status Sistem

Berikut fitur/bug yang sudah diperbaiki dalam session ini:

### ✅ Fixed Issues

1. **Audit Reporting 500 Error**
   - Issue: "Cannot use object of type stdClass as array"
   - Fix: Changed array accessor notation to arrow notation in AuditReportingService
   - Test: http://spmi.ai-tekno.my.id/audit-reporting should load without 500 error

2. **Lab Audit Support (Laboratorium Unit Type)**
   - Issue: System lacked explicit differentiation between PRODI and LAB
   - Fix: Added Laboratorium case to UnitType enum with dashboard separation
   - Test: Create unit with type "Laboratorium", verify separate reporting

3. **Admin Impersonation (Login As)**
   - Issue: Admin needed way to test as other users
   - Fix: Created ImpersonationController with session-based state tracking
   - Test: Users page → Login As button → perform actions → Back to Admin button

4. **Users List 500 Error**
   - Issue: Undefined $user variable in confirm dialog
   - Fix: Made confirm message dynamic Closure
   - Test: http://spmi.ai-tekno.my.id/users loads without error

5. **403 Error on Stop Impersonating**
   - Issue: Stop route was inside admin-only middleware
   - Fix: Moved stop route outside admin group to auth middleware
   - Test: Login As User → perform actions → Back to Admin button works

6. **Evaluation Submission During Evaluasi Phase**
   - Issue: "Periode pengisian normal sedang tidak dibuka" after admin changes to Evaluasi
   - Fix: Modified SemesterWorkflow to allow submission in both Pelaksanaan AND Evaluasi phases
   - Test: See EVALUATION_WORKFLOW.md

## Testing Priority

### Phase 1: Core Workflow (Do First)

```bash
# 1. Setup Test Data
- Create cycle for 2026 (or current year)
- Create 2-3 units (mix of PRODI and LABORATORIUM types)
- Create semester windows with dates in past (so they're "open now"):
  - Pengisian: 1 Oct 2024 - 31 May 2025
  - Pemeriksaan: 1 June 2025 - 30 Sept 2025
- Assign standards to units
- Create admin user and auditee users

# 2. Test Normal Workflow (Pelaksanaan Phase)
✓ Admin login → Siklus → Set tahap to Pelaksanaan
✓ Impersonate Auditee1 (Login As)
✓ Access Evaluasi Diri → Select unit/semester
✓ Fill evaluation form → Save Draft → Save Permanen (submit)
✓ Verify status changes to "Submitted"
✓ Back to Admin

# 3. Test Evaluasi Phase Workflow
✓ Admin → Siklus → Change tahap to Evaluasi
✓ Impersonate Auditee1 (Login As)
✓ Access Evaluasi Diri (same evaluation)
✓ Verify: Form is STILL EDITABLE (THIS IS THE FIX)
✓ Make changes → Save Permanen
✓ Back to Admin

# 4. Test Audit Reporting
✓ Admin → Audit Reporting
✓ See aggregated data by unit type (PRODI vs LABORATORIUM)
✓ Verify Lab audits shown separately from PRODI
```

### Phase 2: Edge Cases & Features

```bash
# 5. Test Reopen Request Workflow
✓ Admin → Change tahap to Evaluasi
✓ Admin → Pengaturan → Permintaan Pembukaan
✓ (Wait for auditee to submit reopen request, or create manually if admin can)
✓ Verify reopen request appears with Pending status
✓ Click Approve
✓ Verify request status → Approved
✓ Impersonate Auditee → Evaluasi Diri → Form editable

# 6. Test Access Control
✓ Non-admin cannot see Users page
✓ Non-auditee cannot see Evaluasi Diri
✓ Impersonated user cannot access admin pages
✓ 403 errors work correctly (no 500 errors)
```

## Bug Hunting Tips

### If you see errors:

**500 Error:**
- Check browser console for errors
- Check app logs: `php artisan log:tail` or check `/storage/logs/`
- Use `laravel-boost-last-error` if available

**403 Forbidden (when shouldn't be):**
- Verify user has correct role
- If impersonating, verify user isn't trying to access admin page
- Check middleware ordering in bootstrap/app.php

**Data not showing:**
- Verify semester window dates include "now()"
- Verify assignment exists for user/unit/indicator
- Check if role filters exclude the resource

### Performance checks:

- Audit Reporting page should load in <2 seconds
- Users list should paginate (20 per page)
- No N+1 queries (use `\DB::enableQueryLog()` in tinker if concerned)

## Quick Database Checks

```bash
# Check active cycle and phase
php artisan tinker
>>> Cycle::active()->first() // should show tahap_aktif
>>> SemesterWindow::where('cycle_id', 1)->first() // should show window dates

# Check users and roles
>>> User::with('roles')->get()

# Check evaluations
>>> SelfEvaluation::where('unit_id', 1)->where('semester', 1)->count()

# Check reopen requests
>>> ReopenRequest::with('selfEvaluation', 'requester')->get()
```

## Success Criteria

- [ ] Audit reporting page loads without 500 error
- [ ] Laboratorium type visible in unit dropdown
- [ ] Lab audits shown separately in dashboard
- [ ] Admin can Login As user
- [ ] Admin can go Back to Admin without 403
- [ ] Users list loads without error
- [ ] Auditee can submit evaluation in Pelaksanaan phase
- [ ] Auditee can STILL edit evaluation in Evaluasi phase (if within pengisian window)
- [ ] Can request reopen and admin can approve
- [ ] All of above works via impersonation flow

---

**Documentation Files:**
- [ADMIN_IMPERSONATION.md](./ADMIN_IMPERSONATION.md) - Feature guide
- [EVALUATION_WORKFLOW.md](./EVALUATION_WORKFLOW.md) - Workflow details
- [TESTING_CHECKLIST.md](./TESTING_CHECKLIST.md) - This file
