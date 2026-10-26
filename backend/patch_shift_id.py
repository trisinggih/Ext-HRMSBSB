with open('app/Http/Controllers/Api/v1/Mobile/MobileOvertimeController.php', 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace("'shift_id' => $request->shift_id,", "'shift_id' => $shift->id,")

with open('app/Http/Controllers/Api/v1/Mobile/MobileOvertimeController.php', 'w', encoding='utf-8') as f:
    f.write(content)
print("MobileOvertimeController shift_id patched.")
