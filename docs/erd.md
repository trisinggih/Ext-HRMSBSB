# ERD — Ext-HRMS (Mermaid)

```mermaid
erDiagram
    admins ||--o{ admin_role : has
    roles ||--o{ admin_role : has
    roles ||--o{ role_permission : has
    permissions ||--o{ role_permission : has

    companies ||--o{ branches : has
    branches ||--o{ departments : has
    departments ||--o{ divisions : has
    divisions ||--o{ positions : has
    branches ||--o{ positions : has
    positions ||--o{ employees : has
    departments ||--o{ employees : has
    divisions ||--o{ employees : has
    branches ||--o{ employees : has

    employees ||--o{ employee_families : has
    employees ||--o{ employee_documents : has
    employees ||--o{ employee_faces : has
    employees ||--o{ employee_devices : has
    employees ||--o{ attendance : records
    employees ||--o{ employee_locations : has
    employees ||--o{ leave_requests : has
    employees ||--o{ permission_requests : has
    employees ||--o{ sick_leave_requests : has
    employees ||--o{ late_requests : has
    employees ||--o{ tasks : assigns
    employees ||--o{ rewards : receives
    employees ||--o{ employee_warnings : gets
    employees ||--o{ employee_terminations : has
    employees ||--o{ employee_payroll_settings : has
    employees ||--o{ payroll_details : gets

    shifts ||--o{ employee_shifts : assigned
    employee_shifts ||--o{ shift_swap_requests : swap
    employee_shifts ||--o{ workday_swap_requests : swap

    leave_types ||--o{ leave_requests : type
    leave_types ||--o{ leave_balances : balance

    branches ||--o{ attendance : location

    approval_workflows ||--o{ approval_steps : has
    approval_requests ||--o{ approval_logs : logs
    approval_steps ||--o{ approval_logs : step

    payroll_periods ||--o{ payroll_runs : has
    payroll_runs ||--o{ payroll_details : detail
    payroll_components ||--o{ payroll_details : component

    tax_rules ||--o{ tax_brackets : has
    tax_employee_settings ||--o{ employees : applies

    tasks ||--o{ task_assignments : has
    tasks ||--o{ task_comments : has

    employee_terminations ||--o{ termination_clearances : checklist

    announcements ||--o{ notifications : notify
    notifications ||--o{ employees : to
```
