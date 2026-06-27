import re
path = 'app/Http/Controllers/Pages/Schedual/ShedualYieldController.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

pattern = r"->where\('sp\.deparment_code', session\('user'\)\['production_code'\]\)"
replacement = r"->where('sp.deparment_code', session('user')['production_code'])\n            ->where('sp.active', 1)"
content = re.sub(pattern, replacement, content)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
print('Updated ShedualYieldController.php')
