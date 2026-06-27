
ALTER TABLE pms.stage_plan_bkc 
ADD COLUMN IF NOT EXISTS blister_mold_id mediumint(9) NULL,
ADD COLUMN IF NOT EXISTS first_in_campaign tinyint(1) NOT NULL DEFAULT 0;
