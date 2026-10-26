import os

reqs = [
    'd:/Project/HRMS/backend/app/Http/Requests/Master/StoreBranchRequest.php',
    'd:/Project/HRMS/backend/app/Http/Requests/Master/UpdateBranchRequest.php'
]

for req in reqs:
    with open(req, 'r', encoding='utf-8') as f:
        c = f.read()
    
    c = c.replace("'latitude' => 'nullable|numeric|between:-90,90'", "'latitude' => 'nullable|string'")
    c = c.replace("'longitude' => 'nullable|numeric|between:-180,180'", "'longitude' => 'nullable|string'")
    
    with open(req, 'w', encoding='utf-8') as f:
        f.write(c)

print('Requests updated')
