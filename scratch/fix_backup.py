import re
import subprocess

# 1. Add columns to DB
sql = """
ALTER TABLE pms.stage_plan_bkc 
ADD COLUMN IF NOT EXISTS blister_mold_id mediumint(9) NULL,
ADD COLUMN IF NOT EXISTS first_in_campaign tinyint(1) NOT NULL DEFAULT 0;
"""
with open('C:/PMS/Production_Plan/scratch/alter_bkc.sql', 'w') as f:
    f.write(sql)

subprocess.run('cmd /c ""C:\\xampp\\mysql\\bin\\mysql.exe" -u root pms < C:\\PMS\\Production_Plan\\scratch\\alter_bkc.sql"', shell=True)

# 2. Modify SchedualController.php
with open('app/Http/Controllers/Pages/Schedual/SchedualController.php', 'r', encoding='utf-8') as f:
    content = f.read()

# backup_schedualer insert array
backup_insert_find = """                'plan_master_id',
                'product_caterogy_id',
                'predecessor_code',"""
backup_insert_replace = """                'plan_master_id',
                'product_caterogy_id',
                'blister_mold_id',
                'predecessor_code',"""
content = content.replace(backup_insert_find, backup_insert_replace)

backup_insert_find2 = """                'stage_code',
                'title',
                'start',"""
backup_insert_replace2 = """                'stage_code',
                'title',
                'first_in_campaign',
                'start',"""
content = content.replace(backup_insert_find2, backup_insert_replace2)

# backup_schedualer select array
backup_select_find = """                    'plan_master_id',
                    'product_caterogy_id',
                    'predecessor_code',"""
backup_select_replace = """                    'plan_master_id',
                    'product_caterogy_id',
                    'blister_mold_id',
                    'predecessor_code',"""
content = content.replace(backup_select_find, backup_select_replace)

backup_select_find2 = """                    'stage_code',
                    'title',
                    'start',"""
backup_select_replace2 = """                    'stage_code',
                    'title',
                    'first_in_campaign',
                    'start',"""
content = content.replace(backup_select_find2, backup_select_replace2)


# restore_schedualer update array
restore_find = """                    'sp.campaign_code' => DB::raw('bkc.campaign_code'),
                    'sp.immediately' => DB::raw('bkc.immediately'),"""
restore_replace = """                    'sp.campaign_code' => DB::raw('bkc.campaign_code'),
                    'sp.immediately' => DB::raw('bkc.immediately'),
                    'sp.blister_mold_id' => DB::raw('bkc.blister_mold_id'),
                    'sp.first_in_campaign' => DB::raw('bkc.first_in_campaign'),"""
content = content.replace(restore_find, restore_replace)


with open('app/Http/Controllers/Pages/Schedual/SchedualController.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Applied backup/restore fixes!")
