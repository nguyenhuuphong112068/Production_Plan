import re

with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'r', encoding='utf-8') as f:
    content = f.read()

target = """                  'next.start as next_start',
  
              )
              ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
              ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
              ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
              ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
              ->leftJoin('stage_plan as next', 'next.code', '=', 'sp.nextcessor_code')
              ->where('sp.active', 1)
              ->where('sp.not_schedule', 0)
              ->where('next.active', 1)
              ->whereIn('sp.stage_code', [1,  2])
              ->whereNull('sp.start')
              ->where('sp.finished', 0)
              ->where('next.finished', 0)
              ->where('next.start', '>', now())
              ->whereNotNull('plan_master.after_weigth_date')
              ->where('sp.deparment_code', session('user.production_code'))
              ->orderBy('next.start', 'asc')"""

replacement = """              )
              ->selectSub(function ($query) {
                  $query->selectRaw('MIN(start)')
                        ->from('stage_plan as next')
                        ->whereColumn('next.predecessor_code', 'sp.code')
                        ->where('next.active', 1)
                        ->where('next.finished', 0)
                        ->where('next.start', '>', now());
              }, 'next_start')
              ->leftJoin('plan_master', 'sp.plan_master_id', 'plan_master.id')
              ->leftJoin('finished_product_category', 'sp.product_caterogy_id', 'finished_product_category.id')
              ->leftJoin('product_name', 'finished_product_category.product_name_id', 'product_name.id')
              ->leftJoin('market', 'finished_product_category.market_id', 'market.id')
              ->where('sp.active', 1)
              ->where('sp.not_schedule', 0)
              ->whereIn('sp.stage_code', [1,  2])
              ->whereNull('sp.start')
              ->where('sp.finished', 0)
              ->whereNotNull(DB::raw('(SELECT MIN(start) FROM stage_plan as next WHERE next.predecessor_code = sp.code AND next.active = 1 AND next.finished = 0 AND next.start > NOW())'))
              ->whereNotNull('plan_master.after_weigth_date')
              ->where('sp.deparment_code', session('user.production_code'))
              ->orderBy('next_start', 'asc')"""

content = content.replace(target, replacement)

with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Updated scheduleWeightStage")
