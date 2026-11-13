-- =====================================================================
-- Libraro: fill the library open time (hour.open_time) of existing branches from their shifts.
-- Same rule as `php artisan branches:fill-open-time` (BranchHoursService::guessOpenTime):
--   the library opens at the start of one of its shifts, in the tightest window of `hour` hours
--   that holds every shift (shifts past midnight count too); on a tie, the earlier start.
--   close time = open_time + hour.
-- Only rows with no open time yet, and branches open less than 24 hours, are touched.
-- Branches with no timed shifts, or shifts spread over more than their hours, are left as they
-- are (step 3 lists them) for the owner to set on the Operating hours page.
-- Plain SQL for phpMyAdmin (Import or SQL tab). Safe to run again. Take a backup first.
-- Needs the hour.open_time column (2026_11_05_000004_add_open_time_to_hour_table).
-- =====================================================================

-- STEP 1 (preview, changes nothing): the time each branch will get
SELECT h.`id` AS `hour_id`, h.`branch_id`, br.`name` AS `branch`, h.`hour` AS `hours`,
       TIME_FORMAT(SEC_TO_TIME(b.`best` * 60), '%h:%i %p') AS `opens`,
       TIME_FORMAT(SEC_TO_TIME(MOD(b.`best` + h.`hour` * 60, 1440) * 60), '%h:%i %p') AS `closes`
FROM `hour` h
JOIN (SELECT v.`hour_id`, v.`branch_id`, MOD(MIN(v.`span` * 10000 + v.`o`), 10000) AS `best`
FROM (
    SELECT h.`id` AS `hour_id`, h.`branch_id`, c.`o`,
           MAX(IF(sh.`s` >= c.`o`, sh.`s`, sh.`s` + 1440) + sh.`len`) - c.`o` AS `span`,
           SUM(IF((sh.`s` >= c.`o` AND sh.`s` + sh.`len` <= c.`o` + h.`hour` * 60)
               OR (sh.`s` + 1440 >= c.`o` AND sh.`s` + 1440 + sh.`len` <= c.`o` + h.`hour` * 60), 0, 1)) AS `bad`
    FROM `hour` h
    JOIN (
        SELECT DISTINCT x.`branch_id`, x.`smin` AS `o` FROM (SELECT pt.`branch_id`,
            MOD(CAST(SUBSTRING_INDEX(TRIM(pt.`start_time`), ':', 1) AS UNSIGNED) * 60 + CAST(SUBSTRING(SUBSTRING_INDEX(TRIM(pt.`start_time`), ':', 2), LOCATE(':', TRIM(pt.`start_time`)) + 1) AS UNSIGNED), 1440) AS `smin`,
            MOD(CAST(SUBSTRING_INDEX(TRIM(pt.`end_time`), ':', 1) AS UNSIGNED) * 60 + CAST(SUBSTRING(SUBSTRING_INDEX(TRIM(pt.`end_time`), ':', 2), LOCATE(':', TRIM(pt.`end_time`)) + 1) AS UNSIGNED), 1440) AS `emin`
        FROM `plan_types` pt
        WHERE pt.`deleted_at` IS NULL AND pt.`parent_id` IS NULL AND pt.`branch_id` IS NOT NULL
          AND TRIM(pt.`start_time`) REGEXP '^([01]?[0-9]|2[0-3]):[0-5][0-9]|^24:00'
          AND TRIM(pt.`end_time`) REGEXP '^([01]?[0-9]|2[0-3]):[0-5][0-9]|^24:00'
          AND NOT (pt.`booking_mode` IN ('duration', 'free', 'flexible') AND LEFT(TRIM(pt.`start_time`), 5) = '00:00' AND LEFT(TRIM(pt.`end_time`), 5) = '00:00')) x
    ) c ON c.`branch_id` = h.`branch_id`
    JOIN (
        SELECT x.`branch_id`, x.`smin` AS `s`, IF(x.`emin` > x.`smin`, x.`emin` - x.`smin`, x.`emin` + 1440 - x.`smin`) AS `len` FROM (SELECT pt.`branch_id`,
            MOD(CAST(SUBSTRING_INDEX(TRIM(pt.`start_time`), ':', 1) AS UNSIGNED) * 60 + CAST(SUBSTRING(SUBSTRING_INDEX(TRIM(pt.`start_time`), ':', 2), LOCATE(':', TRIM(pt.`start_time`)) + 1) AS UNSIGNED), 1440) AS `smin`,
            MOD(CAST(SUBSTRING_INDEX(TRIM(pt.`end_time`), ':', 1) AS UNSIGNED) * 60 + CAST(SUBSTRING(SUBSTRING_INDEX(TRIM(pt.`end_time`), ':', 2), LOCATE(':', TRIM(pt.`end_time`)) + 1) AS UNSIGNED), 1440) AS `emin`
        FROM `plan_types` pt
        WHERE pt.`deleted_at` IS NULL AND pt.`parent_id` IS NULL AND pt.`branch_id` IS NOT NULL
          AND TRIM(pt.`start_time`) REGEXP '^([01]?[0-9]|2[0-3]):[0-5][0-9]|^24:00'
          AND TRIM(pt.`end_time`) REGEXP '^([01]?[0-9]|2[0-3]):[0-5][0-9]|^24:00'
          AND NOT (pt.`booking_mode` IN ('duration', 'free', 'flexible') AND LEFT(TRIM(pt.`start_time`), 5) = '00:00' AND LEFT(TRIM(pt.`end_time`), 5) = '00:00')) x
    ) sh ON sh.`branch_id` = h.`branch_id`
    WHERE h.`deleted_at` IS NULL AND h.`hour` > 0 AND h.`hour` < 24 AND h.`open_time` IS NULL
    GROUP BY h.`id`, h.`branch_id`, c.`o`
    HAVING `bad` = 0
) v
GROUP BY v.`hour_id`, v.`branch_id`) b ON b.`hour_id` = h.`id`
LEFT JOIN `branches` br ON br.`id` = h.`branch_id`
ORDER BY h.`branch_id`;

