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

INSERT INTO condicion_vivienda (cvinombre) VALUES
('PROPIA'),
('ARRENDADA'),
('PRESTADA'),
('ANTICRESIS'),
('CON_PRESTAMO')
ON CONFLICT (cvinombre) DO NOTHING;

INSERT INTO grupo_sanguineo (gsnombre) VALUES
('A+'),
('A-'),
('B+'),
('B-'),
('AB+'),
('AB-'),
('O+'),
('O-')
ON CONFLICT (gsnombre) DO NOTHING;

INSERT INTO atencion_medica (amnombre) VALUES
('CENTRO_SALUD'),
('SUBCENTRO_SALUD'),
('HOSPITAL_PUBLICO'),
('HOSPITAL_PRIVADO')
ON CONFLICT (amnombre) DO NOTHING;

INSERT INTO tipo_condicion_salud (tcsnombre) VALUES
('ALERGIA'),
('ENFERMEDAD'),
('CONDICION_MEDICA'),
('ACCIDENTE'),
('CIRUGIA'),
('PERDIDA_CONOCIMIENTO'),
('OTRO')
ON CONFLICT (tcsnombre) DO NOTHING;

INSERT INTO seguro_medico (smnombre, smactivo) VALUES
('IESS', true),
('ISSFA', true),
('ISSPOL', true),
('Seguro privado', true),
('Ninguno', true)
ON CONFLICT (smnombre) DO NOTHING;

INSERT INTO tipo_embarazo (tenombre) VALUES
('TERMINO'),
('PREMATURO')
ON CONFLICT (tenombre) DO NOTHING;

INSERT INTO tipo_parto (tpnombre) VALUES
('CESAREA'),
('PARTO_NORMAL')
ON CONFLICT (tpnombre) DO NOTHING;

INSERT INTO documento_matricula (
    domnombre,
    domdescripcion,
    domtipo,
    domorigen,
    domurl,
    domplantilla_archivo,
    domplantilla_extension,
    domobligatorio,
    domactivo
) VALUES
('Cedula del estudiante', 'Copia de cedula o documento de identidad del estudiante', 'ESTATICO', 'URL', '#', NULL, NULL, true, true),
('Cedula del representante', 'Copia de cedula o documento de identidad del representante', 'ESTATICO', 'URL', '#', NULL, NULL, true, true),
('Certificado de promocion', 'Certificado de promocion o pase de ano anterior', 'ESTATICO', 'URL', '#', NULL, NULL, true, true),
('Ficha de matricula', 'Plantilla institucional de ficha de matricula', 'PLANTILLA', 'ARCHIVO', NULL, 'ficha_matricula.docx', 'DOCX', true, true)
ON CONFLICT (domnombre) DO NOTHING;

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
SELECT nedid, U&'2do A\00F1o'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Elemental'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, U&'3er A\00F1o'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Elemental'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, U&'4to A\00F1o'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Elemental'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, U&'5to A\00F1o'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Media'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, U&'6to A\00F1o'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Media'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, U&'7mo A\00F1o'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Media'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, U&'8vo A\00F1o'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Superior'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, U&'9no A\00F1o'
FROM nivel_educativo
WHERE nednombre = 'Educacion General Basica Superior'
ON CONFLICT (nedid, granombre) DO NOTHING;

INSERT INTO grado (nedid, granombre)
SELECT nedid, U&'10mo A\00F1o'
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
('Hermano/Hermana'),
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
('Secundaria'),
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
('Rector', 'Maxima autoridad institucional', true),
('Vicerrector', 'Autoridad academica institucional', true),
('Secretaria', 'Personal responsable de procesos administrativos y de secretaria', true),
('Coordinador', 'Personal responsable de coordinacion academica u operativa', true),
('Docente', 'Personal academico responsable de la ensenanza', true),
('DECE', 'Personal del departamento de consejeria estudiantil', true),
('Inspector', 'Personal responsable de control y convivencia', true),
('Servicios', 'Personal operativo y de apoyo general', true),
('Otro', 'Otro tipo de personal institucional', true)
ON CONFLICT (tpnombre) DO NOTHING;

INSERT INTO tipo_matricula (tmanombre, tmadescripcion, tmaestado) VALUES
('ORDINARIA', 'Matricula realizada dentro del proceso regular', true),
('EXTRAORDINARIA', 'Matricula realizada fuera del calendario ordinario', true)
ON CONFLICT (tmanombre) DO NOTHING;
