import os

path = 'd:/Project/HRMS/backend/routes/api.php'
with open(path, 'r', encoding='utf-8') as f:
    c = f.read()

bad_str = """                  'position' => $e->primaryAssignment?->position?->name,
                  'division' => $e->primaryAssignment?->division?->name,
                  'department' => $e->primaryAssignment?->department?->name,
                  'branch_id' => $e->primaryAssignment?->branch_id,
                  'branch' => $e->primaryAssignment?->branch?->name,"""

good_str = """                  'company' => $e->primaryAssignment?->company?->name,
                  'position' => $e->primaryAssignment?->position?->name,
                  'division' => $e->primaryAssignment?->division?->name,
                  'department' => $e->primaryAssignment?->department?->name,
                  'branch_id' => $e->primaryAssignment?->branch_id,
                  'branch' => $e->primaryAssignment?->branch?->name,"""

c = c.replace(bad_str, good_str)
with open(path, 'w', encoding='utf-8') as f:
    f.write(c)

print('routes/api.php updated')
