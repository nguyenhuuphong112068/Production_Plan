with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'r', encoding='utf-8') as f:
    content = f.read()

start_str = "->whereNotNull('plan_master.after_weigth_date')"
end_str = "        if ($stageEarliestDeadline !== null && ($start_date_effective === null || $stageEarliestDeadline->gt($start_date_effective))) {\n            $start_date_effective = $stageEarliestDeadline;\n        }"

start_idx = content.find(start_str)
if start_idx != -1:
    # Find the second occurrence if this is inside the scheduleWarningMR function
    # actually start_idx is the first occurrence. Let's find end_str after start_idx
    end_idx = content.find(end_str, start_idx)
    if end_idx != -1:
        end_idx += len(end_str)
        
        replacement = '''->whereNotNull('plan_master.after_weigth_date')
                    ->orWhereNotNull('plan_master.allow_weight_before_date')
                    ->orWhereNotNull('plan_master.expired_material_date')
                    ->orWhereNotNull('plan_master.preperation_before_date')
                    ->orWhereNotNull('plan_master.blending_before_date')
                    ->orWhereNotNull('plan_master.coating_before_date');
                  if ( == 7) {
                      ->orWhereNotNull('plan_master.after_parkaging_date')
                        ->orWhereNotNull('plan_master.expired_packing_date')
                        ->orWhereNotNull('plan_master.parkaging_before_date');
                  }
              })
              ->where('sp.deparment_code', session('user.production_code'))
              // Sắp xếp: ưu tiên task có deadline chặt nhất trước (Earliest Deadline First)
              ->orderByRaw("
                  LEAST(
                      COALESCE(plan_master.expired_material_date, '9999-12-31'),
                      COALESCE(plan_master.allow_weight_before_date, '9999-12-31'),
                      COALESCE(plan_master.preperation_before_date, '9999-12-31'),
                      COALESCE(plan_master.blending_before_date, '9999-12-31'),
                      COALESCE(plan_master.coating_before_date, '9999-12-31'),
                      COALESCE(plan_master.parkaging_before_date, '9999-12-31'),
                      COALESCE(plan_master.expired_packing_date, '9999-12-31')
                  ) ASC
              ")
              ->orderBy('prev.start', 'asc')
              ->get();

          if (! ->isNotEmpty()) {
              return;
          }

           = [];

          foreach ( as ) {

              if (->is_val === 1) {
                   = ;
              } else {
                   = ;
              }

              // ─── BƯỚC 1: Lower bound (operator '>') ───────────────────────────────────
               =  ? clone  : null;

               = array_filter([
                  ->after_weigth_date,
                  ->allow_weight_before_date,
              ]);

              foreach ( as ) {
                   = Carbon::parse()->setTime(6, 0, 0);
                  if ( === null || ->gt()) {
                       = ;
                  }
              }
              
              // Đã xóa Bước 2 Just-In-Time để xếp sớm nhất có thể'''
              
        new_content = content[:start_idx] + replacement + content[end_idx:]
        with open('c:/PMS/Production_Plan/app/Http/Controllers/Pages/Schedual/SchedualController.php', 'w', encoding='utf-8') as f:
            f.write(new_content)
        print("Success")
    else:
        print("Could not find end_str")
else:
    print("Could not find start_str")
