# SaaS Roadmap Plan — Workload Platform

## Summary
Target produk: **B2B multi-tenant SaaS**.  
Prioritas V1: **Tenant + RBAC + Audit**.  
Billing: **Phase 2**.

## Phase 1 (V1): Tenant + RBAC + Audit
- Tambah domain multi-tenant:
  - `tenants`, `tenant_users`, `audit_logs`
  - `tenant_id` di `projects`, `tasks`, `issues`, `staff`, `roles`, `report_histories`
- Terapkan tenant scoping global:
  - Query otomatis terfilter `tenant_id`
  - Guard create/update/delete agar tidak lintas tenant
- RBAC per tenant:
  - role: `owner`, `admin`, `manager`, `viewer`
  - policy Filament untuk resource/page/action
- Audit trail:
  - log actor, tenant, action, resource, timestamp, payload ringkas
  - cover action sensitif (status/progress, report generate, delete history)
- Security baseline:
  - rate limit endpoint report
  - hardening akses file report by tenant
  - validasi enum/status/priority/date
- Operasional:
  - set timezone `Asia/Jakarta`
  - pindah maintenance overdue ke scheduled job (hindari update massal saat listing)

## Phase 2: Billing & Subscription
- Tambah `plans`, `subscriptions`, `tenant_usage_daily`, `invoices`
- Enforce limit plan:
  - max staff/project/report render/retention
- Billing UI untuk tenant owner:
  - paket, usage, status, histori invoice
- Grace period + read-only mode saat subscription bermasalah

## Phase 3: Automation Engine
- Rule-based trigger:
  - due date mendekat, overdue, stagnasi progress, severity issue naik
- Action:
  - reminder, auto-assign, auto-status, escalation
- Channel:
  - in-app + email digest
- SLA:
  - response/resolution target + breach indicator

## Phase 4: Client Portal (Opsional)
- Portal read-only per client/project
- Shared report link dengan expiry + watermark
- Approval/comment loop untuk milestone tertentu

## Interface / Contract Changes
- `TenantContext` resolver service
- `AuditLogger` service contract
- Policy contract tenant-aware untuk semua resource
- Semua entitas inti wajib punya `tenant_id` + index tenant-aware

## Test Plan
- Isolation: tenant A tidak bisa akses tenant B (UI + route langsung)
- Authorization: permission matrix sesuai role
- Audit: aksi sensitif tercatat lengkap
- Integrity: auto inject `tenant_id`, no orphan cross-tenant
- Performance: dashboard/report tetap responsif dengan index tenant-aware
- Timezone: timestamp sesuai `Asia/Jakarta`
- Migration: backfill `tenant_id` aman, idempotent, rollback-ready

## Assumptions
- Product direction: B2B multi-tenant
- V1 focus: Tenant + RBAC + Audit
- Billing ditunda ke Phase 2
- `admin` existing akan dipisah jadi platform super-admin vs tenant admin
