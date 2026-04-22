-- Datos base de periodos lectivos
-- Puede ejecutarse varias veces sin duplicar registros.

INSERT INTO periodo_lectivo (pledescripcion, plefechainicio, plefechafin, pleactivo)
SELECT '2027-2028', '2027-09-02', '2028-06-30', false
WHERE NOT EXISTS (
    SELECT 1 FROM periodo_lectivo WHERE pledescripcion = '2027-2028'
);

INSERT INTO periodo_lectivo (pledescripcion, plefechainicio, plefechafin, pleactivo)
SELECT '2026 2027', '2026-09-02', '2027-06-16', false
WHERE NOT EXISTS (
    SELECT 1 FROM periodo_lectivo WHERE pledescripcion = '2026 2027'
);

INSERT INTO periodo_lectivo (pledescripcion, plefechainicio, plefechafin, pleactivo)
SELECT '2025 2026', '2025-09-01', '2026-06-30', true
WHERE NOT EXISTS (
    SELECT 1 FROM periodo_lectivo WHERE pledescripcion = '2025 2026'
);
