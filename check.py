import re

file = 'c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php'
with open(file, 'r', encoding='utf-8') as f:
    content = f.read()

# Check for findEarliestSlotCampaign
if "findEarliestSlotCampaign" in content:
    print("Found findEarliestSlotCampaign")
else:
    print("Not found findEarliestSlotCampaign")

