-- Datos base para Gestion Contable.
-- Puede ejecutarse varias veces sin duplicar registros.

INSERT INTO contabilidad_concepto (
    ccocodigo,
    cconombre,
    ccocategoria,
    ccodescripcion,
    ccoestado
)
VALUES
    ('MATRICULA', 'Matricula', 'OBLIGACION', 'Valor obligatorio de matricula del periodo lectivo.', true),
    ('PENSION', 'Pension', 'OBLIGACION', 'Valor mensual obligatorio de pension.', true),
    ('SALIDA_PEDAGOGICA', 'Salida pedagogica', 'RUBRO', 'Rubro adicional por salidas o actividades pedagogicas.', true),
    ('CARNET', 'Carnet', 'RUBRO', 'Rubro adicional por carnet estudiantil.', true),
    ('MATERIAL', 'Material', 'RUBRO', 'Rubro adicional por materiales institucionales o academicos.', true),
    ('EVENTO', 'Evento', 'RUBRO', 'Rubro adicional por eventos institucionales.', true),
    ('REPOSICION', 'Reposicion', 'RUBRO', 'Rubro adicional por reposicion de bienes o documentos.', true),
    ('OTRO', 'Otro', 'RUBRO', 'Otro rubro adicional no clasificado.', true)
ON CONFLICT (ccocodigo) DO UPDATE
SET cconombre = EXCLUDED.cconombre,
    ccocategoria = EXCLUDED.ccocategoria,
    ccodescripcion = EXCLUDED.ccodescripcion,
    ccoestado = EXCLUDED.ccoestado,
    ccofecha_modificacion = CURRENT_TIMESTAMP;

INSERT INTO contabilidad_metodo_pago (
    cmpcodigo,
    cmpnombre,
    cmpdescripcion,
    cmpestado
)
VALUES
    ('TRANSFERENCIA', 'Transferencia', 'Pago realizado por transferencia bancaria.', true),
    ('DEPOSITO', 'Deposito', 'Pago realizado por deposito bancario.', true),
    ('EFECTIVO', 'Efectivo', 'Pago recibido en efectivo por la institucion.', true),
    ('TARJETA', 'Tarjeta', 'Pago realizado mediante tarjeta.', true),
    ('OTRO', 'Otro', 'Otro metodo de pago registrado internamente.', true)
ON CONFLICT (cmpcodigo) DO UPDATE
SET cmpnombre = EXCLUDED.cmpnombre,
    cmpdescripcion = EXCLUDED.cmpdescripcion,
    cmpestado = EXCLUDED.cmpestado,
    cmpfecha_modificacion = CURRENT_TIMESTAMP;
