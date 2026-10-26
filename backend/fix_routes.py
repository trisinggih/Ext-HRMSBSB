import os

path = 'd:/Project/HRMS/backend/routes/api.php'
with open(path, 'r', encoding='utf-8') as f:
    c = f.read()

bad_str_1 = "$e->load(['company', 'branch', 'department', 'division', 'position', 'supervisor', 'documents', 'educations', 'trainings']);"
good_str_1 = "$e->load(['primaryAssignment.company', 'primaryAssignment.branch', 'primaryAssignment.department', 'primaryAssignment.division', 'primaryAssignment.position', 'documents', 'educations', 'trainings']);"

bad_str_2 = """                'position' => $e->position?->name,
                'division' => $e->division?->name,
                'department' => $e->department?->name,
                'branch_id' => $e->branch_id,
                'branch' => $e->branch?->name,"""

good_str_2 = """                'position' => $e->primaryAssignment?->position?->name,
                'division' => $e->primaryAssignment?->division?->name,
                'department' => $e->primaryAssignment?->department?->name,
                'branch_id' => $e->primaryAssignment?->branch_id,
                'branch' => $e->primaryAssignment?->branch?->name,"""

c = c.replace(bad_str_1, good_str_1)
c = c.replace(bad_str_2, good_str_2)

with open(path, 'w', encoding='utf-8') as f:
    f.write(c)

print('routes/api.php fixed')
