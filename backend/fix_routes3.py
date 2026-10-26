import os, re
path = 'd:/Project/HRMS/backend/routes/api.php'
with open(path, 'r', encoding='utf-8') as f:
    c = f.read()

pattern = re.compile(r"Route::get\('/employee/profile', function.*?\}\)->middleware\('ability:employee'\);", re.DOTALL)
match = pattern.search(c)
if match:
    bad_str = match.group(0)
    
    good_str = r"""Route::get('/employee/profile', function (\Illuminate\Http\Request $request) {
            $e = $request->user();
            $assignment = $e->primaryAssignment ?? $e->assignments()->latest('id')->first();
            if ($assignment) {
                $assignment->load(['company', 'branch', 'department', 'division', 'position']);
            }
            $e->load(['documents', 'educations', 'trainings']);
            
            $data = [
                'id' => $e->id,
                'employee_id' => $e->employee_id,
                'name' => $e->name,
                'email' => $e->email,
                'phone' => $e->phone,
                'address' => $e->address,
                'company' => $assignment?->company?->name,
                'position' => $assignment?->position?->name,
                'division' => $assignment?->division?->name,
                'department' => $assignment?->department?->name,
                'branch_id' => $assignment?->branch_id,
                'branch' => $assignment?->branch?->name,
                'can_clock_in_anywhere' => (bool)$e->can_clock_in_anywhere,
                'photo_url' => $e->photo ? \Illuminate\Support\Facades\Storage::disk('public')->url($e->photo) : null,
                'documents' => $e->documents,
                'educations' => $e->educations,
                'trainings' => $e->trainings,
            ];

            return response()->json(['success' => true, 'message' => 'Success', 'data' => $data]);
        })->middleware('ability:employee');"""
    
    c = c.replace(bad_str, good_str)
    
    with open(path, 'w', encoding='utf-8') as f:
        f.write(c)
    
    print('Successfully replaced using replace()')
else:
    print('Pattern not found')