-- STEP 2: fill it
UPDATE `hour` hh
JOIN (SELECT v.`hour_id`, v.`branch_id`, MOD(MIN(v.`span` * 10000 + v.`o`), 10000) AS `best`
FROM (
    SELECT h.`id` AS `hour_id`, h.`branch_id`, c.`o`,
           MAX(IF(sh.`s` >= c.`o`, sh.`s`, sh.`s` + 1440) + sh.`len`) - c.`o` AS `span`,
           SUM(IF((sh.`s` >= c.`o` AND sh.`s` + sh.`len` <= c.`o` + h.`hour` * 60)
               OR (sh.`s` + 1440 >= c.`o` AND sh.`s` + 1440 + sh.`len` <= c.`o` + h.`hour` * 60), 0, 1)) AS `bad`
    FROM `hour` h
    JOIN (
        SELECT DISTINCT x.`branch_id`, x.`smin` AS `o` FROM (SELECT pt.`branch_id`,
            MOD(CAST(SUBSTRING_INDEX(TRIM(pt.`start_time`), ':', 1) AS UNSIGNED) * 60 + CAST(SUBSTRING(SUBSTRING_INDEX(TRIM(pt.`start_time`), ':', 2), LOCATE(':', TRIM(pt.`start_time`)) + 1) AS UNSIGNED), 1440) AS `smin`,
            MOD(CAST(SUBSTRING_INDEX(TRIM(pt.`end_time`), ':', 1) AS UNSIGNED) * 60 + CAST(SUBSTRING(SUBSTRING_INDEX(TRIM(pt.`end_time`), ':', 2), LOCATE(':', TRIM(pt.`end_time`)) + 1) AS UNSIGNED), 1440) AS `emin`
        FROM `plan_types` pt
        WHERE pt.`deleted_at` IS NULL AND pt.`parent_id` IS NULL AND pt.`branch_id` IS NOT NULL
          AND TRIM(pt.`start_time`) REGEXP '^([01]?[0-9]|2[0-3]):[0-5][0-9]|^24:00'
          AND TRIM(pt.`end_time`) REGEXP '^([01]?[0-9]|2[0-3]):[0-5][0-9]|^24:00'
          AND NOT (pt.`booking_mode` IN ('duration', 'free', 'flexible') AND LEFT(TRIM(pt.`start_time`), 5) = '00:00' AND LEFT(TRIM(pt.`end_time`), 5) = '00:00')) x
    ) c ON c.`branch_id` = h.`branch_id`
    JOIN (
        SELECT x.`branch_id`, x.`smin` AS `s`, IF(x.`emin` > x.`smin`, x.`emin` - x.`smin`, x.`emin` + 1440 - x.`smin`) AS `len` FROM (SELECT pt.`branch_id`,
            MOD(CAST(SUBSTRING_INDEX(TRIM(pt.`start_time`), ':', 1) AS UNSIGNED) * 60 + CAST(SUBSTRING(SUBSTRING_INDEX(TRIM(pt.`start_time`), ':', 2), LOCATE(':', TRIM(pt.`start_time`)) + 1) AS UNSIGNED), 1440) AS `smin`,
            MOD(CAST(SUBSTRING_INDEX(TRIM(pt.`end_time`), ':', 1) AS UNSIGNED) * 60 + CAST(SUBSTRING(SUBSTRING_INDEX(TRIM(pt.`end_time`), ':', 2), LOCATE(':', TRIM(pt.`end_time`)) + 1) AS UNSIGNED), 1440) AS `emin`
        FROM `plan_types` pt
        WHERE pt.`deleted_at` IS NULL AND pt.`parent_id` IS NULL AND pt.`branch_id` IS NOT NULL
          AND TRIM(pt.`start_time`) REGEXP '^([01]?[0-9]|2[0-3]):[0-5][0-9]|^24:00'
          AND TRIM(pt.`end_time`) REGEXP '^([01]?[0-9]|2[0-3]):[0-5][0-9]|^24:00'
          AND NOT (pt.`booking_mode` IN ('duration', 'free', 'flexible') AND LEFT(TRIM(pt.`start_time`), 5) = '00:00' AND LEFT(TRIM(pt.`end_time`), 5) = '00:00')) x
    ) sh ON sh.`branch_id` = h.`branch_id`
    WHERE h.`deleted_at` IS NULL AND h.`hour` > 0 AND h.`hour` < 24 AND h.`open_time` IS NULL
    GROUP BY h.`id`, h.`branch_id`, c.`o`
    HAVING `bad` = 0
) v
GROUP BY v.`hour_id`, v.`branch_id`) b ON b.`hour_id` = hh.`id`
SET hh.`open_time` = SEC_TO_TIME(b.`best` * 60), hh.`updated_at` = NOW()
WHERE hh.`open_time` IS NULL;

-- STEP 3: branches still without an open time (owner sets it on the Operating hours page)
SELECT h.`id` AS `hour_id`, h.`branch_id`, br.`name` AS `branch`, h.`hour` AS `hours`,
       IF(EXISTS (SELECT 1 FROM `plan_types` pt WHERE pt.`branch_id` = h.`branch_id` AND pt.`parent_id` IS NULL AND pt.`deleted_at` IS NULL),
          'Shifts spread over more than the open hours: fix the shifts, then set the time',
          'No shifts yet: set on the Operating hours page') AS `why`
FROM `hour` h
LEFT JOIN `branches` br ON br.`id` = h.`branch_id`
WHERE h.`deleted_at` IS NULL AND h.`open_time` IS NULL AND h.`hour` > 0 AND h.`hour` < 24
ORDER BY h.`branch_id`;
