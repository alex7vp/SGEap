-- Datos base de catalogos del sistema
-- Puede ejecutarse varias veces sin duplicar registros.

INSERT INTO paralelo (prlnombre) VALUES
('A'),
('B')
ON CONFLICT (prlnombre) DO NOTHING;

INSERT INTO nivel_educativo (nednombre) VALUES
('Educacion Inicial'),
('Educacion General Basica Elemental'),
('Educacion General Basica Media'),
('Educacion General Basica Superior'),
('Bachillerato General Unificado')
ON CONFLICT (nednombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, 'Inicial 1'
FROM nivel_educativo
WHERE nednombre = 'Educacion Inicial'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, 'Inicial 2'
FROM nivel_educativo
WHERE nednombre = 'Educacion Inicial'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, 'Preparatoria'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Elemental'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, '2do Año'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Elemental'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, '3er Año'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Elemental'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, '4to Año'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Elemental'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, '5to Año'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Media'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, '6to Año'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Media'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, '7mo Año'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Media'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, '8vo Año'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Superior'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, '9no Año'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Superior'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, '10mo Año'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Superior'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, '1ro BGU'
FROM nivel_educativo
WHERE nednombre = 'Bachillerato General Unificado'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, '2do BGU'
FROM nivel_educativo
WHERE nednombre = 'Bachillerato General Unificado'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, '3ro BGU'
FROM nivel_educativo
WHERE nednombre = 'Bachillerato General Unificado'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO parentesco (ptenombre) VALUES
('Padre'),
('Madre'),
('Hermano'),
('Hermana'),
('Abuelo/Abuela'),
('Tio/Tia'),
('Tutor'),
('Apoderado'),
('Responsable'),
('Otro')
ON CONFLICT (ptenombre) DO NOTHING;

INSERT INTO estado_civil (ecinombre) VALUES
('Soltero'),
('Casado'),
('Divorciado'),
('Viudo'),
('Union de hecho')
ON CONFLICT (ecinombre) DO NOTHING;

INSERT INTO instruccion (istnombre) VALUES
('Sin instruccion'),
('Primaria'),
('Bachiller'),
('Tercer Nivel'),
('Cuarto Nivel'),
('Doctorado')
ON CONFLICT (istnombre) DO NOTHING;

INSERT INTO estado_matricula (emdnombre) VALUES
('Inactivo'),
('Activo'),
('Anulado')
ON CONFLICT (emdnombre) DO NOTHING;

INSERT INTO tipo_personal (tpnombre, tpdescripcion, tpestado) VALUES
('Docente', 'Personal academico responsable de la ensenanza', true),
('Administrativo', 'Personal de apoyo administrativo institucional', true),
('Directivo', 'Autoridades y responsables de direccion institucional', true),
('DECE', 'Personal del departamento de consejeria estudiantil', true),
('Inspeccion', 'Personal responsable de control y convivencia', true),
('Servicios', 'Personal operativo y de apoyo general', true)
ON CONFLICT (tpnombre) DO NOTHING;

INSERT INTO tipo_matricula (tmanombre, tmadescripcion, tmaestado) VALUES
('ORDINARIA', 'Matricula realizada dentro del proceso regular', true),
('EXTRAORDINARIA', 'Matricula realizada fuera del calendario ordinario', true)
ON CONFLICT (tmanombre) DO NOTHING;
