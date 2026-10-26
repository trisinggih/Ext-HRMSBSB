import os

path = 'd:/Project/HRMS/backend/routes/api.php'
with open(path, 'r', encoding='utf-8') as f:
    c = f.read()

bad_str = """                'position' => $e->position?->name,
                'branch_id' => $e->branch_id,
                'branch' => $e->branch?->name,"""

good_str = """                'position' => $e->position?->name,
                'division' => $e->division?->name,
                'department' => $e->department?->name,
                'branch_id' => $e->branch_id,
                'branch' => $e->branch?->name,"""

c = c.replace(bad_str, good_str)
with open(path, 'w', encoding='utf-8') as f:
    f.write(c)

print('routes/api.php updated')
