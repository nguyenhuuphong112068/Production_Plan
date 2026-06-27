with open('resources/js/Pages/FullCalender.jsx', 'r', encoding='utf-8') as f:
    full_content = f.read()

with open('scratch/handleOptimizeSchedule.js', 'r', encoding='utf-8') as f:
    old_func = f.read()

with open('scratch/handleOptimizeSchedule_new.js', 'r', encoding='utf-8') as f:
    new_func = f.read()

full_content = full_content.replace(old_func, new_func)

with open('resources/js/Pages/FullCalender.jsx', 'w', encoding='utf-8') as f:
    f.write(full_content)

print("Applied back to FullCalender.jsx")
