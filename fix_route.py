import re

with open('c:/PMS/Production_Plan/routes/web.php', 'r', encoding='utf-8') as f:
    content = f.read()

target = "Route::post('/scheduleAll', [SchedualController::class, 'scheduleAll']);"
replacement = target + "\n    Route::post('/scheduleAllPass2', [SchedualController::class, 'scheduleAllPass2']);"

if target in content:
    content = content.replace(target, replacement)
    with open('c:/PMS/Production_Plan/routes/web.php', 'w', encoding='utf-8') as f:
        f.write(content)
    print("Added route to web.php")
else:
    print("Target not found in web.php")
