-- Datos base para estructura academica usada por asistencia.
-- Puede ejecutarse varias veces sin duplicar registros.

INSERT INTO area_academica (areanombre, areaestado)
VALUES
    ('Matematica', true),
    ('Lengua y Literatura', true),
    ('Ciencias Naturales', true),
    ('Estudios Sociales', true),
    ('Educacion Cultural y Artistica', true),
    ('Educacion Fisica', true),
    ('Lengua Extranjera', true),
    ('Desarrollo Humano Integral', true),
    ('Emprendimiento y Gestion', true)
ON CONFLICT (areanombre) DO NOTHING;

INSERT INTO asignatura (areaid, asgnombre, asgestado)
SELECT
    aa.areaid,
    source.asgnombre,
    true
FROM (
    VALUES
        ('Matematica', 'Matematica'),
        ('Lengua y Literatura', 'Lengua y Literatura'),
        ('Ciencias Naturales', 'Ciencias Naturales'),
        ('Ciencias Naturales', 'Fisica'),
        ('Ciencias Naturales', 'Quimica'),
        ('Ciencias Naturales', 'Biologia'),
        ('Estudios Sociales', 'Estudios Sociales'),
        ('Estudios Sociales', 'Historia'),
        ('Estudios Sociales', 'Educacion para la Ciudadania'),
        ('Estudios Sociales', 'Filosofia'),
        ('Educacion Cultural y Artistica', 'Educacion Cultural y Artistica'),
        ('Educacion Fisica', 'Educacion Fisica'),
        ('Lengua Extranjera', 'Ingles'),
        ('Desarrollo Humano Integral', 'Desarrollo Humano Integral'),
        ('Emprendimiento y Gestion', 'Emprendimiento y Gestion')
) AS source (areanombre, asgnombre)
INNER JOIN area_academica aa ON aa.areanombre = source.areanombre
ON CONFLICT (areaid, asgnombre) DO NOTHING;
